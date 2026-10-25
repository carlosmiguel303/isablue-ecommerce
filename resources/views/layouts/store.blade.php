@php
    $s = $shopSettings;
    $whatsapp = $s['whatsapp'] ?? '';
    $cartCount = array_sum(session('cart', []));
    $shippingBar = $s['shipping_bar_text'] ?? '';
    $shippingBar = $shippingBar !== '' ? $shippingBar : 'Envíos a todo Lima y provincias · Paga fácil y seguro con Yape';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#E9407A">
    @if(request()->is('pedido/*','checkout','carrito','mi-cuenta','ingresar','registro','auth/*','olvide-mi-contrasena','restablecer-contrasena*'))<meta name="robots" content="noindex,nofollow">@endif
    <title>@yield('title','IsaBlue · Juguetes didácticos y de estimulación en Lima')</title>
    <meta name="description" content="@yield('description','Juguetes de estimulación, didácticos y accesorios para bebés y niños. Compra fácil, paga con Yape y recibe en Lima y provincias.')">
    <meta property="og:site_name" content="IsaBlue">
    <meta property="og:title" content="@yield('title','IsaBlue · Jugar, aprender y crecer')">
    <meta property="og:image" content="{{ url(trim($__env->yieldContent('og_image')) ?: '/assets/images/tienda-isablue.webp') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="/assets/images/oso-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/store.css') }}?v={{ filemtime(public_path('assets/store.css')) }}">
    <script src="{{ asset('assets/store.js') }}?v={{ filemtime(public_path('assets/store.js')) }}" defer></script>
    @stack('head')
</head>
<body>
<a class="skip" href="#main">Saltar al contenido</a>

@if($promoActive)
<div class="promo-bar">
    <div class="wrap promo-inner">
        <div class="promo-copy">
            <strong>@include('partials.icon',['name'=>'fire','size'=>18]) {{ $s['promo_text'] }}</strong>
            @if(!empty($s['promo_subtext']))<span>{{ $s['promo_subtext'] }}</span>@endif
        </div>
        @if($promoEndsAt)
        <div class="countdown" data-countdown="{{ $promoEndsAt }}" aria-label="Tiempo restante de la promoción">
            <div><b data-d>00</b><small>días</small></div><i>:</i>
            <div><b data-h>00</b><small>horas</small></div><i>:</i>
            <div><b data-m>00</b><small>min</small></div><i>:</i>
            <div><b data-s>00</b><small>seg</small></div>
        </div>
        @endif
        <a class="btn yellow small" href="{{ $s['promo_cta_link'] ?? '/catalogo?sale=1' }}">{{ ($s['promo_cta_text'] ?? '') ?: '¡Lo quiero!' }}</a>
    </div>
</div>
@endif
<div class="ship-bar"><div class="wrap">@include('partials.icon',['name'=>'truck','size'=>18]) {{ $shippingBar }}@if((float)($s['free_shipping_from'] ?? 0) > 0) · <strong>Envío gratis desde S/ {{ number_format($s['free_shipping_from'],0) }}</strong>@endif</div></div>

<header class="header" data-header>
    <div class="wrap header-main">
        <button class="icon-btn menu-btn" type="button" data-menu-toggle aria-label="Abrir menú" aria-expanded="false">@include('partials.icon',['name'=>'menu'])</button>
        <a class="brand" href="/" aria-label="IsaBlue, inicio">
            <img class="brand-logo" src="/assets/images/reales/logo-isablue.png" alt="IsaBlue · Juguetes y accesorios" width="387" height="172">
        </a>
        <form action="/catalogo" class="search" role="search">
            <label class="sr-only" for="search">Buscar juguetes</label>
            <input id="search" name="q" type="search" value="{{ request('q') }}" placeholder="Busca juguetes, sensoriales, rompecabezas…" maxlength="100" autocomplete="off" list="search-suggestions">
            <datalist id="search-suggestions"></datalist>
            <button aria-label="Buscar">@include('partials.icon',['name'=>'search','size'=>20])</button>
        </form>
        <div class="header-actions">
            @if($whatsapp)<a class="head-help" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">@include('partials.icon',['name'=>'whatsapp','size'=>22])<span><small>¿Ayuda?</small>Escríbenos</span></a>@endif
            @auth
                <a class="icon-btn" href="{{ auth()->user()->isAdmin()?'/admin':'/mi-cuenta' }}" aria-label="Mi cuenta">@include('partials.icon',['name'=>'user'])</a>
            @else
                <a class="icon-btn" href="/ingresar" aria-label="Ingresar">@include('partials.icon',['name'=>'user'])</a>
            @endauth
            <a class="icon-btn cart-btn" href="/carrito" data-cart-open aria-label="Ver carrito">@include('partials.icon',['name'=>'cart'])<span class="cart-count" data-cart-count @if(!$cartCount) hidden @endif>{{ $cartCount }}</span></a>
        </div>
    </div>
    <nav class="nav" aria-label="Navegación principal" data-menu>
        <div class="wrap nav-inner">
            <div class="nav-drop">
                <button type="button" class="nav-link" aria-expanded="false">Categorías @include('partials.icon',['name'=>'chevron','size'=>16])</button>
                <div class="drop-panel mega">
                    @foreach($navCategories as $c)
                        <a href="/categoria/{{ $c->slug }}"><img src="{{ $c->image ?: '/assets/images/oso-logo.png' }}" alt="" width="40" height="40" loading="lazy">{{ $c->name }}</a>
                    @endforeach
                    <a class="see-all" href="/catalogo">Ver todo el catálogo @include('partials.icon',['name'=>'arrow','size'=>16])</a>
                </div>
            </div>
            <div class="nav-drop">
                <button type="button" class="nav-link" aria-expanded="false">Por edad @include('partials.icon',['name'=>'chevron','size'=>16])</button>
                <div class="drop-panel">
                    <a href="/catalogo?age=0-2">👶 0 a 2 años</a>
                    <a href="/catalogo?age=3-4">🧸 3 a 4 años</a>
                    <a href="/catalogo?age=5">🚀 5 años a más</a>
                </div>
            </div>
            <div class="nav-drop">
                <button type="button" class="nav-link" aria-expanded="false">Por precio @include('partials.icon',['name'=>'chevron','size'=>16])</button>
                <div class="drop-panel">
                    <a href="/catalogo?max=50">Menos de S/ 50</a>
                    <a href="/catalogo?min=50&max=100">S/ 50 a S/ 100</a>
                    <a href="/catalogo?min=100">Más de S/ 100</a>
                </div>
            </div>
            <a class="nav-link hot" href="/catalogo?sale=1">@include('partials.icon',['name'=>'fire','size'=>16]) Ofertas</a>
            <a class="nav-link" href="/catalogo?sort=newest">Novedades</a>
            <a class="nav-link" href="/como-comprar">Cómo comprar</a>
            <a class="nav-link" href="/nosotros">Nosotros</a>
            <a class="nav-link" href="/contacto">Contacto</a>
        </div>
    </nav>
</header>

<main id="main">
    @if(($s['demo_mode'] ?? '0') === '1')<div class="demo-notice" role="status"><strong>Modo de prueba</strong> · Edades, stock y entregas simulados. No se realizan cobros reales.</div>@endif
    @if(session('status'))<div class="wrap flash ok" role="status">@include('partials.icon',['name'=>'check','size'=>18]) {{ session('status') }}</div>@endif
    @if($errors->any())<div class="wrap flash bad" role="alert"><strong>Revisa estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>

<section class="trust-strip">
    <div class="wrap trust-grid">
        <div>@include('partials.icon',['name'=>'phone','size'=>30])<strong>Paga con Yape</strong><span>Rápido, sin tarjetas</span></div>
        <div>@include('partials.icon',['name'=>'truck','size'=>30])<strong>Envíos a todo el Perú</strong><span>Lima y provincias</span></div>
        <div>@include('partials.icon',['name'=>'shield','size'=>30])<strong>Compra segura</strong><span>Confirmamos cada pedido</span></div>
        <div>@include('partials.icon',['name'=>'chat','size'=>30])<strong>Atención personalizada</strong><span>Te asesoramos por WhatsApp</span></div>
    </div>
</section>

<footer class="footer">
    <div class="wrap footer-grid">
        <div class="footer-brand">
            <a class="brand footer-logo" href="/"><img src="/assets/images/reales/logo-isablue.png" alt="IsaBlue · Juguetes y accesorios" width="387" height="172"></a>
            <p>Tu espacio para encontrar los mejores juguetes de estimulación, didácticos y accesorios para bebés y niños.</p>
            <div class="socials">
                @foreach(['facebook'=>'Facebook','instagram'=>'Instagram','tiktok'=>'TikTok'] as $key=>$label)
                    @if(!empty($s[$key]))<a href="{{ $s[$key] }}" rel="noopener" target="_blank">{{ $label }}</a>@endif
                @endforeach
            </div>
        </div>
        <div>
            <strong>Compra</strong>
            <a href="/catalogo">Todos los juguetes</a>
            <a href="/catalogo?sale=1">Ofertas</a>
            <a href="/catalogo?age=0-2">Para bebés (0–2)</a>
            <a href="/catalogo?sort=newest">Novedades</a>
        </div>
        <div>
            <strong>Ayuda</strong>
            <a href="/como-comprar">Cómo comprar con Yape</a>
            <a href="/mi-cuenta">Mis pedidos</a>
            <a href="/politicas/returns">Cambios y devoluciones</a>
            <a href="/politicas/privacy">Privacidad</a>
        </div>
        <div>
            <strong>IsaBlue</strong>
            <a href="/nosotros">Quiénes somos</a>
            <a href="/contacto">Contáctanos</a>
            @if(!empty($s['address']))<p>@include('partials.icon',['name'=>'pin','size'=>16]) {{ $s['address'] }}</p>@endif
            @if(!empty($s['phone']))<p>@include('partials.icon',['name'=>'phone','size'=>16]) {{ $s['phone'] }}</p>@endif
            @if(!empty($s['contact_email']))<a href="mailto:{{ $s['contact_email'] }}">{{ $s['contact_email'] }}</a>@endif
        </div>
    </div>
    <div class="wrap footer-bottom">
        <span>© {{ date('Y') }} IsaBlue · Lima, Perú · Precios en soles (S/)</span>
        <span class="pay-badges"><span class="yape-badge">yape</span><span>Pago seguro</span></span>
    </div>
</footer>

{{-- Mini carrito lateral --}}
<div class="drawer-backdrop" data-drawer-close hidden></div>
<aside class="drawer" id="cart-drawer" aria-label="Tu carrito" aria-hidden="true">
    <div class="drawer-head"><h2>Tu carrito</h2><button class="icon-btn" type="button" data-drawer-close aria-label="Cerrar carrito">@include('partials.icon',['name'=>'close'])</button></div>
    <div class="drawer-free" data-free hidden><span data-free-text></span><div class="bar"><i data-free-bar></i></div></div>
    <div class="drawer-body" data-drawer-lines><p class="muted">Cargando…</p></div>
    <div class="drawer-foot">
        <div class="drawer-total"><span>Subtotal</span><strong data-drawer-subtotal>S/ 0.00</strong></div>
        <a class="btn full" href="/checkout">Finalizar compra @include('partials.icon',['name'=>'arrow','size'=>18])</a>
        <a class="btn ghost full" href="/carrito">Ver carrito</a>
    </div>
</aside>

{{-- Popup de oferta --}}
@if($popupProduct && !request()->is('checkout','carrito','pedido/*','ingresar','registro','mi-cuenta','auth/*','olvide-mi-contrasena','restablecer-contrasena*'))
@php($pp = $popupProduct)
<div class="popup" data-popup data-delay="{{ (int)($s['popup_delay'] ?? 4) }}" hidden>
    <div class="popup-card" role="dialog" aria-modal="true" aria-labelledby="popup-title">
        <button class="popup-close" type="button" data-popup-close aria-label="Cerrar">@include('partials.icon',['name'=>'close'])</button>
        <p class="popup-kicker">{{ ($s['popup_title'] ?? '') ?: 'Oferta especial' }}</p>
        @if($promoEndsAt)
        <div class="countdown dark" data-countdown="{{ $promoEndsAt }}">
            <div><b data-d>00</b><small>días</small></div><div><b data-h>00</b><small>horas</small></div><div><b data-m>00</b><small>min</small></div><div><b data-s>00</b><small>seg</small></div>
        </div>
        @endif
        <h2 id="popup-title">{{ ($s['popup_headline'] ?? '') ?: '¡Hasta '.($pp->discount_percent ?: 30).'% de descuento!' }}</h2>
        <div class="popup-product">
            <img src="{{ $pp->image }}" alt="{{ $pp->name }}" width="200" height="200">
            <div>
                <h3>{{ $pp->name }}</h3>
                <p>{{ Str::limit($pp->description, 120) }}</p>
                <div class="price-row big"><strong>S/ {{ number_format($pp->price,2) }}</strong>@if($pp->on_sale)<del>S/ {{ number_format($pp->compare_price,2) }}</del>@endif</div>
                <form method="post" action="/carrito">@csrf<input type="hidden" name="product_id" value="{{ $pp->id }}"><input type="hidden" name="quantity" value="1"><input type="hidden" name="buy_now" value="1"><button class="btn yellow">Comprar ahora</button></form>
            </div>
        </div>
    </div>
</div>
@endif

@if($whatsapp)<a class="whatsapp-float" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode('Hola IsaBlue, quisiera información sobre un juguete') }}" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">@include('partials.icon',['name'=>'whatsapp','size'=>30,'stroke'=>1.8])</a>@endif
<div class="toast" id="toast" role="status" hidden></div>
</body>
</html>
