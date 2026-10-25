<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Integrations;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $r)
    {
        if (! Integrations::googleReady()) {
            return redirect('/ingresar')->withErrors(['google' => 'El acceso con Google todavía está pendiente de activación. Puedes ingresar con correo y contraseña.']);
        }
        $r->session()->put('google.return', $r->query('volver') === 'checkout' ? '/checkout' : '/mi-cuenta');

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(Request $r)
    {
        if (! Integrations::googleReady()) {
            return redirect('/ingresar')->withErrors(['google' => 'Google aún no está configurado.']);
        }
        try {
            $g = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect('/ingresar')->withErrors(['google' => 'No se pudo completar el acceso con Google. Vuelve a intentarlo.']);
        }
        $email = mb_strtolower(trim((string) $g->getEmail()));
        if (! $g->getId() || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! (($g->user['email_verified'] ?? $g->user['verified_email'] ?? false) === true)) {
            return redirect('/ingresar')->withErrors(['google' => 'Google no confirmó el correo de esta cuenta.']);
        }
        $user = User::where('google_id', $g->getId())->first();
        if (! $user) {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                if (! $r->user() || $r->user()->id !== $existing->id || $existing->isAdmin() || $existing->google_id) {
                    return redirect('/ingresar')->withErrors(['google' => 'Este correo ya tiene una cuenta. Ingresa con tu contraseña y vincula Google desde Mi cuenta. El administrador utiliza su acceso habitual.']);
                }
                $existing->forceFill(['google_id' => $g->getId(), 'email_verified_at' => now()])->save();
                $user = $existing;
            } else {
                try {
                    $user = DB::transaction(function () use ($g, $email) {
                        $u = new User(['name' => Str::limit($g->getName() ?: 'Cliente IsaBlue', 120, ''), 'email' => $email, 'password' => Hash::make(Str::random(64))]);
                        $u->forceFill(['google_id' => $g->getId(), 'email_verified_at' => now(), 'role' => 'customer'])->save();

                        return $u;
                    });
                } catch (QueryException $e) {
                    return redirect('/ingresar')->withErrors(['google' => 'La cuenta se actualizó durante el acceso. Inténtalo otra vez.']);
                }
            }
        }
        if ($user->isAdmin()) {
            return redirect('/ingresar')->withErrors(['google' => 'El administrador utiliza su acceso habitual.']);
        }
        Auth::login($user);
        $r->session()->regenerate();

        return redirect($r->session()->pull('google.return', '/mi-cuenta'));
    }
}
