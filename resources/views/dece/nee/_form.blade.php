@php
    $editando = isset($nee) && $nee;
    $valor = fn (string $campo, $defecto = null) => old($campo, $editando ? $nee->{$campo} : $defecto);
    $procedenciaActual = old('procedencia', $editando ? $nee->procedencia : 'interno');
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Procedencia <span class="text-danger">*</span></label>
        <select name="procedencia" id="procedencia" class="form-select" required>
            @foreach($procedencias as $clave => $label)
                <option value="{{ $clave }}" @selected($procedenciaActual === $clave)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-8" id="bloque-alumno">
        @include('dece.partials.buscador-alumno', [
            'alumnoSeleccionado' => $alumnoSeleccionado ?? null,
            'idBuscador' => 'buscadorAlumnoNee',
            'requerido' => $procedenciaActual === 'interno',
        ])
    </div>

    <div class="col-md-6 bloque-externo d-none">
        <label class="form-label">Nombre del estudiante externo <span class="text-danger">*</span></label>
        <input type="text" name="nombre_externo" value="{{ $valor('nombre_externo') }}" class="form-control" maxlength="255">
    </div>

    <div class="col-md-6 bloque-externo d-none">
        <label class="form-label">Curso de referencia <span class="text-danger">*</span></label>
        <input type="text" name="curso_externo" value="{{ $valor('curso_externo') }}" class="form-control" maxlength="120">
    </div>

    <div class="col-md-6">
        <label class="form-label">Diagnóstico del catálogo</label>
        <select name="diagnostico_id" class="form-select">
            <option value="">Sin diagnóstico del catálogo</option>
            @foreach($diagnosticos as $diagnostico)
                <option value="{{ $diagnostico->id }}" @selected($valor('diagnostico_id') == $diagnostico->id)>{{ $diagnostico->etiqueta() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Detalle del diagnóstico</label>
        <input type="text" name="diagnostico_detalle" value="{{ $valor('diagnostico_detalle') }}" class="form-control" maxlength="255">
    </div>

    <div class="col-md-6">
        <label class="form-label">Profesional evaluador</label>
        <input type="text" name="profesional_evaluador" value="{{ $valor('profesional_evaluador') }}" class="form-control" maxlength="255">
    </div>

    <div class="col-md-3">
        <label class="form-label">Fecha de evaluación</label>
        <input type="date" name="fecha_evaluacion" value="{{ $valor('fecha_evaluacion') instanceof \Carbon\CarbonInterface ? $valor('fecha_evaluacion')->format('Y-m-d') : $valor('fecha_evaluacion') }}" class="form-control">
    </div>

    <div class="col-md-3">
        <label class="form-label">Grado de adaptación</label>
        <select name="grado_adaptacion" class="form-select">
            <option value="">Sin adaptación</option>
            @foreach($grados as $grado => $label)
                <option value="{{ $grado }}" @selected($valor('grado_adaptacion') == $grado)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" rows="3" class="form-control">{{ $valor('observaciones') }}</textarea>
    </div>

    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="requiere_informe" value="0">
            <input type="checkbox" name="requiere_informe" value="1" id="requiere_informe" class="form-check-input"
                   @checked((bool) $valor('requiere_informe', false))>
            <label class="form-check-label" for="requiere_informe">Requiere informe psicopedagógico</label>
        </div>
    </div>

    @unless($editando)
    <div class="col-12">
        <label class="form-label">Informe o evidencia (PDF, JPG o PNG · máx. 10 MB)</label>
        <input type="file" name="evidencias[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
    </div>
    @endunless
</div>

@push('scripts')
<script>
    (function () {
        const procedencia = document.getElementById('procedencia');
        const bloqueAlumno = document.getElementById('bloque-alumno');
        const externos = document.querySelectorAll('.bloque-externo');
        const buscador = bloqueAlumno ? bloqueAlumno.querySelector('[data-buscador-input]') : null;

        function alternar() {
            const externo = procedencia.value === 'externo';
            bloqueAlumno.classList.toggle('d-none', externo);
            externos.forEach(el => el.classList.toggle('d-none', !externo));

            if (buscador) {
                buscador.required = !externo;
                buscador.setCustomValidity('');
            }
        }

        procedencia.addEventListener('change', alternar);
        alternar();
    })();
</script>
@endpush
