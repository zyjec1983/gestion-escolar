@extends('layouts.educalinks')

@section('titulo', 'Asistencia')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-calendar-check me-2"></i>Control de Asistencia</h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Clase</label>
            <select name="clase_docente_id" class="form-select" onchange="this.form.submit()">
                @foreach($clases as $c)
                    <option value="{{ $c->id }}" {{ $clase && $clase->id == $c->id ? 'selected' : '' }}>
                        {{ $c->curso->nombre ?? 'N/A' }} - {{ $c->paralelo->nombre ?? 'N/A' }} - {{ $c->materia->nombre ?? 'N/A' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ $fecha }}" onchange="this.form.submit()">
        </div>
    </form>

    @if($alumnos->isNotEmpty())
        <form method="POST" action="{{ route('docente.asistencia.store') }}">
            @csrf
            <input type="hidden" name="clase_docente_id" value="{{ $clase->id }}">
            <input type="hidden" name="fecha" value="{{ $fecha }}">

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Estudiante</th>
                            <th class="text-center">
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" id="todosPresentes">
                                    <label class="form-check-label ms-1">Presente</label>
                                </div>
                            </th>
                            <th class="text-center">Justificada</th>
                            <th class="text-center">Injustificada</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumnos as $i => $alumno)
                            @php
                                $existente = $asistenciaExistente->get($alumno->id);
                                $estado = $existente?->estado ?? 'presente';
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $alumno->apellidos }} {{ $alumno->nombres }}</td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input"
                                        name="asistencia[{{ $alumno->id }}][estado]" value="presente" {{ $estado == 'presente' ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input"
                                        name="asistencia[{{ $alumno->id }}][estado]" value="justificada" {{ $estado == 'justificada' ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input"
                                        name="asistencia[{{ $alumno->id }}][estado]" value="injustificada" {{ $estado == 'injustificada' ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                        name="asistencia[{{ $alumno->id }}][observacion]"
                                        value="{{ $existente?->observacion }}" placeholder="Opcional">
                                </td>
                            </tr>
                            <input type="hidden" name="asistencia[{{ $alumno->id }}][alumno_id]" value="{{ $alumno->id }}">
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar Asistencia
                </button>
            </div>
        </form>
    @elseif($clase)
        <div class="alert alert-info">No hay estudiantes asignados a esta clase.</div>
    @else
        <div class="alert alert-warning">No tienes clases asignadas.</div>
    @endif
</div>

@push('scripts')
<script>
document.getElementById('todosPresentes')?.addEventListener('change', function() {
    document.querySelectorAll('input[type="radio"][value="presente"]').forEach(r => {
        r.checked = this.checked;
    });
});
</script>
@endpush
@endsection
