@extends('layouts.principal')

@section('titulo', 'Acuerdos y actas')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Acuerdos y compromisos</h4>
            <small class="text-muted">Compromisos conductuales, académicos y de convivencia con firma digital.</small>
        </div>
        <a href="{{ route('dece.acuerdos.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Nuevo acuerdo
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.acuerdos.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'acuerdosBuscador',
                        'etiqueta' => 'Buscar',
                        'valor' => $filtros['buscar'] ?? '',
                        'valorId' => $filtros['alumno_id'] ?? '',
                        'campoId' => 'alumno_id',
                        'placeholder' => 'Estudiante o descripción',
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
                        <th>Estudiante</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Compromiso hasta</th>
                        <th>Firmas</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($acuerdos as $acuerdo)
                    <tr>
                        <td>{{ $acuerdo->alumno?->nombreCompleto() }}</td>
                        <td>{{ $acuerdo->tipoLabel() }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($acuerdo->descripcion, 70) }}</td>
                        <td class="text-nowrap">{{ $acuerdo->fecha_compromiso->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge {{ $acuerdo->firmadoCompleto() ? 'bg-success' : 'bg-secondary' }}">
                                {{ $acuerdo->firmasRegistradas() }}/3
                            </span>
                        </td>
                        <td>@include('dece.partials.estado-badge', ['estado' => $acuerdo->estado, 'label' => $acuerdo->estadoLabel()])</td>
                        <td class="text-end">
                            <a href="{{ route('dece.acuerdos.show', $acuerdo) }}" class="btn btn-sm btn-outline-primary">Ver acta</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay acuerdos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($acuerdos->hasPages())
        <div class="card-footer bg-white">{{ $acuerdos->links() }}</div>
        @endif
    </div>
</div>
@endsection
