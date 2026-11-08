@extends('layouts.principal')

@section('titulo', 'Agendar cita')

@section('contenido')
<div class="container-fluid">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Agendar cita a representante</h4>
            <small class="text-muted">
                Puede citar a cualquier estudiante del centro. La cita queda registrada a nombre de
                <strong>{{ trim((auth()->user()->name ?? '') . ' ' . (auth()->user()->apellidos ?? '')) }}</strong>.
            </small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">1. Buscar estudiante</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('inspeccion.citas.agendar') }}">
                        <div class="mb-3">
                            <label for="curso_id" class="form-label">Curso / Nivel</label>
                            <select name="curso_id" id="curso_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Todos los cursos</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}" @selected((string) $curso->id === (string) $cursoId)>
                                        {{ $curso->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="paralelo_id" class="form-label">Paralelo</label>
                            <select name="paralelo_id" id="paralelo_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Todos los paralelos</option>
                                @foreach($paralelos as $paralelo)
                                    <option value="{{ $paralelo->id }}" @selected((string) $paralelo->id === (string) $paraleloId)>
                                        {{ $paralelo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="alumno_id" class="form-label">Estudiante</label>
                            <select name="alumno_id" id="alumno_id" class="form-select" required>
                                <option value="">Seleccionar estudiante...</option>
                                @foreach($alumnos as $al)
                                    <option value="{{ $al->id }}" @selected($alumno && $al->id === $alumno->id)>
                                        {{ $al->apellidos }} {{ $al->nombres }}
                                        @if($al->representante) (Rep: {{ nombreCorto($al->representante->name) }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search me-1"></i>Ver historial del estudiante
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            @if($alumno)
                @php
                    $matricula = $alumno->matriculas->last();
                    $nivel = $matricula
                        ? trim($matricula->curso?->nombre . ' ' . $matricula->paralelo?->nombre)
                        : '—';
                    $asistio = $citasPrevias->where('resultado', 'asistio')->count();
                    $noAsistio = $citasPrevias->where('resultado', 'no_asistio')->count();
                    $sinRegistro = $citasPrevias->whereNull('resultado')->count();
                @endphp

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-bounding-box me-1"></i>Estudiante seleccionado</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <div class="fw-bold">{{ $alumno->apellidos }} {{ $alumno->nombres }}</div>
                                <div class="text-muted small">
                                    <i class="bi bi-mortarboard me-1"></i>{{ $nivel }}
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-person me-1"></i>Representante:
                                    @if($alumno->representante)
                                        {{ nombreCorto($alumno->representante->name) }}
                                    @else
                                        <span class="text-danger">sin usuario vinculado</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-primary">Citas: {{ $citasPrevias->count() }}</span>
                                    <span class="badge bg-success">Asistió: {{ $asistio }}</span>
                                    <span class="badge bg-danger">No asistió: {{ $noAsistio }}</span>
                                    <span class="badge bg-secondary">Sin registrar: {{ $sinRegistro }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">2. Datos de la cita</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('inspeccion.citas.store') }}">
                            @csrf
                            <input type="hidden" name="alumno_id" value="{{ $alumno->id }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="fecha" class="form-label">Fecha</label>
                                    <input type="date" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror"
                                           min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" value="{{ old('fecha') }}" required>
                                    @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="hora" class="form-label">Hora</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <select name="hora" id="hora" class="form-select @error('hora') is-invalid @enderror" required>
                                                <option value="">HH</option>
                                                @for($h = 7; $h <= 18; $h++)
                                                    <option value="{{ $h }}" @selected((int) old('hora') === $h)>{{ sprintf('%02d', $h) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select name="minuto" class="form-select @error('minuto') is-invalid @enderror" required>
                                                <option value="">MM</option>
                                                @foreach([0, 15, 30, 45] as $m)
                                                    <option value="{{ $m }}" @selected((int) old('minuto') === $m)>{{ sprintf('%02d', $m) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @error('hora') <div class="text-danger small">{{ $message }}</div> @enderror
                                    @error('minuto') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-12">
                                    <label for="motivo" class="form-label">Motivo</label>
                                    <select name="motivo" id="motivo" class="form-select @error('motivo') is-invalid @enderror" required>
                                        <option value="">Seleccionar...</option>
                                        @foreach(\App\Http\Controllers\Inspeccion\InspectorCitaController::MOTIVOS as $m)
                                            <option value="{{ $m }}" @selected(old('motivo') === $m)>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                    @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-12">
                                    <label for="mensaje" class="form-label">Mensaje (opcional)</label>
                                    <textarea name="mensaje" id="mensaje" class="form-control" rows="2" maxlength="500">{{ old('mensaje') }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-calendar-plus me-1"></i>Agendar cita
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Citas anteriores de este estudiante</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Citado por</th>
                                    <th>Motivo</th>
                                    <th>Resultado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($citasPrevias as $c)
                                    <tr>
                                        <td class="small text-nowrap">{{ $c->fecha_inicio->format('d/m/Y H:i') }}</td>
                                        <td class="small">
                                            @if($c->docente)
                                                {{ nombreCorto($c->docente->name) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><small>{{ \Illuminate\Support\Str::limit($c->motivo, 60) }}</small></td>
                                        <td>
                                            @if($c->resultado === 'asistio')
                                                <span class="badge bg-success">Asistió</span>
                                            @elseif($c->resultado === 'no_asistio')
                                                <span class="badge bg-danger text-white">No asistió</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $c->fecha_inicio->lte(now()) ? 'Por registrar' : 'Pendiente' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Este estudiante no tiene citas registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inboxes fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Seleccione un curso, paralelo y estudiante para ver su historial y agendar la cita.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
