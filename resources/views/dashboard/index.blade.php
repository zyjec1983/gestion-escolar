{{-- 
  Vista Dashboard Principal.
  Renderiza tarjetas resumen, circulares y notificaciones 
  usando la estructura JSON definida en DashboardResource.
--}}
@extends('layouts.principal')

@section('titulo', 'Dashboard')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
            <small class="text-muted">{{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }} · {{ $usuario['periodo_lectivo'] ?? '' }}</small>
        </div>
        
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-md-2 row-cols-xl-4">
        <div class="col">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Mensajes</h6>
                            <h2 class="mb-0 text-white">{{ $tarjetas_resumen['mensajes']['contador'] }}</h2>
                        </div>
                        <i class="bi bi-envelope fs-1 opacity-50"></i>
                    </div>
                    <a href="{{ $tarjetas_resumen['mensajes']['url_accion'] }}" class="text-white small">Ver todos →</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Clases</h6>
                            <h2 class="mb-0 text-white">{{ $tarjetas_resumen['clases']['contador'] ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-book fs-1 opacity-50"></i>
                    </div>
                    <a href="{{ $tarjetas_resumen['clases']['url_accion'] }}" class="text-white small">Ver clases →</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Agendas</h6>
                            <h2 class="mb-0 text-white">{{ $tarjetas_resumen['agendas_activas']['contador'] ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                    </div>
                    <a href="{{ $tarjetas_resumen['agendas_activas']['url_accion'] }}" class="text-white small">Ver agenda →</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white bg-danger shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Observaciones</h6>
                            <h2 class="mb-0 text-white">{{ $tarjetas_resumen['observaciones']['contador'] ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                    </div>
                    <a href="{{ $tarjetas_resumen['observaciones']['url_accion'] }}" class="text-white small">Ver todas →</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
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
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Notificaciones</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-1">Mensajes nuevos: 
                        <strong>{{ $usuario['notificaciones_pendientes']['mensajes_nuevos'] ?? 0 }}</strong>
                    </p>
                    <p class="text-muted small mb-0">Alertas: 
                        <strong>{{ $usuario['notificaciones_pendientes']['alertas'] ?? 0 }}</strong>
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Perfil</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }}</strong></p>
                    <p class="text-muted small mb-0">{{ $usuario['rol'] ?? '' }}</p>
                    <p class="text-muted small">{{ $usuario['email'] ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
