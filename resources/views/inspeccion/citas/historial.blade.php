@extends('layouts.principal')

@section('titulo', 'Historial de citas')

@section('contenido')
<div class="container-fluid">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-clipboard2-data me-2"></i>Historial de citas</h4>
            <small class="text-muted">Seleccione el nivel y el paralelo para ver el listado completo de estudiantes y sus citas.</small>
        </div>
        <div class="col-auto">
            <a href="{{ route('inspeccion.citas.agendar') }}" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-1"></i>Agendar cita
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inspeccion.citas.historial') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="nivel" class="form-label">Nivel</label>
                        <select name="nivel" id="nivel" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccione nivel...</option>
                            @foreach($niveles as $n)
                                <option value="{{ $n->id }}" @selected($nivelId === $n->id)>{{ $n->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="paralelo" class="form-label">Paralelo</label>
                        <select name="paralelo" id="paralelo" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los paralelos</option>
                            @foreach($paralelos as $p)
                                <option value="{{ $p->id }}" @selected($paraleloId === $p->id)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="alumno" class="form-label">Estudiante</label>
                        <select name="alumno" id="alumno" class="form-select" onchange="this.form.submit()">
                            <option value="">Ver todos los estudiantes</option>
                            @foreach($alumnos as $a)
                                <option value="{{ $a->id }}" @selected($alumnoSel && $alumnoSel->id === $a->id)>
                                    {{ $a->apellidos }} {{ $a->nombres }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($alumnoSel)
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('inspeccion.citas.historial') }}">Historial</a></li>
                @if($nivelId)
                    <li class="breadcrumb-item">
                        <a href="{{ route('inspeccion.citas.historial', ['nivel' => $nivelId]) }}">
                            {{ $niveles->firstWhere('id', $nivelId)?->nombre }}
                        </a>
                    </li>
                @endif
                @if($paraleloId)
                    <li class="breadcrumb-item">
                        <a href="{{ route('inspeccion.citas.historial', ['nivel' => $nivelId, 'paralelo' => $paraleloId]) }}">
                            Paralelo {{ $paralelos->firstWhere('id', $paraleloId)?->nombre }}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $alumnoSel->apellidos }} {{ $alumnoSel->nombres }}</li>
            </ol>
        </nav>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted">Citas registradas</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold text-success">{{ $stats['asistio'] }}</div>
                <small class="text-muted">Asistieron</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold text-danger">{{ $stats['no_asistio'] }}</div>
                <small class="text-muted">No asistieron</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold {{ $stats['reincidentes'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $stats['reincidentes'] }}</div>
                <small class="text-muted">Reincidentes (3+ citas)</small>
            </div>
        </div>
    </div>

    @if($alumnoSel)
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-bounding-box me-1"></i>Estudiante</h6>
                    </div>
                    <div class="card-body">
                        <div class="fw-bold">{{ $alumnoSel->apellidos }} {{ $alumnoSel->nombres }}</div>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-person me-1"></i>Representante:
                            {{ $alumnoSel->representante ? nombreCorto($alumnoSel->representante->name) : 'sin usuario' }}
                        </div>

                        <h6 class="small text-uppercase text-muted">Resumen</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-primary">Total: {{ $stats['total'] }}</span>
                            <span class="badge bg-success">Asistió: {{ $stats['asistio'] }}</span>
                            <span class="badge bg-danger">No asistió: {{ $stats['no_asistio'] }}</span>
                            <span class="badge bg-secondary">Sin registrar: {{ $stats['sin_registrar'] }}</span>
                        </div>

                        <h6 class="small text-uppercase text-muted mt-3 mb-2">Asistencia de la familia citada</h6>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ $stats['asistencia'] }}%;"
                                 role="progressbar" aria-valuenow="{{ $stats['asistencia'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">{{ $stats['asistencia'] }}% asistieron de las {{ $stats['registradas'] }} citas con resultado</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Historial personal</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Citado por</th>
                                    <th>Motivo</th>
                                    <th>Resultado</th>
                                    <th>Registró</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($citas as $c)
                                    <tr>
                                        <td class="small text-nowrap">{{ $c->fecha_inicio->format('d/m/Y H:i') }}</td>
                                        <td class="small">{{ $c->docente ? nombreCorto($c->docente->name) : '—' }}</td>
                                        <td><small>{{ \Illuminate\Support\Str::limit($c->motivo, 70) }}</small></td>
                                        <td>
                                            @if($c->resultado === 'asistio')
                                                <span class="badge bg-success">Asistió</span>
                                            @elseif($c->resultado === 'no_asistio')
                                                <span class="badge bg-danger text-white">No asistió</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $c->fecha_inicio->lte(now()) ? 'Por registrar' : 'Pendiente' }}</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            @if($c->registrador)
                                                {{ nombreCorto($c->registrador->name) }}
                                                <span class="d-block">{{ $c->registrada_en?->format('d/m/Y H:i') }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Este estudiante no tiene citas registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="bi bi-people me-1"></i>Estudiantes del nivel</h6>
                <small class="text-muted">{{ $stats['estudiantes'] }} estudiantes</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Representante</th>
                            <th class="text-center">Citas</th>
                            <th class="text-center">Asistió</th>
                            <th class="text-center">No asistió</th>
                            <th class="text-center">Sin registrar</th>
                            <th>Citado por</th>
                            <th>Última cita</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porAlumno as $fila)
                            <tr>
                                <td>
                                    <strong>{{ $fila['alumno']->apellidos }} {{ $fila['alumno']->nombres }}</strong>
                                    @if($fila['reincidente'])
                                        <span class="badge bg-danger text-white ms-1">Reincidente</span>
                                    @endif
                                </td>
                                <td class="small">{{ $fila['alumno']->representante ? nombreCorto($fila['alumno']->representante->name) : '—' }}</td>
                                <td class="text-center"><span class="badge bg-primary">{{ $fila['total'] }}</span></td>
                                <td class="text-center"><span class="badge bg-success">{{ $fila['asistio'] }}</span></td>
                                <td class="text-center"><span class="badge bg-danger">{{ $fila['no_asistio'] }}</span></td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $fila['sin_registrar'] }}</span></td>
                                <td class="small">{{ $fila['citadores'] ?: '—' }}</td>
                                <td class="small text-nowrap">
                                    @if($fila['ultima_fecha'])
                                        {{ $fila['ultima_fecha']->format('d/m/Y') }}
                                        <span class="d-block text-muted">{{ \Illuminate\Support\Str::limit($fila['ultimo_motivo'], 30) }}</span>
                                    @else
                                        <span class="text-muted">Nunca citado</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inspeccion.citas.historial', [
                                        'nivel' => $nivelId,
                                        'paralelo' => $paraleloId,
                                        'alumno' => $fila['alumno']->id,
                                    ]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-person-lines-fill me-1"></i>Historial
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Seleccione un nivel y un paralelo para ver los estudiantes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
