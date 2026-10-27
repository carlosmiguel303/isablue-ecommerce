@extends('layouts.admin')
@section('title','Reseñas de clientes')
@section('content')
<div class="page-title"><div><span class="eyebrow">Opiniones de compradores</span><h1>Reseñas de clientes</h1></div></div>
<div class="help-box"><strong>¿Cómo funciona?</strong> Solo los clientes con un pedido <b>enviado</b> o <b>entregado</b> pueden dejar una reseña de ese juguete, una sola vez. Nada se muestra en la tienda hasta que presionas «Publicar». Las publicadas aparecen en la ficha del juguete y en Blog → Reseñas de clientes.</div>
<div class="admin-tabs">
    @foreach(\App\Models\Review::STATUSES as $key => $label)
        <a href="/admin/resenas?ver={{ $key }}" class="{{ $tab===$key?'on':'' }} {{ $key==='pending' && ($counts[$key] ?? 0) ? 'alert' : '' }}">{{ $label }} <em>{{ $counts[$key] ?? 0 }}</em></a>
    @endforeach
</div>
@forelse($reviews as $review)
<div class="admin-card" style="display:grid;grid-template-columns:{{ $review->photo ? '120px ' : '' }}1fr auto;gap:18px;align-items:start;margin-bottom:12px">
    @if($review->photo)<a href="{{ $review->photo }}" target="_blank" rel="noopener"><img src="{{ $review->photo }}" alt="Foto enviada" style="width:120px;height:120px;object-fit:cover;border-radius:12px"></a>@endif
    <div>
        <div style="color:#F5A623;font-size:1.2rem;letter-spacing:2px">{{ $review->stars }}</div>
        <p style="white-space:pre-line;margin:6px 0">“{{ $review->comment }}”</p>
        <small class="muted">
            Se publica como <b>{{ $review->author_name }}{{ $review->district ? ' · '.$review->district : '' }}</b> ·
            {{ $review->user?->email }} ·
            Juguete: <a href="/juguete/{{ $review->product?->slug }}" target="_blank" rel="noopener">{{ $review->product?->name }}</a>
            @if($review->order) · Pedido <a href="/admin/pedidos/{{ $review->order->id }}">{{ $review->order->reference }}</a>@endif
            · {{ $review->created_at->format('d/m/Y H:i') }}
        </small>
    </div>
    <div style="display:grid;gap:6px">
        @if($review->status !== 'approved')<form method="post" action="/admin/resenas/{{ $review->id }}/estado">@csrf<input type="hidden" name="status" value="approved"><button class="btn small">✓ Publicar</button></form>@endif
        @if($review->status !== 'rejected')<form method="post" action="/admin/resenas/{{ $review->id }}/estado">@csrf<input type="hidden" name="status" value="rejected"><button class="btn small ghost">{{ $review->status === 'approved' ? 'Ocultar' : 'Rechazar' }}</button></form>@endif
        <form method="post" action="/admin/resenas/{{ $review->id }}" data-confirm="¿Eliminar esta reseña? El cliente podrá escribir una nueva.">@csrf @method('DELETE')<button class="btn small ghost">Eliminar</button></form>
    </div>
</div>
@empty
<div class="admin-card empty-state">@include('partials.icon',['name'=>'star','size'=>40])<p>No hay reseñas en esta sección.</p></div>
@endforelse
@include('admin.pagination',['paginator'=>$reviews])
@endsection
