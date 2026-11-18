@extends('layouts.principal')

@section('titulo', 'Rendimiento académico')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Rendimiento académico</h4>
            <small class="text-muted">
                {{ $periodo?->nombre ?? 'Sin periodo activo' }} · {{ $corte_etiqueta }}
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($prefijo . '.panel') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al panel
            </a>
            <a href="{{ route($prefijo . '.rendimiento.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route($prefijo . '.rendimiento.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route($prefijo . '.rendimiento') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Periodo lectivo</label>
                    <select name="periodo_id" class="form-select form-select-sm">
                        @foreach($periodos as $opcion)
                        <option value="{{ $opcion->id }}" @selected($periodo?->id === $opcion->id)>
                            {{ $opcion->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Corte</label>
                    <select name="corte" class="form-select form-select-sm">
                        @foreach($cortes as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($corte === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Curso</label>
                    <select name="curso_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($cursos as $curso)
                        <option value="{{ $curso->id }}" @selected((int) $filtros['curso_id'] === $curso->id)>
                            {{ $curso->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Paralelo</label>
                    <select name="paralelo_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($paralelos as $paralelo)
                        <option value="{{ $paralelo->id }}" @selected((int) $filtros['paralelo_id'] === $paralelo->id)>
                            {{ $paralelo->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Asignatura</label>
                    <select name="materia_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach($materias as $materia)
                        <option value="{{ $materia->id }}" @selected((int) $filtros['materia_id'] === $materia->id)>
                            {{ $materia->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route($prefijo . '.rendimiento') }}" class="btn btn-outline-secondary btn-sm">
                        Limpiar filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Promedio general</div>
                    <div class="fs-3 fw-bold">
                        {{ $promedio === null ? 'Sin notas' : number_format($promedio, 2, ',', '.') . ' / 10' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Notas evaluadas</div>
                    <div class="fs-3 fw-bold">{{ number_format($evaluados, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0b3d5c, #0dcaf0); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Aprobados</div>
                    <div class="fs-3 fw-bold">{{ number_format($aprobados, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Reprobados</div>
                    <div class="fs-3 fw-bold">{{ number_format($reprobados, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Distribución de notas</h6>
        </div>
        <div class="card-body">
            @foreach($distribucion as $rango)
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span><strong>{{ $rango['etiqueta'] }}</strong> · {{ $rango['rango'] }}</span>
                    <span>{{ $rango['total'] }} ({{ number_format($rango['porcentaje'], 1, ',', '.') }} %)</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-{{ $rango['color'] }}" role="progressbar"
                         style="width: {{ $rango['porcentaje'] }}%"
                         aria-valuenow="{{ $rango['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <ul class="nav nav-tabs" id="tabsRendimiento" role="tablist">
        @foreach([
            'niveles' => 'Por nivel',
            'cursos' => 'Por curso',
            'paralelos' => 'Por paralelo',
            'materias' => 'Por asignatura',
            'estudiantes' => 'Por estudiante',
        ] as $clave => $etiqueta)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $clave }}"
                    data-bs-toggle="tab" data-bs-target="#panel-{{ $clave }}" type="button" role="tab">
                {{ $etiqueta }}
            </button>
        </li>
        @endforeach
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white">
        @foreach([
            'niveles' => $resumen_niveles,
            'cursos' => $resumen_cursos,
            'paralelos' => $resumen_paralelos,
            'materias' => $resumen_materias,
        ] as $clave => $resumen)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="panel-{{ $clave }}" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ $clave === 'materias' ? 'Asignatura' : 'Grupo' }}</th>
                            <th class="text-end">Evaluados</th>
                            <th class="text-end">Promedio</th>
                            <th class="text-end">Aprobados</th>
                            <th class="text-end">Reprobados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumen as $fila)
                        <tr>
                            <td>{{ $fila['etiqueta'] }}</td>
                            <td class="text-end">{{ $fila['evaluados'] }}</td>
                            <td class="text-end">
                                {{ $fila['promedio'] === null ? '—' : number_format($fila['promedio'], 2, ',', '.') }}
                            </td>
                            <td class="text-end">{{ $fila['aprobados'] }}</td>
                            <td class="text-end">{{ $fila['reprobados'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Sin datos para los filtros elegidos</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach

        <div class="tab-pane fade" id="panel-estudiantes" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Paralelo</th>
                            <th class="text-end">Promedio</th>
                            <th class="text-end">Materias</th>
                            <th class="text-end">Reprobadas</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alumnos as $fila)
                        <tr>
                            <td>{{ $fila['alumno']->apellidos }} {{ $fila['alumno']->nombres }}</td>
                            <td>{{ $fila['curso'] ?? '—' }}</td>
                            <td>{{ $fila['paralelo'] ?? '—' }}</td>
                            <td class="text-end">
                                {{ $fila['promedio'] === null ? '—' : number_format($fila['promedio'], 2, ',', '.') }}
                            </td>
                            <td class="text-end">{{ $fila['materias'] }}</td>
                            <td class="text-end">{{ $fila['reprobadas'] }}</td>
                            <td>
                                <span class="badge bg-{{ $fila['estado'] === 'Aprobado' ? 'success' : ($fila['estado'] === 'Reprobado' ? 'danger' : 'secondary') }}">
                                    {{ $fila['estado'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Sin datos para los filtros elegidos</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
