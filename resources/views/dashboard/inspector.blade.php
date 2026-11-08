@extends('layouts.principal')

@section('titulo', 'Panel de Inspección')

@push('styles')
<style>
    .stat-card, .panel-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.5s ease backwards;
        will-change: transform;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('contenido')
@php
    $citasJson = $citas_hoy->map(function ($c) {
        return [
            'id' => $c->id,
            'fecha_hora' => $c->fecha_inicio->format('d/m/Y H:i'),
            'hora_fin' => $c->fecha_fin->format('H:i'),
            'estado' => $c->estado,
            'motivo' => (string) $c->motivo,
            'alumno' => $c->alumno ? trim($c->alumno->apellidos . ' ' . $c->alumno->nombres) : '—',
            'representante' => $c->alumno?->representante ? trim($c->alumno->representante->name . ' ' . $c->alumno->representante->apellidos) : '—',
            'vencida' => $c->fecha_inicio->lte(now()),
            'registrada' => $c->registrada(),
            'propia' => $c->docente_id === auth()->id(),
            'resultado' => $c->resultado,
            'dudas' => (bool) $c->dudas_aclaradas,
            'evidencia' => (string) $c->evidencia,
        ];
    })->values();
@endphp
<div class="container-fluid">
    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-person-badge me-2"></i>Panel de Inspección</h4>
            <small class="text-muted">
                {{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }} · {{ $usuario['periodo_lectivo'] ?? '' }}
            </small>
        </div>
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-md-3">
        <div class="col">
            <a href="{{ route('mensajes.index') }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color: #fff; animation-delay: 0.05s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-envelope-paper fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $mensajes_representantes }}</div>
                            <div class="small text-white-50">Mensajes de representantes sin leer</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('inspeccion.citas.historial') }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color: #fff; animation-delay: 0.1s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-calendar-event fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $citas_hoy->count() }}</div>
                            <div class="small text-white-50">Citas agendadas para hoy</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="#circulares" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff; animation-delay: 0.15s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-text fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ collect($circulares ?? [])->count() }}</div>
                            <div class="small text-white-50">Circulares publicadas</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card panel-card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Citas de hoy</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($citas_hoy as $cita)
                        <div class="list-group-item list-group-item-action cita-clickable d-flex justify-content-between align-items-center"
                             role="button" data-cita-id="{{ $cita->id }}" title="Clic para ver el detalle de la cita" style="cursor: pointer;">
                            <div>
                                <strong>{{ $cita->fecha_inicio?->format('H:i') }}</strong>
                                · {{ trim(($cita->alumno?->apellidos ?? '') . ' ' . ($cita->alumno?->nombres ?? '')) ?: 'Sin estudiante' }}
                                <br>
                                <small class="text-muted">{{ \Illuminate\Support\Str::limit(trim((string) $cita->motivo), 70) }}</small>
                            </div>
                            <span class="badge bg-secondary">{{ ucfirst($cita->estado ?? 'pendiente') }}</span>
                        </div>
                        @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                            No hay citas agendadas para hoy
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card panel-card shadow-sm h-100" id="circulares">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Circulares</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($circulares ?? [] as $circular)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $circular['titulo'] }}</strong>
                                <br>
                                <small class="text-muted">{{ $circular['fecha_publicacion'] }}</small>
                            </div>
                            <a href="{{ $circular['archivo_url'] ?? '#' }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-download"></i> PDF
                            </a>
                        </div>
                        @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No hay circulares disponibles
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('citas._modal', ['citasJson' => $citasJson, 'registrarUrlBase' => url('inspeccion/citas')])
@endsection
