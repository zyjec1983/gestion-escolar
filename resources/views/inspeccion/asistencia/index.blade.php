@extends('layouts.principal')

@section('titulo', 'Asistencia')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-calendar-check me-2"></i>Control de Asistencia</h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <form method="GET" class="row g-3 mb-4" id="filtroAula">
        <div class="col-md-5">
            <label class="form-label">Curso / Paralelo</label>
            <select name="aula" class="form-select" id="selectorAula" onchange="enviarFiltro()">
                @foreach($aulas as $a)
                    <option value="{{ $a->curso_id }}:{{ $a->paralelo_id }}"
                        {{ $aula && $aula->curso_id == $a->curso_id && $aula->paralelo_id == $a->paralelo_id ? 'selected' : '' }}>
                        {{ $a->curso_nombre }} - {{ $a->paralelo_nombre }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="curso_id" id="inputCursoId" value="{{ $aula->curso_id ?? '' }}">
            <input type="hidden" name="paralelo_id" id="inputParaleloId" value="{{ $aula->paralelo_id ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ $fecha }}" onchange="this.form.submit()">
        </div>
    </form>

    @if($alumnos->isNotEmpty())
        <form method="POST" action="{{ route('inspeccion.asistencia.store') }}">
            @csrf
            <input type="hidden" name="curso_id" value="{{ $aula->curso_id }}">
            <input type="hidden" name="paralelo_id" value="{{ $aula->paralelo_id }}">
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
                            <th class="text-center">Atraso</th>
                            <th>Observaciones</th>
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
                                <td class="text-center">
                                    <input type="radio" class="form-check-input"
                                        name="asistencia[{{ $alumno->id }}][estado]" value="atraso" {{ $estado == 'atraso' ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                        name="asistencia[{{ $alumno->id }}][observacion]"
                                        value="{{ $existente?->observacion }}" placeholder="Observaciones">
                                </td>
                                <input type="hidden" name="asistencia[{{ $alumno->id }}][alumno_id]" value="{{ $alumno->id }}">
                            </tr>
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
    @elseif($aula)
        <div class="alert alert-info">No hay estudiantes matriculados en este curso.</div>
    @else
        <div class="alert alert-warning">No hay cursos con estudiantes matriculados.</div>
    @endif
</div>

@push('scripts')
<script>
function enviarFiltro() {
    const partes = document.getElementById('selectorAula').value.split(':');
    document.getElementById('inputCursoId').value = partes[0];
    document.getElementById('inputParaleloId').value = partes[1];
    document.getElementById('filtroAula').submit();
}

document.getElementById('todosPresentes')?.addEventListener('change', function() {
    document.querySelectorAll('input[type="radio"][value="presente"]').forEach(r => {
        r.checked = this.checked;
    });
});
</script>
@endpush
@endsection