@extends('layouts.principal')

@section('titulo', 'Ficha de atención')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Ficha de atención #{{ $atencion->id }}</h4>
            <small class="text-muted">{{ $atencion->fecha_atencion->format('d/m/Y H:i') }} · {{ $atencion->motivo }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dece.atenciones.pdf', $atencion) }}" class="btn btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            @if(!$atencion->estaCerrada())
            <form method="POST" action="{{ route('dece.atenciones.cerrar', $atencion) }}">
                @csrf
                <button class="btn btn-outline-success" onclick="return confirm('¿Cerrar esta atención?')">
                    <i class="bi bi-check2-circle me-1"></i>Cerrar atención
                </button>
            </form>
            @endif
            <a href="{{ route('dece.atenciones.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    @include('dece.partials.flash')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Estudiante</div>
                <div class="card-body">
                    <div class="fw-bold">{{ $atencion->alumno?->nombreCompleto() }}</div>
                    <div class="small text-muted">Código {{ $atencion->alumno?->codigo }}</div>
                    <div class="small text-muted">Representante: {{ $atencion->alumno?->representante?->nombreCompleto() ?? '—' }}</div>
                    <hr>
                    <div class="small"><span class="text-muted">Profesional:</span> {{ $atencion->profesional?->nombreCompleto() ?? '—' }}</div>
                    <div class="small mt-1">
                        <span class="text-muted">Estado:</span>
                        @include('dece.partials.estado-badge', ['estado' => $atencion->estado, 'label' => $atencion->estadoLabel()])
                    </div>
                    @if($atencion->cita)
                    <div class="small mt-2">
                        <span class="text-muted">Cita:</span>
                        <a href="{{ route('dece.citas.show', $atencion->cita) }}">{{ $atencion->cita->fecha_hora->format('d/m/Y H:i') }}</a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Evidencias</div>
                <div class="list-group list-group-flush">
                    @forelse($atencion->archivos as $archivo)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="text-truncate me-2">
                            <i class="bi bi-paperclip me-1"></i>{{ $archivo->nombre_original }}
                            <div class="small text-muted">{{ $archivo->tamanoLegible() }}</div>
                        </div>
                        <a href="{{ $archivo->url() }}" target="_blank" class="btn btn-sm btn-outline-primary">Abrir</a>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin evidencias adjuntas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text me-2"></i>Relatoría</div>
                <div class="card-body">
                    @if($puedeVerConfidencial)
                        <p class="mb-0" style="white-space: pre-line;">{{ $atencion->historia_narrada }}</p>
                    @else
                        <p class="text-muted mb-0"><i class="bi bi-shield-lock me-1"></i>Contenido confidencial reservado al equipo del DECE.</p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-check2-square me-2"></i>Acuerdos y recomendaciones</div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-line;">{{ $atencion->acuerdos ?: 'Sin acuerdos registrados.' }}</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-file-earmark-check me-2"></i>Acuerdos formales</div>
                <div class="list-group list-group-flush">
                    @forelse($atencion->acuerdosFormales as $acuerdo)
                    <a href="{{ route('dece.acuerdos.show', $acuerdo) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>{{ $acuerdo->tipoLabel() }} — {{ \Illuminate\Support\Str::limit($acuerdo->descripcion, 90) }}</span>
                        @include('dece.partials.estado-badge', ['estado' => $acuerdo->estado, 'label' => $acuerdo->estadoLabel()])
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin acuerdos formales asociados.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
