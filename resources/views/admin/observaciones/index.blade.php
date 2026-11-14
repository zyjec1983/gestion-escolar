@extends('layouts.principal')

@section('titulo', 'Observaciones')

{{--
    Depuración de observaciones enviadas por error.

    Qué hace: lista las observaciones con filtros por estudiante, tipo y fecha,
    y permite eliminarlas dejando constancia en el historial de cambios.
    Conecta con: Admin\ObservacionAdminController, Observacion y TipoObservacion.
    Rutas: GET /admin/observaciones y DELETE /admin/observaciones/{id}.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Observaciones</h4>
        <p class="text-muted small mb-0">
            Elimine observaciones registradas por error. Cada eliminación queda en el historial de cambios.
        </p>
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.observaciones.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="buscar">Buscar estudiante o texto</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="buscar" name="buscar" class="form-control" value="{{ $buscar }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="tipo_observacion_id">Tipo</label>
                    <select id="tipo_observacion_id" name="tipo_observacion_id" class="form-select">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos as $tipo)
                        <option value="{{ $tipo->id }}" @selected((int) $tipoId === $tipo->id)>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold" for="desde">Desde</label>
                    <input type="date" id="desde" name="desde" class="form-control" value="{{ $desde }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold" for="hasta">Hasta</label>
                    <input type="date" id="hasta" name="hasta" class="form-control" value="{{ $hasta }}">
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" title="Filtrar"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('admin.observaciones.index') }}" class="btn btn-outline-secondary" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-list-ul me-1"></i>Registros ({{ $observaciones->total() }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Registró</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($observaciones as $observacion)
                    <tr>
                        <td><small>{{ $observacion->created_at?->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <strong>{{ $observacion->alumno?->nombreCompleto() ?? '—' }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $observacion->tipoObservacion?->nombre ?? 'Sin tipo' }}
                            </span>
                        </td>
                        <td><small>{{ \Illuminate\Support\Str::limit($observacion->descripcion_texto, 140) }}</small></td>
                        <td><small>{{ $observacion->profesor?->nombreCompleto() ?? '—' }}</small></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.observaciones.destroy', $observacion) }}"
                                class="d-flex gap-1 justify-content-end"
                                onsubmit="return confirm('¿Eliminar definitivamente esta observación?')">
                                @csrf
                                @method('DELETE')
                                <input type="text" name="motivo" class="form-control form-control-sm"
                                    style="max-width: 190px;" placeholder="Motivo / orden de Rectorado"
                                    title="Indique el motivo y la orden de Rectorado" required>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No hay observaciones que coincidan con los filtros.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($observaciones->hasPages())
        <div class="card-footer bg-white">{{ $observaciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
