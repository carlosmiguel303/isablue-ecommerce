<article class="product-card">
    <a class="product-image" href="{{ route('product',$p->slug) }}">
        <span class="badges">
            @if($p->on_sale)<span class="badge sale">-{{ $p->discount_percent }}%</span>@endif
            @if($p->is_new && $p->source==='admin')<span class="badge new">Nuevo</span>@endif
            @if($p->low_stock)<span class="badge hot">¡Últimas {{ $p->stock }}!</span>@endif
        </span>
        <img src="{{ $p->image }}" alt="{{ $p->name }}" width="320" height="320" loading="lazy">
        @if(!$p->available)<span class="soldout">{{ $p->stock===null?'Por confirmar':'Agotado' }}</span>@endif
    </a>
    <div class="product-copy">
        <span class="card-cat">{{ $p->categories->where('active',true)->first()?->name ?? 'IsaBlue' }}</span>
        <h3><a href="{{ route('product',$p->slug) }}">{{ $p->name }}</a></h3>
        @if($p->age_min_months !== null)<p class="age-chip">{{ $p->demo_data?'Edad de prueba: ':'' }}{{ $p->age_label }}</p>@endif
        <div class="price-row">
            <strong>S/ {{ number_format($p->price,2) }}</strong>
            @if($p->on_sale)<del>S/ {{ number_format($p->compare_price,2) }}</del>@endif
        </div>
        @if($p->available)
            <form method="post" action="/carrito" data-add-cart>@csrf<input type="hidden" name="product_id" value="{{ $p->id }}"><input type="hidden" name="quantity" value="1"><button class="btn small full">@include('partials.icon',['name'=>'cart','size'=>18]) Agregar al carrito</button></form>
        @else
            <a class="btn ghost small full" href="{{ route('product',$p->slug) }}">Ver detalles</a>
        @endif
    </div>
</article>
