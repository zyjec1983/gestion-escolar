@extends('layouts.principal')

@section('titulo', 'Panel de Secretaría')

@push('styles')
<style>
    .sec-hero {
        background: linear-gradient(135deg, #0f766e 0%, #0891b2 100%);
    }
    .sec-kpi, .sec-panel {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .sec-kpi:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.16);
    }
    .sec-kpi-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.18);
        flex-shrink: 0;
    }
    .sec-quick {
        min-height: 72px;
    }
    .min-w-0 {
        min-width: 0;
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="sec-hero rounded-4 p-3 p-md-4 mb-4 text-white shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="min-w-0">
                <span class="badge bg-white text-dark mb-2">Secretaría General</span>
                <h4 class="mb-1">
                    <i class="bi bi-person-vcard me-2"></i>Panel de Secretaría
                </h4>
                <div class="small text-white-50">
                    {{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }} · {{ $usuario['periodo_lectivo'] ?? '' }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.pre-registros.index', ['filtro' => 'pendiente']) }}"
                   class="btn btn-light btn-sm">
                    <i class="bi bi-clipboard-check me-1"></i>Revisar solicitudes
                </a>
                <a href="{{ route('actualizacion.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>Actualizar datos
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-3">
        <div class="col">
            <div class="card sec-kpi shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff;">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="sec-kpi-icon"><i class="bi bi-people-fill fs-4"></i></span>
                    <div class="min-w-0">
                        <div class="fs-4 fw-bold">{{ number_format($kpis['matriculados'], 0, ',', '.') }}</div>
                        <div class="small text-white-50">Estudiantes matriculados</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <a href="{{ route('admin.pre-registros.index', ['filtro' => 'pendiente']) }}" class="text-decoration-none">
                <div class="card sec-kpi shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #7a4a00, #f59f00); color: #fff;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="sec-kpi-icon"><i class="bi bi-hourglass-split fs-4"></i></span>
                        <div class="min-w-0">
                            <div class="fs-4 fw-bold">{{ $kpis['solicitudes'] }}</div>
                            <div class="small text-white-50">Solicitudes pendientes</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('actualizacion.index', ['pendientes' => 1]) }}" class="text-decoration-none">
                <div class="card sec-kpi shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="sec-kpi-icon"><i class="bi bi-person-exclamation fs-4"></i></span>
                        <div class="min-w-0">
                            <div class="fs-4 fw-bold">{{ $kpis['fichas'] }}</div>
                            <div class="small text-white-50">Fichas por regularizar</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('inspeccion.citas.historial') }}" class="text-decoration-none">
                <div class="card sec-kpi shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: #fff;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="sec-kpi-icon"><i class="bi bi-calendar-event fs-4"></i></span>
                        <div class="min-w-0">
                            <div class="fs-4 fw-bold">{{ $kpis['citas'] }}</div>
                            <div class="small text-white-50">Citas pendientes</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <div class="card sec-kpi shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color: #fff;">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="sec-kpi-icon"><i class="bi bi-person-badge-fill fs-4"></i></span>
                    <div class="min-w-0">
                        <div class="fs-4 fw-bold">{{ $kpis['docentes'] }}</div>
                        <div class="small text-white-50">Docentes activos</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card sec-kpi shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #312e81, #6366f1); color: #fff;">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="sec-kpi-icon"><i class="bi bi-file-earmark-text fs-4"></i></span>
                    <div class="min-w-0">
                        <div class="fs-4 fw-bold">{{ $kpis['circulares'] }}</div>
                        <div class="small text-white-50">Circulares publicadas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card sec-panel shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Accesos rápidos</h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('admin.pre-registros.index') }}" class="btn btn-outline-primary w-100 sec-quick d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-clipboard-check fs-5"></i>
                        <span class="small mt-1">Matrículas</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('actualizacion.index') }}" class="btn btn-outline-success w-100 sec-quick d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-pencil-square fs-5"></i>
                        <span class="small mt-1">Datos</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('inspeccion.citas.agendar') }}" class="btn btn-outline-info w-100 sec-quick d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-calendar-plus fs-5"></i>
                        <span class="small mt-1">Citas</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('inspeccion.observaciones.reporte.alumno') }}" class="btn btn-outline-warning w-100 sec-quick d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-exclamation-triangle fs-5"></i>
                        <span class="small mt-1">Observaciones</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('inspeccion.asistencia.estadisticas') }}" class="btn btn-outline-secondary w-100 sec-quick d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-bar-chart-line fs-5"></i>
                        <span class="small mt-1">Asistencias</span>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('mensajes.index') }}" class="btn btn-outline-dark w-100 sec-quick d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-envelope fs-5"></i>
                        <span class="small mt-1">Mensajes</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card sec-panel shadow-sm h-100">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0"><i class="bi bi-inbox me-2"></i>Solicitudes recientes</h6>
                    <a href="{{ route('admin.pre-registros.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Curso / Paralelo</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($solicitudes_recientes as $solicitud)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.pre-registros.show', $solicitud->id) }}" class="text-decoration-none fw-semibold">
                                                {{ $solicitud->nombre_alumno }}
                                            </a>
                                        </td>
                                        <td class="text-muted small">
                                            {{ $solicitud->curso?->nombre ?? '—' }}
                                            {{ $solicitud->paralelo?->nombre ?? '' }}
                                        </td>
                                        <td class="text-muted small">{{ $solicitud->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-check2-circle fs-3 d-block mb-2"></i>
                                            No hay solicitudes pendientes
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card sec-panel shadow-sm h-100">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Próximas citas</h6>
                    <a href="{{ route('inspeccion.citas.historial') }}" class="btn btn-sm btn-outline-primary">Historial</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($citas_proximas as $cita)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <strong class="d-block text-truncate">
                                            {{ $cita->alumno ? trim($cita->alumno->apellidos.' '.$cita->alumno->nombres) : 'Sin estudiante' }}
                                        </strong>
                                        <small class="text-muted d-block text-truncate">
                                            Atiende: {{ $cita->docente?->name ?? 'Sin asignar' }}
                                        </small>
                                    </div>
                                    <span class="badge bg-info text-dark text-nowrap">
                                        {{ $cita->fecha_inicio?->format('d/m H:i') }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                <i class="bi bi-calendar-check fs-3 d-block mb-2"></i>
                                No hay citas próximas
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card sec-panel shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0">
                <i class="bi bi-shield-check me-2"></i>Observaciones recientes
                <span class="badge bg-secondary ms-1">Solo lectura</span>
            </h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('inspeccion.observaciones.reporte.alumno') }}" class="btn btn-sm btn-outline-primary">Reporte por alumno</a>
                <a href="{{ route('inspeccion.observaciones.estadisticas') }}" class="btn btn-sm btn-outline-secondary">Estadísticas</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Tipo</th>
                            <th>Registrada por</th>
                            <th class="text-end">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($observaciones_recientes as $observacion)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $observacion->alumno ? trim($observacion->alumno->apellidos.' '.$observacion->alumno->nombres) : '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $observacion->tipoObservacion?->nombre ?? 'Sin tipo' }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $observacion->profesor?->name ?? '—' }}</td>
                                <td class="text-end text-muted small">{{ $observacion->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No hay observaciones registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
