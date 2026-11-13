@extends('layouts.principal')

@section('titulo', 'Talleres DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>Talleres y actividades colectivas</h4>
            <small class="text-muted">Prevención, orientación vocacional y escuela de padres.</small>
        </div>
        <a href="{{ route('dece.talleres.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nuevo taller
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.talleres.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'talleresBuscador',
                        'etiqueta' => 'Buscar',
                        'valor' => $filtros['buscar'] ?? '',
                        'placeholder' => 'Título, facilitador o lugar',
                        'url' => route('dece.buscar.talleres'),
                    ])
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach($tipos as $valor => $label)
                            <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? '') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dirigido a</label>
                    <select name="dirigido_a" class="form-select">
                        <option value="">Todos</option>
                        @foreach($dirigidos as $valor => $label)
                            <option value="{{ $valor }}" @selected(($filtros['dirigido_a'] ?? '') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control">
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
                        <th>Fecha</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Dirigido a</th>
                        <th>Curso</th>
                        <th>Asistentes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($talleres as $taller)
                    <tr>
                        <td class="text-nowrap">{{ $taller->fecha->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $taller->titulo }}</div>
                            <small class="text-muted">{{ $taller->facilitador ?? '—' }}</small>
                        </td>
                        <td class="small">{{ $taller->tipoLabel() }}</td>
                        <td>{{ $taller->dirigidoLabel() }}</td>
                        <td>{{ $taller->curso?->nombre ?? 'Todos' }}</td>
                        <td><span class="badge bg-secondary">{{ $taller->asistentes_count }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('dece.talleres.show', $taller) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay talleres registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($talleres->hasPages())
        <div class="card-footer bg-white">{{ $talleres->links() }}</div>
        @endif
    </div>
</div>
@endsection
