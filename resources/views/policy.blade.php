@extends('layouts.store') @section('title',($type==='privacy'?'Privacidad':'Cambios y devoluciones').' · Isablue') @section('content')
<div class="wrap section narrow"><span class="eyebrow">Isablue</span><h1>{{ $type==='privacy'?'Política de privacidad':'Cambios y devoluciones' }}</h1><div class="policy-text">{{ $content ?: 'La información de esta sección estará disponible próximamente. Escríbenos si necesitas ayuda.' }}</div></div>
@endsection
