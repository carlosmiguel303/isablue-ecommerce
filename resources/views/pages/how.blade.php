@extends('layouts.store')
@section('title','Cómo comprar con Yape · Isablue')
@section('content')
<div class="wrap">
    <section class="page-hero" style="background:linear-gradient(135deg,#742284,var(--pink))">
        <span class="pill"><span class="yape-badge" style="padding:0 8px">yape</span> Pago fácil y seguro</span>
        <h1>Cómo comprar en Isablue</h1>
        <p>Elige tus juguetes, confirma el pedido y paga directo con Yape. También aceptamos transferencia, contraentrega y efectivo.</p>
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
        <div class="panel" style="margin-top:30px">
            <h2>Formas de pago</h2>
            <p><strong>Yape (recomendado):</strong> pagas al instante desde la web y tu pedido avanza más rápido.</p>
            <p>Si lo prefieres, al finalizar tu compra también puedes elegir:</p>
            <ul>
                <li><strong>Transferencia bancaria:</strong> te enviamos los datos de la cuenta.</li>
                <li><strong>Contraentrega:</strong> pagas al recibir tu pedido.</li>
                <li><strong>Efectivo:</strong> pagas al recibir; ten el monto exacto, por favor.</li>
            </ul>
            <h2>Entregas</h2>
            <ul>
                <li><strong>Envío a domicilio en Lima:</strong> desde S/ 10 (el monto puede variar según el distrito).</li>
                <li><strong>Entrega en estaciones del Metropolitano o Tren:</strong> S/ 5.</li>
                <li><strong>Envíos a provincias:</strong> por Shalom u Olva; el costo depende del destino.</li>
            </ul>
        </div>
        <div style="text-align:center;margin-top:30px"><a class="btn big" href="/catalogo">Empezar a comprar @include('partials.icon',['name'=>'arrow','size'=>20])</a></div>
    </section>
</div>
@endsection
