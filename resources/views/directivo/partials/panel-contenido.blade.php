{{--
    Contenido compartido de los paneles directivos.

    Qué hace: pinta el encabezado, los KPI, las alertas, los accesos rápidos y
    las circulares. Rectorado y Vicerrectorado lo reutilizan con sus propios
    datos y su propio título.
    Espera: $panel_titulo, $panel_rol, $prefijo, $kpis, $alertas, $accesos,
    $usuario y $circulares.
--}}
@push('styles')
<style>
    .stat-card, .panel-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        will-change: transform;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
    }
    .min-w-0 { min-width: 0; }
</style>
@endpush

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0">
                <i class="bi {{ $panel_rol === 'Rectorado' ? 'bi-bank' : 'bi-mortarboard' }} me-2"></i>
                {{ $panel_titulo }}
            </h4>
            <small class="text-muted">
                {{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }}
                · {{ $usuario['periodo_lectivo'] ?? '' }}
                · {{ $panel_rol }}
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($prefijo . '.rendimiento') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-graph-up-arrow me-1"></i>Rendimiento
            </a>
            <a href="{{ route($prefijo . '.estudiantes-riesgo') }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-person-exclamation me-1"></i>Estudiantes en riesgo
            </a>
            <a href="{{ route('admin.circulares.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-megaphone me-1"></i>Circulares
            </a>
        </div>
    </div>

    @include('directivo.partials.kpis', ['kpis' => $kpis])

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            @include('directivo.partials.alertas', ['alertas' => $alertas])
        </div>

        <div class="col-12 col-xl-5">
            <div class="card panel-card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Accesos rápidos</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($accesos as $acceso)
                        <div class="col-12 col-sm-6">
                            <a href="{{ $acceso['url'] }}" class="btn btn-outline-primary w-100 text-start">
                                <i class="bi {{ $acceso['icono'] }} me-2"></i>{{ $acceso['titulo'] }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @include('directivo.partials.circulares', ['circulares' => $circulares])
        </div>
    </div>
</div>
