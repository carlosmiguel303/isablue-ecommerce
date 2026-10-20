@extends('layouts.store')
@section('title','Cómo comprar con Yape · IsaBlue')
@section('content')
<div class="wrap">
    <section class="page-hero" style="background:linear-gradient(135deg,#742284,var(--pink))">
        <span class="pill"><span class="yape-badge" style="padding:0 8px">yape</span> Pago fácil y seguro</span>
        <h1>Cómo comprar en IsaBlue</h1>
        <p>Comprar es rápido: eliges, yapeas y te lo llevamos. Sin tarjetas y sin crear cuenta.</p>
    </section>
    <section class="section">
        <div class="steps">
            <div class="step"><span class="step-n c-pink">1</span><h3>Elige tus juguetes</h3><p>Agrégalos al carrito y presiona «Finalizar compra». Completa tus datos y elige el envío.</p></div>
            <div class="step"><span class="step-n c-purple">2</span><h3>Yapea el total</h3><p>Escanea nuestro QR o yapea al número que verás en pantalla. Escribe tu código de pedido en el mensaje.</p></div>
            <div class="step"><span class="step-n c-teal">3</span><h3>Sube tu captura</h3><p>Adjunta la captura en la página de tu pedido o envíala por WhatsApp.</p></div>
            <div class="step"><span class="step-n c-orange">4</span><h3>Validamos tu pago</h3><p>Confirmamos tu Yape y separamos tus juguetes al instante.</p></div>
            <div class="step"><span class="step-n c-green">5</span><h3>Preparamos y enviamos</h3><p>Te avisamos cuando tu pedido sale en camino.</p></div>
            <div class="step"><span class="step-n c-blue">6</span><h3>¡A jugar!</h3><p>Recibe en casa y disfruta. Cualquier duda, escríbenos.</p></div>
        </div>
        <div style="text-align:center;margin-top:30px"><a class="btn big" href="/catalogo">Empezar a comprar @include('partials.icon',['name'=>'arrow','size'=>20])</a></div>
    </section>
</div>
@endsection
