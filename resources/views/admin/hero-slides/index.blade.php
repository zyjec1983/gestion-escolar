@extends('layouts.admin')

@section('titulo', 'Carrusel Hero')

@section('contenido')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Carrusel del Hero</h1>
        <a href="{{ route('admin.hero-slides.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Slide
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Configuración del Carrusel</h5>
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

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Slides ({{ $slides->count() }})</h5>
        </div>
        <div class="card-body p-0">
            @if($slides->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">Orden</th>
                            <th style="width:120px;">Vista previa</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th style="width:160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slides as $slide)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $slide->orden }}</span>
                            </td>
                            <td>
                                @if($slide->imagen_path)
                                <img src="{{ Storage::url($slide->imagen_path) }}" alt="{{ $slide->titulo }}"
                                    class="img-thumbnail" style="width:100px;height:60px;object-fit:cover;">
                                @else
                                <span class="text-muted small">Sin imagen</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $slide->titulo ?? 'Sin título' }}</strong>
                                @if($slide->subtitulo)
                                <br><small class="text-muted">{{ $slide->subtitulo }}</small>
                                @endif
                            </td>
                            <td>
                                @if($slide->activo)
                                <span class="badge bg-success">Activo</span>
                                @else
                                <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.hero-slides.edit', $slide->id) }}"
                                    class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide->id) }}"
                                    class="d-inline" onsubmit="return confirm('¿Eliminar este slide?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-images display-1 text-muted mb-3 d-block"></i>
                <p class="text-muted">No hay slides. Crea el primero.</p>
                <a href="{{ route('admin.hero-slides.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo Slide
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
