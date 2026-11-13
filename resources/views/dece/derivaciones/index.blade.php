@extends('layouts.principal')

@section('titulo', 'Derivaciones DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-arrow-up-right-circle me-2"></i>Derivaciones</h4>
            <small class="text-muted">
                @if(in_array(auth()->user()->rol, ['DECE', 'ADMIN'], true))
                    Casos remitidos al DECE y derivaciones a la red de protección.
                @else
                    Casos que usted remitió al DECE.
                @endif
            </small>
        </div>
        <a href="{{ route('dece.derivaciones.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nueva derivación
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.derivaciones.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'derivacionesBuscador',
                        'etiqueta' => 'Buscar',
                        'valor' => $filtros['buscar'] ?? '',
                        'valorId' => $filtros['alumno_id'] ?? '',
                        'campoId' => 'alumno_id',
                        'placeholder' => 'Estudiante, motivo, oficio o entidad',
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
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach($estados as $valor => $label)
                            <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                        <th>Estudiante</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Destino</th>
                        <th>Límite</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($derivaciones as $derivacion)
                    <tr class="{{ $derivacion->estaVencida() ? 'table-danger' : '' }}">
                        <td class="text-nowrap">{{ $derivacion->fecha_derivacion->format('d/m/Y') }}</td>
                        <td>{{ $derivacion->alumno?->nombreCompleto() }}</td>
                        <td>{{ $derivacion->tipoLabel() }}</td>
                        <td class="small">{{ $derivacion->motivo }}</td>
                        <td class="small">{{ $derivacion->entidad_externa ?? 'DECE' }}</td>
                        <td class="text-nowrap small">
                            {{ $derivacion->fecha_limite?->format('d/m/Y') ?? '—' }}
                            @if($derivacion->estaVencida())
                                <span class="badge bg-danger ms-1">Vencida</span>
                            @endif
                        </td>
                        <td>@include('dece.partials.estado-badge', ['estado' => $derivacion->estado, 'label' => $derivacion->estadoLabel()])</td>
                        <td class="text-end">
                            <a href="{{ route('dece.derivaciones.show', $derivacion) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay derivaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($derivaciones->hasPages())
        <div class="card-footer bg-white">{{ $derivaciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
