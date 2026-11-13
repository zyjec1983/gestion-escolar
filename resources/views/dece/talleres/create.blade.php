@extends('layouts.principal')

@section('titulo', 'Nuevo taller DECE')

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-people me-2"></i>Nuevo taller</h4>
        <small class="text-muted">Actividad de prevención, orientación o escuela de padres.</small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.talleres.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" class="form-control" maxlength="255" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-select" required>
                        @foreach($tipos as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('tipo') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Dirigido a <span class="text-danger">*</span></label>
                    <select name="dirigido_a" class="form-select" required>
                        @foreach($dirigidos as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('dirigido_a') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Curso (opcional)</label>
                    <select name="curso_id" class="form-select">
                        <option value="">Todos los cursos</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" @selected(old('curso_id') == $curso->id)>{{ $curso->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Facilitador</label>
                    <input type="text" name="facilitador" value="{{ old('facilitador') }}" class="form-control" maxlength="255">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Lugar</label>
                    <input type="text" name="lugar" value="{{ old('lugar') }}" class="form-control" maxlength="255">
                </div>

                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" rows="4" class="form-control">{{ old('descripcion') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('dece.talleres.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar taller</button>
        </div>
    </form>
</div>
@endsection
