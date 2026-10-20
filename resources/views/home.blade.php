@extends('layouts.store')
@section('content')

{{-- Hero --}}
<section class="wrap hero-zone">
    <div class="hero" aria-label="Promociones de IsaBlue" data-slider>
        @foreach($banners as $banner)
            <article class="hero-slide theme-{{ $banner->theme }}" @if(!$loop->first) hidden @endif>
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
            </article>
        @endforeach
        @if($banners->count() > 1)
            <div class="slider-dots">@foreach($banners as $banner)<button type="button" data-slide="{{ $loop->index }}" aria-label="Ver promoción {{ $loop->iteration }}" aria-pressed="{{ $loop->first?'true':'false' }}"></button>@endforeach</div>
        @endif
    </div>
    <div class="hero-side">
        <a class="side-card sc-pink" href="/catalogo?sale=1">
            <span class="side-tag">Hasta {{ $maxDiscount ?: 30 }}% OFF</span>
            <strong>Ofertas que<br>vuelan</strong>
            <span class="side-link">Ver ofertas @include('partials.icon',['name'=>'arrow','size'=>16])</span>
            <img src="/assets/images/reales/bloques-madera-abc.webp" alt="" width="130" height="130">
        </a>
        <a class="side-card sc-teal" href="/catalogo?q=sensorial">
            <span class="side-tag">Estimulación</span>
            <strong>Libros y juguetes<br>sensoriales</strong>
            <span class="side-link">Descubrir @include('partials.icon',['name'=>'arrow','size'=>16])</span>
            <img src="/assets/images/reales/mono-sensorial.webp" alt="" width="130" height="130">
        </a>
    </div>
</section>

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
        <a href="/catalogo?age=0-2" class="age-circle a1"><span><img src="/assets/images/reales/kit-sensorial-bebe.webp" alt="" width="150" height="150" loading="lazy"></span><strong>0 a 2 años</strong><small>Estimulación temprana</small></a>
        <a href="/catalogo?age=3-4" class="age-circle a2"><span><img src="/assets/images/reales/torre-rana.webp" alt="" width="150" height="150" loading="lazy"></span><strong>3 a 4 años</strong><small>Imaginación y motricidad</small></a>
        <a href="/catalogo?age=5" class="age-circle a3"><span><img src="/assets/images/reales/pesca-dinosaurio.webp" alt="" width="150" height="150" loading="lazy"></span><strong>5 años a más</strong><small>Retos y aprendizaje</small></a>
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
    <img src="/assets/images/libros-sensoriales.webp" alt="Infografía de IsaBlue sobre los beneficios de los libros sensoriales" width="560" height="560" loading="lazy">
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
        <div><span class="eyebrow">Recién llegados</span><h2>Novedades en IsaBlue</h2></div>
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
    <img src="/assets/images/reales/tienda-isablue.webp" alt="Estantes de la tienda IsaBlue llenos de juguetes" width="1280" height="449" loading="lazy">
    <div class="store-strip-copy">
        <span class="eyebrow on-dark">Somos IsaBlue</span>
        <h2>Juguetes reales, gente real</h2>
        <p>Cada juguete pasa por nuestras manos antes de llegar a las tuyas.</p>
        <a class="btn yellow small" href="/nosotros">Conócenos</a>
    </div>
</section>
@endsection
