<?php

namespace App\Models;

use App\Services\MailOutbox;
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

    public function sendPasswordResetNotification($token): void
    {
        MailOutbox::enqueue('reset:'.hash('sha256', $token), 'reset', $this->email, ['subject' => 'Restablece tu contraseña de Isablue', 'name' => $this->name, 'message' => 'Recibimos una solicitud para cambiar tu contraseña.', 'url' => url('/restablecer-contrasena/'.$token).'?'.http_build_query(['email' => $this->email]), 'action' => 'Elegir contraseña nueva'], now()->addMinutes(60));
    }

    /** Las cuentas creadas con Google no tienen contraseña local hasta que la recuperan por correo. */
    public function hasLocalPassword(): bool
    {
        return filled($this->password);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
