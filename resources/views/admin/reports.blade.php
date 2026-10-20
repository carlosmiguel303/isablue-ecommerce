@extends('layouts.admin')
@section('title','Ventas y reportes')
@section('content')
@php($maxDay = max(1, (float) $byDay->max('total')))
<div class="page-title">
    <div><span class="eyebrow">Ventas confirmadas (Yape)</span><h1>Ventas y reportes</h1></div>
    <a class="btn small ghost" href="{{ request()->fullUrlWithQuery(['exportar'=>'csv']) }}">⬇ Descargar Excel (CSV)</a>
</div>
<div class="admin-tabs">
    @foreach(['hoy'=>'Hoy','ayer'=>'Ayer','semana'=>'Esta semana','mes'=>'Este mes','mes-pasado'=>'Mes pasado'] as $k=>$label)
        <a href="/admin/reportes?periodo={{ $k }}{{ request()->has('prueba') ? '&prueba='.(int)$demo : '' }}" class="{{ $preset===$k?'on':'' }}">{{ $label }}</a>
    @endforeach
    <form class="range-form" action="/admin/reportes"><input type="hidden" name="periodo" value="rango"><input type="date" name="desde" value="{{ $from->toDateString() }}" aria-label="Desde"><input type="date" name="hasta" value="{{ $to->toDateString() }}" aria-label="Hasta"><button class="btn small">Ver</button></form>
    <a class="chip-toggle {{ $demo?'on':'' }}" href="{{ request()->fullUrlWithQuery(['prueba'=>$demo?0:1]) }}">{{ $demo ? '✓ Incluye pruebas' : 'Solo reales' }}</a>
</div>
<p class="muted">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>

<div class="kpis">
    <div class="kpi k-pink"><span>Total vendido</span><strong>S/ {{ number_format($total,2) }}</strong></div>
    <div class="kpi k-purple"><span>Pedidos pagados</span><strong>{{ $count }}</strong></div>
    <div class="kpi k-teal"><span>Juguetes vendidos</span><strong>{{ $units }}</strong></div>
    <div class="kpi k-orange"><span>Ticket promedio</span><strong>S/ {{ number_format($average,2) }}</strong></div>
</div>

<div class="admin-grid">
    <section class="admin-card span-2">
        <h2>¿Qué se vendió?</h2>
        @if($byProduct->isEmpty())
            <p class="muted">No hay ventas confirmadas en este periodo.</p>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>#</th><th>Juguete</th><th>Unidades</th><th>Pedidos</th><th>Ingresos</th><th>% del total</th></tr></thead>
            <tbody>
            @foreach($byProduct as $row)
                <tr><td>{{ $loop->iteration }}</td><td><strong>{{ $row->name }}</strong><small class="muted">{{ $row->sku }}</small></td><td><strong>{{ (int)$row->units }}</strong></td><td>{{ $row->orders }}</td><td>S/ {{ number_format($row->total,2) }}</td>
                    <td><div class="meter"><i style="width: {{ $total>0 ? min(100, $row->total/$total*100) : 0 }}%"></i></div></td></tr>
            @endforeach
            </tbody>
        </table></div>
        @endif
    </section>
    <section class="admin-card">
        <h2>Por día</h2>
        @forelse($byDay as $d)
            <div class="day-row"><span>{{ \Carbon\Carbon::parse($d->d)->isoFormat('ddd D/MM') }}</span><div class="meter"><i style="width: {{ $d->total/$maxDay*100 }}%"></i></div><strong>S/ {{ number_format($d->total,0) }}</strong><small class="muted">{{ $d->orders }} ped.</small></div>
        @empty
            <p class="muted">Sin ventas.</p>
        @endforelse
    </section>
</div>
@endsection
