@extends('layouts.admin')
@section('title','Carrusel de promociones')
@section('content')
<div class="page-title"><div><span class="eyebrow">Portada de la tienda</span><h1>Carrusel de promociones</h1></div><a class="btn" href="/admin/banners/nuevo">+ Crear banner</a></div>
<section class="admin-card">
    <h2>Sube tus promociones</h2>
    <p>Elige hasta 5 imágenes por carga. Cada imagen ocupará todo el ancho del banner y se mostrará completa. Recomendamos imágenes horizontales de <strong>1920 × 640 píxeles</strong>, con textos grandes para leerlos en el celular. Usa la misma proporción en todas.</p>
    <form class="form-grid" method="post" enctype="multipart/form-data" action="/admin/banners/imagenes">
        @csrf
        <label>Nombre de la campaña<input name="title" required maxlength="160" value="{{ old('title') }}" placeholder="Promociones de octubre"></label>
        <label>Destino al tocar las imágenes<input name="link" required maxlength="255" value="{{ old('link','/catalogo?sale=1') }}"><small>/catalogo · /catalogo?sale=1 · /categoria/rompecabezas</small></label>
        <label class="span-all">Imágenes JPG, PNG o WebP · hasta 5 MB cada una<input name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required data-banner-files></label>
        <div class="span-all banner-previews" data-banner-previews></div>
        <div class="span-all"><button class="btn">Añadir al carrusel</button></div>
    </form>
</section>
<section class="admin-card">
    <h2>Imágenes y banners publicados</h2>
    <p>Se muestran de menor a mayor orden. En «Editar» puedes cambiar la imagen, el destino o desmarcar «Activo» para ocultar una promoción.</p>
    <div class="table-wrap"><table><thead><tr><th>Orden</th><th>Imagen</th><th>Promoción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    @forelse($banners as $b)
        <tr>
            <td>{{ $b->sort_order }}</td>
            <td>@if($b->image)<img class="banner-thumb" src="{{ $b->image }}" alt="{{ $b->title }}" width="160" height="60">@endif</td>
            <td><strong>{{ $b->title }}</strong><br><small>{{ $b->theme === 'image' ? 'Imagen completa' : 'Imagen con texto' }}</small></td>
            <td>{{ $b->active ? 'Activo' : 'Oculto' }}</td>
            <td><a href="/admin/banners/{{ $b->id }}/editar">Editar</a>
                <form method="post" action="/admin/banners/{{ $b->id }}" data-confirm="¿Eliminar esta promoción del carrusel?">
                    @csrf @method('DELETE')<button class="btn small ghost" type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Todavía no hay promociones. Sube tus primeras imágenes arriba.</td></tr>
    @endforelse
    </tbody></table></div>
</section>
@endsection
