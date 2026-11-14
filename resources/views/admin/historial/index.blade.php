@extends('layouts.principal')

@section('titulo', 'Historial de cambios')

{{--
    Historial de cambios.

    Qué hace: muestra quién creó, editó, habilitó o eliminó información en los
    módulos administrables, con filtros por módulo, acción, usuario y fecha.
    Conecta con: Admin\HistorialController y App\Models\HistorialCambio.
    Ruta: GET /admin/historial (admin.historial.index).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>Historial de cambios</h4>
        <p class="text-muted small mb-0">
            Auditoría de las acciones realizadas en el panel de administración.
        </p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.historial.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold" for="buscar">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="buscar" name="buscar" class="form-control" value="{{ $buscar }}"
                            placeholder="Descripción o usuario">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold" for="modulo">Módulo</label>
                    <select id="modulo" name="modulo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($modulos as $opcion)
                        <option value="{{ $opcion }}" @selected($modulo === $opcion)>{{ $opcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold" for="accion">Acción</label>
                    <select id="accion" name="accion" class="form-select">
                        <option value="">Todas</option>
                        @foreach($acciones as $opcion)
                        <option value="{{ $opcion }}" @selected($accion === $opcion)>{{ ucfirst($opcion) }}</option>
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
                    <a href="{{ route('admin.historial.index') }}" class="btn btn-outline-secondary" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-list-ul me-1"></i>Registros ({{ $cambios->total() }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:150px;">Fecha</th>
                        <th style="width:130px;">Módulo</th>
                        <th style="width:120px;">Acción</th>
                        <th>Descripción</th>
                        <th style="width:180px;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cambios as $cambio)
                    <tr>
                        <td><small>{{ $cambio->created_at?->format('d/m/Y H:i:s') }}</small></td>
                        <td><span class="badge bg-light text-dark border">{{ $cambio->modulo }}</span></td>
                        <td>
                            <span class="badge bg-secondary text-capitalize">{{ $cambio->accion }}</span>
                        </td>
                        <td><small>{{ $cambio->descripcion }}</small></td>
                        <td>
                            @if($cambio->usuario)
                            <small><strong>{{ $cambio->usuario->nombreCompleto() }}</strong><br>
                                <span class="text-muted">{{ $cambio->usuario->email }}</span></small>
                            @else
                            <small class="text-muted">Sistema</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No hay cambios registrados con esos filtros.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cambios->hasPages())
        <div class="card-footer bg-white">{{ $cambios->links() }}</div>
        @endif
    </div>
</div>
@endsection
