@extends('layouts.principal')

@section('titulo', 'Expedientes DECE')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Expedientes estudiantiles</h4>
            <small class="text-muted">Consulte el contexto académico y disciplinario antes de cada intervención.</small>
        </div>
    </div>

    @include('dece.partials.flash')

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dece.expedientes.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    @include('dece.partials.buscador-filtro', [
                        'idBuscador' => 'expedientesBuscador',
                        'etiqueta' => 'Buscar estudiante',
                        'valor' => $filtros['buscar'] ?? '',
                        'placeholder' => 'Nombre, apellido, cédula o código',
                        'urlSeleccion' => route('dece.expedientes.show', '__ID__'),
                    ])
                </div>
                <div class="col-md-4">
                    <label class="form-label">Curso</label>
                    <select name="curso_id" class="form-select">
                        <option value="">Todos los cursos</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" @selected(($filtros['curso_id'] ?? null) == $curso->id)>{{ $curso->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
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
                        <th>Cédula</th>
                        <th>Curso / paralelo</th>
                        <th>Representante</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alumnos as $alumno)
                    @php $matricula = $alumno->matriculas->sortByDesc('id')->first(); @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $alumno->nombreCompleto() }}</div>
                            <small class="text-muted">Código {{ $alumno->codigo }}</small>
                        </td>
                        <td>{{ $alumno->numero_cedula ?? '—' }}</td>
                        <td>
                            @if($matricula)
                                {{ $matricula->curso?->nombre }}
                                @if($matricula->paralelo) · {{ $matricula->paralelo->nombre }} @endif
                            @else
                                <span class="text-muted">Sin matrícula</span>
                            @endif
                        </td>
                        <td>{{ $alumno->representante?->nombreCompleto() ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('dece.expedientes.show', $alumno) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Expediente
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No se encontraron estudiantes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alumnos->hasPages())
        <div class="card-footer bg-white">{{ $alumnos->links() }}</div>
        @endif
    </div>
</div>
@endsection
