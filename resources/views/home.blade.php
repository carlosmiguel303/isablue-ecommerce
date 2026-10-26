@extends('layouts.store')
@section('content')

{{-- Hero --}}
@if($banners->isNotEmpty())
<section class="hero-zone hero-wide" aria-label="Promociones de Isablue">
    <div class="hero" role="region" aria-roledescription="carrusel" aria-label="Promociones" data-slider tabindex="0">
        @foreach($banners as $banner)
            <article class="hero-slide theme-{{ $banner->theme }}" role="group" aria-roledescription="diapositiva" aria-label="{{ $loop->iteration }} de {{ $banners->count() }}: {{ $banner->title }}" @if(!$loop->first) hidden @endif>
                @if($banner->theme === 'image')
                    <a class="hero-image-link" href="{{ $banner->link }}">
                        <img src="{{ $banner->image }}" alt="{{ $banner->title }}" class="hero-banner-image" fetchpriority="{{ $loop->first?'high':'auto' }}" loading="{{ $loop->first?'eager':'lazy' }}">
                    </a>
                @else
                <div class="hero-copy">
                    @if($banner->eyebrow)<span class="pill">@include('partials.icon',['name'=>'sparkle','size'=>16]) {{ $banner->eyebrow }}</span>@endif
                    @if($loop->first)<h1>{{ $banner->title }}</h1>@else<h2 class="h1">{{ $banner->title }}</h2>@endif
                    @if($banner->subtitle)<p>{{ $banner->subtitle }}</p>@endif
                    <a class="btn yellow" href="{{ $banner->link }}">{{ $banner->button }} @include('partials.icon',['name'=>'arrow','size'=>18])</a>
                </div>
                <div class="hero-art">
                    <span class="blob b1"></span><span class="blob b2"></span>
                    <img src="{{ $banner->image ?: '/assets/images/reales/tienda-isablue.webp' }}" alt="{{ $banner->title }}" width="420" height="420" class="hero-photo" fetchpriority="{{ $loop->first?'high':'auto' }}">
                </div>
                @endif
            </article>
        @endforeach
        @if($banners->count() > 1)
            <div class="slider-controls">
                <button type="button" data-slide-prev aria-label="Promoción anterior">‹</button>
                <div class="slider-dots">@foreach($banners as $banner)<button type="button" data-slide="{{ $loop->index }}" aria-label="Ver promoción {{ $loop->iteration }}: {{ $banner->title }}" aria-pressed="{{ $loop->first?'true':'false' }}"></button>@endforeach</div>
                <button type="button" data-slide-next aria-label="Siguiente promoción">›</button>
                <button type="button" data-slide-pause aria-label="Pausar carrusel" aria-pressed="false">Pausar</button>
            </div>
        @endif
    </div>
</section>
@endif

{{-- Accesos rápidos --}}
<section class="wrap quick-tiles">
    <a href="/catalogo?sort=newest" class="qt"><span class="qt-ico c-purple">@include('partials.icon',['name'=>'sparkle'])</span><span><strong>Recién llegados</strong><small>Lo último en tienda</small></span></a>
    <a href="/catalogo?sale=1" class="qt"><span class="qt-ico c-pink">@include('partials.icon',['name'=>'tag'])</span><span><strong>Ofertas</strong><small>Precios que enamoran</small></span></a>
    <a href="/catalogo?age=0-2" class="qt"><span class="qt-ico c-teal">@include('partials.icon',['name'=>'baby'])</span><span><strong>Para bebés</strong><small>Estimulación 0–2 años</small></span></a>
    <a href="/como-comprar" class="qt"><span class="qt-ico c-orange">@include('partials.icon',['name'=>'phone'])</span><span><strong>Paga con Yape</strong><small>Así de fácil compras</small></span></a>
</section>

{{-- Ofertas relámpago --}}
@if($offers->isNotEmpty())
<section class="wrap section deals">
    <div class="flash-head">
        <div>
            <span class="eyebrow on-dark">@include('partials.icon',['name'=>'fire','size'=>16]) Ofertas relámpago</span>
            <h2>¡Aprovecha antes que se agoten!</h2>
        </div>
        @if($promoEndsAt)
            <div class="countdown light" data-countdown="{{ $promoEndsAt }}"><div><b data-d>00</b><small>días</small></div><div><b data-h>00</b><small>horas</small></div><div><b data-m>00</b><small>min</small></div><div><b data-s>00</b><small>seg</small></div></div>
        @endif
        <a class="btn yellow small" href="/catalogo?sale=1">Ver todas</a>
    </div>
    <div class="product-grid scroller">@foreach($offers as $p)@include('partials.product-card')@endforeach</div>
</section>
@endif

{{-- Por edad --}}
<section class="wrap section">
    <div class="section-head center">
        <span class="eyebrow">Cada etapa, un descubrimiento</span>
        <h2>Compra por edad</h2>
    </div>
    <div class="age-circles">
        @php($ageCards = ['0-3m'=>['mono-sensorial','Primeros sentidos'],'3-11m'=>['kit-sensorial-bebe','Estimulación temprana'],'1'=>['torre-rana','Primeros pasos'],'2'=>['bloques-madera-abc','Explorar y encajar'],'3'=>['pesca-dinosaurio','Imaginación y motricidad'],'4'=>['bloques-madera-abc','Crear y construir'],'5'=>['pesca-dinosaurio','Retos y aprendizaje']])
        @foreach(\App\Models\Product::AGE_STAGES as $age=>[$ageLabel])
        <a href="/catalogo?age={{ $age }}" class="age-circle a{{ $loop->index % 4 + 1 }}"><span><img src="/assets/images/reales/{{ $ageCards[$age][0] }}.webp" alt="" width="150" height="150" loading="lazy"></span><strong>{{ $ageLabel }}</strong><small>{{ $ageCards[$age][1] }}</small></a>
        @endforeach
        <a href="/catalogo" class="age-circle a4"><span><img src="/assets/images/reales/tienda-isablue.webp" alt="" width="150" height="150" loading="lazy"></span><strong>Todos</strong><small>Ver catálogo completo</small></a>
    </div>
</section>

{{-- Más vendidos / destacados --}}
@if($featured->isNotEmpty())
<section class="wrap section">
    <div class="section-head">
        <div><span class="eyebrow">Los favoritos de los peques</span><h2>Los más pedidos</h2></div>
        <a class="text-link" href="/catalogo">Ver todo @include('partials.icon',['name'=>'arrow','size'=>16])</a>
    </div>
    <div class="product-grid">@foreach($featured as $p)@include('partials.product-card')@endforeach</div>
</section>
@endif

{{-- Banner educativo --}}
<section class="wrap section edu-banner">
    <div class="edu-copy">
        <span class="eyebrow">Aprender con los sentidos</span>
        <h2>¿Por qué son importantes los libros sensoriales?</h2>
        <ul class="ticks">
            <li>@include('partials.icon',['name'=>'check','size'=>18]) Estimulan sus sentidos con texturas, colores y sonidos.</li>
            <li>@include('partials.icon',['name'=>'check','size'=>18]) Desarrollan la motricidad fina y la coordinación.</li>
            <li>@include('partials.icon',['name'=>'check','size'=>18]) Fomentan el lenguaje y la concentración.</li>
            <li>@include('partials.icon',['name'=>'check','size'=>18]) Crean momentos especiales en familia.</li>
        </ul>
        <a class="btn" href="/catalogo?q=sensorial">Ver juguetes sensoriales @include('partials.icon',['name'=>'arrow','size'=>18])</a>
    </div>
    <img src="/assets/images/libros-sensoriales.webp" alt="Infografía de Isablue sobre los beneficios de los libros sensoriales" width="560" height="560" loading="lazy">
</section>

{{-- Categorías --}}
<section class="section categories-section">
    <div class="wrap">
        <div class="section-head">
            <div><span class="eyebrow">Tantas formas de jugar</span><h2>Explora por categoría</h2></div>
            <a class="text-link" href="/catalogo">Ver todas @include('partials.icon',['name'=>'arrow','size'=>16])</a>
        </div>
        <div class="category-grid">
            @foreach($categories->take(8) as $category)
                <a class="category-card cc{{ $loop->index % 6 }}" href="/categoria/{{ $category->slug }}"><span><img src="{{ $category->image ?: '/assets/images/oso-logo.png' }}" alt="" width="84" height="84" loading="lazy"></span><h3>{{ $category->name }}</h3></a>
            @endforeach
        </div>
    </div>
</section>

{{-- Novedades --}}
@if($newProducts->isNotEmpty())
<section class="wrap section">
    <div class="section-head">
        <div><span class="eyebrow">Recién llegados</span><h2>Novedades en Isablue</h2></div>
        <a class="text-link" href="/catalogo?sort=newest">Ver novedades @include('partials.icon',['name'=>'arrow','size'=>16])</a>
    </div>
    <div class="product-grid">@foreach($newProducts as $p)@include('partials.product-card')@endforeach</div>
</section>
@endif

{{-- Cómo comprar --}}
<section class="wrap section steps-block">
    <div class="section-head center"><span class="eyebrow">Fácil, rápido y seguro</span><h2>Compra en 3 pasos</h2></div>
    <div class="steps">
        <div class="step"><span class="step-n c-pink">1</span><h3>Elige tus juguetes</h3><p>Agrégalos al carrito y completa tus datos de entrega en un solo paso.</p></div>
        <div class="step"><span class="step-n c-purple">2</span><h3>Yapea el total</h3><p>Te mostramos el QR y el número. Sube la captura de tu Yape y listo.</p></div>
        <div class="step"><span class="step-n c-teal">3</span><h3>Recibe en casa</h3><p>Validamos tu pago, preparamos tu pedido y te lo enviamos.</p></div>
    </div>
</section>

{{-- Tienda --}}
<section class="wrap section store-strip">
    <img src="/assets/images/reales/tienda-isablue.webp" alt="Estantes de la tienda Isablue llenos de juguetes" width="1280" height="449" loading="lazy">
    <div class="store-strip-copy">
        <span class="eyebrow on-dark">Somos Isablue</span>
        <h2>Juguetes reales, gente real</h2>
        <p>Cada juguete pasa por nuestras manos antes de llegar a las tuyas.</p>
        <a class="btn yellow small" href="/nosotros">Conócenos</a>
    </div>
</section>
@endsection
