@extends('layouts.store')
@section('title','Mi reseña de '.$product->name.' · Isablue')
@section('content')
<div class="wrap section narrow">
    <a class="back" href="/juguete/{{ $product->slug }}">← Volver al juguete</a>
    <div class="section-head"><div><span class="eyebrow">Pedido {{ $order->reference }}</span><h1>¿Qué te pareció {{ $product->name }}?</h1></div></div>
    <form class="panel form-grid" method="post" enctype="multipart/form-data" action="/juguete/{{ $product->slug }}/resena" data-once>@csrf
        @if($errors->any())<div class="notice error span-all">{{ $errors->first() }}</div>@endif
        <fieldset class="span-all" style="border:0;padding:0;margin:0">
            <legend><strong>Tu calificación</strong></legend>
            <div class="star-input">
                @for($n = 5; $n >= 1; $n--)<input type="radio" id="rating-{{ $n }}" name="rating" value="{{ $n }}" @checked((int) old('rating') === $n) required><label for="rating-{{ $n }}" title="{{ $n }} {{ $n === 1 ? 'estrella' : 'estrellas' }}">★</label>@endfor
            </div>
        </fieldset>
        <label class="span-all">Tu opinión<textarea name="comment" rows="6" minlength="10" maxlength="1000" required placeholder="¿Cómo llegó tu pedido? ¿Le gustó a tu peque? ¿Cómo lo usa?">{{ old('comment') }}</textarea></label>
        <label>Distrito o ciudad (opcional)<input name="district" value="{{ old('district') }}" maxlength="60" placeholder="Ej.: Los Olivos"></label>
        <label>Foto (opcional)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><small class="muted">Del juguete o de tu peque jugando. JPG, PNG o WebP hasta 5 MB.</small></label>
        <p class="span-all muted">Se publicará como <strong>{{ \App\Models\Review::publicName(auth()->user()->name) }}</strong>{{ old('district') ? ' · '.old('district') : '' }}. Nunca mostramos tu correo ni tu teléfono.</p>
        <label class="check span-all"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))> Autorizo a Isablue a publicar mi reseña y mi foto en su tienda y blog.</label>
        <div class="span-all"><button class="btn">Enviar mi reseña</button> <small class="muted">La revisamos antes de publicarla.</small></div>
    </form>
</div>
@endsection
