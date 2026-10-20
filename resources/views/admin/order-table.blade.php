@php($selectable = $selectable ?? false)
<div class="table-wrap">
<table class="orders-table">
    <thead><tr>
        @if($selectable)<th class="col-check"><input type="checkbox" data-check-all aria-label="Seleccionar todos"></th>@endif
        <th>Pedido</th><th>Cliente</th><th>Productos</th><th>Total</th><th>Pago</th><th>Estado</th><th></th>
    </tr></thead>
    <tbody>
    @foreach($orders as $o)
        <tr class="{{ $o->payment_status==='review' ? 'row-review' : '' }}">
            @if($selectable)<td class="col-check">@if($o->status==='pending' && $o->payment_status!=='paid' && $o->currency==='PEN')<input type="checkbox" name="ids[]" value="{{ $o->id }}" form="bulk-form" aria-label="Seleccionar {{ $o->reference }}">@endif</td>@endif
            <td><a class="ref" href="/admin/pedidos/{{ $o->id }}">{{ $o->reference }}</a>@if($o->is_demo)<small class="tag-demo">PRUEBA</small>@endif<small class="muted">{{ $o->created_at->format('d/m H:i') }} · {{ $o->created_at->diffForHumans() }}</small></td>
            <td><strong>{{ $o->customer_name }}</strong><small class="muted">{{ $o->phone }}</small></td>
            <td class="items-cell">@foreach($o->items->take(3) as $i){{ (float)$i->quantity }}× {{ Str::limit($i->name, 28) }}<br>@endforeach @if($o->items->count()>3)<small class="muted">+{{ $o->items->count()-3 }} más</small>@endif</td>
            <td><strong>{{ $o->currency==='PEN'?'S/':'' }} {{ number_format($o->total,2) }}</strong></td>
            <td><span class="status-chip p-{{ $o->payment_status }}">{{ __('store.payment.'.$o->payment_status) }}</span>@if($o->voucher_path)<a class="voucher-link" href="/admin/pedidos/{{ $o->id }}/comprobante" target="_blank">📎 Ver captura</a>@endif @if($o->payment_reference)<small class="muted">Op. {{ $o->payment_reference }}</small>@endif</td>
            <td><span class="status-chip {{ $o->status }}">{{ __('store.status.'.$o->status) }}</span></td>
            <td class="row-actions">
                @if($o->status==='pending' && $o->payment_status!=='paid' && $o->currency==='PEN')
                    <form method="post" action="/admin/pedidos/{{ $o->id }}/pago">@csrf<button class="btn small green" title="Confirma que el Yape llegó y descuenta el stock">✓ Yapeó</button></form>
                @elseif($o->status==='confirmed')
                    <form method="post" action="/admin/pedidos/{{ $o->id }}/estado">@csrf<input type="hidden" name="status" value="processing"><button class="btn small teal">Preparar</button></form>
                @elseif($o->status==='processing')
                    <form method="post" action="/admin/pedidos/{{ $o->id }}/estado">@csrf<input type="hidden" name="status" value="shipped"><button class="btn small purple">Enviado</button></form>
                @elseif($o->status==='shipped')
                    <form method="post" action="/admin/pedidos/{{ $o->id }}/estado">@csrf<input type="hidden" name="status" value="completed"><button class="btn small">Entregado</button></form>
                @endif
                <a class="btn small ghost" href="/admin/pedidos/{{ $o->id }}">Ver</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
