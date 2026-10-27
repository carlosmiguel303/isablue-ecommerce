@php
    $path = request()->getPathInfo();
    $menu = [
        ['/admin', 'Resumen', 'home'],
        ['/admin/pedidos', 'Pedidos', 'box'],
        ['/admin/inventario', 'Inventario', 'chart'],
        ['/admin/reportes', 'Ventas y reportes', 'tag'],
        ['/admin/productos', 'Productos', 'gift'],
        ['/admin/categorias', 'Categorías', 'menu'],
        ['/admin/banners', 'Banners', 'image'],
        ['/admin/blog', 'Blog', 'menu'],
        ['/admin/resenas', 'Reseñas de clientes', 'star'],
        ['/admin/configuracion', 'Configuración', 'settings'],
        ['/admin/integraciones', 'Google, correos y respaldo', 'shield'],
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title','Panel') · Isablue Admin</title>
    <link rel="icon" href="/assets/images/oso-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="/assets/store.css?v={{ filemtime(public_path('assets/store.css')) }}">
    <link rel="stylesheet" href="/assets/admin.css?v={{ filemtime(public_path('assets/admin.css')) }}">
    <script src="/assets/admin.js?v={{ filemtime(public_path('assets/admin.js')) }}" defer></script>
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" data-sidebar>
        <a class="brand" href="/admin"><img src="/assets/images/oso-logo.png" alt="" width="42" height="42"><span class="brand-word light"><span class="l1">I</span><span class="l2">S</span><span class="l3">A</span><span class="l4">B</span><span class="l5">L</span><span class="l6">U</span><span class="l7">E</span><small>Panel de la tienda</small></span></a>
        <nav>
            @foreach($menu as [$url, $name, $icon])
                @php($active = $url === '/admin' ? $path === '/admin' : str_starts_with($path, $url))
                <a class="nav-item {{ $active ? 'active' : '' }}" href="{{ $url }}">@include('partials.icon',['name'=>$icon,'size'=>20]) <span>{{ $name }}</span>@if($url==='/admin/pedidos' && $awaitingYape)<em class="nav-badge" title="Pedidos esperando Yape">{{ $awaitingYape }}</em>@endif @if($url==='/admin/resenas' && ($pendingReviews ?? 0))<em class="nav-badge" title="Reseñas por revisar">{{ $pendingReviews }}</em>@endif</a>
            @endforeach
        </nav>
        <div class="sidebar-foot">
            <a class="nav-item" href="/" target="_blank">@include('partials.icon',['name'=>'external','size'=>20]) <span>Ver tienda</span></a>
            <form method="post" action="/salir">@csrf<button class="nav-item">@include('partials.icon',['name'=>'logout','size'=>20]) <span>Cerrar sesión</span></button></form>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-top">
            <button class="icon-btn admin-menu" type="button" data-sidebar-toggle aria-label="Menú">@include('partials.icon',['name'=>'menu'])</button>
            <span>Hola, <strong>{{ auth()->user()->name }}</strong> · {{ now()->isoFormat('dddd D [de] MMMM') }}</span>
            @if($awaitingYape)<a class="top-alert" href="/admin/pedidos?tab=yape">@include('partials.icon',['name'=>'clock','size'=>18]) {{ $awaitingYape }} {{ $awaitingYape===1?'pedido espera':'pedidos esperan' }} Yape</a>@endif
        </div>
        @if(($shopSettings['demo_mode'] ?? '0') === '1')<div class="admin-demo">Modo de prueba activo: las ventas de prueba se incluyen en los números. Desactívalo en Configuración cuando vendas de verdad.</div>@endif
        @if(session('status'))<div class="flash ok" role="status">@include('partials.icon',['name'=>'check','size'=>18]) {{ session('status') }}</div>@endif
        @if($errors->any())<div class="flash bad" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
