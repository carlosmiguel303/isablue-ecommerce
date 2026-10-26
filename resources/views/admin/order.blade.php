@extends('layouts.admin')
@section('title','Pedido '.$order->reference)
@section('content')
@php
    $money = $order->currency==='PEN' ? 'S/ ' : '';
    $canPay = $order->status==='pending' && $order->payment_status!=='paid' && $order->currency==='PEN';
    $labels = ['processing'=>['Pasar a «En preparación»','teal'],'shipped'=>['Marcar como enviado','purple'],'completed'=>['Marcar como entregado','']];
    $waMsg = 'Hola '.$order->customer_name.' 👋, te escribimos de Isablue por tu pedido '.$order->reference.' ('.$money.number_format($order->total,2).').';
@endphp
<a class="back" href="/admin/pedidos">← Volver a pedidos</a>
<div class="page-title">
    <div><span class="eyebrow">{{ $order->created_at->format('d/m/Y H:i') }}</span><h1>Pedido {{ $order->reference }} @if($order->is_demo)<small class="tag-demo">PRUEBA</small>@endif</h1></div>
    <div class="chips"><span class="status-chip {{ $order->status }}">{{ __('store.status.'.$order->status) }}</span><span class="status-chip p-{{ $order->payment_status }}">{{ __('store.payment.'.$order->payment_status) }}</span></div>
</div>
@if($order->currency!=='PEN')<div class="notice">Pedido histórico sin moneda registrada. Los importes se conservan sin convertir.</div>@endif

<div class="order-layout">
    <div>
        @if($canPay)
        <section class="admin-card pay-card">
            <h2>¿Ya llegó el pago de {{ $money }}{{ number_format($order->total,2) }}?</h2>
            <p><strong>Forma de pago elegida:</strong> {{ $order->payment_label }}</p>
            <p class="muted">Verifica en tu app de Yape. Al confirmar se descuenta el stock y el pedido pasa a «Por preparar». Si no ha pagado, el pedido queda en espera.</p>
            @if($shortages)<div class="flash bad" style="margin-bottom:12px">⚠️ Stock insuficiente: {{ implode(', ', $shortages) }}. Ajusta el inventario antes de confirmar o cancela el pedido.</div>@endif
            <div class="pay-grid">
                @if($order->voucher_path)
                    <a class="voucher-preview" href="/admin/pedidos/{{ $order->id }}/comprobante" target="_blank"><img src="/admin/pedidos/{{ $order->id }}/comprobante" alt="Comprobante de Yape enviado por el cliente"><span>Ampliar captura</span></a>
                @else
                    <div class="voucher-empty">@include('partials.icon',['name'=>'image','size'=>34])<span>El cliente aún no sube su captura</span></div>
                @endif
                <form method="post" action="/admin/pedidos/{{ $order->id }}/pago" class="pay-form" data-confirm="¿Confirmas que el Yape de {{ $money }}{{ number_format($order->total,2) }} ya llegó?">@csrf
                    <label>N.º de operación Yape<input name="payment_reference" value="{{ $order->payment_reference }}" maxlength="60" inputmode="numeric"></label>
                    <label>Nota (opcional)<input name="note" maxlength="500" placeholder="Ej.: yapeó desde otro número"></label>
                    <button class="btn green big full">✓ Sí, ya yapeó · Confirmar pago</button>
                </form>
            </div>
        </section>
        @endif

        <section class="admin-card">
            <h2>Productos</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Total</th><th>Stock actual</th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td><div class="prod-cell">@if($item->product)<img src="{{ $item->product->image }}" alt="" width="44" height="44">@endif<span><strong>{{ $item->name }}</strong><small class="muted">{{ $item->sku }}</small></span></div></td>
                        <td>{{ (float)$item->quantity }}</td>
                        <td>{{ $money }}{{ number_format($item->unit_price,2) }}</td>
                        <td><strong>{{ $money }}{{ number_format($item->total,2) }}</strong></td>
                        <td>{{ $item->product?->stock ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="totals">
                <div><span>Subtotal</span><span>{{ $money }}{{ number_format($order->subtotal,2) }}</span></div>
                <div><span>Envío ({{ $order->shipping_name ?? '—' }})</span><span>{{ $money }}{{ number_format($order->shipping_total,2) }}</span></div>
                <div class="grand"><span>Total</span><strong>{{ $money }}{{ number_format($order->total,2) }}</strong></div>
            </div>
            <p class="hint">{{ $order->stock_reserved ? ($order->stock_released ? 'El stock se devolvió al cancelar.' : '✓ El stock de este pedido ya fue descontado.') : 'El stock aún NO se descuenta: se descontará al confirmar el Yape.' }}</p>
        </section>

        <section class="admin-card">
            <h2>Historial</h2>
            @forelse($order->events as $event)
                <div class="event"><strong>{{ $event->event }}</strong>@if($event->note)<p>{{ $event->note }}</p>@endif<small class="muted">{{ $event->created_at?->format('d/m/Y H:i') }}</small></div>
            @empty
                <p class="muted">Pedido importado del sistema anterior.</p>
            @endforelse
        </section>
    </div>

    <aside>
        <section class="admin-card">
            <h2>Cliente</h2>
            <p><strong>{{ $order->customer_name }}</strong></p>
            <p>@include('partials.icon',['name'=>'phone','size'=>16]) {{ $order->phone }}<br>@include('partials.icon',['name'=>'mail','size'=>16]) {{ $order->customer_email }}</p>
            <p>@include('partials.icon',['name'=>'pin','size'=>16]) {{ $order->address }}</p>
            @if($order->customer_note)<div class="notice">📝 {{ $order->customer_note }}</div>@endif
            @if($order->whatsapp_number)<a class="btn green full" href="https://wa.me/{{ $order->whatsapp_number }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener">@include('partials.icon',['name'=>'whatsapp','size'=>18]) Escribir por WhatsApp</a>@endif
        </section>

        <section class="admin-card">
            <h2>Siguiente paso</h2>
            @if($order->status==='pending')
                <p class="muted">{{ $canPay ? 'Confirma el Yape para poder preparar este pedido.' : 'Este pedido no puede avanzar.' }}</p>
            @endif
            @foreach($next as $status)
                @continue($status==='cancelled')
                <form method="post" action="/admin/pedidos/{{ $order->id }}/estado">@csrf<input type="hidden" name="status" value="{{ $status }}"><button class="btn full {{ $labels[$status][1] ?? '' }}" style="margin-bottom:8px">{{ $labels[$status][0] ?? __('store.status.'.$status) }}</button></form>
            @endforeach
            @if(in_array('cancelled', $next))
                <form method="post" action="/admin/pedidos/{{ $order->id }}/estado" data-confirm="¿Cancelar el pedido {{ $order->reference }}?{{ $order->stock_reserved && !$order->stock_released ? ' Las unidades volverán al stock.' : '' }}">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn ghost full danger">Cancelar pedido</button></form>
                <p class="hint">{{ $order->payment_status==='paid' ? 'Si cancelas un pedido pagado, recuerda devolver el dinero.' : 'Cancela si el cliente no yapeó.' }}</p>
            @endif
            @if(!$next)<p class="muted">Pedido cerrado.</p>@endif
            <a class="text-link" href="/pedido/{{ $order->public_token }}" target="_blank">Ver como cliente ↗</a>
        </section>
    </aside>
</div>
@endsection
