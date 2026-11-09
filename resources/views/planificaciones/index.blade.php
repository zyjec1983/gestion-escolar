@extends('layouts.principal')

@section('titulo', 'Planificaciones Académicas')

@section('contenido')
@php
    $rol = auth()->user()->rol;
    $esAutoridad = in_array($rol, ['ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA'], true);
    $puedeCrear = $rol === 'DOCENTE';
    $puedeIntervenir = $esAutoridad;
    $totalRegistros = $planificaciones->flatten(1)->count();
@endphp
<div class="container-fluid">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-journal-bookmark-fill me-2"></i>
                {{ $esAutoridad ? 'Planificaciones Académicas - Visitas Áulicas' : 'Planificaciones de Unidad' }}
            </h4>
            <small class="text-muted">
                @if($esAutoridad)
                    Módulo de consulta, supervisión y gestión administrativa institucional.
                @elseif($puedeCrear)
                    Administración de planificaciones académicas.
                @else
                    Listado de unidades planificadas por la institución.
                @endif
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($esAutoridad)
                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle align-self-center">
                    <i class="bi bi-person-shield me-1"></i>
                    Perfil: Rectorado / Vicerrectorado / Secretaría
                </span>
                <a href="{{ route('planificaciones.bitacora') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i>Bitácora
                </a>
            @endif
            @if($puedeCrear)
                <a href="{{ route('planificaciones.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Nueva Planificación
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div id="swal-perfil-success" data-message="{{ session('success') }}" hidden></div>
    @endif
    @if($errors->has('motivo'))
        <div id="swal-error-motivo" data-message="{{ $errors->first('motivo') }}" hidden></div>
    @endif

    @if($esAutoridad)
        <div class="alert alert-info border-0 shadow-sm d-flex gap-3 mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div>
                <strong class="d-block text-uppercase small">Directrices de uso</strong>
                <span class="small">
                    Las planificaciones son propiedad de los docentes titulares. Use
                    <strong>Revisar / Imprimir</strong> para preparar las visitas áulicas.
                    La intervención administrativa está reservada para ausencia definitiva,
                    abandono del puesto, despido o renuncia del docente titular.
                </span>
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('planificaciones.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="filtroMateria" class="form-label small text-muted mb-1">Materia</label>
                    <select name="materia_id" id="filtroMateria" class="form-select">
                        <option value="">Todas las materias</option>
                        @foreach($materias as $materia)
                            <option value="{{ $materia->id }}" @selected((string) ($filtros['materia_id'] ?? '') === (string) $materia->id)>
                                {{ $materia->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="filtroCurso" class="form-label small text-muted mb-1">Nivel</label>
                    <select name="curso_id" id="filtroCurso" class="form-select">
                        <option value="">Todos los niveles</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" @selected((string) ($filtros['curso_id'] ?? '') === (string) $curso->id)>
                                {{ $curso->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="filtroParalelo" class="form-label small text-muted mb-1">Paralelo</label>
                    <select name="paralelo_id" id="filtroParalelo" class="form-select">
                        <option value="">Todos los paralelos</option>
                        @foreach($paralelos as $paralelo)
                            <option value="{{ $paralelo->id }}" @selected((string) ($filtros['paralelo_id'] ?? '') === (string) $paralelo->id)>
                                {{ $paralelo->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="filtroDocente" class="form-label small text-muted mb-1">Docente</label>
                    <select name="docente_id" id="filtroDocente" class="form-select">
                        <option value="">Todos los docentes</option>
                        @foreach($docentes as $docente)
                            <option value="{{ $docente->id }}" @selected((string) ($filtros['docente_id'] ?? '') === (string) $docente->id)>
                                {{ $docente->nombreCompleto() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-xl-2 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-dark flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Aplicar
                    </button>
                    <a href="{{ route('planificaciones.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="bi bi-folder-tree me-2"></i>Planificaciones registradas</h6>
        <span class="badge bg-light text-dark border">{{ $totalRegistros }} registro(s)</span>
    </div>

    @forelse($planificaciones as $materiaNombre => $items)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold"><i class="bi bi-mortarboard me-2"></i>{{ $materiaNombre }}</span>
                <span class="badge bg-secondary">{{ $items->count() }} registro(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 tabla-planificaciones">
                    <thead class="table-light">
                        <tr>
                            <th>Unidad y contenido</th>
                            <th>Nivel / Paralelo</th>
                            <th>Quimestre / Parcial</th>
                            <th>Fechas / Duración</th>
                            <th>Docente titular</th>
                            @if($esAutoridad)
                                <th class="text-end">Acciones de auditoría</th>
                            @elseif($puedeCrear)
                                <th class="text-end">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $unidad)
                            @php $historialUnidad = $historial($unidad); @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <strong>{{ $unidad->titulo }}</strong>
                                        @if($historialUnidad->isNotEmpty())
                                            <span class="badge bg-warning text-dark" title="Planificación con intervención administrativa">
                                                <i class="bi bi-shield-exclamation me-1"></i>Intervenida
                                            </span>
                                        @endif
                                    </div>
                                    @if($unidad->descripcion)
                                        <small class="text-muted d-block text-truncate planificacion-descripcion" title="{{ $unidad->descripcion }}">
                                            {{ $unidad->descripcion }}
                                        </small>
                                    @endif
                                </td>
                                <td class="small fw-semibold text-nowrap">{{ $unidad->nivelParalelo() }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $unidad->quimestre ?: '—' }}</span>
                                    <span class="badge bg-light text-dark border">{{ $unidad->parcial ?: '—' }}</span>
                                </td>
                                <td class="small text-nowrap">
                                    {{ $unidad->fecha_inicio?->format('d/m/Y') }} → {{ $unidad->fecha_fin?->format('d/m/Y') }}
                                    <span class="badge bg-info text-dark d-inline-block mt-1">{{ $unidad->duracionDias() }} día(s)</span>
                                </td>
                                <td class="small">
                                    {{ $unidad->docenteTitular()?->nombreCompleto() ?? '—' }}
                                </td>
                                @if($esAutoridad)
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            <button type="button"
                                                    class="btn btn-sm btn-primary btn-revisar"
                                                    data-id="{{ $unidad->id }}"
                                                    title="Ver contenido completo y preparar impresión">
                                                <i class="bi bi-eye me-1"></i>Revisar / Imprimir
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning btn-intervencion"
                                                    data-id="{{ $unidad->id }}"
                                                    data-url="{{ route('planificaciones.intervencion', $unidad) }}"
                                                    data-titulo="{{ $unidad->titulo }}"
                                                    title="Intervención administrativa por ausencia, despido o renuncia del docente">
                                                <i class="bi bi-tools"></i>
                                            </button>
                                        </div>
                                    </td>
                                @elseif($puedeCrear)
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('planificaciones.edit', $unidad) }}" class="btn btn-sm btn-outline-primary" title="Editar planificación">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('planificaciones.destroy', $unidad) }}" method="POST" class="d-inline form-delete-unidad">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar planificación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                No hay planificaciones que coincidan con los filtros.
                <br>
                <a href="{{ route('planificaciones.index') }}" class="btn btn-outline-secondary btn-sm mt-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar filtros
                </a>
                @if($puedeCrear)
                    <a href="{{ route('planificaciones.create') }}" class="btn btn-primary btn-sm mt-3">
                        <i class="bi bi-plus-lg me-1"></i>Crear la primera
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

@if($esAutoridad)
    <div class="modal fade" id="modalRevisar" tabindex="-1" aria-labelledby="modalRevisarTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modalRevisarTitulo">Vista de supervisión áulica</h5>
                        <small class="text-muted" id="modalRevisarSubtitulo"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Materia</span>
                            <strong id="modalMateria">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Nivel / Paralelo</span>
                            <strong id="modalNivel">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Docente titular</span>
                            <strong id="modalDocente">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Quimestre / Parcial</span>
                            <strong id="modalQuimestre">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Fechas</span>
                            <strong id="modalFechas">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Duración</span>
                            <strong id="modalDuracion">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Periodo lectivo</span>
                            <strong id="modalPeriodo">—</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small d-block">Registrada por</span>
                            <strong id="modalCreadoPor">—</strong>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="fw-semibold">Contenido y objetivos</h6>
                        <div id="modalDescripcion" class="border rounded bg-light p-3 small modal-descripcion"></div>
                    </div>
                    <div class="mt-4">
                        <h6 class="fw-semibold"><i class="bi bi-shield-check me-1"></i>Historial de intervenciones</h6>
                        <div id="modalAuditoria" class="small"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="#" id="btnImprimirReporte" class="btn btn-primary" target="_blank" rel="noopener">
                        <i class="bi bi-printer me-1"></i>Imprimir para visita áulica
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="formIntervencion" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="motivo" id="motivoIntervencion">
    </form>
@endif
@endsection

@push('styles')
<style>
    .planificacion-descripcion { max-width: 360px; }
    .tabla-planificaciones { min-width: 980px; }
    .tabla-planificaciones thead th { white-space: nowrap; }
    .tabla-planificaciones th,
    .tabla-planificaciones td { overflow-wrap: normal; }
    .tabla-planificaciones .planificacion-descripcion { overflow-wrap: anywhere; }
    .tabla-planificaciones th:last-child,
    .tabla-planificaciones td:last-child {
        position: sticky;
        right: 0;
        background-color: var(--bs-table-bg);
        border-left: 1px solid var(--bs-border-color);
        z-index: 1;
    }
    .tabla-planificaciones thead th:last-child { z-index: 2; }
    .modal-descripcion { white-space: pre-wrap; overflow-wrap: anywhere; max-height: 220px; overflow-y: auto; }
    #modalAuditoria table { font-size: .8rem; }
    @media (max-width: 575.98px) {
        .planificacion-descripcion { max-width: 220px; }
    }
</style>
@endpush

@push('scripts')
<script type="application/json" id="paralelos-por-curso">@json($paralelosPorCurso)</script>
@if($esAutoridad)
<script type="application/json" id="planificaciones-data">@json($datosModal)</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('swal-perfil-success');
    if (el) {
        Swal.fire({ icon: 'success', title: 'Guardado', text: el.dataset.message, confirmButtonText: 'Aceptar' });
    }

    const errorMotivo = document.getElementById('swal-error-motivo');
    if (errorMotivo) {
        Swal.fire({ icon: 'error', title: 'Motivo requerido', text: errorMotivo.dataset.message, confirmButtonText: 'Entendido' });
    }

    @if($puedeCrear)
    document.querySelectorAll('.form-delete-unidad').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar esta planificación?',
                text: 'Se eliminará la unidad y sus réplicas en otros paralelos.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
    @endif

    @if($esAutoridad)
    const datos = JSON.parse(document.getElementById('planificaciones-data').textContent || '{}');
    const modalElement = document.getElementById('modalRevisar');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

    function setText(id, value) {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = value || '—';
    }

    function renderAuditoria(registros) {
        const contenedor = document.getElementById('modalAuditoria');
        contenedor.innerHTML = '';

        if (!registros || !registros.length) {
            const parrafo = document.createElement('p');
            parrafo.className = 'text-muted mb-0';
            parrafo.textContent = 'Sin intervenciones administrativas registradas.';
            contenedor.appendChild(parrafo);
            return;
        }

        const table = document.createElement('table');
        table.className = 'table table-sm table-bordered align-middle mb-0';
        const thead = document.createElement('thead');
        thead.innerHTML = '<tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Motivo</th></tr>';
        const tbody = document.createElement('tbody');

        registros.forEach(function (auditoria) {
            const fila = document.createElement('tr');
            [auditoria.fecha, auditoria.usuario + ' (' + auditoria.rol + ')', auditoria.accion, auditoria.motivo].forEach(function (valor) {
                const celda = document.createElement('td');
                celda.textContent = valor || '—';
                fila.appendChild(celda);
            });
            tbody.appendChild(fila);
        });

        table.appendChild(thead);
        table.appendChild(tbody);
        contenedor.appendChild(table);
    }

    document.querySelectorAll('.btn-revisar').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const dato = datos[boton.dataset.id];
            if (!dato) return;

            setText('modalRevisarTitulo', dato.titulo);
            setText('modalRevisarSubtitulo', [dato.materia, dato.nivelParalelo].filter(Boolean).join(' · '));
            setText('modalMateria', dato.materia);
            setText('modalNivel', dato.nivelParalelo);
            setText('modalDocente', dato.docente);
            setText('modalQuimestre', [dato.quimestre, dato.parcial].filter(Boolean).join(' - '));
            setText('modalFechas', (dato.fechaInicio || '—') + ' → ' + (dato.fechaFin || '—'));
            setText('modalDuracion', dato.duracion + ' día(s)');
            setText('modalPeriodo', dato.periodo);
            setText('modalCreadoPor', dato.creadoPor);

            const descripcion = document.getElementById('modalDescripcion');
            descripcion.textContent = dato.descripcion || 'Sin contenido registrado.';

            renderAuditoria(dato.auditorias);
            document.getElementById('btnImprimirReporte').href = dato.printUrl + '?auto=1';

            if (modal) modal.show();
        });
    });

    const formIntervencion = document.getElementById('formIntervencion');
    const motivoIntervencion = document.getElementById('motivoIntervencion');

    document.querySelectorAll('.btn-intervencion').forEach(function (boton) {
        boton.addEventListener('click', function () {
            Swal.fire({
                icon: 'warning',
                title: 'Intervención Administrativa Extraordinaria',
                html: '<div class="text-start small">' +
                    '<p class="mb-2">Está a punto de modificar la planificación <strong></strong>.</p>' +
                    '<div class="alert alert-warning text-start small mb-2">' +
                    '<strong>Aviso:</strong> este cambio quedará registrado permanentemente en la bitácora de auditoría con su usuario, la fecha exacta y el motivo indicado.' +
                    '</div>' +
                    '<p class="mb-1">Motivo obligatorio de la intervención:</p>' +
                    '<p class="text-muted mb-0">Ej.: Reemplazo por renuncia del titular, abandono del puesto o despido.</p>' +
                    '</div>',
                input: 'textarea',
                inputPlaceholder: 'Describa el motivo de la intervención...',
                inputAttributes: { 'aria-label': 'Motivo de la intervención' },
                showCancelButton: true,
                confirmButtonText: 'Autorizar y desbloquear edición',
                confirmButtonColor: '#d97706',
                cancelButtonText: 'Cancelar',
                inputValidator: function (valor) {
                    if (!valor || valor.trim().length < 10) {
                        return 'Debe especificar un motivo de al menos 10 caracteres.';
                    }
                },
                didOpen: function () {
                    const contenedor = Swal.getHtmlContainer();
                    if (!contenedor) return;
                    const strong = contenedor.querySelector('strong');
                    if (strong) strong.textContent = boton.dataset.titulo || 'seleccionada';
                }
            }).then(function (resultado) {
                if (resultado.isConfirmed && formIntervencion && motivoIntervencion) {
                    formIntervencion.action = boton.dataset.url;
                    motivoIntervencion.value = resultado.value.trim();
                    formIntervencion.submit();
                }
            });
        });
    });
    @endif

    const cursoSelect = document.getElementById('filtroCurso');
    const paraleloSelect = document.getElementById('filtroParalelo');

    if (cursoSelect && paraleloSelect) {
        const mapa = JSON.parse(document.getElementById('paralelos-por-curso').textContent || '{}');
        const opciones = Array.from(paraleloSelect.options).map(function (opcion) {
            return { value: opcion.value, text: opcion.textContent };
        });
        const seleccionado = paraleloSelect.value;

        function actualizarParalelos() {
            const curso = cursoSelect.value;
            const permitidos = curso ? (mapa[curso] || []).map(String) : null;

            paraleloSelect.innerHTML = '';

            opciones.forEach(function (opcion) {
                if (opcion.value === '' || !permitidos || permitidos.indexOf(opcion.value) !== -1) {
                    const nueva = document.createElement('option');
                    nueva.value = opcion.value;
                    nueva.textContent = opcion.text;
                    if (opcion.value === seleccionado) nueva.selected = true;
                    paraleloSelect.appendChild(nueva);
                }
            });
        }

        cursoSelect.addEventListener('change', function () {
            if (paraleloSelect.value) paraleloSelect.value = '';
            actualizarParalelos();
        });

        actualizarParalelos();
    }
});
</script>
@endpush
