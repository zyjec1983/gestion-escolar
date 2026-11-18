{{--
    Alertas del panel directivo.

    Qué hace: muestra las novedades que requieren atención con su severidad y
    un enlace al módulo que las resuelve.
    Espera: $alertas (array de titulo, detalle, icono, severidad, url).
--}}
@php
    $colores = [
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'success' => 'success',
    ];
@endphp

<div class="card shadow-sm h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Alertas y novedades</h6>
        <span class="badge bg-secondary">{{ count($alertas) }}</span>
    </div>
    <div class="list-group list-group-flush">
        @forelse($alertas as $alerta)
        <div class="list-group-item d-flex gap-3 align-items-start">
            <span class="badge bg-{{ $colores[$alerta['severidad']] ?? 'secondary' }} rounded-pill mt-1">
                <i class="bi {{ $alerta['icono'] }}"></i>
            </span>
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $alerta['titulo'] }}</div>
                <div class="small text-muted">{{ $alerta['detalle'] }}</div>
            </div>
            @if(! empty($alerta['url']))
            <a href="{{ $alerta['url'] }}" class="btn btn-sm btn-outline-secondary" title="Ir al módulo">
                <i class="bi bi-arrow-right"></i>
            </a>
            @endif
        </div>
        @empty
        <div class="list-group-item text-center text-muted py-4">
            <i class="bi bi-check2-circle fs-3 d-block mb-2"></i>
            Sin alertas pendientes
        </div>
        @endforelse
    </div>
</div>
