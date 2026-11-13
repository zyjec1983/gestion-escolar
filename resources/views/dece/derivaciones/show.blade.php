@extends('layouts.principal')

@section('titulo', 'Derivación DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-arrow-up-right-circle me-2"></i>Derivación #{{ $derivacion->id }}</h4>
            <small class="text-muted">{{ $derivacion->tipoLabel() }} · {{ $derivacion->alumno?->nombreCompleto() }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dece.derivaciones.pdf', $derivacion) }}" class="btn btn-primary" target="_blank" rel="noopener">
                <i class="bi bi-printer me-1"></i>Imprimir / PDF
            </a>
            <a href="{{ route('dece.derivaciones.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    @include('dece.partials.flash')

    @if($derivacion->estaVencida())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-octagon me-2"></i>
        Esta derivación está vencida (límite {{ $derivacion->fecha_limite?->format('d/m/Y') }}).
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Detalle del caso</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">@include('dece.partials.estado-badge', ['estado' => $derivacion->estado, 'label' => $derivacion->estadoLabel()])</dd>
                        <dt class="col-sm-4">Estudiante</dt>
                        <dd class="col-sm-8">{{ $derivacion->alumno?->nombreCompleto() }}</dd>
                        <dt class="col-sm-4">Tipo</dt>
                        <dd class="col-sm-8">{{ $derivacion->tipoLabel() }}</dd>
                        <dt class="col-sm-4">Motivo</dt>
                        <dd class="col-sm-8">{{ $derivacion->motivo }}</dd>
                        <dt class="col-sm-4">Entidad externa</dt>
                        <dd class="col-sm-8">{{ $derivacion->entidad_externa ?? '—' }}</dd>
                        <dt class="col-sm-4">Oficio</dt>
                        <dd class="col-sm-8">{{ $derivacion->oficio ?? '—' }}</dd>
                        <dt class="col-sm-4">Fecha de derivación</dt>
                        <dd class="col-sm-8">{{ $derivacion->fecha_derivacion->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Fecha límite</dt>
                        <dd class="col-sm-8">{{ $derivacion->fecha_limite?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-sm-4">Derivado por</dt>
                        <dd class="col-sm-8">{{ $derivacion->derivador?->nombreCompleto() ?? '—' }}</dd>
                    </dl>

                    <hr>
                    <div class="small text-uppercase text-muted mb-1">Descripción</div>
                    <p class="mb-0" style="white-space: pre-line;">{{ $derivacion->descripcion }}</p>

                    @if($derivacion->respuesta)
                    <hr>
                    <div class="small text-uppercase text-muted mb-1">Respuesta / seguimiento</div>
                    <p class="mb-0" style="white-space: pre-line;">{{ $derivacion->respuesta }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if(in_array(auth()->user()->rol, ['DECE', 'ADMIN'], true))
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Actualizar seguimiento</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dece.derivaciones.update', $derivacion) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" required>
                                @foreach($estados as $valor => $label)
                                    <option value="{{ $valor }}" @selected($derivacion->estado === $valor)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha límite</label>
                            <input type="date" name="fecha_limite"
                                   value="{{ $derivacion->fecha_limite?->format('Y-m-d') }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Respuesta / seguimiento</label>
                            <textarea name="respuesta" rows="5" class="form-control">{{ $derivacion->respuesta }}</textarea>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Guardar seguimiento</button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
