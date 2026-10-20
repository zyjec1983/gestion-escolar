@extends('layouts.principal')

@section('titulo', 'Estadísticas de Asistencia')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-bar-chart-line me-2"></i>Estadísticas de Asistencia</h4>

    <form method="GET" action="{{ route('inspeccion.asistencia.estadisticas') }}" class="row g-3 mb-4" id="filtroAula">
        <div class="col-md-4">
            <label class="form-label">Curso / Paralelo</label>
            <select name="aula" class="form-select" id="selectorAula" onchange="enviarFiltro()">
                @foreach($aulas as $a)
                    <option value="{{ $a->curso_id }}:{{ $a->paralelo_id }}"
                        {{ $aula && $aula->curso_id == $a->curso_id && $aula->paralelo_id == $a->paralelo_id ? 'selected' : '' }}>
                        {{ $a->curso_nombre }} - {{ $a->paralelo_nombre }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="curso_id" id="inputCursoId" value="{{ $aula->curso_id ?? '' }}">
            <input type="hidden" name="paralelo_id" id="inputParaleloId" value="{{ $aula->paralelo_id ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde?->format('Y-m-d') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta?->format('Y-m-d') }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        </div>
    </form>

    @if($alumnos->isNotEmpty())
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card shadow-sm text-center">
                    <div class="card-body py-2">
                        <div class="h4 mb-0">{{ $totales['total'] }}</div>
                        <small class="text-muted">Días registrados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm text-center border-success">
                    <div class="card-body py-2">
                        <div class="h4 mb-0 text-success">{{ $totales['presente'] }}</div>
                        <small class="text-muted">Presentes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm text-center border-warning">
                    <div class="card-body py-2">
                        <div class="h4 mb-0 text-warning">{{ $totales['faltas'] }}</div>
                        <small class="text-muted">Faltas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm text-center border-info">
                    <div class="card-body py-2">
                        <div class="h4 mb-0 text-info">{{ $totales['atraso'] }}</div>
                        <small class="text-muted">Atrasos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm text-center border-danger">
                    <div class="card-body py-2">
                        <div class="h4 mb-0 text-danger">{{ $totales['injustificada'] }}</div>
                        <small class="text-muted">Injustificadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm text-center border-primary">
                    <div class="card-body py-2">
                        <div class="h4 mb-0 text-primary">{{ $porcentajeGeneral !== null ? $porcentajeGeneral . '%' : '—' }}</div>
                        <small class="text-muted">Asistencia</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Detalle por estudiante
                    @if($fechaDesde || $fechaHasta)
                        <span class="text-muted small">
                            ({{ $fechaDesde?->format('d/m/Y') ?? 'Inicio' }} — {{ $fechaHasta?->format('d/m/Y') ?? 'Hoy' }})
                        </span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Estudiante</th>
                                <th class="text-center">Presentes</th>
                                <th class="text-center">Justificadas</th>
                                <th class="text-center">Injustificadas</th>
                                <th class="text-center">Faltas</th>
                                <th class="text-center">Atrasos</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">% Asistencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alumnos as $i => $alumno)
                                @php $c = $conteos->get($alumno->id, ['presente'=>0,'justificada'=>0,'injustificada'=>0,'atraso'=>0,'faltas'=>0,'total'=>0,'porcentaje'=>null]); @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $alumno->apellidos }} {{ $alumno->nombres }}</td>
                                    <td class="text-center">{{ $c['presente'] }}</td>
                                    <td class="text-center">{{ $c['justificada'] }}</td>
                                    <td class="text-center">{{ $c['injustificada'] }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $c['faltas'] > 0 ? 'bg-warning text-dark' : 'bg-light text-muted' }}">{{ $c['faltas'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $c['atraso'] > 0 ? 'bg-info' : 'bg-light text-muted' }}">{{ $c['atraso'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $c['total'] }}</td>
                                    <td class="text-center">
                                        @if($c['porcentaje'] === null)
                                            <span class="text-muted">—</span>
                                        @else
                                            @php $cls = $c['porcentaje'] >= 90 ? 'success' : ($c['porcentaje'] >= 75 ? 'warning' : 'danger'); @endphp
                                            <span class="badge bg-{{ $cls }}">{{ $c['porcentaje'] }}%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($aula)
        <div class="alert alert-info">No hay estudiantes matriculados en este curso.</div>
    @else
        <div class="alert alert-warning">No hay cursos con estudiantes matriculados.</div>
    @endif
</div>

@push('scripts')
<script>
function enviarFiltro() {
    const partes = document.getElementById('selectorAula').value.split(':');
    document.getElementById('inputCursoId').value = partes[0];
    document.getElementById('inputParaleloId').value = partes[1];
    document.getElementById('filtroAula').submit();
}
</script>
@endpush
@endsection
