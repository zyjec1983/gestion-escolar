@extends('layouts.principal')

@section('titulo', 'Observaciones del representado')

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Observaciones del representado</h4>
            <small class="text-muted">
                @if($alumno) Representado: <strong>{{ $alumno->apellidos }} {{ $alumno->nombres }}</strong> ·
                <span class="badge bg-secondary">Solo lectura</span> @endif
            </small>
        </div>
        @if(count($representados) > 1)
        <div class="col-auto">
            <form method="GET" action="{{ route('representante.observaciones') }}">
                <select name="alumno_id" class="form-select" onchange="this.form.submit()">
                    @foreach($representados as $r)
                        <option value="{{ $r->id }}" {{ $alumno && $alumno->id == $r->id ? 'selected' : '' }}>
                            {{ $r->apellidos }} {{ $r->nombres }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif
    </div>

    @if($observaciones->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-check2-circle fs-1 d-block mb-3"></i>
                No hay observaciones registradas para {{ $alumno?->nombres }} {{ $alumno?->apellidos }}.
            </div>
        </div>
    @else
        @foreach($observaciones as $obs)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <span class="badge bg-{{ in_array(strtolower($obs->tipoObservacion?->nombre ?? ''), ['observación de conducta', 'observacion de conducta']) ? 'danger' : 'info' }}">
                            {{ $obs->tipoObservacion?->nombre }}
                        </span>
                        @if($obs->notificar_representante)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Notificada</span>
                        @endif
                        <small class="text-muted">{{ $obs->created_at?->format('d/m/Y H:i') }}</small>
                    </div>
                    <p class="mb-0">{{ $obs->descripcion_texto }}</p>
                    <small class="text-muted">
                        @if($obs->profesor)
                            <i class="bi bi-person me-1"></i>{{ $obs->profesor->name }} {{ $obs->profesor->apellidos }}
                        @endif
                    </small>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection