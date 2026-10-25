@extends('layouts.store')
@section('title','Cómo comprar con Yape · IsaBlue')
@section('content')
<div class="wrap">
    <section class="page-hero" style="background:linear-gradient(135deg,#742284,var(--pink))">
        <span class="pill"><span class="yape-badge" style="padding:0 8px">yape</span> Pago fácil y seguro</span>
        <h1>Cómo comprar en IsaBlue</h1>
        <p>Elige tus juguetes, confirma el pedido y paga con Yape. Tu cuenta te permite consultar el estado de la compra.</p>
    </section>
    <section class="section">
        <div class="steps">
            <div class="step"><span class="step-n c-pink">1</span><h3>Elige tus juguetes</h3><p>Agrégalos al carrito y presiona «Finalizar compra». Completa tus datos, ingresa o crea tu cuenta y elige el envío. Confirma el pedido para reservar el stock durante 30 minutos.</p></div>
            <div class="step"><span class="step-n c-purple">2</span><h3>Yapea el total</h3><p>Después de confirmar el pedido, escanea nuestro QR o yapea al número que verás en su página antes de que venza la reserva. Escribe tu código de pedido en el mensaje.</p></div>
            <div class="step"><span class="step-n c-teal">3</span><h3>Sube tu captura</h3><p>Adjunta la captura en la página de tu pedido antes de que venza la reserva. La primera captura enviada a tiempo amplía la reserva a 24 horas para validar el pago.</p></div>
            <div class="step"><span class="step-n c-orange">4</span><h3>Validamos tu pago</h3><p>Revisamos el abono y confirmamos tu pago. Puedes consultar el estado actualizado desde Mi cuenta.</p></div>
            <div class="step"><span class="step-n c-green">5</span><h3>Preparamos y enviamos</h3><p>Te avisamos cuando tu pedido sale en camino.</p></div>
            <div class="step"><span class="step-n c-blue">6</span><h3>¡A jugar!</h3><p>Recibe en casa y disfruta. Cualquier duda, escríbenos.</p></div>
        </div>
        <div style="text-align:center;margin-top:30px"><a class="btn big" href="/catalogo">Empezar a comprar @include('partials.icon',['name'=>'arrow','size'=>20])</a></div>
    </section>
</div>
@endsection
