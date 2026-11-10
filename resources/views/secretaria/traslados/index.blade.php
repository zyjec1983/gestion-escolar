@extends('layouts.principal')

@section('titulo', 'Traslados e historial académico')

@push('styles')
<style>
    .traslados-hero {
        background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #0f172a 100%);
    }
    .kpi-traslado {
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .kpi-traslado:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(15, 118, 110, .15);
    }
    .kpi-icono {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .9rem;
        flex-shrink: 0;
    }
    .tabla-traslados th { white-space: nowrap; }
    @media (max-width: 767.98px) {
        .tabla-traslados thead { display: none; }
        .tabla-traslados tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            padding: .5rem;
            margin-bottom: .75rem;
        }
        .tabla-traslados tbody td {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border: 0;
            padding: .35rem .5rem;
        }
        .tabla-traslados tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            font-size: .75rem;
            text-transform: uppercase;
            flex-shrink: 0;
        }
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="traslados-hero rounded-4 p-3 p-md-4 mb-4 text-white shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-white text-dark mb-2">Módulo de gestión de expedientes</span>
                <h4 class="mb-1"><i class="bi bi-arrow-left-right me-2"></i>Traslados e historial académico</h4>
                <div class="small text-white-50">
                    Registro de ingresos y salidas, check-list documental dinámico, homologación de notas y certificados institucionales.
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('traslados.create') }}" class="btn btn-light btn-sm fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo registro de traslado
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif
    @if($errors->any())
        <div id="swal-error" data-message="{{ $errors->first() }}" hidden></div>
    @endif

    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
        <div class="col">
            <div class="card kpi-traslado h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="kpi-icono bg-success-subtle text-success"><i class="bi bi-box-arrow-in-right fs-4"></i></span>
                    <div>
                        <div class="fs-4 fw-bold">{{ number_format($kpis['ingresos'], 0, ',', '.') }}</div>
                        <div class="small text-muted">Ingresos este año</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('traslados.index', ['estado' => 'por_regularizar']) }}" class="text-decoration-none">
                <div class="card kpi-traslado h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icono bg-warning-subtle text-warning-emphasis"><i class="bi bi-clock-history fs-4"></i></span>
                        <div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($kpis['por_regularizar'], 0, ',', '.') }}</div>
                            <div class="small text-muted">Por regularizar</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('traslados.index', ['estado' => 'definitiva']) }}" class="text-decoration-none">
                <div class="card kpi-traslado h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icono bg-primary-subtle text-primary"><i class="bi bi-patch-check fs-4"></i></span>
                        <div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($kpis['definitivas'], 0, ',', '.') }}</div>
                            <div class="small text-muted">Matrículas definitivas</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('traslados.index', ['tipo' => 'salida']) }}" class="text-decoration-none">
                <div class="card kpi-traslado h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="kpi-icono bg-danger-subtle text-danger"><i class="bi bi-box-arrow-right fs-4"></i></span>
                        <div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($kpis['salidas'], 0, ',', '.') }}</div>
                            <div class="small text-muted">Salidas / retiros</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('traslados.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="buscar" class="form-label small fw-semibold">Buscar estudiante, cédula o institución</label>
                    <input type="text" class="form-control" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="100">
                </div>
                <div class="col-6 col-md-2">
                    <label for="tipo" class="form-label small fw-semibold">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="ingreso" @selected(($filtros['tipo'] ?? '') === 'ingreso')>Ingreso</option>
                        <option value="salida" @selected(($filtros['tipo'] ?? '') === 'salida')>Salida</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="estado" class="form-label small fw-semibold">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="por_regularizar" @selected(($filtros['estado'] ?? '') === 'por_regularizar')>Por regularizar</option>
                        <option value="definitiva" @selected(($filtros['estado'] ?? '') === 'definitiva')>Definitiva</option>
                        <option value="cerrado" @selected(($filtros['estado'] ?? '') === 'cerrado')>Cerrado</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-teal text-white flex-grow-1" style="background:#0f766e;">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('traslados.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Expedientes registrados</h6>
            <span class="badge bg-secondary">{{ $expedientes->total() }} expediente(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabla-traslados">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Estudiante / identificación</th>
                            <th>Tipo / institución</th>
                            <th>Momento</th>
                            <th>Fecha</th>
                            <th>Estado del expediente</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expedientes as $expediente)
                            <tr>
                                <td class="ps-3" data-label="Estudiante">
                                    <a href="{{ route('traslados.show', $expediente) }}" class="text-decoration-none fw-semibold">
                                        {{ $expediente->nombreAlumno() }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $expediente->alumno?->numero_cedula ?: $expediente->alumno?->codigo }}
                                        @if($expediente->curso)
                                            · {{ $expediente->curso->nombre }}
                                        @endif
                                        @if($expediente->paralelo)
                                            {{ $expediente->paralelo->nombre }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Tipo">
                                    <span class="badge bg-{{ $expediente->tipoBadge() }}">
                                        {{ mb_strtoupper($expediente->tipoLabel()) }}
                                    </span>
                                    <div class="small text-muted text-truncate" style="max-width:180px;" title="{{ $expediente->institucion }}">
                                        {{ $expediente->institucion }}
                                    </div>
                                </td>
                                <td class="small" data-label="Momento">{{ $expediente->momentoLabel() }}</td>
                                <td class="small text-nowrap" data-label="Fecha">{{ $expediente->fecha_traslado?->format('d/m/Y') }}</td>
                                <td data-label="Estado">
                                    <span class="badge bg-{{ $expediente->estadoBadge() }}">
                                        @if($expediente->estado === 'por_regularizar')
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                        @elseif($expediente->estado === 'definitiva')
                                            <i class="bi bi-check-circle me-1"></i>
                                        @else
                                            <i class="bi bi-lock me-1"></i>
                                        @endif
                                        {{ $expediente->estadoLabel() }}
                                    </span>
                                    @if($expediente->tipo === 'ingreso' && $expediente->matricula)
                                        <div class="small text-muted mt-1">
                                            Matrícula: {{ $expediente->matricula->estado === 'definitiva' ? 'definitiva' : 'provisional' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end pe-3" data-label="Acciones">
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        <a href="{{ route('traslados.show', $expediente) }}" class="btn btn-sm btn-outline-secondary" title="Ver expediente">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('traslados.historial.pdf', $expediente) }}" class="btn btn-sm btn-outline-primary" title="Historial académico PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                        @if($expediente->tipo === 'salida' && $expediente->puedeEmitirCertificadoSalida())
                                            <a href="{{ route('traslados.certificado.pdf', $expediente) }}" class="btn btn-sm btn-outline-danger" title="Certificado de salida PDF">
                                                <i class="bi bi-file-earmark-check"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-folder-x fs-2 d-block mb-2"></i>
                                    No hay expedientes de traslado registrados.
                                    <div class="mt-3">
                                        <a href="{{ route('traslados.create') }}" class="btn btn-sm text-white" style="background:#0f766e;">
                                            <i class="bi bi-plus-lg me-1"></i>Crear el primero
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($expedientes->hasPages())
            <div class="card-footer bg-white">{{ $expedientes->links() }}</div>
        @endif
    </div>

    <div class="alert alert-secondary border-0 shadow-sm small mt-4">
        <i class="bi bi-shield-check me-1"></i>
        <strong>Reglas aplicadas:</strong> RN-01 exige libreta y asistencia solo con el período activo; RN-02 bloquea el certificado de salida por deudas;
        RN-03 vuelve inmutable el historial externo al cerrar el expediente; RN-04 cambia la matrícula a definitiva únicamente con el 100% de documentos.
    </div>
</div>
@endsection
