@extends('layouts.principal')

@section('titulo', 'Agenda de citas DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-calendar2-week me-2"></i>Agenda de citas</h4>
            <small class="text-muted">Entrevistas con representantes legales y control de asistencia.</small>
        </div>
        <a href="{{ route('dece.citas.create') }}" class="btn btn-primary">
            <i class="bi bi-calendar-plus me-1"></i>Agendar cita
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.citas.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'citasBuscador',
                        'etiqueta' => 'Buscar',
                        'valor' => $filtros['buscar'] ?? '',
                        'valorId' => $filtros['alumno_id'] ?? '',
                        'campoId' => 'alumno_id',
                        'placeholder' => 'Estudiante o motivo',
                    ])
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach($estados as $valor => $label)
                            <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Profesional</label>
                    <select name="profesional_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($profesionales as $profesional)
                            <option value="{{ $profesional->id }}" @selected(($filtros['profesional_id'] ?? null) == $profesional->id)>{{ $profesional->nombreCompleto() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Estudiante</th>
                        <th>Representante</th>
                        <th>Motivo</th>
                        <th>Profesional</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($citas as $cita)
                    <tr>
                        <td class="text-nowrap">
                            {{ $cita->fecha_hora->format('d/m/Y') }}
                            <div class="small text-muted">{{ $cita->fecha_hora->format('H:i') }}–{{ $cita->horaFin() }}</div>
                        </td>
                        <td>{{ $cita->alumno?->nombreCompleto() }}</td>
                        <td>{{ $cita->representante?->nombreCompleto() ?? '—' }}</td>
                        <td>{{ $cita->motivo }}</td>
                        <td>{{ $cita->profesional?->nombreCompleto() ?? '—' }}</td>
                        <td>@include('dece.partials.estado-badge', ['estado' => $cita->estado, 'label' => $cita->estadoLabel()])</td>
                        <td class="text-end">
                            <a href="{{ route('dece.citas.show', $cita) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay citas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($citas->hasPages())
        <div class="card-footer bg-white">{{ $citas->links() }}</div>
        @endif
    </div>
</div>
@endsection
