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
        }$user = User::where('email', $data['email'])->first();
        if (! $user || ! password_verify($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
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
        $d = $r->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:8|max:72|confirmed']);
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
