@extends('layouts.principal')

@section('titulo', 'Registro NEE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-universal-access me-2"></i>{{ $nee->nombreEstudiante() }}</h4>
            <small class="text-muted">{{ $nee->procedenciaLabel() }} · {{ $nee->gradoLabel() }}</small>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('dece.nee.toggle', $nee) }}">
                @csrf
                <button class="btn btn-outline-{{ $nee->activo ? 'warning' : 'success' }}">
                    <i class="bi bi-power me-1"></i>{{ $nee->activo ? 'Desactivar' : 'Reactivar' }}
                </button>
            </form>
            <a href="{{ route('dece.nee.edit', $nee) }}" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Editar</a>
            <a href="{{ route('dece.nee.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    @include('dece.partials.flash')

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Datos del registro</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Procedencia</dt>
                        <dd class="col-sm-8">@include('dece.partials.estado-badge', ['estado' => $nee->procedencia, 'label' => $nee->procedenciaLabel()])</dd>
                        <dt class="col-sm-4">Estudiante</dt>
                        <dd class="col-sm-8">{{ $nee->nombreEstudiante() }}</dd>
                        @if($nee->curso_externo)
                        <dt class="col-sm-4">Curso de referencia</dt>
                        <dd class="col-sm-8">{{ $nee->curso_externo }}</dd>
                        @endif
                        <dt class="col-sm-4">Diagnóstico</dt>
                        <dd class="col-sm-8">{{ $nee->diagnostico?->etiqueta() ?? $nee->diagnostico_detalle ?? '—' }}</dd>
                        <dt class="col-sm-4">Grado de adaptación</dt>
                        <dd class="col-sm-8">{{ $nee->gradoLabel() }}</dd>
                        <dt class="col-sm-4">Profesional evaluador</dt>
                        <dd class="col-sm-8">{{ $nee->profesional_evaluador ?? '—' }}</dd>
                        <dt class="col-sm-4">Fecha de evaluación</dt>
                        <dd class="col-sm-8">{{ $nee->fecha_evaluacion?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-sm-4">Informe requerido</dt>
                        <dd class="col-sm-8">{{ $nee->requiere_informe ? 'Sí' : 'No' }}</dd>
                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8"><span class="badge {{ $nee->activo ? 'bg-success' : 'bg-secondary' }}">{{ $nee->activo ? 'Activo' : 'Inactivo' }}</span></dd>
                        <dt class="col-sm-4">Registrado por</dt>
                        <dd class="col-sm-8">{{ $nee->creador?->nombreCompleto() ?? '—' }}</dd>
                    </dl>

                    @if($nee->observaciones)
                    <hr>
                    <div class="small text-uppercase text-muted mb-1">Observaciones</div>
                    <p class="mb-0" style="white-space: pre-line;">{{ $nee->observaciones }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Informes y evidencias</div>
                <div class="list-group list-group-flush">
                    @forelse($nee->archivos as $archivo)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="text-truncate me-2">
                            <i class="bi bi-paperclip me-1"></i>{{ $archivo->nombre_original }}
                            <div class="small text-muted">{{ $archivo->tamanoLegible() }}</div>
                        </div>
                        <a href="{{ $archivo->url() }}" target="_blank" class="btn btn-sm btn-outline-primary">Abrir</a>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Sin informes adjuntos.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
