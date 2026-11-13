@extends('layouts.principal')

@section('titulo', 'Taller DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>{{ $taller->titulo }}</h4>
            <small class="text-muted">
                {{ $taller->fecha->format('d/m/Y') }} · {{ $taller->tipoLabel() }} · {{ $taller->dirigidoLabel() }}
            </small>
        </div>
        <a href="{{ route('dece.talleres.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    @include('dece.partials.flash')

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Datos del taller</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Curso</dt>
                        <dd class="col-7">{{ $taller->curso?->nombre ?? 'Todos' }}</dd>
                        <dt class="col-5">Facilitador</dt>
                        <dd class="col-7">{{ $taller->facilitador ?? '—' }}</dd>
                        <dt class="col-5">Lugar</dt>
                        <dd class="col-7">{{ $taller->lugar ?? '—' }}</dd>
                        <dt class="col-5">Registrado por</dt>
                        <dd class="col-7">{{ $taller->creador?->nombreCompleto() ?? '—' }}</dd>
                        <dt class="col-5">Asistentes</dt>
                        <dd class="col-7">{{ $taller->asistentes->count() }}</dd>
                    </dl>

                    @if($taller->descripcion)
                    <hr>
                    <p class="mb-0" style="white-space: pre-line;">{{ $taller->descripcion }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Control de asistencia</div>
                <form method="POST" action="{{ route('dece.talleres.asistencia', $taller) }}">
                    @csrf
                    <div class="card-body" style="max-height: 460px; overflow-y: auto;">
                        @forelse($candidatos as $candidato)
                            @php [$tipo, $id] = explode(':', $candidato['clave']); @endphp
                            <div class="form-check border-bottom py-2">
                                <input class="form-check-input" type="checkbox"
                                       name="{{ $tipo === 'alumno' ? 'alumno_ids[]' : 'user_ids[]' }}"
                                       value="{{ $id }}" id="candidato-{{ $tipo }}-{{ $id }}"
                                       @checked($tipo === 'alumno' ? in_array((int) $id, $asistentesAlumnos, true) : in_array((int) $id, $asistentesUsuarios, true))>
                                <label class="form-check-label" for="candidato-{{ $tipo }}-{{ $id }}">
                                    {{ $candidato['nombre'] }}
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay personas disponibles para este taller. Verifique el curso y el público objetivo.</p>
                        @endforelse
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-end">
                        <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar asistencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
