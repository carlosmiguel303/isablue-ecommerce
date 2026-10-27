@extends('layouts.admin') @section('content')<div class="page-title"><h1>Blog</h1><a href="/admin/blog/nuevo" class="btn">+ Nueva publicación</a></div>
<div class="admin-card" style="padding:0"><div class="table-wrap"><table><thead><tr><th>Título</th><th>Sección</th><th>Estado</th><th>Fecha</th><th></th></tr></thead><tbody>
@forelse($posts as $p)<tr><td>{{ $p->title }}@if($p->video_url) <small class="muted">· 🎬 video</small>@endif</td><td>{{ $p->category_icon }} {{ $p->category_label }}</td><td>{{ $p->published ? 'Publicado' : 'Borrador' }}</td><td>{{ ($p->published_at ?? $p->created_at)->format('d/m/Y') }}</td><td><a href="/admin/blog/{{ $p->id }}/editar">Editar</a>@if($p->published) · <a href="/blog/{{ $p->slug }}" target="_blank" rel="noopener">Ver</a>@endif
<form method="post" action="/admin/blog/{{ $p->id }}" data-confirm="¿Eliminar esta publicación del blog?">@csrf @method('DELETE')<button class="btn small ghost" type="submit">Eliminar</button></form></td></tr>
@empty<tr><td colspan="5">Aún no hay publicaciones. Crea la primera con «+ Nueva publicación».</td></tr>@endforelse
</tbody></table></div></div>
@include('admin.pagination',['paginator'=>$posts])
@endsection
