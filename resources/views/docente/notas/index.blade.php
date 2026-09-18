@extends('layouts.principal')

@section('titulo', 'Notas')

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
                                        <input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center examen-input" name="notas[{{ $alumno->id }}][nota_examen]" value="{{ $n && $ne > 0 ? number_format($ne, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required>
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
                                <th class="text-center">FII<br><small>10%</small></th>
                                <th class="text-center">FFI<br><small>10%</small></th>
                                <th class="text-center">FIG<br><small>10%</small></th>
                                <th class="text-center">FFG<br><small>10%</small></th>
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
                                    <td><input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][fii]" value="{{ $n && $fii > 0 ? number_format($fii, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][ffi]" value="{{ $n && $ffi > 0 ? number_format($ffi, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][fig]" value="{{ $n && $fig > 0 ? number_format($fig, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][ffg]" value="{{ $n && $ffg > 0 ? number_format($ffg, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td class="text-center fw-bold form70">{{ number_format($trunc($form70), 2, '.', '') }}</td>
                                    <td><input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][proyecto_integrador]" value="{{ $n && $pi > 0 ? number_format($pi, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
                                    <td><input type="number" step="0.01" min="0.01" max="10" class="form-control form-control-sm text-center nota-input" name="notas[{{ $alumno->id }}][evaluacion_parcial]" value="{{ $n && $ep > 0 ? number_format($ep, 2, '.', '') : '' }}" placeholder="0.00" {{ $esFinal ? 'readonly' : '' }} required></td>
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

    form.querySelectorAll('.nota-input').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            if (!row) return;
            const fii = parseFloat(row.querySelector('[name$="[fii]"]')?.value) || 0;
            const ffi = parseFloat(row.querySelector('[name$="[ffi]"]')?.value) || 0;
            const fig = parseFloat(row.querySelector('[name$="[fig]"]')?.value) || 0;
            const ffg = parseFloat(row.querySelector('[name$="[ffg]"]')?.value) || 0;
            const pi = parseFloat(row.querySelector('[name$="[proyecto_integrador]"]')?.value) || 0;
            const ep = parseFloat(row.querySelector('[name$="[evaluacion_parcial]"]')?.value) || 0;
            const form70 = ((fii + ffi + fig + ffg) / 40) * 7;
            const eval30 = ((pi + ep) / 20) * 3;
            const total = form70 + eval30;
            row.querySelector('.form70').textContent = trunc2(form70).toFixed(2);
            row.querySelector('.eval30').textContent = trunc2(eval30).toFixed(2);
            row.querySelector('.total').textContent = trunc2(total).toFixed(2);
        });
    });

    form.querySelectorAll('button[data-tipo]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const tipo = this.dataset.tipo;

            if (tipo === 'parcial') {
                document.getElementById('tipo_guardado').value = 'parcial';
                form.submit();
                return;
            }

            const selector = esExamen ? '.examen-input' : '.nota-input';
            const inputs = form.querySelectorAll(selector + ':not([readonly])');
            inputs.forEach(inp => inp.classList.remove('is-invalid'));

            const vacios = [];
            inputs.forEach(inp => {
                if (!inp.value || inp.value.trim() === '') {
                    inp.classList.add('is-invalid');
                    const nombre = inp.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim();
                    if (nombre && !vacios.includes(nombre)) vacios.push(nombre);
                }
            });

            if (vacios.length > 0) {
                const lista = vacios.map(n => '• ' + n).join('\n');
                Swal.fire({
                    icon: 'error',
                    title: 'Completa todos los casilleros',
                    text: 'Estudiantes con campos vacíos:\n' + lista,
                    confirmButtonText: 'OK',
                });
                return;
            }

            const invalidos = [];
            inputs.forEach(inp => {
                const val = parseFloat(inp.value);
                if (isNaN(val) || val <= 0 || val > 10) {
                    inp.classList.add('is-invalid');
                    const nombre = inp.closest('tr')?.querySelector('td:nth-child(2)')?.textContent?.trim();
                    const campo = inp.name.match(/\[(\w+)\]$/)?.[1] ?? '';
                    invalidos.push(nombre + ' (' + campo + ')');
                }
            });

            if (invalidos.length > 0) {
                const lista = invalidos.map(n => '• ' + n).join('\n');
                Swal.fire({
                    icon: 'error',
                    title: 'Notas fuera de rango',
                    text: 'Cada nota debe ser > 0 y ≤ 10. Revisa:\n' + lista,
                    confirmButtonText: 'OK',
                });
                return;
            }

            const inputsConComa = [];
            form.querySelectorAll(selector).forEach(inp => {
                if (inp.value.includes(',')) {
                    inputsConComa.push(inp.name);
                }
            });
            if (inputsConComa.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Usa punto (.) como separador decimal',
                    text: 'Campos con coma: ' + inputsConComa.join(', '),
                    confirmButtonText: 'Corregir',
                });
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
})();
</script>
@endpush
@endsection