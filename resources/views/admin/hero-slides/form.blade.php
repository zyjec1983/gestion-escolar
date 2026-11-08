@extends('layouts.principal')

@section('titulo', $slide ? 'Editar Slide' : 'Nuevo Slide')

@section('contenido')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $slide ? 'Editar Slide' : 'Nuevo Slide' }}</h1>
        <a href="{{ route('admin.configuracion.index') }}#panel-carrusel" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ $slide ? route('admin.hero-slides.update', $slide->id) : route('admin.hero-slides.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if($slide) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="titulo" class="form-label">Título</label>
                        <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror"
                            value="{{ old('titulo', $slide->titulo ?? '') }}" maxlength="255">
                        @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="orden" class="form-label">Orden</label>
                        <input type="number" name="orden" id="orden" class="form-control @error('orden') is-invalid @enderror"
                            value="{{ old('orden', $slide->orden ?? $ultimoOrden ?? 0) }}" min="0">
                        @error('orden')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="subtitulo" class="form-label">Subtítulo</label>
                        <input type="text" name="subtitulo" id="subtitulo" class="form-control @error('subtitulo') is-invalid @enderror"
                            value="{{ old('subtitulo', $slide->subtitulo ?? '') }}" maxlength="255">
                        @error('subtitulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="link" class="form-label">Enlace (opcional)</label>
                        <input type="url" name="link" id="link" class="form-control @error('link') is-invalid @enderror"
                            value="{{ old('link', $slide->link ?? '') }}" placeholder="https://..." maxlength="255">
                        @error('link')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="imagen" class="form-label">
                            Imagen{{ $slide ? ' (dejar vacío para mantener la actual)' : '' }}
                        </label>
                        <input type="file" name="imagen" id="imagen"
                            class="form-control @error('imagen') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">Formatos: JPEG, PNG, GIF, WebP. Máximo 5MB.</small>
                        @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($slide && $slide->imagen_path)
                        <div class="mt-2">
                            <img src="{{ Storage::url($slide->imagen_path) }}" alt="Vista previa"
                                class="img-thumbnail" style="max-height:200px;object-fit:cover;">
                        </div>
                        @endif
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" id="activo" class="form-check-input" value="1"
                                {{ old('activo', $slide->activo ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>{{ $slide ? 'Actualizar' : 'Crear' }} Slide
                    </button>
                    <a href="{{ route('admin.configuracion.index') }}#panel-carrusel" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
