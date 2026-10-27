@extends('layouts.admin') @section('content')<a class="back" href="/admin/blog">← Volver al blog</a><h1>{{ $post->exists?'Editar publicación':'Nueva publicación' }}</h1>
<form class="admin-card form-grid" method="post" enctype="multipart/form-data" action="/admin/blog{{ $post->exists?'/'.$post->id:'' }}">@csrf @if($post->exists)@method('PUT')@endif
<label class="span-all">Título<input name="title" value="{{ old('title',$post->title) }}" maxlength="200" required></label>
<label>Sección<select name="category" required>@foreach(\App\Models\Post::CATEGORIES as $key => [$label, $icon])<option value="{{ $key }}" @selected(old('category',$post->category ?: 'novedades')===$key)>{{ $icon }} {{ $label }}</option>@endforeach</select><small class="muted">Historias, reseñas de ventas, juguetes que recomiendas o novedades.</small></label>
<label>URL amigable (opcional)<input name="slug" value="{{ old('slug',$post->slug) }}" placeholder="se crea a partir del título" pattern="[a-z0-9]+(-[a-z0-9]+)*"></label>
<label>Imagen de portada (JPG, PNG o WebP)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp">@if($post->image)<small class="muted">Imagen actual: <a href="{{ $post->image }}" target="_blank" rel="noopener">ver</a></small>@endif</label>
<label>Video (opcional)<input type="url" name="video_url" value="{{ old('video_url',$post->video_url) }}" maxlength="500" placeholder="https://www.youtube.com/watch?v=…"><small class="muted">Pega el enlace de YouTube, TikTok, Instagram, Facebook o Vimeo. Se verá dentro de la publicación.</small></label>
<label class="span-all">Resumen (aparece en la lista del blog)<input name="excerpt" value="{{ old('excerpt',$post->excerpt) }}" maxlength="300"></label>
<label class="span-all">Contenido<textarea name="body" rows="14" required>{{ old('body',$post->body) }}</textarea><small class="muted">Separa los párrafos con una línea en blanco.</small></label>
<label class="check"><input type="checkbox" name="published" value="1" @checked(old('published',$post->published))> Publicado (visible en la web)</label>
<div class="span-all"><button class="btn">Guardar publicación</button></div></form>@endsection
