@extends('layouts.principal')

@section('titulo', 'Útiles escolares')

{{--
    Útiles escolares.

    Qué hace: administra las listas de útiles por nivel o curso con el editor
    tipo Excel. Es una vista independiente, sin pestañas.
    Conecta con: Admin\SitioController, ListaUtil, UtilItem y
    admin/sitio/_utiles.blade.php.
    Ruta: GET /admin/sitio/utiles (admin.sitio.utiles).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-backpack2 me-2"></i>Útiles escolares</h4>
        <p class="text-muted small mb-0">
            Listas de útiles por nivel o curso que se publican en el portal.
        </p>
    </div>

    @include('admin.sitio._avisos')

    @include('admin.sitio._utiles')
</div>
@endsection
