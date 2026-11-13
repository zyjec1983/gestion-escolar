@extends('layouts.principal')

@section('titulo', 'Reportes DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Reportes del DECE</h4>
            <small class="text-muted">Indicadores agregados para la dirección institucional, sin datos clínicos individuales.</small>
        </div>
        <a href="{{ route('dece.reportes.pdf', request()->query()) }}" class="btn btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.reportes.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Curso</label>
                    <select name="curso_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" @selected($cursoId == $curso->id)>{{ $curso->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Aplicar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $indicadores = [
                ['Atenciones del período', $stats['atencionesPeriodo'], 'primary'],
                ['Citas registradas', $citasPorEstado->sum(), 'info'],
                ['Acuerdos activos', $stats['acuerdosActivos'], 'success'],
                ['Derivaciones abiertas', $stats['derivacionesAbiertas'], 'warning'],
                ['Derivaciones vencidas', $stats['derivacionesVencidas'], 'danger'],
                ['NEE activas', $stats['neeActivos'], 'secondary'],
            ];
        @endphp
        @foreach($indicadores as [$label, $valor, $color])
        <div class="col-6 col-xl-2">
            <div class="card shadow-sm h-100 border-0 border-start border-4 border-{{ $color }}">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ $label }}</div>
                    <div class="fs-4 fw-bold">{{ $valor }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Motivos de atención más frecuentes</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Motivo</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse($stats['porMotivo'] as $motivo => $total)
                            <tr><td>{{ $motivo }}</td><td class="text-end">{{ $total }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin atenciones en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Cursos con más atenciones</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Curso</th><th class="text-end">Atenciones</th></tr></thead>
                        <tbody>
                            @forelse($atencionesPorCurso as $curso => $total)
                            <tr><td>{{ $curso }}</td><td class="text-end">{{ $total }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin datos en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Citas por estado</div>
                <ul class="list-group list-group-flush">
                    @foreach($citasPorEstado as $estado => $total)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ \App\Models\DeceCita::ESTADOS[$estado] ?? $estado }}</span>
                        <span class="badge bg-secondary">{{ $total }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Derivaciones por tipo</div>
                <ul class="list-group list-group-flush">
                    @foreach($derivacionesPorTipo as $tipo => $total)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ \App\Models\DeceDerivacion::TIPOS[$tipo] ?? $tipo }}</span>
                        <span class="badge bg-secondary">{{ $total }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">NEE por categoría</div>
                <ul class="list-group list-group-flush">
                    @forelse($neePorCategoria as $categoria => $total)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="small">{{ $categoria }}</span>
                        <span class="badge bg-secondary">{{ $total }}</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">Sin registros NEE activos.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Talleres realizados en el período</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Fecha</th><th>Título</th><th>Tipo</th><th>Dirigido a</th><th class="text-end">Asistentes</th></tr>
                        </thead>
                        <tbody>
                            @forelse($talleres as $taller)
                            <tr>
                                <td class="text-nowrap">{{ $taller->fecha->format('d/m/Y') }}</td>
                                <td>{{ $taller->titulo }}</td>
                                <td>{{ $taller->tipoLabel() }}</td>
                                <td>{{ $taller->dirigidoLabel() }}</td>
                                <td class="text-end">{{ $taller->asistentes_count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Sin talleres en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
