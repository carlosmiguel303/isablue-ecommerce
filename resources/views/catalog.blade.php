@extends('layouts.store')
@section('title','Catálogo de juguetes · Isablue')
@section('content')
@php
    $sorts = ['newest'=>'Novedades','price_asc'=>'Menor precio','price_desc'=>'Mayor precio','name'=>'Nombre A–Z'];
    $title = !empty($filters['sale']) ? 'Ofertas Isablue' : (!empty($filters['q']) ? 'Resultados para “'.$filters['q'].'”' : 'Todos los juguetes');
@endphp
<div class="wrap section">
    <div class="breadcrumb"><a href="/">Inicio</a> / Catálogo</div>
    <div class="catalog-banner">
        <div><h1>{{ $title }}</h1><p>{{ $products->total() }} {{ $products->total()===1?'juguete encontrado':'juguetes encontrados' }} · Paga fácil con Yape</p></div>
        <a class="btn yellow small" href="/catalogo?sale=1">@include('partials.icon',['name'=>'fire','size'=>16]) Ver ofertas</a>
    </div>

    <div class="chip-row">
        <a href="/catalogo" class="{{ empty($filters['category']) && empty($filters['age']) && empty($filters['sale']) ? 'on':'' }}">Todos</a>
        @foreach(\App\Models\Product::AGE_STAGES as $v=>[$label])<a href="/catalogo?age={{ $v }}" class="{{ (string)($filters['age']??'')===(string)$v?'on':'' }}">{{ $label }}</a>@endforeach
        @foreach($categories->take(8) as $c)<a href="/categoria/{{ $c->slug }}" class="{{ in_array($c->id,$filters['category']??[])?'on':'' }}">{{ $c->name }}</a>@endforeach
    </div>

    <div class="catalog-layout">
        <aside>
            <details class="filters" open>
                <summary>Filtrar @include('partials.icon',['name'=>'chevron','size'=>18])</summary>
                <form action="/catalogo" method="get">
                    <label>Buscar<input name="q" value="{{ $filters['q']??'' }}" placeholder="Nombre o código"></label>
                    <fieldset><legend>Edad</legend>
                        <label><input type="radio" name="age" value="" @checked(empty($filters['age']))> Todas las edades</label>
                        @foreach(\App\Models\Product::AGE_STAGES as $value=>[$label])<label><input type="radio" name="age" value="{{ $value }}" @checked((string)($filters['age']??'')===(string)$value)> {{ $label }}</label>@endforeach
                    </fieldset>
                    <fieldset><legend>Categorías</legend>
                        @foreach($categories as $c)<label><input type="checkbox" name="category[]" value="{{ $c->id }}" @checked(in_array($c->id,$filters['category']??[]))> {{ $c->name }}</label>@endforeach
                    </fieldset>
                    <fieldset><legend>Precio (S/)</legend>
                        <div class="price-inputs"><label class="sr-only" for="min">Desde</label><input id="min" name="min" type="number" min="0" step="0.01" value="{{ $filters['min']??'' }}" placeholder="Desde"><label class="sr-only" for="max">Hasta</label><input id="max" name="max" type="number" min="0" step="0.01" value="{{ $filters['max']??'' }}" placeholder="Hasta"></div>
                    </fieldset>
                    <label>Disponibilidad<select name="availability"><option value="">Todas</option><option value="available" @selected(($filters['availability']??'')==='available')>En stock</option><option value="out" @selected(($filters['availability']??'')==='out')>Agotados</option></select></label>
                    <label><input type="checkbox" name="sale" value="1" @checked(!empty($filters['sale']))> Solo ofertas</label>
                    <input type="hidden" name="sort" value="{{ $filters['sort']??'newest' }}">
                    <button class="btn full">Aplicar filtros</button>
                    <a class="clear" href="/catalogo">Limpiar filtros</a>
                </form>
            </details>
        </aside>
        <div>
            <div class="toolbar">
                <div class="active-filters">
                    @if(!empty($filters['age']))<span>Edad: {{ \App\Models\Product::AGE_STAGES[$filters['age']][0] ?? ['0-2'=>'0 a 2 años','3-4'=>'3 a 4 años'][$filters['age']] ?? $filters['age'] }}</span>@endif
                    @foreach($categories->whereIn('id',$filters['category']??[]) as $c)<span>{{ $c->name }}</span>@endforeach
                    @if(isset($filters['min'])||isset($filters['max']))<span>S/ {{ $filters['min']??0 }} – {{ $filters['max']??'sin límite' }}</span>@endif
                    @if(!empty($filters['availability']))<span>{{ $filters['availability']==='available'?'En stock':'Agotados' }}</span>@endif
                    @if(!empty($filters['sale']))<span>En oferta</span>@endif
                </div>
                <form method="get" action="/catalogo" class="inline-form">
                    @foreach(request()->except(['sort','page']) as $k=>$v)
                        @if(is_array($v))@foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach @else<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <label for="sort" class="muted">Ordenar:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">@foreach($sorts as $v=>$label)<option value="{{ $v }}" @selected(($filters['sort']??'newest')===$v)>{{ $label }}</option>@endforeach</select>
                </form>
            </div>
            @if($products->count())
                <div class="product-grid catalog-grid">@foreach($products as $p)@include('partials.product-card')@endforeach</div>
            @else
                <div class="empty"><span>@include('partials.icon',['name'=>'search','size'=>48])</span><h2>Aún no encontramos ese juguete</h2><p>Prueba otra combinación o escríbenos: lo buscamos por ti.</p><a class="btn" href="/catalogo">Ver todos los juguetes</a></div>
            @endif
            @if($products->lastPage() > 1)
            <div class="pagination">
                @if($products->previousPageUrl())<a class="btn ghost small" href="{{ $products->previousPageUrl() }}">← Anterior</a>@endif
                <span>Página {{ $products->currentPage() }} de {{ $products->lastPage() }}</span>
                @if($products->nextPageUrl())<a class="btn ghost small" href="{{ $products->nextPageUrl() }}">Siguiente →</a>@endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
