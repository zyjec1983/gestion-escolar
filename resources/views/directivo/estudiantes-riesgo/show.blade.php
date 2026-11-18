@extends('layouts.principal')

@section('titulo', 'Ficha académica')

@section('contenido')
<div class="container-fluid">
    @php($alumno = $ficha['alumno'] ?? $estudiante['alumno'] ?? null)

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-person-vcard me-2"></i>{{ $alumno?->nombreCompleto() ?? 'Estudiante' }}</h4>
            <small class="text-muted">
                {{ $ficha['matricula']?->curso?->nombre ?? 'Sin curso' }}
                {{ $ficha['matricula']?->paralelo?->nombre ?? '' }}
                · {{ $periodo?->nombre ?? 'Sin periodo' }} · {{ $corte_etiqueta }}
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($prefijo . '.estudiantes-riesgo', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al listado
            </a>
            <a href="{{ route($prefijo . '.estudiantes-riesgo.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF del listado
            </a>
        </div>
    </div>

    @unless($ficha)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No hay un periodo lectivo disponible para construir la ficha académica.
    </div>
    @else
    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Promedio general</div>
                    <div class="fs-3 fw-bold">
                        {{ $ficha['promedio'] === null ? 'Sin notas' : number_format($ficha['promedio'], 2, ',', '.') . ' / 10' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0b3d5c, #0dcaf0); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Asignaturas evaluadas</div>
                    <div class="fs-3 fw-bold">{{ $ficha['materias'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Asignaturas reprobadas</div>
                    <div class="fs-3 fw-bold">{{ $ficha['reprobadas'] }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color: #fff;">
                <div class="card-body">
                    <div class="small text-white-50">Asistencia efectiva</div>
                    <div class="fs-3 fw-bold">
                        {{ $ficha['asistencia']['porcentaje'] === null ? 'Sin registros' : number_format($ficha['asistencia']['porcentaje'], 1, ',', '.') . ' %' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Calificaciones por asignatura</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Asignatura</th>
                                    <th>Docente</th>
                                    <th class="text-end">Nota</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ficha['filas'] as $fila)
                                <tr>
                                    <td>{{ $fila['clase']->materia?->nombre ?? 'Sin asignatura' }}</td>
                                    <td>
                                        {{ trim(($fila['clase']->docente?->apellidos ?? '') . ' ' . ($fila['clase']->docente?->name ?? '')) ?: '—' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $fila['nota'] === null ? '—' : number_format($fila['nota'], 2, ',', '.') }}
                                    </td>
                                    <td>
                                        @if($fila['aprobado'] === null)
                                        <span class="badge bg-secondary">Sin nota</span>
                                        @elseif($fila['aprobado'])
                                        <span class="badge bg-success">Aprobado</span>
                                        @else
                                        <span class="badge bg-danger">Reprobado</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No hay calificaciones registradas para este corte
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Asistencia</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Presente</span>
                        <strong>{{ $ficha['asistencia']['presente'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Atraso</span>
                        <strong>{{ $ficha['asistencia']['atraso'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Justificada</span>
                        <strong>{{ $ficha['asistencia']['justificada'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Ausente</span>
                        <strong>{{ $ficha['asistencia']['ausente'] }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Registros</span>
                        <strong>{{ $ficha['asistencia']['total'] }}</strong>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-universal-access me-2"></i>NEE</h6>
                </div>
                <div class="card-body">
                    @if($ficha['nee'])
                    <p class="mb-2">
                        <span class="badge bg-{{ $ficha['nee']->grado_adaptacion ? 'success' : 'warning' }} text-{{ $ficha['nee']->grado_adaptacion ? 'white' : 'dark' }}">
                            {{ $ficha['nee']->grado_adaptacion ? 'Con adaptación' : 'Sin adaptación' }}
                        </span>
                    </p>
                    <p class="mb-0 small text-muted">
                        {{ $ficha['nee']->grado_adaptacion
                            ? (\App\Models\DeceNee::GRADOS_ADAPTACION[$ficha['nee']->grado_adaptacion] ?? 'Grado registrado')
                            : 'El expediente no registra grado de adaptación.' }}
                    </p>
                    @else
                    <p class="mb-0 small text-muted">Sin registro NEE activo.</p>
                    @endif
                    <p class="mb-0 mt-2 small text-muted">
                        <i class="bi bi-shield-lock me-1"></i>
                        Los detalles clínicos del expediente son confidenciales del DECE.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Últimas observaciones</h6>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($ficha['observaciones'] as $observacion)
                    <div class="list-group-item">
                        <div class="fw-semibold small">
                            {{ $observacion->tipoObservacion?->nombre ?? 'Observación' }}
                        </div>
                        <div class="small text-muted">
                            {{ \Illuminate\Support\Str::limit($observacion->descripcion ?? '', 120) }}
                        </div>
                        <div class="small text-muted">
                            {{ $observacion->created_at?->format('d/m/Y') }}
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">
                        Sin observaciones registradas
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endunless
</div>
@endsection
