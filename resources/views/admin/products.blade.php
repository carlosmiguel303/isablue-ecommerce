@extends('layouts.admin') @section('title','Productos') @section('content')
<div class="page-title"><div><span class="eyebrow">Catálogo</span><h1>Productos</h1></div><a class="btn" href="/admin/productos/nuevo">+ Nuevo producto</a></div>
<div class="admin-tabs">
    <a href="/admin/productos" class="{{ request('ver')!=='publicados'?'on':'' }}">Todos</a>
    <a href="/admin/productos?ver=publicados" class="{{ request('ver')==='publicados'?'on':'' }}">Publicados en la tienda</a>
    <form class="admin-search" action="/admin/productos"><input type="hidden" name="ver" value="{{ request('ver') }}"><input name="q" placeholder="Buscar producto o código" value="{{ request('q') }}"><button class="btn small">Buscar</button></form>
</div>
<div class="admin-card" style="padding:0"><div class="table-wrap"><table>
<thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th></th></tr></thead>
<tbody>@foreach($products as $p)<tr>
<td><div class="prod-cell"><img src="{{ $p->image }}" alt="" width="44" height="44"><span><strong>{{ $p->name }}</strong><small class="muted">{{ $p->sku }} · {{ $p->categories->pluck('name')->take(2)->join(', ') }}</small></span></div></td>
<td><strong>S/ {{ number_format($p->price,2) }}</strong>@if($p->on_sale)<small class="txt-red">antes S/ {{ number_format($p->compare_price,2) }} (-{{ $p->discount_percent }}%)</small>@endif</td>
<td><span class="stock-pill {{ $p->stock===null ? 'none' : ($p->stock==0 ? 'out' : ($p->stock <= 5 ? 'low' : 'ok')) }}">{{ $p->stock ?? 'Sin conteo' }}</span></td>
<td>@if($p->active&&$p->catalog_visible)<span class="status-chip confirmed">Publicado</span>@else<span class="status-chip">Oculto</span>@endif @if($p->featured)<span class="status-chip processing">Destacado</span>@endif @if($p->demo_data)<small class="tag-demo">PRUEBA</small>@endif</td>
<td class="row-actions"><a class="btn small ghost" href="/admin/productos/{{ $p->id }}/editar">Editar</a>@if($p->active&&$p->catalog_visible)<a class="btn small ghost" href="/juguete/{{ $p->slug }}" target="_blank">Ver ↗</a>@endif</td>
</tr>@endforeach</tbody></table></div></div>
@include('admin.pagination',['paginator'=>$products])
@endsection
