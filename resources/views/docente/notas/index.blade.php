@extends('layouts.principal')

@section('titulo', 'Notas')

@push('styles')
<style>
    /* El resaltado rojo de una casilla con nota inválida se mantiene hasta
       que el valor se corrija, aunque el docente haga clic en otra casilla. */
    #notas-table .form-control.is-invalid {
        border-color: #dc3545 !important;
    }
</style>
@endpush

@php
    $trunc = function($v) {
        if ($v === null) return null;
        return floor(($v * 100) + 1e-9) / 100;
    };
    $opcionActual = collect($opciones ?? [])->firstWhere('parcial', $parcial) ?? [];
@endphp

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-file-earmark-text me-2"></i>Registro de Notas</h4>

    @if(session('success'))
        <div id="swal-success-notas" data-message="{{ session('success') }}" hidden></div>
    @endif

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-5">
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
            <label class="form-label">Periodo</label>
            <select name="parcial" class="form-select" onchange="this.form.submit()">
                @foreach($grupos ?? collect() as $nombreGrupo => $grupoOpciones)
                    @if($nombreGrupo)
                    <optgroup label="{{ $nombreGrupo }}">
                        @foreach($grupoOpciones as $op)
                            <option value="{{ $op['parcial'] }}" {{ $parcial == $op['parcial'] ? 'selected' : '' }}>{{ $op['label'] }}</option>
                        @endforeach
                    </optgroup>
                    @else
                        @foreach($grupoOpciones as $op)
                            <option value="{{ $op['parcial'] }}" {{ $parcial == $op['parcial'] ? 'selected' : '' }}>{{ $op['label'] }}</option>
                        @endforeach
                    @endif
                @endforeach
            </select>
        </div>
        @if(!empty($opcionActual))
        <div class="col-md-2 d-flex align-items-end">
            <span class="badge bg-primary mb-1" style="font-size: 1rem; line-height: 1.5; padding: 0.5rem 0.75rem;">{{ $opcionActual['label'] }} @if(isset($opcionActual['grupo']))<span class="fw-light">— {{ $opcionActual['grupo'] }}</span>@endif</span>
        </div>
        @endif
    </form>

    @if($alumnos->isNotEmpty())
        <form method="POST" action="{{ route('docente.notas.store') }}" id="notas-form" data-modo="{{ $tipo }}">
            @csrf
            <input type="hidden" name="clase_docente_id" value="{{ $clase->id }}">
            <input type="hidden" name="parcial" value="{{ $parcial }}">
            <input type="hidden" name="tipo_guardado" id="tipo_guardado" value="parcial">

            @if($tipo === 'examen')
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="notas-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Estudiante</th>
                                <th class="text-center">Examen Quimestral<br><small>0 - 10</small></th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alumnos as $i => $alumno)
                                @php
                                    $n = $notasExistentes->get($alumno->id);
                                    $ne = $n?->nota_examen !== null ? (float) $n->nota_examen : null;
                                    $esFinal = $n && $n->estado === 'final';
                                @endphp
                                <tr data-alumno="{{ $alumno->id }}" class="{{ $esFinal ? 'table-secondary' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $alumno->apellidos }} {{ $alumno->nombres }}</td>
                                    <td>
                                        <input type="text" inputmode="decimal" class="form-control form-control-sm text-center examen-input" name="notas[{{ $alumno->id }}][nota_examen]" value="{{ $n && $ne > 0 ? number_format($ne, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required>
                                    </td>
                                    <td class="text-center">
                                        @if($esFinal)
                                            <span class="badge bg-success">Final</span>
                                        @elseif($n)
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                        @else
                                            <span class="badge bg-secondary">Sin guardar</span>
                                        @endif
                                    </td>
                                </tr>
                                <input type="hidden" name="notas[{{ $alumno->id }}][alumno_id]" value="{{ $alumno->id }}">
                            @endforeach
                        </tbody>
                        @if(isset($promedio))
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td></td>
                                <td>Promedio</td>
                                <td class="text-center">{{ number_format($trunc($promedio['total'] ?? 0), 2, '.', '') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="notas-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Estudiante</th>
                                <th class="text-center">FII</th>
                                <th class="text-center">FFI</th>
                                <th class="text-center">FIG</th>
                                <th class="text-center">FFG</th>
                                <th class="text-center">Form. 70%</th>
                                <th class="text-center">Pr. Integrador<br><small>15%</small></th>
                                <th class="text-center">Eval. Parcial<br><small>15%</small></th>
                                <th class="text-center">Eval. 30%</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alumnos as $i => $alumno)
                                @php
                                    $n = $notasExistentes->get($alumno->id);
                                    $fii = (float) ($n?->fii ?? 0);
                                    $ffi = (float) ($n?->ffi ?? 0);
                                    $fig = (float) ($n?->fig ?? 0);
                                    $ffg = (float) ($n?->ffg ?? 0);
                                    $pi = (float) ($n?->proyecto_integrador ?? 0);
                                    $ep = (float) ($n?->evaluacion_parcial ?? 0);
                                    $esFinal = $n && $n->estado === 'final';
                                    $sumForm = $fii + $ffi + $fig + $ffg;
                                    $sumEval = $pi + $ep;
                                    $form70 = $sumForm / 40 * 7;
                                    $eval30 = $sumEval / 20 * 3;
                                    $total = $form70 + $eval30;
                                @endphp
                                <tr data-alumno="{{ $alumno->id }}" class="{{ $esFinal ? 'table-secondary' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $alumno->apellidos }} {{ $alumno->nombres }}</td>
                                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][fii]" value="{{ $n && $fii > 0 ? number_format($fii, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][ffi]" value="{{ $n && $ffi > 0 ? number_format($ffi, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][fig]" value="{{ $n && $fig > 0 ? number_format($fig, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][ffg]" value="{{ $n && $ffg > 0 ? number_format($ffg, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td class="text-center fw-bold form70">{{ number_format($trunc($form70), 2, '.', '') }}</td>
                                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][proyecto_integrador]" value="{{ $n && $pi > 0 ? number_format($pi, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][evaluacion_parcial]" value="{{ $n && $ep > 0 ? number_format($ep, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td class="text-center fw-bold eval30">{{ number_format($trunc($eval30), 2, '.', '') }}</td>
                                    <td class="text-center fw-bold total">{{ number_format($trunc($total), 2, '.', '') }}</td>
                                    <td class="text-center">
                                        @if($esFinal)
                                            <span class="badge bg-success">Final</span>
                                        @elseif($n)
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                        @else
                                            <span class="badge bg-secondary">Sin guardar</span>
                                        @endif
                                    </td>
                                </tr>
                                <input type="hidden" name="notas[{{ $alumno->id }}][alumno_id]" value="{{ $alumno->id }}">
                            @endforeach
                        </tbody>
                        @if(isset($promedio))
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td></td>
                                <td>Promedio</td>
                                <td colspan="4"></td>
                                <td class="text-center">{{ number_format($trunc($promedio['form70']), 2, '.', '') }}</td>
                                <td colspan="2"></td>
                                <td class="text-center">{{ number_format($trunc($promedio['eval30']), 2, '.', '') }}</td>
                                <td class="text-center">{{ number_format($trunc($promedio['total']), 2, '.', '') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            @endif

            @php
                $todasFinal = $alumnos->every(fn($a) => ($notasExistentes->get($a->id)?->estado ?? 'parcial') === 'final');
            @endphp

            @if(!$todasFinal)
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="submit" class="btn btn-outline-primary" data-tipo="parcial">
                    <i class="bi bi-save me-1"></i>Guardado Parcial
                </button>
                <button type="submit" class="btn btn-primary" data-tipo="final">
                    <i class="bi bi-check-circle me-1"></i>Guardado Final
                </button>
            </div>
            @endif
        </form>
    @elseif($clase)
        <div class="alert alert-info">No hay estudiantes asignados a esta clase.</div>
    @else
        <div class="alert alert-warning">No tienes clases asignadas.</div>
    @endif

    @if(!empty($resumen))
        <div class="card mt-5 shadow-sm">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-bar-chart me-2"></i>Resumen {{ $sistema === 'quimestre' ? 'Quimestral - Anual' : 'Trimestral - Anual' }}
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    @if($sistema === 'quimestre')
                    <table class="table table-sm align-middle table-bordered mb-0">
                        <thead class="table-secondary text-center small">
                            <tr>
                                <th rowspan="2" class="align-middle">Estudiante</th>
                                <th colspan="7">Quimestre 1</th>
                                <th colspan="7">Quimestre 2</th>
                                <th rowspan="2" class="align-middle">AÑO</th>
                            </tr>
                            <tr>
                                <th>P1</th><th>P2</th><th>Prom.</th><th>70%</th><th>T.Exam</th><th>30%</th><th>Total</th>
                                <th>P3</th><th>P4</th><th>Prom.</th><th>70%</th><th>T.Exam</th><th>30%</th><th>Total</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($resumen as $r)
                                @php
                                    $prom1 = ($r['p1'] !== null && $r['p2'] !== null) ? ($r['p1'] + $r['p2']) / 2 : null;
                                    $v701 = $prom1 !== null ? $prom1 * 0.7 : null;
                                    $v301 = $r['te1'] !== null ? $r['te1'] * 0.3 : null;
                                    $prom2 = ($r['p3'] !== null && $r['p4'] !== null) ? ($r['p3'] + $r['p4']) / 2 : null;
                                    $v702 = $prom2 !== null ? $prom2 * 0.7 : null;
                                    $v302 = $r['te2'] !== null ? $r['te2'] * 0.3 : null;
                                    $fmt = fn($v) => $v !== null ? number_format($trunc($v), 2, '.', '') : '—';
                                @endphp
                                <tr>
                                    <td class="text-start fw-semibold">{{ $r['alumno']->apellidos }} {{ $r['alumno']->nombres }}</td>
                                    <td>{{ $fmt($r['p1']) }}</td>
                                    <td>{{ $fmt($r['p2']) }}</td>
                                    <td>{{ $fmt($prom1) }}</td>
                                    <td>{{ $fmt($v701) }}</td>
                                    <td>{{ $fmt($r['te1']) }}</td>
                                    <td>{{ $fmt($v301) }}</td>
                                    <td class="fw-bold">{{ $fmt($r['q1']) }}</td>
                                    <td>{{ $fmt($r['p3']) }}</td>
                                    <td>{{ $fmt($r['p4']) }}</td>
                                    <td>{{ $fmt($prom2) }}</td>
                                    <td>{{ $fmt($v702) }}</td>
                                    <td>{{ $fmt($r['te2']) }}</td>
                                    <td>{{ $fmt($v302) }}</td>
                                    <td class="fw-bold">{{ $fmt($r['q2']) }}</td>
                                    <td class="fw-bold bg-warning-subtle">{{ $fmt($r['anio']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <table class="table table-sm align-middle table-bordered mb-0">
                        <thead class="table-secondary text-center small">
                            <tr>
                                <th>Estudiante</th>
                                <th>Trimestre 1</th>
                                <th>Trimestre 2</th>
                                <th>Trimestre 3</th>
                                <th>AÑO</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($resumen as $r)
                                <tr>
                                    <td class="text-start fw-semibold">{{ $r['alumno']->apellidos }} {{ $r['alumno']->nombres }}</td>
                                    <td>{{ $r['t1'] !== null ? number_format($trunc($r['t1']), 2, '.', '') : '—' }}</td>
                                    <td>{{ $r['t2'] !== null ? number_format($trunc($r['t2']), 2, '.', '') : '—' }}</td>
                                    <td>{{ $r['t3'] !== null ? number_format($trunc($r['t3']), 2, '.', '') : '—' }}</td>
                                    <td class="fw-bold bg-warning-subtle">{{ $r['anio'] !== null ? number_format($trunc($r['anio']), 2, '.', '') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function() {
    var el = document.getElementById('swal-success-notas');
    if (el) {
        Swal.fire({
            icon: 'success',
            title: el.dataset.message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#0d6efd',
        });
    }
    const trunc2 = v => Math.floor((v * 100) + 1e-9) / 100;
    const form = document.getElementById('notas-form');
    if (!form) return;
    const esExamen = form.dataset.modo === 'examen';

    const RANGO_MIN = 0.01;
    const RANGO_MAX = 10;
    const RANGO_TEXTO = 'entre ' + RANGO_MIN + ' y ' + RANGO_MAX;

    const MENSAJE_RANGO = 'La nota debe estar ' + RANGO_TEXTO
        + '. El 0 no se admite porque significa "sin nota": deja el campo vacío.';
    const MENSAJE_FORMATO = 'Formato inválido. Escribe un número de 0 a 10 con hasta 2 decimales '
        + '(ejemplo: 9.5, 9,50 o 10).';

    const ETIQUETAS_CAMPO = {
        fii: 'FII',
        ffi: 'FFI',
        fig: 'FIG',
        ffg: 'FFG',
        proyecto_integrador: 'Proyecto Integrador',
        evaluacion_parcial: 'Evaluación Parcial',
        nota_examen: 'Examen Quimestral',
    };

    const nombreDeAlumno = inp =>
        inp.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim() || '';

    const nombreDeCampo = inp => {
        const clave = inp.name.match(/\[([^\]]+)\]$/)?.[1] ?? '';
        return ETIQUETAS_CAMPO[clave] || clave;
    };

    const listaHTML = items => items.map(t => '• ' + t).join('<br>');

    // Acepta coma o punto al escribir y deja siempre el punto en el casillero.
    function normalizarComa(nota) {
        if (nota.value.includes(',')) {
            nota.value = nota.value.replace(/,/g, '.');
        }
    }

    // Diagnostica una nota mientras se digita. Campo vacio = sin nota, es valido.
    function problemaDe(nota) {
        const bruto = nota.value.trim();

        if (bruto === '') return null;
        if (!/^-?\d+(\.\d+)?$/.test(bruto)) return { codigo: 'formato', mensaje: MENSAJE_FORMATO };

        const decimales = bruto.split('.')[1] ?? '';

        if (decimales.length > 2) {
            return { codigo: 'formato', mensaje: 'Máximo 2 decimales. Escribió ' + decimales.length + '.' };
        }

        const valor = parseFloat(bruto);

        if (valor < RANGO_MIN || valor > RANGO_MAX) return { codigo: 'rango', mensaje: MENSAJE_RANGO };

        return null;
    }

    // Resalta la celda y devuelve true si la nota es válida. El detalle del
    // error se muestra con SweetAlert, no debajo del casillero.
    function marcarNota(nota) {
        const problema = problemaDe(nota);

        if (problema === null) {
            nota.classList.remove('is-invalid');
            return true;
        }

        nota.classList.add('is-invalid');
        return false;
    }

    const filaEsValida = row =>
        [...row.querySelectorAll('.nota-input, .examen-input')]
            .every(nota => problemaDe(nota) === null);

    // Recalcula la fila. Si algún valor es inválido no muestra un total
    // inventado: lo deja en "—" para no confundir.
    function pintarFila(row) {
        if (!row) return;

        const celdas = ['form70', 'eval30', 'total']
            .map(clase => row.querySelector('.' + clase))
            .filter(Boolean);

        if (celdas.length === 0) return;

        if (!filaEsValida(row)) {
            celdas.forEach(celda => {
                celda.textContent = '—';
                celda.classList.add('text-danger', 'fw-bold');
            });
            return;
        }

        const leer = campo => {
            const bruto = (row.querySelector('[name$="[' + campo + ']"]')?.value ?? '')
                .trim()
                .replace(',', '.');
            return bruto === '' ? 0 : parseFloat(bruto);
        };

        const form70 = ((leer('fii') + leer('ffi') + leer('fig') + leer('ffg')) / 40) * 7;
        const eval30 = ((leer('proyecto_integrador') + leer('evaluacion_parcial')) / 20) * 3;

        celdas.forEach(celda => celda.classList.remove('text-danger'));
        row.querySelector('.form70').textContent = trunc2(form70).toFixed(2);
        row.querySelector('.eval30').textContent = trunc2(eval30).toFixed(2);
        row.querySelector('.total').textContent = trunc2(form70 + eval30).toFixed(2);
    }

    // No abrir dos SweetAlert seguidos cuando el docente pulsa un boton de
    // guardado con un campo invalido (blur ocurre antes que click).
    let yendoAGuardar = false;
    form.querySelectorAll('button[data-tipo]').forEach(btn => {
        btn.addEventListener('mousedown', () => { yendoAGuardar = true; });
    });

    // Validación en vivo: la casilla se marca en rojo al instante y el
    // resaltado se mantiene hasta que el valor quede correcto, aunque el
    // docente haga clic en otra casilla.
    form.querySelectorAll('.nota-input, .examen-input').forEach(nota => {
        nota.addEventListener('input', function () {
            normalizarComa(this);
            marcarNota(this);
            pintarFila(this.closest('tr'));
        });

        // Al perder el foco se vuelve a evaluar para que el rojo no se borre
        // por el solo hecho de mover el cursor a otra casilla.
        nota.addEventListener('blur', function () {
            marcarNota(this);
        });

        // Al salir del campo se avisa con SweetAlert y se devuelve el foco.
        nota.addEventListener('change', function () {
            if (yendoAGuardar || marcarNota(this)) return;

            Swal.fire({
                icon: 'error',
                title: 'Nota inválida',
                html: '<b>' + nombreDeAlumno(this) + '</b> — ' + nombreDeCampo(this)
                    + '<br><br>' + problemaDe(this).mensaje,
                confirmButtonText: 'Corregir',
            }).then(() => this.focus());
        });
    });

    function revisarNotas(inputs, exigirTodos) {
        const malFormato = [];
        const fueraDeRango = [];
        const vacios = [];

        inputs.forEach(inp => {
            const bruto = inp.value.trim();

            if (bruto === '') {
                marcarNota(inp);
                if (exigirTodos) vacios.push(nombreDeAlumno(inp));
                return;
            }

            if (marcarNota(inp)) return;

            const referencia = nombreDeAlumno(inp) + ' — ' + nombreDeCampo(inp) + ': ' + bruto;

            if (problemaDe(inp).codigo === 'formato') malFormato.push(referencia);
            else fueraDeRango.push(referencia);
        });

        if (malFormato.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Formato de nota inválido',
                html: 'Se admiten hasta <b>2 decimales</b>, con punto o con coma.<br>'
                    + 'Ejemplos válidos: 9.5, 9,50, 10.<br><br>'
                    + 'Corrige estos campos:<br>' + listaHTML(malFormato),
                confirmButtonText: 'Corregir',
            });
            return true;
        }

        if (fueraDeRango.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Notas fuera de rango',
                html: 'Cada nota debe estar <b>' + RANGO_TEXTO + '</b>.<br>'
                    + 'El <b>0</b> no se admite porque significa "sin nota"; '
                    + 'para dejar un campo vacío simplemente no lo llenes.<br><br>'
                    + 'Revisa estos campos:<br>' + listaHTML(fueraDeRango),
                confirmButtonText: 'Corregir',
            });
            return true;
        }

        if (vacios.length > 0) {
            const unicos = [...new Set(vacios)];
            Swal.fire({
                icon: 'error',
                title: 'Completa todos los casilleros',
                text: 'Para guardar como Final no puede quedar ningún campo vacío.\n\n'
                    + 'Estudiantes con campos vacíos:\n' + unicos.map(n => '• ' + n).join('\n'),
                confirmButtonText: 'OK',
            });
            return true;
        }

        return false;
    }

    const inputsEditables = () => {
        const selector = esExamen ? '.examen-input' : '.nota-input';
        return form.querySelectorAll(selector + ':not([readonly])');
    };

    form.querySelectorAll('button[data-tipo]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const tipo = this.dataset.tipo;

            if (revisarNotas(inputsEditables(), tipo === 'final')) {
                return;
            }

            if (tipo === 'parcial') {
                document.getElementById('tipo_guardado').value = 'parcial';
                form.submit();
                return;
            }

            Swal.fire({
                icon: 'question',
                title: '¿Guardado Final?',
                text: 'Una vez guardado como final no podrás editar las notas. ¿Estás de acuerdo?',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar como final',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
            }).then(result => {
                if (result.isConfirmed) {
                    document.getElementById('tipo_guardado').value = 'final';
                    form.submit();
                }
            });
        });
    });

    // Al pulsar Enter dentro de la tabla se valida igual que "Guardado Parcial".
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (revisarNotas(inputsEditables(), false)) {
            return;
        }
        document.getElementById('tipo_guardado').value = 'parcial';
        form.submit();
    });
})();
</script>
@endpush
@endsection