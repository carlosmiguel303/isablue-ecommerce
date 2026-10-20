@extends('layouts.admin')
@section('title','Inventario')
@section('content')
<div class="page-title">
    <div><span class="eyebrow">Stock y rotación</span><h1>Inventario</h1></div>
    <form class="admin-search" action="/admin/inventario"><input type="hidden" name="ver" value="{{ $filter }}"><input name="q" value="{{ request('q') }}" placeholder="Buscar juguete o código"><button class="btn small">Buscar</button></form>
</div>
<div class="admin-tabs">
    @foreach(['publicados'=>'En la tienda','bajo'=>'Stock bajo (≤'.$low.')','sin-ventas'=>'Sin ventas este mes','todos'=>'Todos'] as $k=>$label)
        <a href="/admin/inventario?ver={{ $k }}" class="{{ $filter===$k?'on':'' }}">{{ $label }}</a>
    @endforeach
    <a class="chip-toggle {{ $demo?'on':'' }}" href="?ver={{ $filter }}&prueba={{ $demo?0:1 }}">{{ $demo ? '✓ Incluye pruebas' : 'Solo reales' }}</a>
</div>

<div class="admin-card" style="padding:0">
<div class="table-wrap">
<table class="inv-table">
    <thead><tr><th>Juguete</th><th>Stock</th><th title="Pedidos esperando Yape (aún no descontados)">Apartado</th><th>Hoy</th><th>Semana</th><th>Mes</th><th>Total vendido</th><th>Última venta</th><th>Ajustar stock</th></tr></thead>
    <tbody>
    @forelse($products as $p)
        @php($s = $sales[$p->id] ?? null)
        <tr>
            <td><div class="prod-cell"><img src="{{ $p->image }}" alt="" width="44" height="44"><span><a href="/admin/productos/{{ $p->id }}/editar"><strong>{{ $p->name }}</strong></a><small class="muted">{{ $p->sku }} · S/ {{ number_format($p->price,2) }} @if(!($p->active && $p->catalog_visible))· oculto @endif</small></span></div></td>
            <td><span class="stock-pill {{ $p->stock===null ? 'none' : ($p->stock==0 ? 'out' : ($p->stock <= $low ? 'low' : 'ok')) }}">{{ $p->stock===null ? 'Sin conteo' : ($p->stock==0 ? 'Agotado' : $p->stock) }}</span></td>
            <td>{{ (int)($pending[$p->id] ?? 0) ?: '—' }}</td>
            <td>{{ (int)($s->today ?? 0) ?: '—' }}</td>
            <td>{{ (int)($s->week ?? 0) ?: '—' }}</td>
            <td><strong>{{ (int)($s->month ?? 0) ?: '—' }}</strong></td>
            <td>{{ (int)($s->total ?? 0) ?: '—' }}</td>
            <td>@if($s?->last_sale){{ \Carbon\Carbon::parse($s->last_sale)->diffForHumans() }}@else<span class="muted">Nunca</span>@endif</td>
            <td>
                <form class="stock-inline" method="post" action="/admin/productos/{{ $p->id }}/stock">@csrf
                    <input type="number" name="stock" min="0" value="{{ $p->stock }}" required aria-label="Nuevo stock de {{ $p->name }}">
                    <input type="hidden" name="reason" value="Ajuste desde inventario">
                    <button class="btn small ghost">Guardar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="9" class="muted" style="text-align:center;padding:30px">No hay productos en esta vista.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
@include('admin.pagination',['paginator'=>$products])

<h2 style="margin-top:30px">Últimos movimientos de stock</h2>
<div class="admin-card" style="padding:0"><div class="table-wrap"><table>
    <thead><tr><th>Fecha</th><th>Juguete</th><th>Antes</th><th>Cambio</th><th>Después</th><th>Motivo</th><th>Usuario</th></tr></thead>
    <tbody>
    @foreach($movements as $m)
        <tr><td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }}</td><td>{{ $m->name }}</td><td>{{ $m->before ?? '—' }}</td><td><strong class="{{ $m->change < 0 ? 'txt-red' : 'txt-green' }}">{{ $m->change > 0 ? '+' : '' }}{{ $m->change }}</strong></td><td>{{ $m->after }}</td><td>{{ $m->reason }}</td><td>{{ $m->user_name ?? 'Sistema' }}</td></tr>
    @endforeach
    </tbody>
</table></div></div>
@endsection
