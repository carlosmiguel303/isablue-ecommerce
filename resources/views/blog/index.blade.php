@extends('layouts.store')
@section('title','Blog · Isablue')
@section('description','Historias, reseñas de clientes, juguetes recomendados y novedades de Isablue.')
@section('content')
<div class="wrap">
    <section class="page-hero" style="background:linear-gradient(135deg,var(--teal),var(--purple))">
        <span class="pill">@include('partials.icon',['name'=>'heart','size'=>16]) Aprender jugando</span>
        <h1>Blog Isablue</h1>
        <p>Historias, reseñas de nuestros clientes, juguetes que recomendamos y novedades para cada etapa de tu peque.</p>
    </section>
    <nav class="blog-tabs" aria-label="Secciones del blog">
        <a href="/blog" @class(['active' => ! $category])>Todo</a>
        @foreach(\App\Models\Post::CATEGORIES as $key => [$label, $icon])<a href="/blog?seccion={{ $key }}" @class(['active' => $category === $key])>{{ $icon }} {{ $label }}</a>@endforeach
    </nav>
    <section class="section">
        @if($posts->isEmpty())
            <p class="notice">{{ $category ? 'Aún no hay publicaciones en esta sección.' : 'Muy pronto compartiremos nuestras primeras publicaciones.' }}</p>
        @else
        <div class="blog-grid">
            @foreach($posts as $post)
            <article class="blog-card">
                <a href="/blog/{{ $post->slug }}">
                    <div class="blog-media">
                        @if($post->image)<img src="{{ $post->image }}" alt="" loading="lazy">@else<div class="blog-ph">Isablue</div>@endif
                        @if($post->video_url)<span class="blog-play" aria-label="Incluye video">▶</span>@endif
                    </div>
                    <div class="blog-card-body">
                        <small class="blog-cat">{{ $post->category_icon }} {{ $post->category_label }} · {{ $post->published_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</small>
                        <h2>{{ $post->title }}</h2>
                        <p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body), 140) }}</p>
                        <span class="text-link">{{ $post->video_url ? 'Ver publicación →' : 'Leer más →' }}</span>
                    </div>
                </a>
            </article>
            @endforeach
        </div>
        @if($posts->lastPage() > 1)
        <div class="pagination">
            @if($posts->previousPageUrl())<a class="btn ghost small" href="{{ $posts->previousPageUrl() }}">← Anterior</a>@endif
            <span>Página {{ $posts->currentPage() }} de {{ $posts->lastPage() }}</span>
            @if($posts->nextPageUrl())<a class="btn ghost small" href="{{ $posts->nextPageUrl() }}">Siguiente →</a>@endif
        </div>
        @endif
        @endif
    </section>
</div>
@endsection
