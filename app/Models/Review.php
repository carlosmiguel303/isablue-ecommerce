<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = ['id'];

    const STATUSES = [
        'pending' => 'Por revisar',
        'approved' => 'Publicada',
        'rejected' => 'Rechazada',
    ];

    /** Solo pueden opinar quienes recibieron (o ya tienen en camino) el juguete. */
    const REVIEWABLE_ORDER_STATUSES = ['shipped', 'completed'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'approved_at' => 'datetime'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->latest('approved_at');
    }

    /** Pedido del cliente que demuestra que compró este juguete, o null si no lo compró. */
    public static function purchaseOrder(?User $user, Product $product): ?Order
    {
        if (! $user) {
            return null;
        }

        return Order::where('user_id', $user->id)
            ->whereIn('status', self::REVIEWABLE_ORDER_STATUSES)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()->first();
    }

    /** "Andrea Rojas Pérez" → "Andrea R." para no exponer el nombre completo. */
    public static function publicName(string $fullName): string
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
        $first = mb_convert_case(mb_substr($parts[0] ?? 'Cliente', 0, 40), MB_CASE_TITLE);

        return isset($parts[1]) ? $first.' '.mb_strtoupper(mb_substr($parts[1], 0, 1)).'.' : $first;
    }

    public function getStarsAttribute(): string
    {
        return str_repeat('★', $this->rating).str_repeat('☆', 5 - $this->rating);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
