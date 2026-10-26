<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Hash de relleno: iguala el tiempo de respuesta cuando el correo no existe. */
    private const DUMMY_HASH = '$2y$12$rRCpRLdIbp08FM7FAgVCgOhKksmgt9kYAYNkwOLTQTiioWB9XEXyK';

    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:200']);
        $key = 'login:'.mb_strtolower($data['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Espera un minuto.']);
        }$user = User::where('email', mb_strtolower(trim($data['email'])))->first();
        // Se compara siempre contra un hash para no revelar por tiempo si el correo existe.
        $valid = password_verify($data['password'], $user?->hasLocalPassword() ? $user->password : self::DUMMY_HASH);
        if (! $user || ! $user->hasLocalPassword() || ! $valid) {
            RateLimiter::hit($key, 60);
            if ($user && ! $user->hasLocalPassword() && $user->google_id && ! $user->isAdmin()) {
                throw ValidationException::withMessages(['email' => 'Esta cuenta utiliza Google para iniciar sesión. Selecciona «Continuar con Google» en la parte inferior.']);
            }
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }RateLimiter::clear($key);
        if (password_needs_rehash($user->password, PASSWORD_BCRYPT, ['cost' => 12])) {
            $user->password = Hash::make($data['password']);
            $user->save();
        }Auth::login($user);
        $r->session()->regenerate();

        if ($r->input('volver') === 'checkout') {
            return redirect('/checkout');
        }

        return redirect()->intended($user->isAdmin() ? '/admin' : '/mi-cuenta');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $r)
    {
        $r->merge(['email' => mb_strtolower(trim((string) $r->input('email')))]);
        $d = $r->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:8|max:72|confirmed'], ['email.unique' => 'Ese correo ya tiene una cuenta. Inicia sesión con tu contraseña o con «Continuar con Google», o usa «¿Olvidaste tu contraseña?».']);
        $d['password'] = Hash::make($d['password']);
        $u = User::create($d);
        Auth::login($u);
        $r->session()->regenerate();

        return redirect('/mi-cuenta');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }

    public function account(Request $r)
    {
        return view('auth.account', ['orders' => Order::where('user_id', $r->user()->id)->latest()->paginate(10)]);
    }
}
