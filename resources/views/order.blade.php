@extends('layouts.store')
@section('title','Pedido '.$order->reference.' · IsaBlue')
@section('content')
@php
    $yape = \App\Models\Setting::valueOf('yape_number');
    $holder = \App\Models\Setting::valueOf('yape_holder');
    $qr = \App\Models\Setting::valueOf('yape_qr');
    $wa = \App\Models\Setting::valueOf('whatsapp');
    $paid = $order->payment_status === 'paid';
    $awaiting = !$paid && $order->status === 'pending';
    $money = ($order->currency==='PEN' ? 'S/ ' : '');
    $steps = ['pending'=>'Pedido recibido','confirmed'=>'Yape confirmado','processing'=>'En preparación','shipped'=>'Enviado','completed'=>'Entregado'];
    $keys = array_keys($steps);
    $pos = array_search($order->status, $keys);
    $waText = 'Hola IsaBlue 👋 Hice el pedido '.$order->reference.' por '.$money.number_format($order->total,2).'. Te envío la captura de mi Yape.';
@endphp
@if($order->is_demo)<div class="wrap"><div class="notice" style="margin-top:16px"><strong>Pedido de prueba</strong> · Sin cobro ni entrega reales.</div></div>@endif
<div class="wrap section narrow">
    <div class="order-hero {{ $paid ? 'ok' : 'wait' }}">
        <div class="big-ico">@include('partials.icon',['name'=>$paid?'check':'clock','size'=>42])</div>
        <span class="eyebrow">¡Gracias por comprar en IsaBlue!</span>
        <h1>{{ $paid ? '¡Pago confirmado!' : ($order->status==='cancelled' ? 'Pedido cancelado' : '¡Pedido recibido! Solo falta tu Yape') }}</h1>
        <span class="order-ref">{{ $order->reference }}</span>
    </div>

    @if($order->status !== 'cancelled')
    <div class="panel">
        <div class="timeline">
            @foreach($steps as $label)
                @php($done = $loop->index <= (int) $pos)
                <div class="{{ $done ? 'done' : ($loop->index === (int) $pos + 1 ? 'now' : '') }}"><b>{{ $done ? '✓' : $loop->iteration }}</b>{{ $label }}</div>
            @endforeach
        </div>
    </div>
    @endif

    @if($awaiting)
    <section class="panel co-block">
        <h2><b>1</b> Yapea <span class="amount-pill">{{ $money }}{{ number_format($order->total,2) }}</span></h2>
        <div class="yape-box">
            @if($qr)<img class="qr" src="{{ $qr }}" alt="Código QR de Yape de IsaBlue" width="170" height="170">@else<div class="qr-placeholder">yape</div>@endif
            <div>
                @if($yape)
                    <div class="yape-number">{{ $yape }}</div>
                    <button type="button" class="copy-btn" data-copy="{{ preg_replace('/\s/','',$yape) }}">Copiar número</button>
                    @if($holder)<p class="hint" style="margin-top:6px">A nombre de: <strong>{{ $holder }}</strong></p>@endif
                @else
                    <p>Te enviaremos el número de Yape por WhatsApp en unos minutos.</p>
                @endif
                <p class="hint">En el mensaje del Yape escribe tu código <strong>{{ $order->reference }}</strong>.</p>
            </div>
        </div>
    </section>

    <section class="panel co-block">
        <h2><b>2</b> Envíanos tu comprobante</h2>
        @if($order->voucher_path)
            <div class="flash ok" style="margin:0 0 14px">@include('partials.icon',['name'=>'check','size'=>18]) Recibimos tu comprobante. Estamos validando tu pago.</div>
        @endif
        <form method="post" action="/pedido/{{ $order->public_token }}/comprobante" enctype="multipart/form-data" class="form-grid" data-once>
            @csrf
            <label class="file-drop span-all">@include('partials.icon',['name'=>'upload','size'=>24])<span data-file-name>{{ $order->voucher_path ? 'Subir otra captura' : 'Toca aquí para subir la captura de tu Yape' }}</span><input type="file" name="voucher" accept="image/jpeg,image/png,image/webp" class="sr-only" required></label>
            <label class="span-all">N.º de operación (opcional)<input name="payment_reference" value="{{ old('payment_reference',$order->payment_reference) }}" maxlength="60" inputmode="numeric"></label>
            <button class="btn purple span-all" type="submit">Enviar comprobante</button>
        </form>
        @if($wa)
            <p class="hint" style="text-align:center;margin:14px 0 8px">o si prefieres</p>
            <a class="btn green full" href="https://wa.me/{{ $wa }}?text={{ urlencode($waText) }}" target="_blank" rel="noopener">@include('partials.icon',['name'=>'whatsapp','size'=>20]) Enviar captura por WhatsApp</a>
        @endif
    </section>
    @endif

    <section class="panel">
        <h2>Detalle del pedido</h2>
        <p><span class="status-chip {{ $order->status }}">{{ __('store.status.'.$order->status) }}</span> <span class="status-chip p-{{ $order->payment_status }}">{{ __('store.payment.'.$order->payment_status) }}</span></p>
        @foreach($order->items as $item)
            <div class="summary-line"><span>{{ (float)$item->quantity }} × {{ $item->name }}</span><strong>S/ {{ number_format($item->total,2) }}</strong></div>
        @endforeach
        <div class="summary-line"><span>Envío: {{ $order->shipping_name }}</span><strong>{{ (float)$order->shipping_total > 0 ? $money.number_format($order->shipping_total,2) : 'Gratis' }}</strong></div>
        <div class="summary-line total"><span>Total</span><strong>{{ $money }}{{ number_format($order->total,2) }}</strong></div>
        <p class="hint">Entrega a: {{ $order->customer_name }} · {{ $order->address }}</p>
        @if($awaiting)<p class="hint">Separamos los juguetes cuando confirmamos tu Yape. ¡Yapea pronto para asegurar tu pedido!</p>@endif
    </section>
    <a class="btn ghost" href="/catalogo">Seguir comprando</a>
</div>
@endsection
