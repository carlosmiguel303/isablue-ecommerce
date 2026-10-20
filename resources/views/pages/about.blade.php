@extends('layouts.store')
@section('title','Quiénes somos · IsaBlue')
@section('description','IsaBlue es una tienda en Lima de juguetes de estimulación, didácticos y accesorios para bebés y niños.')
@section('content')
<div class="wrap">
    <section class="page-hero">
        <span class="pill">@include('partials.icon',['name'=>'heart','size'=>16]) Jugar, aprender y crecer</span>
        <h1>Somos IsaBlue</h1>
        <p>Tu espacio para encontrar los mejores juguetes de estimulación, didácticos y accesorios para bebés y niños, aquí en Lima.</p>
    </section>

    <section class="section two-col">
        <div>
            <span class="eyebrow">Nuestra historia</span>
            <h2>Juguetes que acompañan cada etapa</h2>
            <p>En IsaBlue creemos que el juego es la forma más natural de aprender. Por eso elegimos juguetes que estimulan los sentidos, la motricidad y la imaginación de los más pequeños.</p>
            <p>Revisamos cada pedido antes de enviarlo y te acompañamos por WhatsApp para ayudarte a elegir el juguete ideal según la edad.</p>
            <a class="btn" href="/catalogo">Ver juguetes @include('partials.icon',['name'=>'arrow','size'=>18])</a>
        </div>
        <img src="/assets/images/reales/tienda-isablue.webp" alt="Estantes de la tienda IsaBlue" width="1280" height="449" loading="lazy">
    </section>

    <section class="section">
        <div class="section-head center"><span class="eyebrow">Lo que nos mueve</span><h2>Nuestros valores</h2></div>
        <div class="value-grid">
            <div class="value-card"><span class="qt-ico c-pink">@include('partials.icon',['name'=>'heart'])</span><h3>Con cariño</h3><p>Elegimos cada juguete pensando en tu peque.</p></div>
            <div class="value-card"><span class="qt-ico c-purple">@include('partials.icon',['name'=>'sparkle'])</span><h3>Aprender jugando</h3><p>Estimulación, motricidad y creatividad.</p></div>
            <div class="value-card"><span class="qt-ico c-teal">@include('partials.icon',['name'=>'shield'])</span><h3>Compra segura</h3><p>Confirmamos cada pago y pedido personalmente.</p></div>
            <div class="value-card"><span class="qt-ico c-orange">@include('partials.icon',['name'=>'chat'])</span><h3>Cerca de ti</h3><p>Te asesoramos por WhatsApp antes y después de tu compra.</p></div>
        </div>
    </section>
</div>
@endsection
