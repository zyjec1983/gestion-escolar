@extends('layouts.principal')

@section('titulo', 'NEE y diagnósticos')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-universal-access me-2"></i>Necesidades educativas específicas</h4>
            <small class="text-muted">Registro interno o externo, adaptaciones y respaldo diagnóstico.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dece.diagnosticos.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-journal-medical me-1"></i>Catálogo de diagnósticos
            </a>
            <a href="{{ route('dece.nee.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nuevo registro
            </a>
        </div>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.nee.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'neeBuscador',
                        'etiqueta' => 'Buscar',
                        'valor' => $filtros['buscar'] ?? '',
                        'valorId' => $filtros['alumno_id'] ?? '',
                        'campoId' => 'alumno_id',
                        'placeholder' => 'Estudiante, diagnóstico o curso',
                    ])
                </div>
                <div class="col-md-3">
                    <label class="form-label">Procedencia</label>
                    <select name="procedencia" class="form-select">
                        <option value="">Todas</option>
                        @foreach($procedencias as $valor => $label)
                            <option value="{{ $valor }}" @selected(($filtros['procedencia'] ?? '') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Diagnóstico</label>
                    <select name="diagnostico_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($diagnosticos as $diagnostico)
                            <option value="{{ $diagnostico->id }}" @selected(($filtros['diagnostico_id'] ?? null) == $diagnostico->id)>{{ $diagnostico->etiqueta() }}</option>
                        @endforeach
                    </select>
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
                        <th>Estudiante</th>
                        <th>Procedencia</th>
                        <th>Diagnóstico</th>
                        <th>Adaptación</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $registro)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $registro->nombreEstudiante() }}</div>
                            @if($registro->curso_externo)<small class="text-muted">{{ $registro->curso_externo }}</small>@endif
                        </td>
                        <td>@include('dece.partials.estado-badge', ['estado' => $registro->procedencia, 'label' => $registro->procedenciaLabel()])</td>
                        <td>{{ $registro->diagnostico?->nombre ?? $registro->diagnostico_detalle ?? '—' }}</td>
                        <td class="small">{{ $registro->gradoLabel() }}</td>
                        <td><span class="badge {{ $registro->activo ? 'bg-success' : 'bg-secondary' }}">{{ $registro->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('dece.nee.show', $registro) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay registros NEE.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registros->hasPages())
        <div class="card-footer bg-white">{{ $registros->links() }}</div>
        @endif
    </div>
</div>
@endsection
