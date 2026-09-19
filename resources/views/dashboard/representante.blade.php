@extends('layouts.principal')

@section('titulo', 'Mi Espacio Familiar')

@push('styles')
<style>
    .stat-card, .materia-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.5s ease backwards;
        will-change: transform;
    }
    .stat-card:hover, .materia-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .materia-card.hay-actividad {
        outline: 2px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 0 18px rgba(255, 193, 7, 0.65), 0 10px 20px rgba(0, 0, 0, 0.25);
    }
    .estrella-actividad {
        animation: pulsoEstrella 1.1s ease-in-out infinite;
        color: #ffc107;
        display: inline-block;
    }
    @keyframes pulsoEstrella {
        0%, 100% { transform: scale(1); filter: drop-shadow(0 0 2px rgba(255, 193, 7, 0.6)); }
        50%      { transform: scale(1.3); filter: drop-shadow(0 0 10px rgba(255, 193, 7, 1)); }
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-house-heart me-2"></i>Mi Espacio Familiar</h4>
            <small class="text-muted">
                {{ auth()->user()->name }} {{ auth()->user()->apellidos }}
                @if($alumno) · Representado: <strong>{{ $alumno->apellidos }} {{ $alumno->nombres }}</strong> @endif
            </small>
        </div>
        @if(count($representados) > 1)
        <div class="col-auto">
            <form method="GET" action="{{ route('dashboard') }}">
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

    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-md-4">
        <div class="col">
            <a href="{{ route('mensajes.index') }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color: #fff; animation-delay: 0.05s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-envelope fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $stats['mensajes'] ?? 0 }}</div>
                            <div class="small text-white-50">Mensajes sin leer</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('representante.agendas', ['alumno_id' => $alumno?->id]) }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color: #fff; animation-delay: 0.075s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-folder2-open fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $stats['materiales'] ?? 0 }}</div>
                            <div class="small text-white-50">Materiales disponibles</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('representante.citas', ['alumno_id' => $alumno?->id]) }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #b08a00, #ffb703); color: #fff; animation-delay: 0.1s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-calendar-event fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $stats['citas_pendientes'] ?? 0 }}</div>
                            <div class="small text-white-50">Citas pendientes</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('representante.observaciones', ['alumno_id' => $alumno?->id]) }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff; animation-delay: 0.15s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $stats['observaciones'] ?? 0 }}</div>
                            <div class="small text-white-50">Observaciones</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Agendas por materia</h5>
        <span class="badge bg-secondary">Solo lectura</span>
    </div>
    <p class="text-muted small mb-3">
        Aquí ves las tareas y actividades que los docentes han enviado. La estrella
        <i class="bi bi-star-fill estrella-actividad" style="animation: none;"></i> indica que hay actividad reciente en esa materia.
    </p>

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-md-3">
        @forelse($materias ?? [] as $materia)
        <div class="col">
            <a href="{{ route('representante.agendas', ['alumno_id' => $alumno?->id, 'materia_id' => $materia['id']]) }}"
               class="text-decoration-none">
                <div class="card materia-card text-white shadow-sm h-100 {{ $materia['hay_actividad'] ? 'hay-actividad' : '' }}"
                     style="background-color: {{ $materia['color'] }}; animation-delay: {{ $loop->index * 0.07 }}s;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <i class="bi bi-mortarboard-fill fs-4"></i>
                            @if($materia['hay_actividad'])
                                <span class="d-flex align-items-center gap-1">
                                    <i class="bi bi-star-fill estrella-actividad"></i>
                                    <span class="badge rounded-pill bg-white text-dark">{{ $materia['agendas_activas'] }}</span>
                                </span>
                            @endif
                        </div>
                        <h6 class="card-title fw-bold mb-1">{{ $materia['nombre'] }}</h6>
                        <p class="card-text small mb-0 text-white-50">
                            @if($materia['docente']) Docente: {{ $materia['docente'] }} @else Sin docente asignado @endif
                        </p>
                        <div class="mt-auto pt-3">
                            <small>
                                {{ $materia['total_agendas'] }} tarea{{ $materia['total_agendas'] != 1 ? 's' : '' }}
                                @if($materia['hay_actividad'])
                                    · <strong>{{ $materia['agendas_activas'] }} pendiente{{ $materia['agendas_activas'] != 1 ? 's' : '' }}</strong>
                                @endif
                            </small>
                            <i class="bi bi-arrow-right-circle ms-1"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                    No hay agendas registradas para tu representado.
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection