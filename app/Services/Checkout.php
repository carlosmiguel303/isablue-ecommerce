<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\StockHold;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Flujo de venta con Yape:
 *  1. El cliente confirma el pedido: se valida el stock, pero NO se descuenta.
 *  2. El cliente yapea y (opcionalmente) sube su comprobante.
 *  3. El administrador confirma el Yape: recién ahí se descuenta el stock.
 *  4. Solo un pedido pagado puede pasar a preparación, envío y entrega.
 */
class Checkout
{
    /** Estados a los que se puede avanzar desde cada estado. */
    public const FLOW = [
        'pending' => ['cancelled'],
        'confirmed' => ['processing', 'shipped', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function shippingPrice(ShippingMethod $method, int $subtotalCents): int
    {
        $free = (float) Setting::valueOf('free_shipping_from', '0');
        if ($free > 0 && $subtotalCents >= Cart::cents($free)) {
            return 0;
        }

        return Cart::cents($method->price);
    }

    public function place(array $cart, array $data, ?int $userId, string $key): Order
    {
        return DB::transaction(function () use ($cart, $data, $userId, $key) {
            if ($existing = Order::where('idempotency_key', $key)->first()) {
                return $existing;
            }
            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Tu carrito está vacío.']);
            }
            StoreReadiness::assertCanOrder();
            $shipping = ShippingMethod::where('active', true)->find($data['shipping_method_id']);
            if (! $shipping) {
                throw ValidationException::withMessages(['shipping_method_id' => 'Selecciona una opción de entrega disponible.']);
            }
            $products = Product::whereIn('id', array_keys($cart))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($existing = Order::where('idempotency_key', $key)->first()) {
                return $existing;
            }
            $subtotal = 0;
            foreach ($cart as $id => $qty) {
                $p = $products->get($id);
                if (! $p || ! $p->available || ! is_int($qty) || $qty < 1 || $qty > 99 || $p->sellable_stock < $qty) {
                    throw ValidationException::withMessages(['cart' => 'Revisa la disponibilidad y cantidades de tu carrito.']);
                }
                $subtotal += Cart::cents($p->price) * $qty;
            }
            if (Setting::valueOf('demo_mode', '0') !== '1' &&
                ($shipping->demo_data || $products->contains(fn ($p) => (bool) $p->demo_data))) {
                throw ValidationException::withMessages(['cart' => 'Uno de los productos o la entrega todavía está pendiente de verificación. Contacta con la tienda.']);
            }
            $shippingCents = self::shippingPrice($shipping, $subtotal);
            $method = array_key_exists($data['payment_method'] ?? '', Order::PAYMENT_METHODS) ? $data['payment_method'] : 'yape';
            $order = Order::create([
                'reference' => 'ISA-'.strtoupper(Str::random(6)),
                'public_token' => Str::uuid(),
                'idempotency_key' => $key,
                'user_id' => $userId,
                'customer_name' => $data['name'],
                'customer_email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'customer_note' => $data['note'] ?? null,
                'shipping_name' => $shipping->name,
                'subtotal' => $subtotal / 100,
                'shipping_total' => $shippingCents / 100,
                'total' => ($subtotal + $shippingCents) / 100,
                'payment_method' => $method,
                'payment_reference' => $data['payment_reference'] ?? null,
                'stock_reserved' => false,
                // Yape se paga al momento; las demás formas se coordinan y reservan por 24 horas.
                'reserved_until' => $method === 'yape' ? now()->addMinutes(30) : now()->addDay(),
                'is_demo' => Setting::valueOf('demo_mode', '0') === '1' || (bool) $shipping->demo_data || $products->contains(fn ($p) => (bool) $p->demo_data),
            ]);
            foreach ($cart as $id => $qty) {
                $p = $products[$id];
                StockHold::create(['order_id' => $order->id, 'product_id' => $id, 'quantity' => $qty, 'expires_at' => $order->reserved_until]);
                $order->items()->create(['product_id' => $id, 'name' => $p->name, 'sku' => $p->sku, 'quantity' => $qty, 'unit_price' => $p->price, 'total' => Cart::cents($p->price) * $qty / 100]);
            }
            OrderEvent::create(['order_id' => $order->id, 'user_id' => $userId, 'event' => 'Pedido recibido', 'note' => $method === 'yape' ? 'Esperando el pago por Yape.' : 'Pago por '.Order::PAYMENT_METHODS[$method].': coordinar con el cliente.']);

            return $order;
        }, 3);
    }

    /**
     * Confirma el pago y descuenta el stock en la misma transacción.
     * Devuelve false si ya estaba pagado (operación idempotente).
     */
    public function confirmPayment(Order $order, ?int $actor, string $event, ?string $note = null): bool
    {
        return DB::transaction(function () use ($order, $actor, $event, $note) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->payment_status === 'paid') {
                return false;
            }
            if ($o->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'No se puede confirmar el pago de un pedido cancelado.']);
            }
            if ($o->currency !== 'PEN') {
                throw ValidationException::withMessages(['payment' => 'El pedido histórico no registra moneda. Requiere conciliación antes de registrar el cobro.']);
            }
            if (! $o->stock_reserved) {
                $items = $o->items()->orderBy('product_id')->get();
                $products = Product::whereIn('id', $items->pluck('product_id')->filter())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                foreach ($items as $item) {
                    $p = $products->get($item->product_id);
                    if (! $p || ((int) $p->stock - (int) StockHold::active()->where('product_id', $p->id)->where('order_id', '!=', $o->id)->sum('quantity')) < (int) $item->quantity) {
                        throw ValidationException::withMessages(['payment' => 'Stock insuficiente de «'.($p?->name ?? $item->name).'»: quedan '.($p?->sellable_stock ?? 0).' y el pedido necesita '.(int) $item->quantity.'. Ajusta el inventario o cancela el pedido.']);
                    }
                }
                foreach ($items as $item) {
                    if (! $p = $products->get($item->product_id)) {
                        continue;
                    }
                    $before = $p->stock;
                    $p->stock -= (int) $item->quantity;
                    $p->save();
                    StockMovement::create(['product_id' => $p->id, 'user_id' => $actor, 'order_id' => $o->id, 'before' => $before, 'change' => -(int) $item->quantity, 'after' => $p->stock, 'reason' => 'Venta pagada '.$o->reference]);
                }
                $o->stock_reserved = true;
            }
            StockHold::where('order_id', $o->id)->whereNull('released_at')->update(['released_at' => now()]);
            $o->payment_status = 'paid';
            $o->paid_at = now();
            if ($o->status === 'pending') {
                $o->status = 'confirmed';
            }
            $o->save();
            OrderEvent::create(['order_id' => $o->id, 'user_id' => $actor, 'event' => $event, 'note' => $note]);
            MailOutbox::enqueue('payment:'.$o->id, 'payment', $o->customer_email, ['subject' => ($o->is_demo ? '[PRUEBA] ' : '').'Pago confirmado · '.$o->reference, 'name' => $o->customer_name, 'message' => $o->is_demo ? 'Confirmamos el pago simulado. Tu pedido de prueba está en proceso; no habrá un cobro ni una entrega real.' : 'Confirmamos tu Yape. Tu pedido está en proceso y pendiente de preparación. Te avisaremos por los canales de la tienda cuando esté listo para enviar.', 'details' => 'Pedido: '.$o->reference.' · Total: S/ '.number_format($o->total, 2), 'url' => url('/pedido/'.$o->public_token), 'action' => 'Consultar mi pedido']);

            return true;
        });
    }

    public function transition(Order $order, string $status, int $actor): void
    {
        DB::transaction(function () use ($order, $status, $actor) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->status === $status) {
                return;
            }
            if ($status !== 'cancelled' && $o->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Primero confirma el pago por Yape. Un pedido sin pago no puede avanzar.']);
            }
            if (! in_array($status, self::FLOW[$o->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Ese cambio de estado no está permitido.']);
            }
            if ($status === 'cancelled') {
                StockHold::where('order_id', $o->id)->whereNull('released_at')->update(['released_at' => now()]);
            }
            if ($status === 'cancelled' && $o->stock_reserved && ! $o->stock_released) {
                foreach ($o->items()->orderBy('product_id')->get() as $item) {
                    $p = Product::lockForUpdate()->find($item->product_id);
                    if (! $p) {
                        continue;
                    }
                    $before = $p->stock;
                    $p->stock += (int) $item->quantity;
                    $p->save();
                    StockMovement::create(['product_id' => $p->id, 'user_id' => $actor, 'order_id' => $o->id, 'before' => $before, 'change' => (int) $item->quantity, 'after' => $p->stock, 'reason' => 'Devolución al stock por cancelación '.$o->reference]);
                }
                $o->stock_released = true;
            }
            if ($status === 'cancelled' && $o->payment_status === 'paid') {
                $o->payment_status = 'refund_pending';
            }
            $o->status = $status;
            $o->save();
            OrderEvent::create(['order_id' => $o->id, 'user_id' => $actor, 'event' => 'Estado: '.__('store.status.'.$status)]);
        });
    }
}
