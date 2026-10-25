@extends('layouts.store') @section('title','Ingresar · IsaBlue') @section('content')
<div class="wrap"><div class="auth-wrap">
    <div class="auth-art"><img src="/assets/images/oso-logo.png" alt="" width="160" height="160"><h2>¡Qué bueno verte de nuevo!</h2><p>Revisa tus pedidos y compra más rápido.</p></div>
    <form class="auth-form" method="post" action="/ingresar">@csrf @if(request('volver')==='checkout')<input type="hidden" name="volver" value="checkout">@endif
        <span class="eyebrow">Tu mundo IsaBlue</span><h1 style="font-size:2rem">Ingresar</h1>
        @include('auth.google-button')
        <label>Correo electrónico<input name="email" type="email" value="{{ old('email') }}" autocomplete="username" required></label>
        <label>Contraseña<input name="password" type="password" autocomplete="current-password" required></label>
        <button class="btn full">Ingresar</button>
        <p><a class="text-link" href="/olvide-mi-contrasena">¿Olvidaste tu contraseña?</a></p><p class="hint">¿Es tu primera vez? <a class="text-link" href="/registro">Crea tu cuenta</a> y consulta tus pedidos.</p>
    </form>
</div></div>
@endsection
