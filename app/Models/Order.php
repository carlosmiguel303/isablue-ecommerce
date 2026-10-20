<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['legacy_data' => 'array', 'stock_reserved' => 'boolean', 'stock_released' => 'boolean', 'is_demo' => 'boolean', 'confirmation_sent_at' => 'datetime', 'paid_at' => 'datetime', 'voucher_uploaded_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->latest()->orderByDesc('id');
    }

    /** Pedidos que cuentan como venta: Yape confirmado, en soles y no cancelados. */
    public function scopeSold($q, bool $includeDemo = false)
    {
        $q->where('payment_status', 'paid')->where('currency', 'PEN')->where('status', '!=', 'cancelled');

        return $includeDemo ? $q : $q->where('is_demo', false);
    }

    /** Teléfono en formato internacional para abrir WhatsApp. */
    public function getWhatsappNumberAttribute(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->phone);
        if (strlen($digits) === 9) {
            $digits = '51'.$digits;
        }

        return strlen($digits) >= 8 ? $digits : null;
    }
}
