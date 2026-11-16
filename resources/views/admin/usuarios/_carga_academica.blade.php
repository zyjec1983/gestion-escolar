{{--
    Contrato y carga académica docente.

    Qué hace: el contrato se escribe en horas reloj (no en una lista fija) y el
    sistema calcula el 75 % de horas de plantel y el 25 % de horas autónomas.
    Las horas de reunión de área, atención a padres y tutoría son horas
    académicas de 40 minutos y se suman a la carga de clases.
    Conecta con: Admin\UserController y App\Services\CargaHorariaDocente.
--}}
@php
    $contratoActual = old('horas_contrato', $usuario->horas_contrato ?? 40);
    $tutorActual = old('es_tutor', $usuario->es_tutor ?? false);
    $atencionActual = old('periodos_atencion_padres', $usuario->periodos_atencion_padres ?? 0);
    $reunionActual = old('periodos_reunion_area', $usuario->periodos_reunion_area ?? 0);
    $tutoriaActual = old('periodos_tutoria', $usuario->periodos_tutoria ?? 0);
@endphp

<hr class="my-4">

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Carga académica</h6>
    <span class="badge bg-info-subtle text-info border">Horas académicas de 40 minutos</span>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="horas_contrato">Contrato (horas reloj)</label>
        <div class="input-group">
            <input type="number" id="horas_contrato" name="horas_contrato"
                class="form-control @error('horas_contrato') is-invalid @enderror"
                value="{{ $contratoActual }}" min="1" max="60" step="1" inputmode="numeric" required>
            <span class="input-group-text">h</span>
            @error('horas_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">
            Plantel: <strong data-contrato="plantel">30</strong> h ·
            Autónomas: <strong data-contrato="autonomas">10</strong> h
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="periodos_atencion_padres">Atención a padres</label>
        <div class="input-group">
            <input type="number" id="periodos_atencion_padres" name="periodos_atencion_padres"
                class="form-control @error('periodos_atencion_padres') is-invalid @enderror"
                value="{{ $atencionActual }}" min="0" max="40" step="1" inputmode="numeric">
            <span class="input-group-text">per.</span>
            @error('periodos_atencion_padres')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Equivale a <strong data-periodos="periodos_atencion_padres">0</strong> h reloj.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="periodos_reunion_area">Reunión de área</label>
        <div class="input-group">
            <input type="number" id="periodos_reunion_area" name="periodos_reunion_area"
                class="form-control @error('periodos_reunion_area') is-invalid @enderror"
                value="{{ $reunionActual }}" min="0" max="40" step="1" inputmode="numeric">
            <span class="input-group-text">per.</span>
            @error('periodos_reunion_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Equivale a <strong data-periodos="periodos_reunion_area">0</strong> h reloj.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="periodos_tutoria">Tutoría</label>
        <div class="input-group">
            <input type="number" id="periodos_tutoria" name="periodos_tutoria"
                class="form-control @error('periodos_tutoria') is-invalid @enderror"
                value="{{ $tutoriaActual }}" min="0" max="40" step="1" inputmode="numeric">
            <span class="input-group-text">per.</span>
            @error('periodos_tutoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">Equivale a <strong data-periodos="periodos_tutoria">0</strong> h reloj.</div>
    </div>

    <div class="col-md-8 d-flex align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="es_tutor" id="es_tutor" value="1"
                @checked($tutorActual)>
            <label class="form-check-label" for="es_tutor">
                <strong>Docente tutor</strong>
            </label>
            <div class="form-text">
                Marque si el docente es tutor de curso y recibe horas de gestión.
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var contrato = document.getElementById('horas_contrato');

        if (!contrato) return;

        function numero(valor) {
            return valor.toFixed(2).replace(/\.?0+$/, '').replace('.', ',');
        }

        function pintarContrato() {
            var horas = parseInt(contrato.value, 10);

            if (isNaN(horas) || horas < 0) horas = 0;

            var plantel = document.querySelector('[data-contrato="plantel"]');
            var autonomas = document.querySelector('[data-contrato="autonomas"]');

            if (plantel) plantel.textContent = numero(horas * 0.75);
            if (autonomas) autonomas.textContent = numero(horas * 0.25);
        }

        function pintarPeriodos(id) {
            var input = document.getElementById(id);
            var salida = document.querySelector('[data-periodos="' + id + '"]');

            if (!input || !salida) return;

            var periodos = parseInt(input.value, 10);

            if (isNaN(periodos) || periodos < 0) periodos = 0;

            salida.textContent = numero((periodos * 40) / 60);
        }

        contrato.addEventListener('input', pintarContrato);

        ['periodos_atencion_padres', 'periodos_reunion_area', 'periodos_tutoria'].forEach(function (id) {
            var input = document.getElementById(id);

            if (input) {
                input.addEventListener('input', function () { pintarPeriodos(id); });
            }

            pintarPeriodos(id);
        });

        pintarContrato();
    });
</script>
@endpush
@endonce
