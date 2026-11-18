{{--
    Tarjetas KPI del panel directivo.

    Qué hace: pinta los indicadores que entrega IndicadoresInstitucionales o
    IndicadoresAcademicos, con enlace opcional al módulo de detalle.
    Espera: $kpis (array de etiqueta, valor, detalle, icono, color, url).
--}}
@php
    $gradientes = [
        'primary' => 'linear-gradient(135deg, #1a1a2e, #4361ee)',
        'success' => 'linear-gradient(135deg, #0b3d2e, #2d6a4f)',
        'warning' => 'linear-gradient(135deg, #7a4a00, #f59f00)',
        'danger' => 'linear-gradient(135deg, #7a0c0c, #e63946)',
        'info' => 'linear-gradient(135deg, #0b3d5c, #0dcaf0)',
        'secondary' => 'linear-gradient(135deg, #343a40, #6c757d)',
        'dark' => 'linear-gradient(135deg, #111111, #343a40)',
    ];
@endphp

<div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
    @foreach($kpis as $kpi)
    <div class="col">
        @if(! empty($kpi['url']))
        <a href="{{ $kpi['url'] }}" class="text-decoration-none d-block h-100">
        @endif
            <div class="card stat-card shadow-sm h-100 border-0"
                 style="background: {{ $gradientes[$kpi['color']] ?? $gradientes['primary'] }}; color: #fff;">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi {{ $kpi['icono'] }} fs-2"></i>
                    <div class="min-w-0">
                        <div class="fs-4 fw-bold lh-1">{{ $kpi['valor'] }}</div>
                        <div class="small text-white-50 text-truncate">{{ $kpi['etiqueta'] }}</div>
                        @if(! empty($kpi['detalle']))
                        <div class="small text-white-50 text-truncate">{{ $kpi['detalle'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @if(! empty($kpi['url']))
        </a>
        @endif
    </div>
    @endforeach
</div>
