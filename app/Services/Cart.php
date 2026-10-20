<?php

namespace App\Services;

use App\Models\Product;

class Cart
{
    public static function cents($value): int
    {
        return (int) round((float) $value * 100);
    }

    public function lines(): array
    {
        $ids = session('cart', []);
        $products = Product::with('images')->whereIn('id', array_keys($ids))->get()->keyBy('id');
        $lines = [];
        foreach ($ids as $id => $qty) {
            if ($p = $products->get($id)) {
                $lines[] = ['product' => $p, 'quantity' => $qty, 'total' => self::cents($p->price) * $qty];
            }
        }

        return $lines;
    }

    public function subtotal(): int
    {
        return array_sum(array_column($this->lines(), 'total'));
    }
}
