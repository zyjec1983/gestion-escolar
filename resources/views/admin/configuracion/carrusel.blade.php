@extends('layouts.principal')

@section('titulo', 'Carrusel del portal')

{{--
    Carrusel de la portada del portal.

    Qué hace: ajusta la velocidad de transición y administra las imágenes que
    se muestran en la página de inicio. Es una vista independiente, sin
    pestañas.
    Conecta con: Admin\HeroSlideController, App\Models\HeroSlide y
    admin/configuracion/_slides-tabla.blade.php.
    Ruta: GET /admin/configuracion/carrusel (admin.configuracion.carrusel).
--}}
@push('styles')
<style>
    .slide-thumb { width: 100px; height: 60px; object-fit: cover; }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-images me-2"></i>Carrusel del portal</h4>
        <p class="text-muted small mb-0">
            Imágenes y velocidad del carrusel de la página de inicio.
        </p>
    </div>

    @include('admin.configuracion._mensajes')

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-speedometer2 me-1"></i>Configuración del carrusel</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.hero-slides.velocidad') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-auto">
                    <label for="carousel_interval" class="form-label">Velocidad de transición</label>
                    <div class="input-group">
                        <input type="number" name="carousel_interval" id="carousel_interval"
                            class="form-control" value="{{ old('carousel_interval', $institucion->carousel_interval ?? 2000) }}"
                            min="1000" max="30000" step="500">
                        <span class="input-group-text">ms</span>
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-check-lg me-1"></i>Actualizar
                    </button>
                </div>
                <div class="col-auto">
                    <small class="text-muted">1 segundo = 1000 ms. Mínimo 1000, máximo 30000.</small>
                </div>
            </form>
        </div>
    </div>

    @include('admin.configuracion._slides-tabla', [
        'lista' => $slidesInicio,
        'titulo' => 'Slides del portal',
        'ubicacion' => 'inicio',
    ])
</div>
@endsection
