@extends('layouts.admin')
@section('title','Resumen')
@section('content')
@php
    $max = max(1, collect($chart)->max('total'));
    $cfg = \App\Models\Setting::pluck('value', 'key');
    $setup = array_filter([
        empty($cfg['yape_number']) ? ['Configura tu número de Yape', 'Los clientes lo verán al pagar.'] : null,
        empty($cfg['yape_qr']) ? ['Sube tu código QR de Yape', 'Hace el pago mucho más rápido.'] : null,
        empty($cfg['whatsapp']) ? ['Agrega tu WhatsApp', 'Para el botón flotante y la atención.'] : null,
    ]);
@endphp
<div class="page-title">
    <div><span class="eyebrow">Tu tienda hoy</span><h1>Resumen de ventas</h1></div>
    <a class="chip-toggle {{ $demo?'on':'' }}" href="?prueba={{ $demo?0:1 }}">{{ $demo ? '✓ Incluye pedidos de prueba' : 'Solo ventas reales' }}</a>
</div>

@if($awaitingCount)
<a class="yape-alert" href="/admin/pedidos?tab=yape">
    <span class="yape-badge big">yape</span>
    <div><strong>{{ $awaitingCount }} {{ $awaitingCount===1?'pedido está esperando':'pedidos están esperando' }} confirmación de Yape</strong>
        <span>{{ $reviewCount ? $reviewCount.' ya enviaron su comprobante. ' : '' }}Revisa tu app de Yape y confírmalos para descontar el stock.</span></div>
    <span class="btn yellow small">Revisar ahora →</span>
</a>
@endif

<div class="kpis">
    <div class="kpi k-pink"><span>Vendido hoy</span><strong>S/ {{ number_format($kpis['today']['total'],2) }}</strong><small>{{ $kpis['today']['orders'] }} pedidos · {{ $kpis['today']['units'] }} juguetes</small></div>
    <div class="kpi k-purple"><span>Esta semana</span><strong>S/ {{ number_format($kpis['week']['total'],2) }}</strong><small>{{ $kpis['week']['orders'] }} pedidos · {{ $kpis['week']['units'] }} juguetes</small></div>
    <div class="kpi k-teal"><span>Este mes</span><strong>S/ {{ number_format($kpis['month']['total'],2) }}</strong><small>{{ $kpis['month']['orders'] }} pedidos · {{ $kpis['month']['units'] }} juguetes</small></div>
    <a class="kpi k-orange" href="/admin/pedidos?tab=preparar"><span>Por preparar / enviar</span><strong>{{ $toPrepare }}</strong><small>Pagados, listos para despachar →</small></a>
</div>

@if($setup)
<div class="admin-card setup">
    <h2>Para empezar a vender</h2>
    @foreach($setup as [$t, $d])<a href="/admin/configuracion" class="setup-item">@include('partials.icon',['name'=>'settings','size'=>18])<span><strong>{{ $t }}</strong><small>{{ $d }}</small></span></a>@endforeach
</div>
@endif

<div class="admin-grid">
    <section class="admin-card span-2">
        <div class="card-head"><h2>Ventas de los últimos 14 días</h2><a href="/admin/reportes">Ver reportes →</a></div>
        <div class="bars" role="img" aria-label="Ventas diarias de los últimos 14 días">
            @foreach($chart as $day)
                <div class="bar-col" title="{{ $day['date'] }}: S/ {{ number_format($day['total'],2) }}">
                    <span class="bar-val">{{ $day['total'] > 0 ? number_format($day['total'],0) : '' }}</span>
                    <div class="bar" style="height: {{ max(2, $day['total'] / $max * 100) }}%"></div>
                    <small>{{ $day['label'] }}</small>
                </div>
            @endforeach
        </div>
    </section>

    <section class="admin-card">
        <div class="card-head"><h2>Más vendidos del mes</h2></div>
        @forelse($top as $i => $t)
            <div class="rank"><b>{{ $i+1 }}</b><span>{{ $t->name }}</span><strong>{{ (int)$t->units }} u.</strong></div>
        @empty
            <p class="muted">Aún no hay ventas confirmadas este mes.</p>
        @endforelse
    </section>

    <section class="admin-card">
        <div class="card-head"><h2>⚠️ Stock bajo</h2><a href="/admin/inventario?ver=bajo">Ver todo →</a></div>
        @forelse($lowStock as $p)
            <div class="rank"><span>{{ $p->name }}</span><strong class="{{ $p->stock==0?'txt-red':'txt-orange' }}">{{ $p->stock==0 ? 'Agotado' : $p->stock.' u.' }}</strong></div>
        @empty
            <p class="muted">Todo tu catálogo tiene buen stock. 👍</p>
        @endforelse
    </section>

    <section class="admin-card">
        <div class="card-head"><h2>Sin ventas en 30 días</h2><a href="/admin/inventario?ver=sin-ventas">Ver todo →</a></div>
        @forelse($noSales as $p)
            <div class="rank"><span>{{ $p->name }}</span><small class="muted">stock {{ $p->stock ?? '—' }}</small></div>
        @empty
            <p class="muted">Todos tus productos se vendieron este mes. 🎉</p>
        @endforelse
        @if($noSales->isNotEmpty())<p class="hint">Idea: ponlos en oferta o en el popup de la tienda.</p>@endif
    </section>

    <section class="admin-card span-2">
        <div class="card-head"><h2>Esperando Yape</h2><a href="/admin/pedidos?tab=yape">Ver todos →</a></div>
        @if($awaiting->isEmpty())
            <p class="muted">No hay pedidos pendientes de pago.</p>
        @else
            @include('admin.order-table',['orders'=>$awaiting,'selectable'=>false])
        @endif
    </section>
</div>
@endsection
