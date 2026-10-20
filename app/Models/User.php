<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory,Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'address'];

    protected $hidden = ['password', 'remember_token', 'legacy_data'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'legacy_data' => 'array'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
