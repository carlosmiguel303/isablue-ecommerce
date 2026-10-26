@extends('layouts.store')
@section('title','Contáctanos · Isablue')
@section('content')
@php
    $wa = \App\Models\Setting::valueOf('whatsapp');
    $phone = \App\Models\Setting::valueOf('phone');
    $email = \App\Models\Setting::valueOf('contact_email');
    $address = \App\Models\Setting::valueOf('address');
    $hours = \App\Models\Setting::valueOf('hours');
@endphp
<div class="wrap">
    <section class="page-hero" style="background:linear-gradient(135deg,var(--pink),var(--orange))">
        <h1>Contáctanos</h1>
        <p>¿Tienes dudas sobre un juguete, tu pedido o un regalo? Estamos para ayudarte.</p>
    </section>
    <section class="section contact-grid">
        @if($wa)
        <div class="contact-card">
            <span class="qt-ico c-green">@include('partials.icon',['name'=>'whatsapp'])</span>
            <h3>WhatsApp</h3>
            <p class="muted">La forma más rápida de hablar con nosotros.</p>
            <a class="btn green" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">Escribir ahora</a>
        </div>
        @endif
        <div class="contact-card">
            <span class="qt-ico c-purple">@include('partials.icon',['name'=>'phone'])</span>
            <h3>Teléfono y correo</h3>
            @if($phone)<p><strong>{{ $phone }}</strong></p>@endif
            @if($email)<a class="text-link" href="mailto:{{ $email }}">{{ $email }}</a>@endif
            @if(!$phone && !$email)<p class="muted">Escríbenos por nuestras redes sociales.</p>@endif
        </div>
        <div class="contact-card">
            <span class="qt-ico c-teal">@include('partials.icon',['name'=>'pin'])</span>
            <h3>Dónde estamos</h3>
            <p>{{ $address ?: 'Lima, Perú' }}</p>
            @if($hours)<p class="muted">{{ $hours }}</p>@endif
        </div>
    </section>
    <section class="section faq narrow" style="margin-inline:auto">
        <div class="section-head center"><h2>Preguntas frecuentes</h2></div>
        <details><summary>¿Cómo pago mi pedido?</summary><p>Con Yape. Al confirmar tu pedido verás el QR y el número. Yapea el total y sube tu captura.</p></details>
        <details><summary>¿Cuándo envían mi pedido?</summary><p>Apenas validamos tu Yape preparamos tu pedido y coordinamos la entrega contigo.</p></details>
        <details><summary>¿Hacen envíos a provincias?</summary><p>Sí, enviamos a todo el Perú. Elige la opción de envío al finalizar tu compra.</p></details>
        <details><summary>¿Puedo cambiar un producto?</summary><p>Revisa nuestra <a class="text-link" href="/politicas/returns">política de cambios y devoluciones</a>.</p></details>
    </section>
</div>
@endsection
