@extends('layouts.principal')

@section('titulo', 'Panel DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Panel DECE</h4>
            <small class="text-muted">Departamento de Consejería Estudiantil · {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</small>
        </div>
        @if(in_array(auth()->user()->rol, ['DECE', 'ADMIN'], true))
        <div class="d-flex gap-2">
            <a href="{{ route('dece.atenciones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Nueva atención
            </a>
            <a href="{{ route('dece.citas.create') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar-plus me-1"></i> Agendar cita
            </a>
        </div>
        @endif
    </div>

    @include('dece.partials.flash')

    <div class="row g-3 mb-4">
        @php
            $tarjetas = [
                ['label' => 'Atenciones del mes', 'valor' => $stats['atencionesMes'], 'icono' => 'clipboard2-pulse', 'color' => 'primary'],
                ['label' => 'Citas próximas', 'valor' => $stats['citasProximas'], 'icono' => 'calendar2-week', 'color' => 'info'],
                ['label' => 'Acuerdos activos', 'valor' => $stats['acuerdosActivos'], 'icono' => 'file-earmark-check', 'color' => 'success'],
                ['label' => 'NEE activas', 'valor' => $stats['neeActivos'], 'icono' => 'universal-access', 'color' => 'warning'],
            ];
        @endphp
        @foreach($tarjetas as $tarjeta)
        <div class="col-6 col-xl-3">
            <div class="card shadow-sm h-100 border-0 border-start border-4 border-{{ $tarjeta['color'] }}">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase">{{ $tarjeta['label'] }}</div>
                        <div class="fs-3 fw-bold">{{ $tarjeta['valor'] }}</div>
                    </div>
                    <i class="bi bi-{{ $tarjeta['icono'] }} fs-3 text-{{ $tarjeta['color'] }} opacity-50"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Citas esta semana</div>
                    <div class="fs-4 fw-bold">{{ $stats['citasSemana'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Acuerdos por vencer</div>
                    <div class="fs-4 fw-bold text-warning">{{ $stats['acuerdosPorVencer'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Derivaciones abiertas</div>
                    <div class="fs-4 fw-bold text-info">{{ $stats['derivacionesAbiertas'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Derivaciones vencidas</div>
                    <div class="fs-4 fw-bold text-danger">{{ $stats['derivacionesVencidas'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-calendar2-week me-2"></i>Próximas citas
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Representante</th>
                                <th>Motivo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($proximas as $cita)
                            <tr>
                                <td class="text-nowrap">{{ $cita->fecha_hora->format('d/m/Y H:i') }}</td>
                                <td>{{ $cita->alumno?->nombreCompleto() }}</td>
                                <td>{{ $cita->representante?->nombreCompleto() ?? '—' }}</td>
                                <td class="small">{{ $cita->motivo }}</td>
                                <td class="text-end">
                                    <a href="{{ route('dece.citas.show', $cita) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay citas próximas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-exclamation-triangle me-2 text-danger"></i>Alertas tempranas
                </div>
                <div class="list-group list-group-flush">
                    @forelse($alertas->take(6) as $alerta)
                    <a href="{{ route('dece.expedientes.show', $alerta['alumno']) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">{{ $alerta['alumno']->nombreCompleto() }}</div>
                                <small class="text-muted">
                                    {{ $alerta['atenciones'] }} atenciones ·
                                    {{ $alerta['observaciones'] }} faltas graves ·
                                    {{ $alerta['inasistencias'] }} inasistencias
                                </small>
                            </div>
                            <span class="badge {{ $alerta['nivel'] === 'alto' ? 'bg-danger' : ($alerta['nivel'] === 'medio' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                {{ ucfirst($alerta['nivel']) }}
                            </span>
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">
                        Sin focos rojos en el período analizado.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clock-history me-2"></i>Últimas atenciones
                </div>
                <div class="list-group list-group-flush">
                    @forelse($ultimas as $atencion)
                    <a href="{{ route('dece.atenciones.show', $atencion) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $atencion->alumno?->nombreCompleto() }}</div>
                                <small class="text-muted">{{ $atencion->motivo }}</small>
                            </div>
                            <small class="text-muted text-nowrap">{{ $atencion->fecha_atencion->format('d/m/Y') }}</small>
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Todavía no hay atenciones registradas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-bar-chart-line me-2"></i>Atenciones por mes
                </div>
                <div class="card-body">
                    <canvas id="chartAtenciones" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-file-text me-2"></i>Circulares publicadas
                </div>
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
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        No hay circulares disponibles.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-bell me-2"></i>Notificaciones
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-1">Mensajes nuevos:
                        <strong>{{ $notificaciones['mensajes_nuevos'] ?? 0 }}</strong>
                    </p>
                    <p class="text-muted small mb-3">Alertas:
                        <strong>{{ $notificaciones['alertas'] ?? 0 }}</strong>
                    </p>
                    <p class="mb-1"><strong>{{ auth()->user()->nombreCompleto() }}</strong></p>
                    <p class="text-muted small mb-0">{{ auth()->user()->rol }} · {{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const meses = @json($stats['porMes']->keys());
    const totales = @json($stats['porMes']->values());

    new Chart(document.getElementById('chartAtenciones'), {
        type: 'bar',
        data: {
            labels: meses.length ? meses : ['Sin datos'],
            datasets: [{
                label: 'Atenciones',
                data: totales.length ? totales : [0],
                backgroundColor: 'rgba(111, 66, 193, 0.65)',
                borderRadius: 6
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endsection
