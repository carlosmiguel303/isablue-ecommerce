@extends('layouts.store')
@section('title','Tu carrito · IsaBlue')
@section('content')
@php($free = (float) \App\Models\Setting::valueOf('free_shipping_from','0'))
<div class="wrap section">
    <div class="checkout-steps"><span class="on"><b>1</b>Carrito</span><i></i><span><b>2</b>Datos y entrega</span><i></i><span><b>3</b>Pago con Yape</span></div>
    <h1>Tu carrito</h1>
    @if($lines)
    <div class="checkout-grid">
        <div>
            @foreach($lines as $line)
                @php($p = $line['product'])
                <article class="cart-item">
                    <img src="{{ $p->image }}" alt="{{ $p->name }}" width="100" height="100">
                    <div>
                        <h3><a href="/juguete/{{ $p->slug }}">{{ $p->name }}</a></h3>
                        <p>S/ {{ number_format($p->price,2) }} c/u</p>
                        <form method="post" action="/carrito/{{ $p->id }}" class="inline-form">@csrf @method('PATCH')
                            <div class="qty" data-qty>
                                <button type="button" data-step="-1" aria-label="Quitar uno">@include('partials.icon',['name'=>'minus','size'=>16])</button>
                                <input aria-label="Cantidad" type="number" min="1" max="{{ max(1,min(99,(int)$p->stock)) }}" name="quantity" value="{{ $line['quantity'] }}">
                                <button type="button" data-step="1" aria-label="Agregar uno">@include('partials.icon',['name'=>'plus','size'=>16])</button>
                            </div>
                            <button class="btn ghost small">Actualizar</button>
                            <button class="link-button" name="quantity" value="0">Quitar</button>
                        </form>
                        @if(!$p->available || $line['quantity']>$p->stock)<p class="error">Revisa el stock de este juguete antes de continuar.</p>@endif
                    </div>
                    <strong>S/ {{ number_format($line['total']/100,2) }}</strong>
                </article>
            @endforeach
            <a class="text-link" href="/catalogo">← Seguir comprando</a>
        </div>
        <aside class="summary">
            <h2>Resumen</h2>
            <div><span>Subtotal</span><strong>S/ {{ number_format($subtotal/100,2) }}</strong></div>
            @if($free > 0)
                @php($missing = max(0, $free - $subtotal/100))
                <div class="free-ship">{{ $missing > 0 ? '¡Te faltan S/ '.number_format($missing,2).' para el envío gratis!' : '🎉 ¡Tu pedido tiene envío gratis!' }}<div class="bar"><i style="width: {{ min(100, $subtotal/100/$free*100) }}%"></i></div></div>
            @endif
            <p class="hint">El costo de envío lo eliges en el siguiente paso.</p>
            <a class="btn full big" href="/checkout">Continuar compra @include('partials.icon',['name'=>'arrow','size'=>20])</a>
            <p class="secure">@include('partials.icon',['name'=>'shield','size'=>18]) Compra segura · Pago con Yape</p>
        </aside>
    </div>
    @else
        <div class="empty"><span>@include('partials.icon',['name'=>'heart','size'=>48])</span><h2>Tu carrito está esperando aventuras</h2><p>Encuentra un juguete que le encante.</p><a class="btn" href="/catalogo">Explorar juguetes</a></div>
    @endif
</div>
@endsection
