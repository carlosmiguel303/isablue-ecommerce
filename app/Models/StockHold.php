<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHold extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function scopeActive($q)
    {
        return $q->whereNull('released_at')->where('expires_at', '>', now());
    }
}
