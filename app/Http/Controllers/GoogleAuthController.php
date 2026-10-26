<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Integrations;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        if ($r->user() && (! $user || $user->id !== $r->user()->id) && mb_strtolower($r->user()->email) !== $email) {
            return redirect('/mi-cuenta')->withErrors(['google' => 'Esa cuenta de Google usa otro correo. Para vincularla, usa la cuenta de Google de '.$r->user()->email.'.']);
        }
        if (! $user) {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                if (! $r->user() || $r->user()->id !== $existing->id || $existing->isAdmin() || $existing->google_id) {
                    return redirect('/ingresar')->withErrors(['google' => 'Ya existe una cuenta con este correo. Inicia sesión con tu correo y contraseña; luego podrás vincular Google desde «Mi cuenta». Si no recuerdas tu contraseña, usa «¿Olvidaste tu contraseña?».']);
                }
                $existing->forceFill(['google_id' => $g->getId(), 'email_verified_at' => now()])->save();
                $user = $existing;
            } else {
                try {
                    $user = DB::transaction(function () use ($g, $email) {
                        $u = new User(['name' => Str::limit($g->getName() ?: 'Cliente Isablue', 120, ''), 'email' => $email]);
                        $u->forceFill(['password' => null, 'google_id' => $g->getId(), 'auth_provider' => 'google', 'email_verified_at' => now(), 'role' => 'customer'])->save();

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
