@extends('layouts.admin')
@section('title','Editar promoción')
@section('content')
<h1>{{ $banner->exists ? 'Editar promoción' : 'Nueva promoción' }}</h1>
<form class="admin-card form-grid" method="post" enctype="multipart/form-data" action="/admin/banners{{ $banner->exists?'/'.$banner->id:'' }}">
    @csrf @if($banner->exists)@method('PUT')@endif
    <label class="span-all">Título y descripción de la imagen<input name="title" value="{{ old('title',$banner->title) }}" required maxlength="180"><small>Describe la oferta. En «Imagen completa» lo leen los lectores de pantalla, sin superponerlo a tu diseño.</small></label>
    <label>Presentación<select name="theme">
        @foreach(['image'=>'Imagen completa a todo el ancho','main'=>'Imagen con texto: morado y rosa','baby'=>'Imagen con texto: rosa y naranja','montessori'=>'Imagen con texto: turquesa y azul','sunny'=>'Imagen con texto: naranja y amarillo','mint'=>'Imagen con texto: verde y turquesa'] as $v=>$label)
            <option value="{{ $v }}" @selected(old('theme',$banner->theme??'image')===$v)>{{ $label }}</option>
        @endforeach
    </select></label>
    <label>Orden (menor número primero)<input name="sort_order" type="number" min="0" max="1000" value="{{ old('sort_order',$banner->sort_order??0) }}" required></label>
    <label class="span-all">Imagen JPG, PNG o WebP · hasta 5 MB<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-banner-files><small>Para imagen completa: recomendado 1920 × 640 px. Se muestra sin recortes.</small></label>
    <div class="span-all banner-previews" data-banner-previews>
        @if($banner->image)<img src="{{ $banner->image }}" alt="Imagen actual: {{ $banner->title }}">@endif
    </div>
    <label class="span-all">Enlace dentro de la tienda<input name="link" value="{{ old('link',$banner->link??'/catalogo?sale=1') }}" required maxlength="255"><small>Por ejemplo: /catalogo?sale=1 o /catalogo. Se abre al tocar la imagen.</small></label>
    <label>Texto del botón<input name="button" value="{{ old('button',$banner->button??'Ver promoción') }}" required maxlength="60"></label>
    <label>Etiqueta superior<input name="eyebrow" value="{{ old('eyebrow',$banner->eyebrow) }}" maxlength="100"></label>
    <label class="span-all">Texto de apoyo<textarea name="subtitle" maxlength="500">{{ old('subtitle',$banner->subtitle) }}</textarea><small>El botón, la etiqueta y el texto de apoyo solo aparecen en las presentaciones «Imagen con texto».</small></label>
    <input type="hidden" name="active" value="0">
    <label class="check span-all"><input type="checkbox" name="active" value="1" @checked(old('active',$banner->active??true))> Activo en la tienda</label>
    <div class="span-all"><button class="btn">Guardar promoción</button> <a class="btn ghost" href="/admin/banners">Volver</a></div>
</form>
@endsection
