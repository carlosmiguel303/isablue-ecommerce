@extends('layouts.store') @section('title','Mi cuenta · Isablue') @section('content')
@if(!auth()->user()->isAdmin() && !auth()->user()->google_id && \App\Services\Integrations::googleReady())<div class="wrap notice"><a href="/auth/google">Vincular mi cuenta de Google para ingresar más rápido</a></div>@endif

<div class="wrap section narrow">
    <div class="section-head"><div><span class="eyebrow">Mi cuenta</span><h1>Hola, {{ auth()->user()->name }}</h1></div><form method="post" action="/salir">@csrf<button class="btn ghost small">Cerrar sesión</button></form></div>
    <h2>Mis pedidos</h2>
    @forelse($orders as $order)
        <a class="account-order" href="/pedido/{{ $order->public_token }}"><span><strong>{{ $order->reference }}</strong><br><small class="muted">{{ $order->created_at->format('d/m/Y') }}</small></span><span class="status-chip {{ $order->status }}">{{ __('store.status.'.$order->status) }}</span><strong>{{ $order->currency==='PEN'?'S/':'Importe original' }} {{ number_format($order->total,2) }}</strong></a>
    @empty
        <div class="empty"><p>Aún no tienes pedidos.</p><a class="btn" href="/catalogo">Explorar juguetes</a></div>
    @endforelse
    @if($reviewable->isNotEmpty())
    <h2 id="mis-resenas" style="margin-top:32px">Califica tus juguetes</h2>
    <p class="muted">Cuéntale a otras familias cómo les fue. Revisamos cada reseña antes de publicarla.</p>
    <div class="review-todo">
        @foreach($reviewable as $p)
        <div class="review-todo-item">
            <img src="{{ $p->image }}" alt="" width="56" height="56" loading="lazy">
            <strong>{{ $p->name }}</strong>
            @if(isset($myReviews[$p->id]))
                <span class="status-chip">{{ $myReviews[$p->id] === 'approved' ? '✓ Reseña publicada' : ($myReviews[$p->id] === 'pending' ? 'Reseña en revisión' : 'Reseña no publicada') }}</span>
            @else
                <a class="btn small" href="/juguete/{{ $p->slug }}/resena">⭐ Dejar mi reseña</a>
            @endif
        </div>
        @endforeach
    </div>
    @endif
    <div class="pagination">@if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">← Anterior</a>@endif @if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Siguiente →</a>@endif</div>
</div>
@endsection
