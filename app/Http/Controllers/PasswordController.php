<?php

namespace App\Http\Controllers;

use App\Services\Integrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function forgot()
    {
        return view('auth.forgot');
    }

    public function email(Request $r)
    {
        $d = $r->validate(['email' => 'required|email|max:255']);
        if (! Integrations::mailReady()) {
            return back()->withErrors(['email' => 'La recuperación por correo aún no está activada. Contacta con la tienda.']);
        }
        Password::sendResetLink(['email' => mb_strtolower(trim($d['email']))]);

        return back()->with('status', 'Si el correo tiene una cuenta, recibirás un enlace para cambiar la contraseña. Revisa también spam.');
    }

    public function form(Request $r, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $r->query('email', '')]);
    }

    public function reset(Request $r)
    {
        $d = $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|string|min:12|max:72|confirmed']);
        $result = Password::reset($d, function ($u, $password) {
            $u->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });
        if ($result !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);
        }

        return redirect('/ingresar')->with('status', 'Contraseña actualizada. Ingresa con tu nueva contraseña.');
    }
}
