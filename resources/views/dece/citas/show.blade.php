@extends('layouts.principal')

@section('titulo', 'Cita DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-calendar2-week me-2"></i>Cita #{{ $cita->id }}</h4>
            <small class="text-muted">{{ $cita->fecha_hora->format('d/m/Y H:i') }} – {{ $cita->horaFin() }} · {{ $cita->motivo }}</small>
        </div>
        <div class="d-flex gap-2">
            @if(in_array(auth()->user()->rol, ['DECE', 'ADMIN'], true))
            <a href="{{ route('dece.atenciones.create', ['alumno_id' => $cita->alumno_id]) }}" class="btn btn-primary">
                <i class="bi bi-clipboard2-plus me-1"></i>Registrar atención
            </a>
            @endif
            <a href="{{ route('dece.citas.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    @include('dece.partials.flash')

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Datos de la cita</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Estado</dt>
                        <dd class="col-7">@include('dece.partials.estado-badge', ['estado' => $cita->estado, 'label' => $cita->estadoLabel()])</dd>
                        <dt class="col-5">Estudiante</dt>
                        <dd class="col-7">{{ $cita->alumno?->nombreCompleto() }}</dd>
                        <dt class="col-5">Representante</dt>
                        <dd class="col-7">{{ $cita->representante?->nombreCompleto() ?? '—' }}</dd>
                        <dt class="col-5">Profesional</dt>
                        <dd class="col-7">{{ $cita->profesional?->nombreCompleto() ?? '—' }}</dd>
                        <dt class="col-5">Notificado</dt>
                        <dd class="col-7">{{ $cita->notificado_en?->format('d/m/Y H:i') ?? 'Sin notificar' }}</dd>
                        <dt class="col-5">Registrada por</dt>
                        <dd class="col-7">{{ $cita->creador?->nombreCompleto() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if($cita->estaProgramada())
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Registrar resultado</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dece.citas.registrar', $cita) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Resultado</label>
                            <select name="estado" class="form-select" required>
                                <option value="asistio">Asistió</option>
                                <option value="no_asistio">No asistió</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <textarea name="observaciones" rows="3" class="form-control">{{ $cita->observaciones }}</textarea>
                        </div>
                        <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Guardar resultado</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Observaciones</div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-line;">{{ $cita->observaciones ?: 'Sin observaciones.' }}</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Atenciones vinculadas</div>
                <div class="list-group list-group-flush">
                    @forelse($cita->atenciones as $atencion)
                    <a href="{{ route('dece.atenciones.show', $atencion) }}" class="list-group-item list-group-item-action">
                        {{ $atencion->fecha_atencion->format('d/m/Y H:i') }} — {{ $atencion->motivo }}
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-3">Esta cita aún no tiene una atención registrada.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
