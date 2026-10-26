@extends('layouts.store')
@section('title',$post->title.' · Blog Isablue')
@section('description',$post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body),155))
@section('content')
<div class="wrap section narrow">
    <a class="back" href="/blog">← Volver al blog</a>
    <article class="blog-post">
        <small class="muted">{{ $post->published_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</small>
        <h1>{{ $post->title }}</h1>
        @if($post->image)<img class="blog-cover" src="{{ $post->image }}" alt="">@endif
        <div class="blog-body">{{ $post->body }}</div>
    </article>
    @if($more->isNotEmpty())
    <section class="section">
        <h2>Sigue leyendo</h2>
        <div class="blog-grid">
            @foreach($more as $m)
            <article class="blog-card"><a href="/blog/{{ $m->slug }}">@if($m->image)<img src="{{ $m->image }}" alt="" loading="lazy">@else<div class="blog-ph">Isablue</div>@endif<div class="blog-card-body"><h2>{{ $m->title }}</h2></div></a></article>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
