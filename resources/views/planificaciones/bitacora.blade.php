@extends('layouts.principal')

@section('titulo', 'Bitácora de Planificaciones')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Bitácora de auditoría</h4>
            <small class="text-muted">Registro inalterable de intervenciones administrativas sobre planificaciones.</small>
        </div>
        <a href="{{ route('planificaciones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver a planificaciones
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('planificaciones.bitacora') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label small text-muted mb-1" for="bitacoraUsuario">Usuario</label>
                    <select name="usuario_id" id="bitacoraUsuario" class="form-select">
                        <option value="">Todos los usuarios</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) request('usuario_id') === (string) $usuario->id)>
                                {{ $usuario->nombreCompleto() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label small text-muted mb-1" for="bitacoraAccion">Acción</label>
                    <select name="accion" id="bitacoraAccion" class="form-select">
                        <option value="">Todas las acciones</option>
                        @foreach($acciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(request('accion') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label small text-muted mb-1" for="bitacoraDesde">Desde</label>
                    <input type="date" name="desde" id="bitacoraDesde" class="form-control" value="{{ request('desde') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label small text-muted mb-1" for="bitacoraHasta">Hasta</label>
                    <input type="date" name="hasta" id="bitacoraHasta" class="form-control" value="{{ request('hasta') }}">
                </div>
                <div class="col-12 col-xl-3">
                    <label class="form-label small text-muted mb-1" for="bitacoraBuscar">Buscar</label>
                    <div class="input-group">
                        <input type="search" name="q" id="bitacoraBuscar" class="form-control" value="{{ request('q') }}" placeholder="Unidad o motivo...">
                        <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i></button>
                        <a href="{{ route('planificaciones.bitacora') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </form>
            @if($errors->any())
                <div class="alert alert-danger small mt-3 mb-0">{{ $errors->first() }}</div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha exacta</th>
                        <th>Planificación</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Motivo</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditorias as $auditoria)
                        <tr>
                            <td class="small text-nowrap">{{ $auditoria->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <strong class="small">{{ $auditoria->unidad_titulo }}</strong>
                                @if($auditoria->unidad_id)
                                    <a href="{{ route('planificaciones.print', $auditoria->unidad_id) }}" target="_blank" rel="noopener" class="small d-block">
                                        Ver planificación
                                    </a>
                                @endif
                            </td>
                            <td class="small">
                                {{ $auditoria->usuario_nombre }}
                                <span class="badge bg-secondary d-block mt-1">{{ $auditoria->usuario_rol }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $auditoria->accion === \App\Models\PlanificacionAuditoria::ACCION_ACTUALIZACION ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ $auditoria->accionLabel() }}
                                </span>
                            </td>
                            <td class="small">{{ $auditoria->motivo }}</td>
                            <td class="small text-muted">{{ $auditoria->ip ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-shield-check fs-1 d-block mb-2"></i>
                                No hay intervenciones administrativas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($auditorias->hasPages())
            <div class="card-footer bg-white">
                {{ $auditorias->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
