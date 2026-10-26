<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function scopePublic($query)
    {
        return $query->where('published', true)->where('published_at', '<=', now())->latest('published_at');
    }
}
