<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Cart $cart)
    {
        return view('cart', ['lines' => $cart->lines(), 'subtotal' => $cart->subtotal()]);
    }

    /** Datos del mini carrito lateral. */
    public function mini(Cart $cart)
    {
        return response()->json($this->summary($cart));
    }

    public function add(Request $r, Cart $cart)
    {
        $d = $r->validate(['product_id' => 'required|integer', 'quantity' => 'required|integer|min:1|max:99']);
        $p = Product::publicCatalog()->findOrFail($d['product_id']);
        $items = $r->session()->get('cart', []);
        $qty = ($items[$p->id] ?? 0) + (int) $d['quantity'];
        if (! $p->available || $qty > $p->stock || $qty > 99) {
            throw ValidationException::withMessages(['quantity' => 'No hay suficientes unidades disponibles.']);
        }
        $items[$p->id] = $qty;
        $r->session()->put('cart', $items);
        if ($r->boolean('buy_now')) {
            return redirect('/checkout');
        }
        if ($r->expectsJson()) {
            return response()->json(['message' => 'Añadido al carrito', 'count' => array_sum($items)] + $this->summary($cart));
        }

        return back()->with('status', 'Juguete añadido al carrito.');
    }

    public function update(Request $r, int $id)
    {
        $d = $r->validate(['quantity' => 'required|integer|min:0|max:99']);
        $items = $r->session()->get('cart', []);
        if ($d['quantity'] == 0) {
            unset($items[$id]);
        } else {
            $p = Product::publicCatalog()->findOrFail($id);
            if (! $p->available || $d['quantity'] > $p->stock) {
                throw ValidationException::withMessages(['quantity' => 'Cantidad superior al stock disponible.']);
            }
            $items[$id] = (int) $d['quantity'];
        }
        $r->session()->put('cart', $items);

        return back()->with('status', 'Carrito actualizado.');
    }

    private function summary(Cart $cart): array
    {
        $lines = $cart->lines();
        $subtotal = array_sum(array_column($lines, 'total'));
        $free = Cart::cents(Setting::valueOf('free_shipping_from', '0'));

        return [
            'count' => array_sum(array_column($lines, 'quantity')),
            'subtotal' => $subtotal / 100,
            'free_from' => $free / 100,
            'missing_for_free' => $free > 0 ? max(0, $free - $subtotal) / 100 : null,
            'lines' => array_map(fn ($l) => [
                'id' => $l['product']->id,
                'name' => $l['product']->name,
                'image' => $l['product']->image,
                'url' => route('product', $l['product']->slug),
                'quantity' => $l['quantity'],
                'total' => $l['total'] / 100,
            ], $lines),
        ];
    }
}
