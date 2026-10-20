@extends('layouts.admin')
@section('title','Pedidos')
@section('content')
<div class="page-title">
    <div><span class="eyebrow">Gestión de pedidos</span><h1>Pedidos</h1></div>
    <form class="admin-search" action="/admin/pedidos"><input type="hidden" name="tab" value="{{ $tab }}"><input name="q" value="{{ request('q') }}" placeholder="Código, cliente, celular u operación"><button class="btn small">Buscar</button></form>
</div>

<div class="admin-tabs">
    @foreach($tabs as $key => $label)
        <a href="/admin/pedidos?tab={{ $key }}" class="{{ $tab===$key?'on':'' }} {{ $key==='yape' && $counts[$key] ? 'alert' : '' }}">{{ $label }} <em>{{ $counts[$key] }}</em></a>
    @endforeach
</div>

@if($tab==='yape')
    <div class="help-box">
        <strong>¿Cómo funciona?</strong> Abre tu app de Yape y compara los pagos recibidos con estos pedidos (monto y código en el mensaje).
        Marca los que <b>ya yapearon</b> y presiona «Confirmar Yape». Recién ahí se descuenta el stock y el pedido pasa a <b>Por preparar</b>.
        Los que tienen <span class="status-chip p-review">Comprobante por revisar</span> ya enviaron su captura.
    </div>
@endif

@if($orders->isEmpty())
    <div class="admin-card empty-state">@include('partials.icon',['name'=>'box','size'=>40])<p>No hay pedidos en esta sección.</p></div>
@else
    @if($tab==='yape')
        <form id="bulk-form" method="post" action="/admin/pedidos/confirmar-yape" class="bulk-bar" data-confirm="¿Confirmas que estos pedidos YA YAPEARON? Se descontará el stock.">@csrf
            <span data-selected-count>0 seleccionados</span>
            <button class="btn green small" type="submit">✓ Confirmar Yape de los seleccionados</button>
        </form>
    @endif
    @include('admin.order-table',['selectable'=>$tab==='yape'])
    @include('admin.pagination',['paginator'=>$orders])
@endif
@endsection
