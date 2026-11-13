@extends('layouts.principal')

@section('titulo', 'Atenciones DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Atenciones individuales</h4>
            <small class="text-muted">Registro confidencial de entrevistas y seguimientos del DECE.</small>
        </div>
        <a href="{{ route('dece.atenciones.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nueva atención
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.atenciones.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'atencionesBuscador',
                        'etiqueta' => 'Buscar',
                        'valor' => $filtros['buscar'] ?? '',
                        'valorId' => $filtros['alumno_id'] ?? '',
                        'campoId' => 'alumno_id',
                        'placeholder' => 'Estudiante o relatoría',
                    ])
                </div>
                <div class="col-md-3">
                    <label class="form-label">Motivo</label>
                    <select name="motivo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($motivos as $motivo)
                            <option value="{{ $motivo }}" @selected(($filtros['motivo'] ?? '') === $motivo)>{{ $motivo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="abierta" @selected(($filtros['estado'] ?? '') === 'abierta')>Abierta</option>
                        <option value="cerrada" @selected(($filtros['estado'] ?? '') === 'cerrada')>Cerrada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Motivo</th>
                        <th>Profesional</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atenciones as $atencion)
                    <tr>
                        <td class="text-nowrap">{{ $atencion->fecha_atencion->format('d/m/Y H:i') }}</td>
                        <td>{{ $atencion->alumno?->nombreCompleto() }}</td>
                        <td>{{ $atencion->motivo }}</td>
                        <td>{{ $atencion->profesional?->nombreCompleto() ?? '—' }}</td>
                        <td>@include('dece.partials.estado-badge', ['estado' => $atencion->estado, 'label' => $atencion->estadoLabel()])</td>
                        <td class="text-end">
                            <a href="{{ route('dece.atenciones.show', $atencion) }}" class="btn btn-sm btn-outline-primary">Ver ficha</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay atenciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($atenciones->hasPages())
        <div class="card-footer bg-white">{{ $atenciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
