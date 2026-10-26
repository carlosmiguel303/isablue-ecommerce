@extends('layouts.store')
@section('title','Blog · Isablue')
@section('description','Consejos de juego, estimulación y desarrollo infantil de Isablue.')
@section('content')
<div class="wrap">
    <section class="page-hero" style="background:linear-gradient(135deg,var(--teal),var(--purple))">
        <span class="pill">@include('partials.icon',['name'=>'heart','size'=>16]) Aprender jugando</span>
        <h1>Blog Isablue</h1>
        <p>Ideas, consejos y novedades para acompañar cada etapa del juego de tu peque.</p>
    </section>
    <section class="section">
        @if($posts->isEmpty())
            <p class="notice">Muy pronto compartiremos nuestros primeros artículos.</p>
        @else
        <div class="blog-grid">
            @foreach($posts as $post)
            <article class="blog-card">
                <a href="/blog/{{ $post->slug }}">
                    @if($post->image)<img src="{{ $post->image }}" alt="" loading="lazy">@else<div class="blog-ph">Isablue</div>@endif
                    <div class="blog-card-body">
                        <small class="muted">{{ $post->published_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</small>
                        <h2>{{ $post->title }}</h2>
                        <p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body), 140) }}</p>
                        <span class="text-link">Leer más →</span>
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
