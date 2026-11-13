@extends('layouts.principal')

@section('titulo', 'Expediente del estudiante')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-person-vcard me-2"></i>{{ $alumno->nombreCompleto() }}</h4>
            <small class="text-muted">
                Código {{ $alumno->codigo }} · Cédula {{ $alumno->numero_cedula ?? '—' }}
                @if($matricula)
                    · {{ $matricula->curso?->nombre }} {{ $matricula->paralelo?->nombre }}
                @endif
            </small>
        </div>
        <div class="d-flex gap-2">
            @if(in_array(auth()->user()->rol, ['DECE', 'ADMIN'], true))
            <a href="{{ route('dece.atenciones.create', ['alumno_id' => $alumno->id]) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Registrar atención
            </a>
            <a href="{{ route('dece.citas.create', ['alumno_id' => $alumno->id]) }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar-plus me-1"></i>Agendar cita
            </a>
            @endif
            <a href="{{ route('dece.expedientes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    @include('dece.partials.flash')

    @unless($puedeVerConfidencial)
    <div class="alert alert-info">
        <i class="bi bi-shield-lock me-2"></i>
        Está viendo el resumen del expediente. Las relatorías clínicas y los detalles confidenciales solo están disponibles para el equipo del DECE.
    </div>
    @endunless

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Promedio general</div>
                    <div class="fs-3 fw-bold">{{ $promedioGeneral !== null ? number_format($promedioGeneral, 2) : '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Asistencia</div>
                    <div class="fs-3 fw-bold">{{ $asistencia['porcentaje'] !== null ? $asistencia['porcentaje'].'%' : '—' }}</div>
                    <small class="text-muted">{{ $asistencia['asistidas'] }} de {{ $asistencia['total'] }} registros</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Faltas graves</div>
                    <div class="fs-3 fw-bold text-danger">{{ $faltasGraves }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Atenciones DECE</div>
                    <div class="fs-3 fw-bold text-primary">{{ $atenciones->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-check me-2"></i>Rendimiento académico</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Materia</th><th class="text-end">Promedio</th></tr></thead>
                        <tbody>
                            @forelse($resumenNotas as $nota)
                            <tr>
                                <td>{{ $nota['materia'] }}</td>
                                <td class="text-end">{{ $nota['anio'] !== null ? number_format($nota['anio'], 2) : '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin notas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Observaciones de inspección</div>
                <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                    @forelse($observaciones as $observacion)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $observacion->tipoObservacion?->nombre ?? 'Observación' }}</span>
                            <small class="text-muted">{{ $observacion->created_at?->format('d/m/Y') }}</small>
                        </div>
                        <div class="small text-muted">{{ $observacion->descripcion_texto }}</div>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin observaciones registradas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard2-pulse me-2"></i>Atenciones del DECE</div>
                <div class="list-group list-group-flush">
                    @forelse($atenciones as $atencion)
                    <a href="{{ route('dece.atenciones.show', $atencion) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $atencion->motivo }}</div>
                                @if($puedeVerConfidencial)
                                <small class="text-muted d-block text-truncate" style="max-width: 420px;">{{ $atencion->historia_narrada }}</small>
                                @endif
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">{{ $atencion->fecha_atencion->format('d/m/Y') }}</small>
                                @include('dece.partials.estado-badge', ['estado' => $atencion->estado, 'label' => $atencion->estadoLabel()])
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin atenciones registradas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-file-earmark-check me-2"></i>Acuerdos y compromisos</div>
                <div class="list-group list-group-flush">
                    @forelse($acuerdos as $acuerdo)
                    <a href="{{ route('dece.acuerdos.show', $acuerdo) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $acuerdo->tipoLabel() }}</div>
                                <small class="text-muted">Compromiso hasta {{ $acuerdo->fecha_compromiso->format('d/m/Y') }}</small>
                            </div>
                            @include('dece.partials.estado-badge', ['estado' => $acuerdo->estado, 'label' => $acuerdo->estadoLabel()])
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin acuerdos registrados.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-calendar2-week me-2"></i>Citas con representantes</div>
                <div class="list-group list-group-flush">
                    @forelse($citas as $cita)
                    <a href="{{ route('dece.citas.show', $cita) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $cita->motivo }}</div>
                                <small class="text-muted">{{ $cita->fecha_hora->format('d/m/Y H:i') }} · {{ $cita->representante?->nombreCompleto() ?? 'Sin representante' }}</small>
                            </div>
                            @include('dece.partials.estado-badge', ['estado' => $cita->estado, 'label' => $cita->estadoLabel()])
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin citas registradas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-universal-access me-2"></i>NEE y diagnósticos</div>
                <div class="list-group list-group-flush">
                    @forelse($nee as $registro)
                    <a href="{{ route('dece.nee.show', $registro) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $registro->diagnostico?->nombre ?? $registro->diagnostico_detalle ?? 'Sin diagnóstico' }}</div>
                                <small class="text-muted">{{ $registro->gradoLabel() }}</small>
                            </div>
                            <span class="badge {{ $registro->activo ? 'bg-success' : 'bg-secondary' }}">{{ $registro->activo ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin registros NEE.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-arrow-up-right-circle me-2"></i>Derivaciones</div>
                <div class="list-group list-group-flush">
                    @forelse($derivaciones as $derivacion)
                    <a href="{{ route('dece.derivaciones.show', $derivacion) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $derivacion->motivo }}</div>
                                <small class="text-muted">{{ $derivacion->tipoLabel() }} · {{ $derivacion->fecha_derivacion->format('d/m/Y') }}</small>
                            </div>
                            @include('dece.partials.estado-badge', ['estado' => $derivacion->estado, 'label' => $derivacion->estadoLabel()])
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin derivaciones registradas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
