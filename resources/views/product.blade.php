@extends('layouts.store')
@section('title',$product->name.' · IsaBlue')
@section('description',Str::limit($product->description,155))
@section('og_image',$product->image)
@section('content')
@php($wa = \App\Models\Setting::valueOf('whatsapp'))
<div class="wrap section">
    <div class="breadcrumb"><a href="/">Inicio</a> / <a href="/catalogo">Juguetes</a> / {{ $product->name }}</div>
    <div class="product-detail">
        <div class="gallery">
            <div class="detail-image">
                <span class="badges">
                    @if($product->on_sale)<span class="badge sale">-{{ $product->discount_percent }}%</span>@endif
                    @if($product->low_stock)<span class="badge hot">¡Últimas unidades!</span>@endif
                </span>
                <img id="detail-photo" src="{{ $product->image }}" alt="{{ $product->name }}" width="600" height="600">
            </div>
            @if($product->images->count() > 1)
            <div class="thumbnails">@foreach($product->images as $image)<button type="button" data-photo="{{ $image->path }}" aria-current="{{ $loop->first?'true':'false' }}" aria-label="Ver fotografía {{ $loop->iteration }}"><img src="{{ $image->path }}" alt="" width="80" height="80"></button>@endforeach</div>
            @endif
        </div>

        <div class="detail-copy">
            @if($product->demo_data)<div class="notice">Ficha con edades y existencias simuladas para pruebas.</div>@endif
            <span class="detail-cats">{{ $product->categories->where('active',true)->pluck('name')->join(' · ') }}</span>
            <h1>{{ $product->name }}</h1>
            <div class="price-row big">
                <strong>S/ {{ number_format($product->price,2) }}</strong>
                @if($product->on_sale)<del>S/ {{ number_format($product->compare_price,2) }}</del><span class="save-tag">Ahorras S/ {{ number_format($product->compare_price-$product->price,2) }}</span>@endif
            </div>
            @if($product->available)
                <p class="stock-line {{ $product->low_stock?'low':'' }}"><span class="dot"></span>{{ $product->low_stock ? '¡Solo quedan '.$product->stock.' unidades!' : 'Disponible · listo para enviar' }}</p>
            @else
                <p class="stock-line out"><span class="dot"></span>{{ $product->stock===null ? 'Disponibilidad por confirmar' : 'Agotado por ahora' }}</p>
            @endif

            @if($product->available)
            <form class="buy-box" method="post" action="/carrito" data-add-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="buy-row">
                    <div class="qty" data-qty>
                        <button type="button" data-step="-1" aria-label="Quitar uno">@include('partials.icon',['name'=>'minus','size'=>18])</button>
                        <input aria-label="Cantidad" type="number" name="quantity" value="1" min="1" max="{{ min(99,$product->stock) }}">
                        <button type="button" data-step="1" aria-label="Agregar uno">@include('partials.icon',['name'=>'plus','size'=>18])</button>
                    </div>
                    <button class="btn purple" type="submit">@include('partials.icon',['name'=>'cart','size'=>20]) Agregar al carrito</button>
                </div>
                <button class="btn big full" type="submit" name="buy_now" value="1">Comprar ahora @include('partials.icon',['name'=>'arrow','size'=>20])</button>
                <ul class="perks">
                    <li>@include('partials.icon',['name'=>'phone','size'=>20]) Paga fácil con <strong>Yape</strong></li>
                    <li>@include('partials.icon',['name'=>'truck','size'=>20]) Envíos a Lima y provincias</li>
                    <li>@include('partials.icon',['name'=>'shield','size'=>20]) Confirmamos tu pedido personalmente</li>
                </ul>
            </form>
            @elseif($wa)
                <a class="btn green full" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hola, ¿cuándo vuelve a estar disponible '.$product->name.'?') }}" target="_blank" rel="noopener">@include('partials.icon',['name'=>'whatsapp','size'=>20]) Avísame cuando llegue</a>
            @endif

            <div class="detail-facts">
                <div><span>Edad recomendada</span><strong>{{ $product->age_label }}</strong></div>
                <div><span>Código</span><strong>{{ $product->sku }}</strong></div>
                @if($product->material)<div><span>Material</span><strong>{{ $product->material }}</strong></div>@endif
                @if($product->brand)<div><span>Marca</span><strong>{{ $product->brand }}</strong></div>@endif
            </div>

            <details class="info" open><summary>Descripción</summary><p style="white-space:pre-line">{{ $product->description }}</p></details>
            @if($product->safety)<details class="info"><summary>Información de seguridad</summary><p>{{ $product->safety }}</p></details>@endif
            <details class="info"><summary>Envío y entrega</summary><p>{{ \App\Models\Setting::valueOf('delivery_info') ?: 'Coordinamos la entrega contigo apenas confirmemos tu Yape.' }}</p></details>
            <details class="info"><summary>¿Cómo pago?</summary><p>Al finalizar tu pedido verás nuestro QR y número de Yape. Yapea el total, sube la captura y nosotros validamos tu pago para preparar tu envío.</p></details>
            @if($wa)<a class="btn green" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hola, tengo una consulta sobre '.$product->name) }}" target="_blank" rel="noopener">@include('partials.icon',['name'=>'whatsapp','size'=>20]) Consultar por WhatsApp</a>@endif
        </div>
    </div>

    @if($related->count())
    <section class="section">
        <div class="section-head"><div><span class="eyebrow">Combínalo con</span><h2>También te puede gustar</h2></div></div>
        <div class="product-grid">@foreach($related as $p)@include('partials.product-card')@endforeach</div>
    </section>
    @endif
</div>

@if($product->available)
<div class="sticky-buy">
    <strong>S/ {{ number_format($product->price,2) }}</strong>
    <form method="post" action="/carrito" style="flex:1;display:flex">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="quantity" value="1"><input type="hidden" name="buy_now" value="1"><button class="btn full">Comprar ahora</button></form>
</div>
@endif
@endsection
@push('head')<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'Product','name'=>$product->name,'description'=>$product->description,'sku'=>$product->sku,'image'=>url($product->image),'offers'=>['@type'=>'Offer','price'=>$product->price,'priceCurrency'=>'PEN','url'=>url('/juguete/'.$product->slug)]+($product->stock!==null?['availability'=>$product->stock>0?'https://schema.org/InStock':'https://schema.org/OutOfStock']:[])],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>@endpush
