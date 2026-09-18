@extends('layouts.principal')

@section('titulo', 'Observaciones')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-exclamation-triangle me-2"></i>Observaciones de Estudiantes</h4>

                    @if(session('success'))
                        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
                    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <label class="form-label">Clase</label>
                        <select name="clase_docente_id" class="form-select mb-3" onchange="this.form.submit()">
                            @foreach($clases as $c)
                                <option value="{{ $c->id }}" {{ $clase && $clase->id == $c->id ? 'selected' : '' }}>
                                    {{ $c->curso->nombre ?? 'N/A' }} - {{ $c->paralelo->nombre ?? 'N/A' }} - {{ $c->materia->nombre ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>

                        <label class="form-label">Estudiante</label>
                        <select name="alumno_id" class="form-select mb-3" onchange="this.form.submit()">
                            @foreach($alumnos as $al)
                                <option value="{{ $al->id }}" {{ $alumnoSeleccionado && $alumnoSeleccionado->id == $al->id ? 'selected' : '' }}>
                                    {{ $al->apellidos }} {{ $al->nombres }}
                                </option>
                            @endforeach
                        </select>

                        @if($alumnoSeleccionado)
                            <a href="{{ route('docente.observaciones.print', $alumnoSeleccionado->id) }}"
                               class="btn btn-outline-secondary btn-sm w-100" target="_blank">
                                <i class="bi bi-printer me-1"></i>Imprimir historial
                            </a>
                        @endif
                    </form>

                    <hr>

                    @if($alumnoSeleccionado)
                        <h6 class="fw-bold">Nueva Observación</h6>
                        <form method="POST" action="{{ route('docente.observaciones.store') }}">
                            @csrf
                            <input type="hidden" name="alumno_id" value="{{ $alumnoSeleccionado->id }}">

                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo_observacion_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($tiposObservacion as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion_texto" class="form-control" rows="3" maxlength="1000" required></textarea>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notificar_representante" value="1" id="notificar">
                                <label class="form-check-label" for="notificar">Notificar al representante</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-1"></i>Registrar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        Historial
                        @if($alumnoSeleccionado)
                            <span class="text-muted">- {{ $alumnoSeleccionado->apellidos }} {{ $alumnoSeleccionado->nombres }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($observaciones->isEmpty())
                        <div class="alert alert-info mb-0">No hay observaciones registradas para este estudiante.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Profesor</th>
                                        <th>Notificado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($observaciones as $obs)
                                        <tr>
                                            <td>{{ $obs->created_at->format('d/m/Y H:i') }}</td>
                                            <td><span class="badge bg-info">{{ $obs->tipoObservacion?->nombre }}</span></td>
                                            <td>{{ Str::limit($obs->descripcion_texto, 80) }}</td>
                                            <td>{{ $obs->profesor?->name }}</td>
                                            <td>
                                                @if($obs->notificar_representante)
                                                    <span class="badge bg-success">Sí</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
