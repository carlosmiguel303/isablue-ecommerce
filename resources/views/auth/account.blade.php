@extends('layouts.store') @section('title','Mi cuenta · IsaBlue') @section('content')
<div class="wrap section narrow">
    <div class="section-head"><div><span class="eyebrow">Mi cuenta</span><h1>Hola, {{ auth()->user()->name }}</h1></div><form method="post" action="/salir">@csrf<button class="btn ghost small">Cerrar sesión</button></form></div>
    <h2>Mis pedidos</h2>
    @forelse($orders as $order)
        <a class="account-order" href="/pedido/{{ $order->public_token }}"><span><strong>{{ $order->reference }}</strong><br><small class="muted">{{ $order->created_at->format('d/m/Y') }}</small></span><span class="status-chip {{ $order->status }}">{{ __('store.status.'.$order->status) }}</span><strong>{{ $order->currency==='PEN'?'S/':'Importe original' }} {{ number_format($order->total,2) }}</strong></a>
    @empty
        <div class="empty"><p>Aún no tienes pedidos.</p><a class="btn" href="/catalogo">Explorar juguetes</a></div>
    @endforelse
    <div class="pagination">@if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">← Anterior</a>@endif @if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Siguiente →</a>@endif</div>
</div>
@endsection
