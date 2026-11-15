@extends('layouts.principal')

@section('titulo', 'Institución')

{{--
    Identidad institucional.

    Qué hace: edita el nombre, el subtítulo y el logo de la institución. Se
    abre desde el grupo "Contenido institucional" del menú y es una vista
    independiente, sin pestañas.
    Conecta con: Admin\ConfiguracionController@updateInstitucion,
    App\Models\Institucion y layouts/principal.blade.php.
    Rutas: GET/POST /admin/configuracion/institucion.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-building me-2"></i>Institución</h4>
        <p class="text-muted small mb-0">
            Nombre, subtítulo y logo que se muestran en el portal, el login y el menú.
        </p>
    </div>

    @include('admin.configuracion._mensajes')

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-building me-1"></i>Datos de la institución</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Este nombre aparece en el inicio, en el login, en el menú lateral y en el título de las pestañas.
                        El logo se usa también como favicon.
                    </p>

                    <form method="POST" action="{{ route('admin.configuracion.institucion.update') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre de la institución</label>
                            <input type="text" name="nombre" id="nombre" class="form-control"
                                value="{{ old('nombre', $institucion->nombre) }}" required maxlength="255">
                            @error('nombre')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="subtitulo" class="form-label fw-semibold">Subtítulo</label>
                            <input type="text" name="subtitulo" id="subtitulo" class="form-control"
                                value="{{ old('subtitulo', $institucion->subtitulo) }}" maxlength="255"
                                placeholder="Unidad Educativa Particular Bilingüe">
                            @error('subtitulo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label fw-semibold">Logo institucional</label>
                            <input type="file" name="logo" id="logo" class="form-control"
                                accept="image/png,image/jpeg,image/webp">
                            <div class="form-text">PNG, JPG o WEBP, máximo 2 MB. Se recomienda un cuadrado.</div>
                            @error('logo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        @if($institucion->logo_path)
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="quitar_logo" id="quitar_logo" value="1">
                            <label class="form-check-label" for="quitar_logo">Quitar el logo actual</label>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar institución
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-eye me-1"></i>Vista previa</h6>
                </div>
                <div class="card-body text-center">
                    @if($institucion->logo_path)
                    <img src="{{ $institucion->logoUrl() }}" alt="Logo institucional"
                        class="img-fluid mb-3" style="max-height:110px;">
                    @else
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:96px;height:96px;">
                        <i class="bi bi-mortarboard-fill fs-1 text-secondary"></i>
                    </div>
                    @endif
                    <h5 class="fw-bold mb-1">{{ $institucion->nombre }}</h5>
                    <p class="text-muted small mb-0">{{ $institucion->subtitulo }}</p>
                    <hr>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-bookmark-star me-1"></i>
                        Favicon de las pestañas: {{ $institucion->logo_path ? 'logo institucional' : 'icono por defecto' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
