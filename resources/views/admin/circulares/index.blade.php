@extends('layouts.principal')

@section('titulo', 'Circulares')

{{--
    Carga y administración de circulares.

    Qué hace: publica circulares con número, fecha y PDF; permite avisar por
    correo y SMS al contacto registrado de los perfiles seleccionados y
    eliminar circulares cargadas por error.
    Conecta con: Admin\CircularController, CircularPublicada y NotificadorCircular.
    Rutas: GET/POST /admin/circulares y DELETE /admin/circulares/{id}.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-megaphone me-2"></i>Circulares</h4>
            <p class="text-muted small mb-0">
                Publique circulares institucionales y avise por correo electrónico y SMS al contacto registrado.
            </p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formNuevaCircular"
                aria-expanded="false" aria-controls="formNuevaCircular">
            <i class="bi bi-plus-circle me-1"></i>Nueva circular
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="collapse mb-4" id="formNuevaCircular">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-upload me-1"></i>Publicar circular</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.circulares.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control" required maxlength="255"
                                value="{{ old('titulo') }}" placeholder="Ej. Convocatoria a reunión de representantes">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número</label>
                            <input type="text" name="numero" class="form-control" maxlength="30"
                                value="{{ old('numero') }}" placeholder="Ej. CIRC-2026-014">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de publicación <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_publicacion" class="form-control" required
                                value="{{ old('fecha_publicacion', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Archivo PDF</label>
                            <input type="file" name="archivo" class="form-control" accept="application/pdf">
                            <div class="form-text">PDF de hasta 10 MB. Si no adjunta archivo, la circular queda solo como aviso.</div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notificar" id="notificar" value="1"
                                    @checked(old('notificar'))>
                                <label class="form-check-label" for="notificar">
                                    Enviar aviso por correo y SMS al contacto registrado
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Perfiles que reciben el aviso</label>
                            <div class="row g-1">
                                @foreach($roles as $rol)
                                <div class="col-md-3 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]"
                                            id="rol_circular_{{ $rol }}" value="{{ $rol }}"
                                            @checked(in_array($rol, old('roles', $roles), true))>
                                        <label class="form-check-label" for="rol_circular_{{ $rol }}">
                                            {{ \App\Models\User::etiquetaRol($rol) }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="form-text">Si no marca ninguno, el aviso se envía a todos los perfiles.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse"
                                data-bs-target="#formNuevaCircular">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Publicar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-collection me-1"></i>Circulares publicadas ({{ $circulares->total() }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Publicación</th>
                        <th>Archivo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($circulares as $circular)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $circular->numero ?: '—' }}</span></td>
                        <td><strong>{{ $circular->titulo }}</strong></td>
                        <td><small>{{ $circular->fecha_publicacion?->format('d/m/Y') }}</small></td>
                        <td>
                            @if($circular->archivo_url)
                            <a href="{{ $circular->archivo_url }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                            </a>
                            @else
                            <span class="text-muted small">Sin archivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.circulares.destroy', $circular) }}"
                                onsubmit="return confirm('¿Eliminar la circular «{{ $circular->titulo }}»?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No hay circulares publicadas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($circulares->hasPages())
        <div class="card-footer bg-white">{{ $circulares->links() }}</div>
        @endif
    </div>
</div>
@endsection
