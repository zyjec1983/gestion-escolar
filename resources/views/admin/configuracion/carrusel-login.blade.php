@extends('layouts.principal')

@section('titulo', 'Carrusel del login')

{{--
    Carrusel de la pantalla de inicio de sesión.

    Qué hace: administra las imágenes independientes que se muestran junto al
    formulario de login. Es una vista independiente, sin pestañas.
    Conecta con: Admin\HeroSlideController, App\Models\HeroSlide y
    admin/configuracion/_slides-tabla.blade.php.
    Ruta: GET /admin/configuracion/carrusel-login (admin.configuracion.carrusel-login).
--}}
@push('styles')
<style>
    .slide-thumb { width: 100px; height: 60px; object-fit: cover; }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-box-arrow-in-right me-2"></i>Carrusel del login</h4>
        <p class="text-muted small mb-0">
            Imágenes que acompañan el formulario de inicio de sesión.
        </p>
    </div>

    @include('admin.configuracion._mensajes')

    <div class="alert alert-light border small mb-4">
        <i class="bi bi-info-circle me-1 text-primary"></i>
        Estas imágenes se muestran en el carrusel del login. Si no hay ninguna,
        el login usa el logo o la imagen institucional de respaldo.
    </div>

    @include('admin.configuracion._slides-tabla', [
        'lista' => $slidesLogin,
        'titulo' => 'Slides del login',
        'ubicacion' => 'login',
    ])
</div>
@endsection
