@extends('layouts.store')
@section('title',$post->title.' · Blog Isablue')
@section('description',$post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body),155))
@section('content')
<div class="wrap section narrow">
    <a class="back" href="/blog">← Volver al blog</a>
    <article class="blog-post">
        <small class="blog-cat"><a href="/blog?seccion={{ $post->category }}">{{ $post->category_icon }} {{ $post->category_label }}</a> · {{ $post->published_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</small>
        <h1>{{ $post->title }}</h1>
        @if($post->video_embed)
            <div @class(['blog-video', 'vertical' => $post->video_vertical])><iframe src="{{ $post->video_embed }}" title="Video: {{ $post->title }}" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
        @elseif($post->image)
            <img class="blog-cover" src="{{ $post->image }}" alt="">
        @endif
        @if($post->video_url && ! $post->video_embed)<p><a class="btn" href="{{ $post->video_url }}" target="_blank" rel="noopener">▶ Ver el video</a></p>@endif
        <div class="blog-body">{{ $post->body }}</div>
        @if($post->video_embed && $post->image)<img class="blog-cover" src="{{ $post->image }}" alt="" loading="lazy">@endif
    </article>
    @if($more->isNotEmpty())
    <section class="section">
        <h2>Sigue leyendo</h2>
        <div class="blog-grid">
            @foreach($more as $m)
            <article class="blog-card"><a href="/blog/{{ $m->slug }}"><div class="blog-media">@if($m->image)<img src="{{ $m->image }}" alt="" loading="lazy">@else<div class="blog-ph">Isablue</div>@endif @if($m->video_url)<span class="blog-play" aria-label="Incluye video">▶</span>@endif</div><div class="blog-card-body"><small class="blog-cat">{{ $m->category_icon }} {{ $m->category_label }}</small><h2>{{ $m->title }}</h2></div></a></article>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
