@extends('layouts.principal')

@section('titulo', 'Reporte de observaciones por alumno')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-file-earmark-text me-2"></i>
                Reporte de observaciones por alumno
            </h4>
            <small class="text-muted">Filtro por nivel, paralelo, estudiante y rango de fechas. Exporta a PDF.</small>
        </div>
        <a href="{{ route('inspeccion.observaciones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Observaciones
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inspeccion.observaciones.reporte.alumno') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Nivel</label>
                        <select name="nivel" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach ($niveles as $n)
                                <option value="{{ $n->id }}" @selected($nivelId === $n->id)>{{ $n->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Paralelo</label>
                        <select name="paralelo" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach ($paralelos as $p)
                                <option value="{{ $p->id }}" @selected($paraleloId === $p->id)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estudiante</label>
                        <select name="alumno" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos (del nivel/paralelo)</option>
                            @foreach ($alumnos as $a)
                                <option value="{{ $a->id }}" @selected($alumnoId === $a->id)>{{ $a->apellidos }} {{ $a->nombres }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde ? $fechaDesde->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta ? $fechaHasta->format('Y-m-d') : '' }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end justify-content-end gap-2 flex-wrap">
                        @if ($observaciones->count() > 0)
                            <a href="{{ route('inspeccion.observaciones.reporte.alumno.pdf', request()->query()) }}" class="btn btn-danger">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
                            </a>
                        @endif
                        <a href="{{ route('inspeccion.observaciones.reporte.alumno') }}" class="btn btn-outline-secondary">Limpiar filtros</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($alumnoSel)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="mb-1">{{ $alumnoSel->apellidos }} {{ $alumnoSel->nombres }}</h6>
                <small class="text-muted">
                    @if ($alumnoSel->representante)
                        Representante: {{ nombreCorto($alumnoSel->representante->name) ?? '' }}
                    @else
                        Sin representante
                    @endif
                </small>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Observaciones ({{ $observaciones->count() }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        @if (!$alumnoSel)
                            <th>Estudiante</th>
                        @endif
                        <th>Tipo</th>
                        <th>Clasificación</th>
                        <th>Descripción</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($observaciones as $obs)
                        <tr>
                            <td class="text-nowrap small">{{ $obs->created_at->format('d/m/Y H:i') }}</td>
                            @if (!$alumnoSel)
                                <td class="small">{{ $obs->alumno->apellidos ?? '' }} {{ $obs->alumno->nombres ?? '' }}</td>
                            @endif
                            <td class="fw-semibold small">{{ $obs->tipoObservacion->nombre ?? '' }}</td>
                            <td>
                                @php $c=$obs->tipoObservacion->clasificacion ?? 'leve'; $b=match($c){'positiva'=>'bg-success','muy_grave'=>'bg-danger','grave'=>'bg-warning text-dark','leve'=>'bg-secondary',default=>'bg-secondary'}; @endphp
                                <span class="badge {{ $b }} small">{{ $obs->tipoObservacion?->etiquetaClasificacion() }}</span>
                            </td>
                            <td class="small" style="white-space: pre-wrap;">{{ $obs->descripcion_texto }}</td>
                            <td class="small text-muted">{{ nombreCorto($obs->profesor->name ?? '') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $alumnoSel ? 5 : 6 }}" class="text-center text-muted py-4">
                                No hay observaciones para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
