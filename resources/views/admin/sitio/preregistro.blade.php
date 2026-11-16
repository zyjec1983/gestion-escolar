@extends('layouts.principal')

@section('titulo', 'Pre-registro')

{{--
    Cabecera del formulario de pre-registro.

    Qué hace: edita los textos que aparecen en la parte superior de la
    solicitud de matrícula en línea y en su PDF. Es una vista independiente,
    sin pestañas.
    Conecta con: Admin\SitioController, PortalContenido y SiteContent.
    Ruta: GET /admin/sitio/preregistro (admin.sitio.preregistro).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-file-earmark-text me-2"></i>Pre-registro</h4>
        <p class="text-muted small mb-0">
            Cabecera de la solicitud de matrícula en línea y del PDF.
        </p>
    </div>

    @include('admin.sitio._avisos')

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-file-earmark-text me-1"></i>Cabecera del formulario de pre-registro</h6>
            <small class="text-muted">
                Estos datos aparecen en la parte superior de la solicitud de matrícula en línea y en el PDF.
            </small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sitio.contenido', 'preregistro') }}">
                @csrf
                <div class="row g-3">
                    @foreach(\App\Support\PortalContenido::porGrupo('preregistro') as $clave => $campo)
                    <div class="col-md-6">
                        <label class="form-label">{{ $campo['etiqueta'] }}</label>
                        <input type="text" name="campos[{{ $clave }}]" class="form-control"
                               maxlength="10000" value="{{ \App\Support\PortalContenido::valor($clave) }}">
                    </div>
                    @endforeach
                </div>
                <div class="alert alert-light border mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Vista previa del encabezado: la primera línea corresponde al nombre de la institución
                    y la segunda al título de la solicitud. El período lectivo se toma del sistema.
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Guardar cabecera del pre-registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
