@extends('layouts.principal')

@section('titulo', 'Historial de citas')

@section('contenido')
@php
    $citasJson = $citas->map(function ($c) {
        return [
            'id' => $c->id,
            'fecha_hora' => $c->fecha_inicio->format('d/m/Y H:i'),
            'hora_fin' => $c->fecha_fin->format('H:i'),
            'estado' => $c->estado,
            'motivo' => (string) $c->motivo,
            'alumno' => $c->alumno ? trim($c->alumno->apellidos . ' ' . $c->alumno->nombres) : '—',
            'representante' => $c->alumno?->representante ? trim($c->alumno->representante->name . ' ' . $c->alumno->representante->apellidos) : '—',
            'vencida' => $c->fecha_inicio->lte(now()),
            'registrada' => $c->registrada(),
            'propia' => $c->docente_id === auth()->id(),
            'resultado' => $c->resultado,
            'dudas' => (bool) $c->dudas_aclaradas,
            'evidencia' => (string) $c->evidencia,
        ];
    })->values();
@endphp
<div class="container-fluid">

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-clipboard2-data me-2"></i>Historial de citas</h4>
            <small class="text-muted">Seleccione el nivel y el paralelo para ver el listado completo de estudiantes y sus citas.</small>
        </div>
        <div class="col-auto">
            <a href="{{ route('inspeccion.citas.agendar') }}" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-1"></i>Agendar cita
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inspeccion.citas.historial') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="nivel" class="form-label">Nivel</label>
                        <select name="nivel" id="nivel" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccione nivel...</option>
                            @foreach($niveles as $n)
                                <option value="{{ $n->id }}" @selected($nivelId === $n->id)>{{ $n->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="paralelo" class="form-label">Paralelo</label>
                        <select name="paralelo" id="paralelo" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los paralelos</option>
                            @foreach($paralelos as $p)
                                <option value="{{ $p->id }}" @selected($paraleloId === $p->id)>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="alumno" class="form-label">Estudiante</label>
                        <select name="alumno" id="alumno" class="form-select" onchange="this.form.submit()">
                            <option value="">Ver todos los estudiantes</option>
                            @foreach($alumnos as $a)
                                <option value="{{ $a->id }}" @selected($alumnoSel && $alumnoSel->id === $a->id)>
                                    {{ $a->apellidos }} {{ $a->nombres }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($alumnoSel)
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('inspeccion.citas.historial') }}">Historial</a></li>
                @if($nivelId)
                    <li class="breadcrumb-item">
                        <a href="{{ route('inspeccion.citas.historial', ['nivel' => $nivelId]) }}">
                            {{ $niveles->firstWhere('id', $nivelId)?->nombre }}
                        </a>
                    </li>
                @endif
                @if($paraleloId)
                    <li class="breadcrumb-item">
                        <a href="{{ route('inspeccion.citas.historial', ['nivel' => $nivelId, 'paralelo' => $paraleloId]) }}">
                            Paralelo {{ $paralelos->firstWhere('id', $paraleloId)?->nombre }}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $alumnoSel->apellidos }} {{ $alumnoSel->nombres }}</li>
            </ol>
        </nav>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted">Citas registradas</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold text-success">{{ $stats['asistio'] }}</div>
                <small class="text-muted">Asistieron</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold text-danger">{{ $stats['no_asistio'] }}</div>
                <small class="text-muted">No asistieron</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 h-100">
                <div class="fs-3 fw-bold {{ $stats['reincidentes'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $stats['reincidentes'] }}</div>
                <small class="text-muted">Reincidentes (3+ citas)</small>
            </div>
        </div>
    </div>

    @if($alumnoSel)
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-bounding-box me-1"></i>Estudiante</h6>
                    </div>
                    <div class="card-body">
                        <div class="fw-bold">{{ $alumnoSel->apellidos }} {{ $alumnoSel->nombres }}</div>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-person me-1"></i>Representante:
                            {{ $alumnoSel->representante ? nombreCorto($alumnoSel->representante->name) : 'sin usuario' }}
                        </div>

                        <h6 class="small text-uppercase text-muted">Resumen</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-primary">Total: {{ $stats['total'] }}</span>
                            <span class="badge bg-success">Asistió: {{ $stats['asistio'] }}</span>
                            <span class="badge bg-danger">No asistió: {{ $stats['no_asistio'] }}</span>
                            <span class="badge bg-secondary">Sin registrar: {{ $stats['sin_registrar'] }}</span>
                        </div>

                        <h6 class="small text-uppercase text-muted mt-3 mb-2">Asistencia de la familia citada</h6>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ $stats['asistencia'] }}%;"
                                 role="progressbar" aria-valuenow="{{ $stats['asistencia'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">{{ $stats['asistencia'] }}% asistieron de las {{ $stats['registradas'] }} citas con resultado</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Historial personal</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Citado por</th>
                                    <th>Motivo</th>
                                    <th>Resultado</th>
                                    <th>Registró</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($citas as $c)
                                    <tr>
                                        <td class="small text-nowrap">{{ $c->fecha_inicio->format('d/m/Y H:i') }}</td>
                                        <td class="small">{{ $c->docente ? nombreCorto($c->docente->name) : '—' }}</td>
                                        <td><small>{{ \Illuminate\Support\Str::limit($c->motivo, 70) }}</small></td>
                                        <td>
                                            @if($c->resultado === 'asistio')
                                                <span class="badge bg-success">Asistió</span>
                                            @elseif($c->resultado === 'no_asistio')
                                                <span class="badge bg-danger text-white">No asistió</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $c->fecha_inicio->lte(now()) ? 'Por registrar' : 'Pendiente' }}</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            @if($c->registrador)
                                                {{ nombreCorto($c->registrador->name) }}
                                                <span class="d-block">{{ $c->registrada_en?->format('d/m/Y H:i') }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $puedeRegistrar = $c->docente_id === auth()->id()
                                                    && ! $c->registrada()
                                                    && $c->fecha_inicio->lte(now());
                                            @endphp
                                            @if($puedeRegistrar)
                                                <button type="button" class="btn btn-sm btn-primary cita-clickable" data-cita-id="{{ $c->id }}">
                                                    <i class="bi bi-clipboard-check me-1"></i>Registrar
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary cita-clickable" data-cita-id="{{ $c->id }}">
                                                    <i class="bi bi-eye me-1"></i>Ver
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Este estudiante no tiene citas registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="bi bi-people me-1"></i>Estudiantes del nivel</h6>
                <small class="text-muted">{{ $stats['estudiantes'] }} estudiantes</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Estudiante</th>
                            <th>Representante</th>
                            <th class="text-center">Citas</th>
                            <th class="text-center">Asistió</th>
                            <th class="text-center">No asistió</th>
                            <th class="text-center">Sin registrar</th>
                            <th>Citado por</th>
                            <th>Última cita</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porAlumno as $fila)
                            <tr>
                                <td>
                                    <strong>{{ $fila['alumno']->apellidos }} {{ $fila['alumno']->nombres }}</strong>
                                    @if($fila['reincidente'])
                                        <span class="badge bg-danger text-white ms-1">Reincidente</span>
                                    @endif
                                </td>
                                <td class="small">{{ $fila['alumno']->representante ? nombreCorto($fila['alumno']->representante->name) : '—' }}</td>
                                <td class="text-center"><span class="badge bg-primary">{{ $fila['total'] }}</span></td>
                                <td class="text-center"><span class="badge bg-success">{{ $fila['asistio'] }}</span></td>
                                <td class="text-center"><span class="badge bg-danger">{{ $fila['no_asistio'] }}</span></td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $fila['sin_registrar'] }}</span></td>
                                <td class="small">{{ $fila['citadores'] ?: '—' }}</td>
                                <td class="small text-nowrap">
                                    @if($fila['ultima_fecha'])
                                        {{ $fila['ultima_fecha']->format('d/m/Y') }}
                                        <span class="d-block text-muted">{{ \Illuminate\Support\Str::limit($fila['ultimo_motivo'], 30) }}</span>
                                    @else
                                        <span class="text-muted">Nunca citado</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inspeccion.citas.historial', [
                                        'nivel' => $nivelId,
                                        'paralelo' => $paraleloId,
                                        'alumno' => $fila['alumno']->id,
                                    ]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-person-lines-fill me-1"></i>Historial
                                    </a>
                                </td>
                            </tr>
                        @empty
                                                    <tr><td colspan="9" class="text-center text-muted py-4">Seleccione un nivel o paralelo para ver los estudiantes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<div class="modal fade" id="citaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-event me-2"></i>Detalle y registro de la cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="citaModalInfo" class="mb-4"></div>

                <div id="citaFuturaMsg" class="alert alert-info d-none">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Esta cita aún no se ha celebrado. Podrás registrar el resultado cuando pase la hora de la cita.
                </div>

                <div id="citaAjenaMsg" class="alert alert-warning d-none">
                    <i class="bi bi-info-circle me-1"></i>
                    Esta cita fue agendada por otro usuario y está pendiente de registro. Solo quien la agendó puede registrar su resultado.
                </div>

                <div id="citaRegistroFormWrap" class="d-none">
                    <h6 class="mb-2"><i class="bi bi-clipboard-data me-1"></i>Registrar resultado de la cita</h6>
                    <form id="citaRegistroForm" method="POST" action="">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">¿El representante asistió a la cita?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="resultado" id="resAsistio" value="asistio" required>
                                <label class="form-check-label" for="resAsistio">Sí, asistió</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="resultado" id="resNoAsistio" value="no_asistio" required>
                                <label class="form-check-label" for="resNoAsistio">No asistió</label>
                            </div>
                        </div>

                        <div class="mb-3 d-none" id="dudasWrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dudas_aclaradas" id="dudasCheck" value="1">
                                <label class="form-check-label" for="dudasCheck">Sí, aclaró sus dudas</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Evidencia por escrito <span class="text-danger">*</span></label>
                            <textarea name="evidencia" id="evidenciaInput" class="form-control" rows="4" required
                                      placeholder="Describe por escrito lo que sucedió en la cita: temas tratados, acuerdos, compromisos del representante, etc. Esta evidencia queda guardada y no puede eliminarse."></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i>Guardar registro de la cita
                            </button>
                        </div>
                    </form>
                </div>

                <div id="citaActaWrap" class="d-none">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h6 class="mb-0"><i class="bi bi-journal-check me-1"></i>Acta de la cita (verificada)</h6>
                        <span class="badge bg-success">No se puede modificar</span>
                    </div>
                    <div id="citaActaResultado" class="mb-2"></div>
                    <div class="alert alert-light border" id="citaActaEvidencia"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const successEl = document.getElementById('swal-success');
    if (successEl) {
        Swal.fire({ icon: 'success', title: 'Listo', text: successEl.dataset.message, confirmButtonText: 'Aceptar' });
    }

    const citasContexto = @json($citasJson);

    const modal = new bootstrap.Modal(document.getElementById('citaModal'));
    const modalInfo = document.getElementById('citaModalInfo');
    const futuroMsg = document.getElementById('citaFuturaMsg');
    const ajenaMsg = document.getElementById('citaAjenaMsg');
    const registroWrap = document.getElementById('citaRegistroFormWrap');
    const registroForm = document.getElementById('citaRegistroForm');
    const actaWrap = document.getElementById('citaActaWrap');
    const actaResultado = document.getElementById('citaActaResultado');
    const actaEvidencia = document.getElementById('citaActaEvidencia');
    const dudasWrap = document.getElementById('dudasWrap');

    document.querySelectorAll('.cita-clickable').forEach(function (el) {
        el.addEventListener('click', function () {
            const c = citasContexto.find(function (x) { return String(x.id) === el.dataset.citaId; });
            if (c) abrirModal(c);
        });
    });

    function abrirModal(c) {
        modalInfo.innerHTML =
            '<div class="row g-3">' +
            '<div class="col-md-6"><span class="text-muted small d-block">Fecha y hora</span><strong>' + c.fecha_hora + ' - ' + c.hora_fin + '</strong></div>' +
            '<div class="col-md-6"><span class="text-muted small d-block">Estado</span><strong>' + c.estado.charAt(0).toUpperCase() + c.estado.slice(1) + '</strong></div>' +
            '<div class="col-md-6"><span class="text-muted small d-block">Estudiante</span><strong>' + c.alumno + '</strong></div>' +
            '<div class="col-md-6"><span class="text-muted small d-block">Representante citado</span><strong>' + c.representante + '</strong></div>' +
            '<div class="col-12"><span class="text-muted small d-block">Motivo</span>' + c.motivo + '</div>' +
            '</div>';

        actaWrap.classList.add('d-none');
        futuroMsg.classList.add('d-none');
        ajenaMsg.classList.add('d-none');
        registroWrap.classList.add('d-none');

        if (c.registrada) {
            const asistio = c.resultado === 'asistio';
            actaResultado.innerHTML =
                '<span class="badge bg-' + (asistio ? 'success' : 'danger') + ' text-white">' + (asistio ? 'El representante asistió' : 'El representante NO asistió') + '</span>' +
                '<span class="ms-2 badge bg-' + (c.dudas ? 'primary' : 'secondary') + '">' + (asistio ? (c.dudas ? 'Aclaró sus dudas' : 'No aclaró sus dudas') : 'Sin asistencia') + '</span>';
            actaEvidencia.textContent = c.evidencia || 'Sin evidencia registrada.';
            actaWrap.classList.remove('d-none');
        } else if (c.vencida && c.propia) {
            registroForm.action = '{{ url('inspeccion/citas') }}/' + c.id + '/registrar';
            registroForm.reset();
            dudasWrap.classList.add('d-none');
            registroWrap.classList.remove('d-none');
        } else if (c.vencida) {
            ajenaMsg.classList.remove('d-none');
        } else {
            futuroMsg.classList.remove('d-none');
        }

        modal.show();
    }

    const radiosAsistio = document.getElementById('resAsistio');
    const radiosNo = document.getElementById('resNoAsistio');
    const dudasCheck = document.getElementById('dudasCheck');

    function sincronizarDudas() {
        const asistio = radiosAsistio.checked;
        dudasWrap.classList.toggle('d-none', !asistio);
        if (asistio) {
            dudasCheck.required = true;
        } else {
            dudasCheck.required = false;
            dudasCheck.checked = false;
        }
    }

    radiosAsistio.addEventListener('change', sincronizarDudas);
    radiosNo.addEventListener('change', sincronizarDudas);

    registroForm.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({
            icon: 'question',
            title: '¿Guardar registro de la cita?',
            text: 'El resultado y la evidencia quedarán guardados de forma permanente y no podrán modificarse.',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                registroForm.submit();
            }
        });
    });
});
</script>
@endpush
