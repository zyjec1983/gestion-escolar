@extends('layouts.principal')

@section('titulo', 'Catálogo de diagnósticos')

@push('styles')
<style>
    .diagnosticos-page .bi { font-size: 1rem; line-height: 1; }
    .diagnosticos-page h4 .bi { font-size: 1.35rem; }
    .diagnosticos-page .btn-sm .bi { font-size: 0.95rem; }
</style>
@endpush

@section('contenido')
<div class="container-fluid diagnosticos-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-journal-medical me-2"></i>Catálogo de diagnósticos</h4>
            <small class="text-muted">CIE-10 / MSP / MinEduc. Solo los diagnósticos activos se ofrecen en los formularios.</small>
        </div>
        <a href="{{ route('dece.nee.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver a NEE
        </a>
    </div>

    @include('dece.partials.flash')

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Diagnósticos registrados</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($diagnosticos as $diagnostico)
                            <tr>
                                <td class="fw-semibold">{{ $diagnostico->codigo }}</td>
                                <td>{{ $diagnostico->nombre }}</td>
                                <td class="small">{{ $diagnostico->categoria }}</td>
                                <td><span class="badge {{ $diagnostico->activo ? 'bg-success' : 'bg-secondary' }}">{{ $diagnostico->activo ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                            data-bs-target="#editar-{{ $diagnostico->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('dece.diagnosticos.toggle', $diagnostico) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-{{ $diagnostico->activo ? 'warning' : 'success' }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="editar-{{ $diagnostico->id }}">
                                <td colspan="5" class="bg-light">
                                    <form method="POST" action="{{ route('dece.diagnosticos.update', $diagnostico) }}" class="row g-2 align-items-end">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-2">
                                            <input type="text" name="codigo" value="{{ $diagnostico->codigo }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="nombre" value="{{ $diagnostico->nombre }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="categoria" class="form-select form-select-sm" required>
                                                @foreach($categorias as $categoria)
                                                    <option value="{{ $categoria }}" @selected($diagnostico->categoria === $categoria)>{{ $categoria }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button class="btn btn-sm btn-primary">Guardar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">El catálogo está vacío.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($diagnosticos->hasPages())
                <div class="card-footer bg-white">{{ $diagnosticos->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Agregar diagnóstico</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dece.diagnosticos.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Código CIE-10</label>
                            <input type="text" name="codigo" value="{{ old('codigo') }}" class="form-control" maxlength="30" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-select" required>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria }}" @selected(old('categoria') === $categoria)>{{ $categoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
