<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart;
use App\Services\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $r, Cart $cart)
    {
        if (! $r->session()->has('checkout_key') || ($r->session()->get('cart', []) && Order::where('idempotency_key', $r->session()->get('checkout_key'))->exists())) {
            $r->session()->put('checkout_key', (string) Str::uuid());
        }
        $subtotal = $cart->subtotal();
        $shipping = ShippingMethod::where('active', true)->orderBy('price')->get()
            ->each(fn ($m) => $m->final_price = Checkout::shippingPrice($m, $subtotal) / 100);

        return view('checkout', ['lines' => $cart->lines(), 'subtotal' => $subtotal, 'shipping' => $shipping]);
    }

    public function store(Request $r, Checkout $checkout)
    {
        $d = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +()-]{7,30}$/'],
            'address' => 'required|string|max:500',
            'note' => 'nullable|string|max:500',
            'shipping_method_id' => 'required|integer',
            'payment_reference' => 'nullable|string|max:60',
            'voucher' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'checkout_key' => 'required|uuid',
            'password' => [$r->user() ? 'nullable' : 'required', 'string', 'min:8', 'max:72'],
        ], [
            'phone.regex' => 'Escribe un número de celular válido.',
            'password.required' => 'Crea una contraseña para tu cuenta: así podrás ver tus pedidos la próxima vez.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);
        abort_unless(hash_equals((string) $r->session()->get('checkout_key', ''), $d['checkout_key']), 419);
        $user = $r->user() ?? $this->accountFor($r, $d);
        $order = $checkout->place($r->session()->get('cart', []), $d, $user->id, $d['checkout_key']);
        if ($r->hasFile('voucher') && ! $order->voucher_path) {
            $this->saveVoucher($order, $r->file('voucher'), $d['payment_reference'] ?? null);
        }
        $r->session()->forget('cart');
        $r->session()->put('last_order', $order->public_token);
        if (! $order->confirmation_sent_at) {
            try {
                Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                $order->update(['confirmation_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar confirmación', ['order_id' => $order->id]);
                OrderEvent::create(['order_id' => $order->id, 'event' => 'Correo pendiente de reenvío']);
            }
            $this->notifyShop($order);
        }

        return redirect('/pedido/'.$order->public_token);
    }

    /**
     * Comprador sin sesión: crea su cuenta con el correo y la contraseña del checkout.
     * Si el correo ya tiene cuenta, la contraseña debe coincidir para usarla.
     */
    private function accountFor(Request $r, array $d): User
    {
        $user = User::where('email', $d['email'])->first();
        if ($user) {
            if (! password_verify($d['password'], $user->password)) {
                throw ValidationException::withMessages(['password' => 'Este correo ya tiene una cuenta en IsaBlue. Escribe tu contraseña o usa otro correo.']);
            }
        } else {
            $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => Hash::make($d['password']), 'phone' => $d['phone'], 'address' => $d['address']]);
        }
        Auth::login($user);
        $r->session()->regenerate();

        return $user;
    }

    /** Aviso de nuevo pedido al correo de contacto de la tienda (si está configurado). */
    private function notifyShop(Order $order): void
    {
        $to = Setting::valueOf('contact_email');
        if (! $to) {
            return;
        }
        try {
            Mail::raw("Nuevo pedido {$order->reference} por S/ ".number_format($order->total, 2)." de {$order->customer_name} ({$order->phone}).\nEstá esperando el Yape. Revísalo en: ".url('/admin/pedidos/'.$order->id), fn ($m) => $m->to($to)->subject('🧸 Nuevo pedido '.$order->reference.' · S/ '.number_format($order->total, 2)));
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar a la tienda', ['order_id' => $order->id]);
        }
    }

    public function show(string $token)
    {
        $order = Order::with('items.product.images')->where('public_token', $token)->firstOrFail();

        return view('order', compact('order'));
    }

    /** El cliente sube la captura de su Yape desde la página del pedido. */
    public function voucher(Request $r, string $token)
    {
        $order = Order::where('public_token', $token)->firstOrFail();
        $d = $r->validate([
            'voucher' => 'required|image|mimes:jpg,jpeg,png,webp|max:6144',
            'payment_reference' => 'nullable|string|max:60',
        ], ['voucher.required' => 'Adjunta la captura de tu Yape.']);
        if ($order->payment_status === 'paid' || $order->status === 'cancelled') {
            return back()->with('status', 'Este pedido ya no necesita comprobante.');
        }
        $this->saveVoucher($order, $r->file('voucher'), $d['payment_reference'] ?? null);

        return back()->with('status', '¡Gracias! Recibimos tu comprobante. Validaremos tu Yape y te escribiremos.');
    }

    private function saveVoucher(Order $order, $file, ?string $reference): void
    {
        $path = $file->storeAs('vouchers', $order->reference.'-'.Str::lower(Str::random(8)).'.'.$file->extension(), 'local');
        DB::transaction(function () use ($order, $path, $reference) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            $o->voucher_path = $path;
            $o->voucher_uploaded_at = now();
            if ($reference) {
                $o->payment_reference = $reference;
            }
            if ($o->payment_status === 'pending') {
                $o->payment_status = 'review';
            }
            $o->save();
            OrderEvent::create(['order_id' => $o->id, 'event' => 'Cliente envió comprobante de Yape', 'note' => $reference ? 'N.º de operación: '.$reference : null]);
        });
    }
}
