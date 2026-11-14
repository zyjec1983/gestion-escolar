@extends('layouts.principal')

@section('titulo', $panel_titulo ?? 'Panel de Rectorado')

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
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-bank me-2"></i>{{ $panel_titulo ?? 'Panel de Rectorado' }}</h4>
            <small class="text-muted">
                {{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }} · {{ $usuario['periodo_lectivo'] ?? '' }}
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.pre-registros.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-clipboard-check me-1"></i>Solicitudes
            </a>
            <a href="{{ route('admin.configuracion.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear me-1"></i>Configuración
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-md-2 row-cols-xl-4">
        <div class="col">
            <a href="{{ route('actualizacion.index') }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color: #fff; animation-delay: 0.05s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-people-fill fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($kpis['estudiantes'], 0, ',', '.') }}</div>
                            <div class="small text-white-50">Estudiantes matriculados</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="card stat-card shadow-sm h-100 border-0"
                 style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color: #fff; animation-delay: 0.1s;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-person-badge-fill fs-2"></i>
                    <div>
                        <div class="fs-4 fw-bold">{{ $kpis['docentes'] }}</div>
                        <div class="small text-white-50">Docentes activos</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('admin.pre-registros.index', ['filtro' => 'pendiente']) }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm h-100 border-0"
                     style="background: linear-gradient(135deg, #7a4a00, #f59f00); color: #fff; animation-delay: 0.15s;">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-hourglass-split fs-2"></i>
                        <div>
                            <div class="fs-4 fw-bold">{{ $kpis['pendientes'] }}</div>
                            <div class="small text-white-50">Solicitudes pendientes</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="card stat-card shadow-sm h-100 border-0"
                 style="background: linear-gradient(135deg, #7a0c0c, #e63946); color: #fff; animation-delay: 0.2s;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-building-fill fs-2"></i>
                    <div>
                        <div class="fs-4 fw-bold">{{ $kpis['cursos'] }}</div>
                        <div class="small text-white-50">Cursos abiertos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card panel-card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-clipboard2-data me-2"></i>Últimas solicitudes</h6>
                    <a href="{{ route('admin.pre-registros.index') }}" class="btn btn-sm btn-outline-primary">
                        Ver todas
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($pendientes_recientes ?? [] as $solicitud)
                        <a href="{{ route('admin.pre-registros.show', $solicitud->id) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $solicitud->nombre_alumno }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ $solicitud->curso?->nombre }} {{ $solicitud->paralelo?->nombre }}
                                    · {{ $solicitud->created_at?->format('d/m/Y H:i') }}
                                </small>
                            </div>
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        </a>
                        @empty
                        <div class="list-group-item text-center text-muted py-4">
                            <i class="bi bi-check2-circle fs-3 d-block mb-2"></i>
                            No hay solicitudes pendientes
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
@endsection
