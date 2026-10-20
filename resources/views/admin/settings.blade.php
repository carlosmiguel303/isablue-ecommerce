@extends('layouts.admin')
@section('title','Configuración')
@section('content')
@php($v = fn ($k, $d = '') => old($k, $settings[$k] ?? $d))
<div class="page-title"><div><span class="eyebrow">Tu tienda</span><h1>Configuración</h1></div></div>

<section class="admin-card">
    <h2>Preparar la tienda para vender</h2>
    @if($launchIssues)
        <p>Completa estos datos antes de desactivar el modo de prueba:</p>
        <ul>@foreach($launchIssues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
        <p><a href="/admin/productos">Revisar productos</a> · <a href="/admin/inventario">Actualizar existencias</a> · <a href="#envios">Revisar entregas</a></p>
    @else
        <p>Hay productos con stock, entrega y datos de Yape configurados. Revisa su exactitud y desmarca «Modo de prueba» al final de este formulario para recibir ventas reales.</p>
    @endif
    @if(!$mailReady)
        <p class="notice">El envío de correos aún no está configurado. Revisa los pedidos desde el panel; el cliente puede consultarlos en «Mi cuenta». Configura el correo del servidor para recibir avisos por email.</p>
    @endif
    <p>Los banners se administran desde <a href="/admin/banners">Carrusel de promociones</a>.</p>
</section>

<div class="settings-nav">
    <a href="#yape">Yape</a><a href="#promo">Promociones y anuncios</a><a href="#contacto">Contacto</a><a href="#envios">Envíos</a><a href="#politicas">Políticas</a>
</div>

<form method="post" action="/admin/configuracion" enctype="multipart/form-data">
    @csrf
    <section class="admin-card" id="yape">
        <h2><span class="yape-badge">yape</span> Datos de pago</h2>
        <p class="muted">Se muestran al cliente en el checkout y en la página de su pedido.</p>
        <div class="form-grid">
            <label>Número de Yape<input name="yape_number" value="{{ $v('yape_number') }}" placeholder="999 999 999" inputmode="numeric"></label>
            <label>Titular (como aparece en Yape)<input name="yape_holder" value="{{ $v('yape_holder') }}" placeholder="Ej.: Isabel R."></label>
            <label class="span-all">Código QR de Yape (captura de tu QR)<input type="file" name="yape_qr" accept="image/jpeg,image/png,image/webp"></label>
            @if(!empty($settings['yape_qr']))<div class="span-all"><img src="{{ $settings['yape_qr'] }}" alt="QR actual" width="140" style="border-radius:12px;border:1px solid var(--line)"></div>@endif
        </div>
    </section>

    <section class="admin-card" id="promo">
        <h2>🔥 Promociones y anuncios</h2>
        <p class="muted">Así se arma la tienda tipo «campaña»: una barra superior con cuenta regresiva y un popup con un producto en oferta. Para que un producto salga en «Ofertas», pon un <b>precio anterior</b> mayor al precio en su ficha.</p>

        <h3>Barra superior con cuenta regresiva</h3>
        <div class="form-grid">
            <input type="hidden" name="promo_enabled_present" value="1">
            <label class="check span-all"><input type="checkbox" name="promo_enabled" value="1" @checked($v('promo_enabled')==='1')> Mostrar barra de promoción</label>
            <label>Texto principal<input name="promo_text" value="{{ $v('promo_text') }}" maxlength="160" placeholder="¡Últimos días del CYBER ISABLUE! Hasta 40% OFF"></label>
            <label>Texto secundario<input name="promo_subtext" value="{{ $v('promo_subtext') }}" maxlength="160" placeholder="Último gran descuento del año"></label>
            <label>Termina el (fecha y hora)<input type="datetime-local" name="promo_ends_at" value="{{ $v('promo_ends_at') ? \Carbon\Carbon::parse($v('promo_ends_at'))->format('Y-m-d\TH:i') : '' }}"></label>
            <label>Texto del botón<input name="promo_cta_text" value="{{ $v('promo_cta_text') }}" maxlength="40" placeholder="¡Lo quiero!"></label>
            <label class="span-all">Enlace del botón<input name="promo_cta_link" value="{{ $v('promo_cta_link','/catalogo?sale=1') }}" placeholder="/catalogo?sale=1"><small class="muted">Ejemplos: /catalogo?sale=1 · /categoria/peluches · /catalogo?age=0-2</small></label>
        </div>

        <h3>Barra de envíos</h3>
        <div class="form-grid">
            <label class="span-all">Texto<input name="shipping_bar_text" value="{{ $v('shipping_bar_text') }}" maxlength="200" placeholder="Envíos a todo Lima y provincias · Paga fácil y seguro con Yape"></label>
            <label>Envío gratis desde (S/) · 0 = desactivado<input type="number" name="free_shipping_from" min="0" step="1" value="{{ $v('free_shipping_from','0') }}"></label>
        </div>

        <h3>Popup de oferta</h3>
        <div class="form-grid">
            <input type="hidden" name="popup_enabled_present" value="1">
            <label class="check span-all"><input type="checkbox" name="popup_enabled" value="1" @checked($v('popup_enabled')==='1')> Mostrar popup al entrar (una vez por visita)</label>
            <label>Producto a destacar<select name="popup_product_id"><option value="">— Elegir —</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected($v('popup_product_id')==$p->id)>{{ $p->name }} · S/ {{ number_format($p->price,2) }}</option>@endforeach</select></label>
            <label>Aparece a los (segundos)<input type="number" name="popup_delay" min="0" max="120" value="{{ $v('popup_delay','4') }}"></label>
            <label>Título pequeño<input name="popup_title" value="{{ $v('popup_title') }}" maxlength="120" placeholder="ÚLTIMO GRAN DESCUENTO DEL AÑO"></label>
            <label>Título grande<input name="popup_headline" value="{{ $v('popup_headline') }}" maxlength="120" placeholder="¡Cyber IsaBlue! Hasta 40%"></label>
        </div>
        <p class="hint">El popup usa la misma cuenta regresiva de la barra superior.</p>
    </section>

    <section class="admin-card" id="contacto">
        <h2>Contacto y redes</h2>
        <div class="form-grid">
            <label>WhatsApp (con 51, solo dígitos)<input name="whatsapp" value="{{ $v('whatsapp') }}" placeholder="51999999999"></label>
            <label>Teléfono visible<input name="phone" value="{{ $v('phone') }}" placeholder="920 097 746"></label>
            <label>Correo de contacto<input type="email" name="contact_email" value="{{ $v('contact_email') }}"></label>
            <label>Horario de atención<input name="hours" value="{{ $v('hours') }}" placeholder="Lun a Sáb, 9 a. m. – 7 p. m."></label>
            <label class="span-all">Dirección<input name="address" value="{{ $v('address') }}" placeholder="Lima, Perú"></label>
            <label>Facebook<input type="url" name="facebook" value="{{ $v('facebook') }}"></label>
            <label>Instagram<input type="url" name="instagram" value="{{ $v('instagram') }}"></label>
            <label>TikTok<input type="url" name="tiktok" value="{{ $v('tiktok') }}"></label>
        </div>
    </section>

    <section class="admin-card" id="politicas">
        <h2>Textos y políticas</h2>
        <div class="form-grid">
            <label class="span-all">Información de entrega (se ve en cada producto)<textarea name="delivery_info">{{ $v('delivery_info') }}</textarea></label>
            <label>Política de privacidad<textarea name="privacy">{{ $v('privacy') }}</textarea></label>
            <label>Cambios y devoluciones<textarea name="returns">{{ $v('returns') }}</textarea></label>
            <label class="check span-all"><input type="checkbox" name="demo_mode" value="1" @checked(($settings['demo_mode']??'0')==='1')> Modo de prueba (muestra el aviso en la tienda e incluye ventas de prueba en los reportes)</label>
        </div>
    </section>
    <div class="save-bar"><button class="btn big">Guardar configuración</button></div>
</form>

<section class="admin-card" id="envios">
    <h2>Opciones de envío</h2>
    <p class="muted">El cliente elige una opción y ve el total antes de confirmar.</p>
    @foreach($shipping as $method)
        <form class="ship-row" method="post" action="/admin/envios">@csrf<input type="hidden" name="id" value="{{ $method->id }}">
            <label>Nombre<input name="name" value="{{ $method->name }}" required></label>
            <label>Tarifa S/<input type="number" name="price" min="0" step="0.01" value="{{ $method->price }}" required></label>
            <label class="grow">Cobertura<input name="description" value="{{ $method->description }}"></label>
            <label class="check"><input type="checkbox" name="active" value="1" @checked($method->active)> Activa</label>
            <label class="check"><input type="checkbox" name="demo_data" value="1" @checked($method->demo_data)> Prueba</label>
            <button class="btn small">Guardar</button>
        </form>
    @endforeach
    <form class="ship-row new" method="post" action="/admin/envios">@csrf
        <label>Nueva opción<input name="name" required placeholder="Delivery Lima Metropolitana"></label>
        <label>Tarifa S/<input type="number" name="price" min="0" step="0.01" required></label>
        <label class="grow">Cobertura<input name="description" placeholder="Entrega en 24–48 h"></label>
        <label class="check"><input type="checkbox" name="active" value="1" checked> Activa</label>
        <button class="btn small purple">Crear</button>
    </form>
</section>
@endsection
