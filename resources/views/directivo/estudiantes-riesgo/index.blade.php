@extends('layouts.principal')

@section('titulo', 'Estudiantes en riesgo')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-person-exclamation me-2"></i>Estudiantes en riesgo</h4>
            <small class="text-muted">
                {{ $periodo?->nombre ?? 'Sin periodo activo' }} · {{ $corte_etiqueta }}
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($prefijo . '.panel') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al panel
            </a>
            <a href="{{ route($prefijo . '.estudiantes-riesgo.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route($prefijo . '.estudiantes-riesgo.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route($prefijo . '.estudiantes-riesgo') }}" class="row g-2 align-items-end">
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
                <div class="col-6 col-md-2">
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
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Semáforo</label>
                    <select name="nivel" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="rojo" @selected($nivel === 'rojo')>Rojo</option>
                        <option value="amarillo" @selected($nivel === 'amarillo')>Amarillo</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route($prefijo . '.estudiantes-riesgo') }}" class="btn btn-outline-secondary btn-sm">
                        Limpiar filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-3">
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Nivel rojo</div>
                    <div class="fs-3 fw-bold">{{ number_format($total_rojo, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #7a4a00, #f59f00); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Nivel amarillo</div>
                    <div class="fs-3 fw-bold">{{ number_format($total_amarillo, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Total en seguimiento</div>
                    <div class="fs-3 fw-bold">{{ number_format($estudiantes->count(), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Listado de estudiantes</h6>
            <span class="badge bg-secondary">{{ $estudiantes->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Paralelo</th>
                            <th class="text-end">Promedio</th>
                            <th class="text-end">Reprobadas</th>
                            <th class="text-end">Asistencia</th>
                            <th>Semáforo</th>
                            <th>Motivos</th>
                            <th class="text-end">Ficha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estudiantes as $estudiante)
                        <tr>
                            <td>{{ $estudiante['alumno']->nombreCompleto() }}</td>
                            <td>{{ $estudiante['curso'] ?? '—' }}</td>
                            <td>{{ $estudiante['paralelo'] ?? '—' }}</td>
                            <td class="text-end">
                                {{ $estudiante['promedio'] === null ? '—' : number_format($estudiante['promedio'], 2, ',', '.') }}
                            </td>
                            <td class="text-end">{{ $estudiante['reprobadas'] }}</td>
                            <td class="text-end">
                                {{ $estudiante['asistencia'] === null ? '—' : number_format($estudiante['asistencia'], 1, ',', '.') . ' %' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $estudiante['nivel'] === 'rojo' ? 'danger' : 'warning' }} text-{{ $estudiante['nivel'] === 'rojo' ? 'white' : 'dark' }}">
                                    {{ $estudiante['nivel'] === 'rojo' ? 'Rojo' : 'Amarillo' }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ implode(' · ', $estudiante['motivos']) }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route($prefijo . '.estudiantes-riesgo.show', ['alumno' => $estudiante['alumno']->id] + request()->query()) }}"
                                   class="btn btn-sm btn-outline-primary" title="Ver ficha académica">
                                    <i class="bi bi-person-vcard"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No hay estudiantes en riesgo para los filtros elegidos
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
