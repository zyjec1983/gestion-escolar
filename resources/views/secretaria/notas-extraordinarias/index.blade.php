@extends('layouts.principal')

@section('titulo', 'Corrección extraordinaria de notas')

@push('styles')
<style>
    .notas-hero {
        background: linear-gradient(135deg, #0f766e 0%, #0e7490 55%, #1e3a8a 100%);
    }
    .nota-valor {
        font-variant-numeric: tabular-nums;
    }
    .tabla-notas th {
        white-space: nowrap;
    }
    /* El formulario es el único hijo directo de .modal-content. Debe ser flex
       para que .modal-body pueda desplazarse dentro del modal scrollable y el
       pie con los botones quede siempre visible. */
    .form-correccion {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }
    @media (max-width: 767.98px) {
        .tabla-notas thead { display: none; }
        .tabla-notas tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            margin-bottom: .75rem;
            padding: .5rem;
        }
        .tabla-notas tbody td {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border: 0;
            padding: .35rem .5rem;
        }
        .tabla-notas tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            font-size: .78rem;
            text-transform: uppercase;
        }
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="notas-hero rounded-4 p-3 p-md-4 mb-4 text-white shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-white text-dark mb-2">Secretaría General</span>
                <h4 class="mb-1"><i class="bi bi-shield-exclamation me-2"></i>Corrección extraordinaria de notas</h4>
                <div class="small text-white-50">
                    Error de digitación, recalificación o ajuste autorizado. Cada cambio exige motivo y queda en una bitácora inalterable.
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('notas-extraordinarias.bitacora') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-journal-text me-1"></i>Bitácora
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif
    @if($errors->any())
        <div id="swal-error" data-message="{{ $errors->first() }}" hidden></div>
    @endif

    <div class="alert alert-info border-0 shadow-sm d-flex gap-3 mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div class="small">
            <strong>Uso restringido:</strong> esta pantalla solo permite corregir notas existentes. Los docentes siguen siendo responsables de la carga ordinaria.
            Una nota final solo puede modificarse desde aquí y el registro conserva usuario, fecha, IP, agente de usuario y valores anteriores y nuevos.
            <strong>No es posible editar ni borrar la bitácora.</strong>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Seleccionar clase y parcial</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('notas-extraordinarias.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label for="clase_docente_id" class="form-label small fw-semibold">Clase</label>
                    <select name="clase_docente_id" id="clase_docente_id" class="form-select">
                        @forelse($clases as $opcionClase)
                            <option value="{{ $opcionClase->id }}" @selected($clase?->id === $opcionClase->id)>
                                {{ $opcionClase->materia?->nombre ?? 'Sin materia' }}
                                · {{ $opcionClase->curso?->nombre ?? 'Sin curso' }}
                                · {{ $opcionClase->paralelo?->nombre ?? '—' }}
                                · {{ $opcionClase->docente?->nombreCompleto() ?? 'Sin docente' }}
                            </option>
                        @empty
                            <option value="">No hay clases registradas</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="parcial" class="form-label small fw-semibold">Parcial / período</label>
                    <select name="parcial" id="parcial" class="form-select">
                        @foreach($grupos as $grupo => $items)
                            <optgroup label="{{ $grupo }}">
                                @foreach($items as $opcion)
                                    <option value="{{ $opcion['parcial'] }}" @selected((int) $parcial === (int) $opcion['parcial'])>
                                        {{ $opcion['label'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-grid">
                    <button type="submit" class="btn btn-teal text-white" style="background:#0f766e;">
                        <i class="bi bi-search me-1"></i>Consultar notas
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(! $clase)
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No hay clases registradas para consultar.
            </div>
        </div>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="small text-muted">
                <strong>{{ $clase->materia?->nombre ?? 'Sin materia' }}</strong>
                · {{ $clase->curso?->nombre ?? 'Sin curso' }} {{ $clase->paralelo?->nombre ?? '' }}
                · Docente: {{ $clase->docente?->nombreCompleto() ?? 'Sin docente' }}
            </div>
            <span class="badge bg-light text-dark border">
                {{ $tipo === 'examen' ? 'Examen quimestral' : 'Parcial' }} · {{ count($alumnos) }} estudiantes
            </span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 tabla-notas">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:48px;">#</th>
                                <th>Estudiante</th>
                                <th>Nota actual</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Correcciones</th>
                                <th class="text-end pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alumnos as $i => $alumno)
                                @php
                                    $nota = $notasExistentes->get($alumno->id);
                                    $esExamen = $nota && $nota->tipo_nota === 'examen';
                                @endphp
                                <tr>
                                    <td class="ps-3 text-muted" data-label="#">{{ $i + 1 }}</td>
                                    <td data-label="Estudiante">
                                        <div class="fw-semibold">{{ $alumno->apellidos }} {{ $alumno->nombres }}</div>
                                        <small class="text-muted">{{ $alumno->numero_cedula ?: $alumno->codigo }}</small>
                                    </td>
                                    <td data-label="Nota actual">
                                        @if(! $nota)
                                            <span class="text-muted small">Sin nota registrada</span>
                                        @elseif($esExamen)
                                            <span class="nota-valor fw-semibold">{{ number_format((float) $nota->nota_examen, 2) }}</span>
                                            <small class="text-muted">/ 10 · examen</small>
                                        @else
                                            <div class="d-flex flex-wrap gap-2 small">
                                                <span class="badge bg-light text-dark border">FII {{ number_format((float) $nota->fii, 2) }}</span>
                                                <span class="badge bg-light text-dark border">FFI {{ number_format((float) $nota->ffi, 2) }}</span>
                                                <span class="badge bg-light text-dark border">FIG {{ number_format((float) $nota->fig, 2) }}</span>
                                                <span class="badge bg-light text-dark border">FFG {{ number_format((float) $nota->ffg, 2) }}</span>
                                                <span class="badge bg-light text-dark border">PI {{ number_format((float) $nota->proyecto_integrador, 2) }}</span>
                                                <span class="badge bg-light text-dark border">EP {{ number_format((float) $nota->evaluacion_parcial, 2) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Estado">
                                        @if(! $nota)
                                            <span class="badge bg-secondary">Sin registro</span>
                                        @elseif($nota->estado === 'final')
                                            <span class="badge bg-success">Final</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Correcciones">
                                        @if($nota && $nota->auditorias_count > 0)
                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                                {{ $nota->auditorias_count }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3" data-label="Acción">
                                        @if($nota)
                                            <button type="button" class="btn btn-sm btn-outline-teal"
                                                    style="--bs-btn-color:#0f766e;--bs-btn-border-color:#0f766e;--bs-btn-hover-bg:#0f766e;--bs-btn-hover-color:#fff;"
                                                    data-bs-toggle="modal" data-bs-target="#modalNota{{ $nota->id }}">
                                                <i class="bi bi-pencil-square me-1"></i>Corregir
                                            </button>
                                        @else
                                            <span class="small text-muted">El docente debe cargar la nota</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No hay estudiantes matriculados en esta clase y período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @foreach($notasExistentes as $nota)
            @php($esExamen = $nota->tipo_nota === 'examen')
            <div class="modal fade" id="modalNota{{ $nota->id }}" tabindex="-1" aria-labelledby="modalNotaLabel{{ $nota->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('notas-extraordinarias.store') }}" class="form-correccion">
                            @csrf
                            <input type="hidden" name="nota_id" value="{{ $nota->id }}">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title" id="modalNotaLabel{{ $nota->id }}">
                                        <i class="bi bi-shield-exclamation me-2"></i>Corrección extraordinaria
                                    </h5>
                                    <small class="text-muted">
                                        @php($etiquetaParcial = collect($opciones)->firstWhere('parcial', $nota->parcial)['label'] ?? ('Parcial '.$nota->parcial))
                                        {{ $nota->alumno?->apellidos }} {{ $nota->alumno?->nombres }}
                                        · {{ $clase->materia?->nombre }}
                                        · {{ $etiquetaParcial }}
                                    </small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Los valores actuales se reemplazarán. El registro anterior quedará guardado en la bitácora y no podrá editarse ni eliminarse.
                                </div>

                                <div class="row g-3">
                                    @if($esExamen)
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold" for="nota_examen_{{ $nota->id }}">Nota del examen</label>
                                            <input type="text" inputmode="decimal" class="form-control" id="nota_examen_{{ $nota->id }}"
                                                   name="nota_examen" value="{{ number_format((float) $nota->nota_examen, 2, '.', '') }}" required>
                                            <div class="form-text">Valor anterior: {{ number_format((float) $nota->nota_examen, 2) }} / 10</div>
                                        </div>
                                    @else
                                        @foreach([
                                            'fii' => 'FII', 'ffi' => 'FFI', 'fig' => 'FIG', 'ffg' => 'FFG',
                                            'proyecto_integrador' => 'Proyecto integrador', 'evaluacion_parcial' => 'Evaluación parcial',
                                        ] as $campo => $etiqueta)
                                            <div class="col-6 col-md-4">
                                                <label class="form-label small fw-semibold" for="{{ $campo }}_{{ $nota->id }}">{{ $etiqueta }}</label>
                                                <input type="text" inputmode="decimal" class="form-control" id="{{ $campo }}_{{ $nota->id }}"
                                                       name="{{ $campo }}" value="{{ number_format((float) $nota->{$campo}, 2, '.', '') }}" required>
                                                <div class="form-text">Antes: {{ number_format((float) $nota->{$campo}, 2) }}</div>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="col-12">
                                        <label class="form-label small fw-semibold" for="motivo_{{ $nota->id }}">Motivo de la corrección <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="motivo_{{ $nota->id }}" name="motivo" rows="3" minlength="10" maxlength="1000" required
                                                  placeholder="Ej.: Error de digitación en la nota FII, se corrige según acta física firmada por el docente."></textarea>
                                        <div class="form-text">Obligatorio, mínimo 10 caracteres. Quedará visible en la bitácora.</div>
                                    </div>

                                    @if($nota->estado !== 'final')
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="marcar_final" value="1" id="marcar_final_{{ $nota->id }}">
                                                <label class="form-check-label small" for="marcar_final_{{ $nota->id }}">
                                                    Marcar la nota como final después de la corrección
                                                </label>
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <span class="badge bg-success">Nota final</span>
                                            <span class="small text-muted ms-1">La corrección conserva el estado final.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn text-white" style="background:#0f766e;">
                                    <i class="bi bi-shield-check me-1"></i>Registrar corrección
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.form-correccion').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    title: '¿Registrar corrección extraordinaria?',
                    text: 'La nota anterior quedará guardada de forma inalterable en la bitácora.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0f766e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, corregir',
                    cancelButtonText: 'Revisar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
