@extends('layouts.store') @section('title','Crear cuenta · IsaBlue') @section('content')
<div class="wrap"><div class="auth-wrap">
    <div class="auth-art" style="background:linear-gradient(135deg,var(--teal),var(--purple))"><img src="/assets/images/oso-logo.png" alt="" width="160" height="160"><h2>Únete a IsaBlue</h2><p>Guarda tus datos y sigue tus pedidos.</p></div>
    <form class="auth-form" method="post" action="/registro">@csrf
        <h1 style="font-size:2rem">Crea tu cuenta</h1>
        @include('auth.google-button')
        <label>Nombre completo<input name="name" value="{{ old('name') }}" required autocomplete="name"></label>
        <label>Correo electrónico<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label>Contraseña (mínimo 8 caracteres)<input name="password" type="password" minlength="8" maxlength="72" required autocomplete="new-password"></label>
        <label>Repite tu contraseña<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
        <button class="btn full">Crear cuenta</button>
        <p class="hint"><a class="text-link" href="/ingresar">Ya tengo una cuenta</a></p>
    </form>
</div></div>
@endsection
