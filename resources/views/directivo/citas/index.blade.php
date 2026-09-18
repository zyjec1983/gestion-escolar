@extends('layouts.principal')

@section('titulo', 'Historial de citas')

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-clipboard2-data me-2"></i>Historial estadístico de citas</h4>
            <small class="text-muted">Control de citas a representantes: asistencia, quién citó, motivo y evidencia, para seguimiento de INSPECCIÓN y DECE.</small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-3 fw-bold text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted">Citas totales</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-3 fw-bold text-success">{{ $stats['registradas'] }}</div>
                <small class="text-muted">Con resultado registrado</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-3 fw-bold text-info">{{ $stats['asistencia'] }}%</div>
                <small class="text-muted">Asistencia de representantes</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <div class="fs-3 fw-bold {{ $stats['reincidentes'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $stats['reincidentes'] }}</div>
                <small class="text-muted">Estudiantes reincidentes (3+ citas)</small>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-people me-1"></i>Resumen por estudiante</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Estudiante</th>
                        <th>Nivel</th>
                        <th>Citas</th>
                        <th>Asistió</th>
                        <th>No asistió</th>
                        <th>Sin registrar</th>
                        <th>Docente(s) que citó(aron)</th>
                        <th>Último motivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumen as $r)
                        <tr>
                            <td><strong>{{ $r['alumno'] }}</strong></td>
                            <td>{{ $r['nivel'] }}</td>
                            <td><span class="badge bg-primary">{{ $r['total'] }}</span></td>
                            <td><span class="badge bg-success">{{ $r['asistio'] }}</span></td>
                            <td><span class="badge bg-danger">{{ $r['no_asistio'] }}</span></td>
                            <td><span class="badge bg-secondary">{{ $r['sin_registrar'] }}</span></td>
                            <td><small>{{ $r['docentes'] }}</small></td>
                            <td><small class="text-muted">{{ mb_strimwidth($r['ultimo_motivo'] ?? '', 0, 45, '…') }}</small></td>
                            <td>
                                @if($r['reincidente'])
                                    <span class="badge bg-danger text-white">Reincidente</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Aún no hay citas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0"><i class="bi bi-list-ul me-1"></i>Detalle de las citas</h6>
            <form method="GET" class="d-flex gap-2">
                <select name="alumno_id" class="form-select form-select-sm">
                    <option value="">Todos los estudiantes</option>
                    @foreach($alumnos as $al)
                        <option value="{{ $al->id }}" {{ (string) $estudianteId === (string) $al->id ? 'selected' : '' }}>
                            {{ $al->apellidos }} {{ $al->nombres }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Nivel</th>
                        <th>Representante solicitante</th>
                        <th>Docente que citó</th>
                        <th>Motivo</th>
                        <th>Resultado</th>
                        <th>Dudas aclaradas</th>
                        <th>Evidencia</th>
                        <th>Registrada por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($citas as $cita)
                        @php
                            $mat = $cita->alumno ? $cita->alumno->matriculas->last() : null;
                            $nivelCita = $mat ? trim(($mat->curso?->nombre ?? '') . ' ' . ($mat->paralelo?->nombre ?? '')) : '—';
                        @endphp
                        <tr>
                            <td class="small text-nowrap">{{ $cita->fecha_inicio->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($cita->alumno)
                                    <strong>{{ $cita->alumno->apellidos }} {{ $cita->alumno->nombres }}</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $nivelCita }}</td>
                            <td>
                                @if($cita->solicitante)
                                    {{ trim($cita->solicitante->name . ' ' . $cita->solicitante->apellidos) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $cita->docente ? trim($cita->docente->name . ' ' . $cita->docente->apellidos) : '—' }}</td>
                            <td><small>{{ $cita->motivo }}</small></td>
                            <td>
                                @if($cita->registrada())
                                    <span class="badge bg-{{ $cita->resultado == 'asistio' ? 'success' : 'danger' }} text-white">
                                        {{ $cita->resultado == 'asistio' ? 'Asistió' : 'No asistió' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">{{ $cita->fecha_inicio->lte(now()) ? 'Por registrar' : 'Pendiente' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($cita->resultado == 'asistio')
                                    <span class="badge bg-{{ $cita->dudas_aclaradas ? 'primary' : 'secondary' }}">
                                        {{ $cita->dudas_aclaradas ? 'Sí' : 'No' }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cita->evidencia)
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ addslashes($cita->evidencia) }}">
                                        <i class="bi bi-eye me-1"></i>Ver
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($cita->registrador)
                                    {{ trim($cita->registrador->name . ' ' . $cita->registrador->apellidos) }}
                                    <span class="text-muted d-block">{{ $cita->registrada_en?->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No hay citas que coincidan con el filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(function (el) { new bootstrap.Tooltip(el); });
});
</script>
@endpush