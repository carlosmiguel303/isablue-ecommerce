@extends('layouts.store')
@section('title','Finalizar compra · IsaBlue')
@section('content')
@php
    $yape = \App\Models\Setting::valueOf('yape_number');
    $holder = \App\Models\Setting::valueOf('yape_holder');
    $qr = \App\Models\Setting::valueOf('yape_qr');
    $u = auth()->user();
@endphp
<div class="wrap section">
    <div class="checkout-steps"><span class="on"><b>✓</b>Carrito</span><i></i><span class="on"><b>2</b>Datos y entrega</span><i></i><span class="on"><b>3</b>Pago con Yape</span></div>
    <h1>Finaliza tu compra</h1>
    @if(!$lines)
        <div class="empty"><p>Agrega un juguete a tu carrito para continuar.</p><a href="/catalogo" class="btn">Explorar juguetes</a></div>
    @else
    @if(!$paymentReady && !$demoMode)
        <div class="notice" role="alert">El pago todavía no está disponible. Contacta con la tienda antes de realizar un abono.</div>
    @endif
    <div class="checkout-grid">
        <form method="post" action="/checkout" enctype="multipart/form-data" data-once id="checkout-form">
            @csrf
            <input type="hidden" name="checkout_key" value="{{ session('checkout_key') }}">

            <section class="panel co-block">
                <h2><b>1</b> Tus datos</h2>
                @guest @if(\App\Services\Integrations::googleReady())<a class="btn ghost" href="/auth/google?volver=checkout">Continuar con Google</a>@endif <p class="hint">Con tu correo y una contraseña creamos tu cuenta al confirmar, para que veas tus pedidos cuando quieras. <a class="text-link" href="/ingresar?volver=checkout">¿Ya tienes cuenta? Ingresa</a></p>@endguest
                @auth<p class="hint">Comprando como <strong>{{ $u->email }}</strong>. Verás este pedido en <a class="text-link" href="/mi-cuenta">Mi cuenta</a>.</p>@endauth
                <div class="form-grid">
                    <label>Nombre y apellido<input name="name" value="{{ old('name',$u?->name) }}" required autocomplete="name"></label>
                    <label>Celular (WhatsApp)<input name="phone" type="tel" inputmode="tel" value="{{ old('phone',$u?->phone) }}" required autocomplete="tel" placeholder="9XX XXX XXX"></label>
                    @guest
                        <label>Correo electrónico<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
                        <label>Crea tu contraseña (mín. 8)<input type="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password"></label>
                    @else
                        <input type="hidden" name="email" value="{{ $u->email }}">
                    @endguest
                    <label class="span-all">Dirección, distrito y referencia<input name="address" value="{{ old('address',$u?->address) }}" required autocomplete="street-address" placeholder="Av. ..., Distrito, referencia"></label>
                    <label class="span-all">Nota para tu pedido (opcional)<input name="note" value="{{ old('note') }}" maxlength="500" placeholder="Ej.: es para regalo, horario de entrega…"></label>
                </div>
            </section>

            <section class="panel co-block">
                <h2><b>2</b> Entrega</h2>
                @forelse($shipping as $method)
                    <label class="shipping-choice">
                        <input type="radio" name="shipping_method_id" value="{{ $method->id }}" data-shipping="{{ $method->final_price }}" required @checked(old('shipping_method_id', $shipping->count()===1 ? $method->id : null)==$method->id)>
                        <span><strong>{{ $method->name }}</strong>@if($method->description)<small>{{ $method->description }}</small>@endif</span>
                        <em>{{ $method->final_price > 0 ? 'S/ '.number_format($method->final_price,2) : 'Gratis' }}</em>
                    </label>
                @empty
                    <div class="notice">Estamos configurando las opciones de entrega. Escríbenos por WhatsApp para completar tu compra.</div>
                @endforelse
            </section>

            <section class="panel co-block"><h2><b>3</b> Reserva y pago</h2><p>Al confirmar reservaremos tus juguetes durante <strong>30 minutos</strong>. En la siguiente pantalla verás el número y QR de Yape. Envía tu comprobante dentro del plazo para mantener la reserva hasta 24 horas mientras la tienda revisa el pago.</p>@if($demoMode)<p class="notice">Pedido de prueba: no realices un pago.</p>@endif</section>
        </form>

        <aside class="summary">
            <h2>Tu pedido</h2>
            @foreach($lines as $line)
                <div><span>{{ $line['quantity'] }} × {{ $line['product']->name }}</span><strong>S/ {{ number_format($line['total']/100,2) }}</strong></div>
            @endforeach
            <div><span>Subtotal</span><strong>S/ {{ number_format($subtotal/100,2) }}</strong></div>
            <div><span>Envío</span><strong id="shipping-total">Elige una opción</strong></div>
            <div class="total"><span>Total</span><strong id="checkout-total" data-subtotal="{{ $subtotal/100 }}">S/ {{ number_format($subtotal/100,2) }}</strong></div>
            <button class="btn full big" form="checkout-form" type="submit" @disabled($shipping->isEmpty() || (!$paymentReady && !$demoMode))>Confirmar pedido @include('partials.icon',['name'=>'check','size'=>20])</button>
            <p class="secure">@include('partials.icon',['name'=>'shield','size'=>18]) Tu pedido se separa al confirmar tu Yape</p>
        </aside>
    </div>
    @endif
</div>
@endsection
