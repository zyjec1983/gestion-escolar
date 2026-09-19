@extends('layouts.aula')

@section('titulo', ($clase_actual['materia'] ?? '') . ' - Nuevo Tema de Foro')

@section('contenido-aula')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nuevo Tema de Foro</h4>
        <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
    </div>
    <a href="{{ route('clases.foros', $clase->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Foros</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('clases.foros.store', $clase->id) }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Título del tema</label>
                    <input type="text" name="titulo" class="form-control" maxlength="255" required
                           value="{{ old('titulo') }}" placeholder="Ej: Resultados PISA y la Inteligencia Artificial">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Pregunta de debate</label>
                    <textarea name="pregunta" class="form-control" rows="5" maxlength="5000" required
                              placeholder="Plantea la pregunta sobre la que los estudiantes deberán argumentar...">{{ old('pregunta') }}</textarea>
                    <div class="form-text">Los estudiantes responderán con sus propias palabras; no podrán copiar ni pegar.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fecha de inicio</label>
                    <input type="datetime-local" name="fecha_inicio" class="form-control" required
                           value="{{ old('fecha_inicio') }}">
                    <div class="form-text">A partir de esta fecha los estudiantes pueden participar.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fecha de cierre</label>
                    <input type="datetime-local" name="fecha_cierre" class="form-control" required
                           value="{{ old('fecha_cierre') }}">
                    <div class="form-text">Al llegar este momento el foro se bloquea y nadie puede intervenir.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Enlaces de consulta (máximo 3)</label>
                    <div class="form-text mb-2">Sitios web que el estudiante debe revisar <strong>antes</strong> de responder en el foro.</div>

                    @for($i = 0; $i < 3; $i++)
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <input type="text" name="links[{{ $i }}][nombre]" class="form-control"
                                   maxlength="100" placeholder="Nombre del sitio (opcional)"
                                   value="{{ old('links.' . $i . '.nombre') }}">
                        </div>
                        <div class="col-md-8">
                            <input type="url" name="links[{{ $i }}][url]" class="form-control"
                                   maxlength="2048" placeholder="https://ejemplo.com/articulo"
                                   value="{{ old('links.' . $i . '.url') }}">
                        </div>
                    </div>
                    @endfor
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('clases.foros', $clase->id) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Crear foro</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection