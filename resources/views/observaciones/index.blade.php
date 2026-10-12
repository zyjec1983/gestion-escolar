@extends('layouts.principal')

@section('titulo', 'Observaciones')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-exclamation-triangle me-2"></i>Observaciones de Estudiantes</h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    @if(session('warning'))
        <div id="swal-warning" data-message="{{ session('warning') }}" hidden></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route($rutaBase . '.index') }}" class="mb-3">
                        @if($selector === 'clase')
                            <label class="form-label">Clase</label>
                            <select name="clase_docente_id" class="form-select mb-3" onchange="this.form.submit()">
                                @foreach($clases as $c)
                                    <option value="{{ $c->id }}" @selected($clase && $clase->id === $c->id)>
                                        {{ $c->curso->nombre ?? 'N/A' }} - {{ $c->paralelo->nombre ?? 'N/A' }} - {{ $c->materia->nombre ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <label for="curso_id" class="form-label">Curso / Nivel</label>
                            <select name="curso_id" id="curso_id" class="form-select mb-3" onchange="this.form.submit()">
                                <option value="">Todos los cursos</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}" @selected((string) $curso->id === (string) $cursoId)>
                                        {{ $curso->nombre }}
                                    </option>
                                @endforeach
                            </select>

                            <label for="paralelo_id" class="form-label">Paralelo</label>
                            <select name="paralelo_id" id="paralelo_id" class="form-select mb-3" onchange="this.form.submit()">
                                <option value="">Todos los paralelos</option>
                                @foreach($paralelos as $paralelo)
                                    <option value="{{ $paralelo->id }}" @selected((string) $paralelo->id === (string) $paraleloId)>
                                        {{ $paralelo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <label class="form-label">Estudiante</label>
                        <select name="alumno_id" class="form-select mb-3" onchange="this.form.submit()">
                            <option value="">Seleccionar estudiante...</option>
                            @foreach($alumnos as $al)
                                <option value="{{ $al->id }}" @selected($alumnoSeleccionado && $alumnoSeleccionado->id === $al->id)>
                                    {{ $al->apellidos }} {{ $al->nombres }}
                                    @if($selector !== 'clase' && $al->representante)
                                        (Rep: {{ nombreCorto($al->representante->name) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @if($alumnoSeleccionado)
                            <a href="{{ route($rutaBase . '.print', $alumnoSeleccionado->id) }}"
                               class="btn btn-outline-secondary btn-sm w-100" target="_blank">
                                <i class="bi bi-printer me-1"></i>Imprimir historial
                            </a>
                        @endif
                    </form>

                    <hr>

                    @if($alumnoSeleccionado)
                        <h6 class="fw-bold">Nueva Observación</h6>
                        <form method="POST" action="{{ route($rutaBase . '.store') }}">
                            @csrf
                            @if($selector === 'clase')
                                <input type="hidden" name="clase_docente_id" value="{{ $clase?->id }}">
                            @else
                                <input type="hidden" name="curso_id" value="{{ $cursoId }}">
                                <input type="hidden" name="paralelo_id" value="{{ $paraleloId }}">
                            @endif
                            <input type="hidden" name="alumno_id" value="{{ $alumnoSeleccionado->id }}">

                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo_observacion_id" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($tiposObservacion as $tipo)
                                        <option value="{{ $tipo->id }}"
                                            @selected($notificarObligatorio && $tipo->codigo === 'OBS_CONDUCTA')>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion_texto" class="form-control" rows="3" maxlength="1000" required></textarea>
                            </div>

                            @if($notificarObligatorio)
                                <input type="hidden" name="notificar_representante" value="1">
                                <div class="form-text mb-3">
                                    <i class="bi bi-bell me-1"></i>El representante de
                                    {{ $alumnoSeleccionado->apellidos }} {{ $alumnoSeleccionado->nombres }}
                                    será notificado siempre que se registre la observación.
                                </div>
                            @else
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="notificar_representante" value="1" id="notificar">
                                    <label class="form-check-label" for="notificar">Notificar al representante</label>
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-1"></i>Registrar
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info mb-0">
                            Selecciona un estudiante para registrar una observación.
                        </div>
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
                    @if(!$alumnoSeleccionado)
                        <div class="alert alert-info mb-0">Selecciona un estudiante para ver su historial.</div>
                    @elseif($observaciones->isEmpty())
                        <div class="alert alert-info mb-0">No hay observaciones registradas para este estudiante.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Registrado por</th>
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