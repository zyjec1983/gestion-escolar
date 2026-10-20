@extends('layouts.principal')

@section('titulo', 'Estadísticas de Observaciones')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-bar-chart-line me-2"></i>Estadísticas de Observaciones</h4>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inspeccion.observaciones.estadisticas') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="nivel" class="form-label">Curso / Nivel</label>
                    <select name="nivel" id="nivel" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los cursos</option>
                        @foreach($niveles as $curso)
                            <option value="{{ $curso->id }}" @selected($nivelId === $curso->id)>{{ $curso->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="paralelo" class="form-label">Paralelo</label>
                    <select name="paralelo" id="paralelo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los paralelos</option>
                        @foreach($paralelos as $paralelo)
                            <option value="{{ $paralelo->id }}" @selected($paraleloId === $paralelo->id)>{{ $paralelo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ $fechaDesde?->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ $fechaHasta?->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-primary">
                <div class="card-body">
                    <div class="display-6 text-primary">{{ $totalObservaciones }}</div>
                    <div class="text-muted">Observaciones registradas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-success">
                <div class="card-body">
                    <div class="display-6 text-success">{{ $porTipo->sum(fn ($t) => $t->clasificacion === 'positiva' ? $t->total : 0) }}</div>
                    <div class="text-muted">Positivas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-warning">
                <div class="card-body">
                    <div class="display-6 text-warning">{{ $porTipo->sum(fn ($t) => in_array($t->clasificacion, ['leve', 'grave']) ? $t->total : 0) }}</div>
                    <div class="text-muted">Leves / Graves</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center border-danger">
                <div class="card-body">
                    <div class="display-6 text-danger">{{ $porTipo->sum(fn ($t) => $t->clasificacion === 'muy_grave' ? $t->total : 0) }}</div>
                    <div class="text-muted">Muy Graves</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Observaciones por tipo (curso completo)</h6>
                </div>
                <div class="card-body">
                    @if($porTipo->isEmpty())
                        <div class="alert alert-info mb-0">Sin observaciones para el filtro seleccionado.</div>
                    @else
                        <canvas id="chartTipo" height="300"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Docentes que más registran observaciones</h6>
                </div>
                <div class="card-body">
                    @if($porDocente->isEmpty())
                        <div class="alert alert-info mb-0">Sin observaciones para el filtro seleccionado.</div>
                    @else
                        <canvas id="chartDocente" height="300"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endonce
<script>
document.addEventListener('DOMContentLoaded', function () {
    const colores = @json($colores);

    @if($porTipo->isNotEmpty())
    new Chart(document.getElementById('chartTipo'), {
        type: 'bar',
        data: {
            labels: @json($porTipo->pluck('tipo_nombre')),
            datasets: [{
                label: 'Observaciones',
                data: @json($porTipo->pluck('total')),
                backgroundColor: @json($porTipo->map(fn ($t) => $colores[$t->clasificacion] ?? '#6c757d')),
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    @endif

    @if($porDocente->isNotEmpty())
    new Chart(document.getElementById('chartDocente'), {
        type: 'bar',
        data: {
            labels: @json($porDocente->map(fn ($d) => trim($d->apellidos . ' ' . $d->name))),
            datasets: [{
                label: 'Observaciones',
                data: @json($porDocente->pluck('total')),
                backgroundColor: '#0d6efd',
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
    @endif
});
</script>
@endpush
@endsection
