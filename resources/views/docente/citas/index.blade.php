@extends('layouts.principal')

@section('titulo', 'Citas')

@section('contenido')
@php
    $citasContexto = $citasDelDia->concat($citasPendientes)->concat($citasPorRegistrar)->unique('id')->values();
    $citasJson = $citasContexto->map(function ($c) {
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
            'resultado' => $c->resultado,
            'dudas' => (bool) $c->dudas_aclaradas,
            'evidencia' => (string) $c->evidencia,
        ];
    })->values();
@endphp
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-calendar-event me-2"></i>Agenda de Citas</h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('docente.citas.index', ['anio' => $prevYear, 'mes' => $prevMonth]) }}"
                           class="btn btn-outline-secondary btn-sm">&larr; Anterior</a>
                        <h5 class="mb-0">{{ strftime('%B %Y', $date->timestamp) }}</h5>
                        <a href="{{ route('docente.citas.index', ['anio' => $nextYear, 'mes' => $nextMonth]) }}"
                           class="btn btn-outline-secondary btn-sm">Siguiente &rarr;</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dom</th><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calendar as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            @php
                                                $tieneCita = $day && in_array($day, $diasConCitas);
                                                $isToday = $day && \Carbon\Carbon::createFromDate($year, $month, $day)->isToday();
                                            @endphp
                                            <td class="{{ $day ? ($tieneCita ? 'bg-danger bg-opacity-10' : '') : 'bg-light' }}" style="min-height:60px;">
                                                @if($day)
                                                    <a href="{{ route('docente.citas.index', ['anio' => $year, 'mes' => $month, 'dia' => $day]) }}"
                                                       class="d-block p-2 text-decoration-none {{ $isToday ? 'btn btn-primary btn-sm' : 'text-dark' }}">
                                                        {{ $day }}
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($diaSeleccionado)
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            Citas del {{ \Carbon\Carbon::createFromDate($year, $month, $diaSeleccionado)->format('d/m/Y') }}
                        </h6>
                        @if($citasDelDia->count() > 0)
                            <span class="badge bg-warning text-dark">{{ $citasDelDia->count() }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($citasDelDia->isEmpty())
                            <div class="alert alert-info mb-0">No hay citas para este día.</div>
                        @else
                            <ul class="list-group">
                                @foreach($citasDelDia as $cita)
                                    <li class="list-group-item list-group-item-action cita-clickable"
                                        role="button" data-cita-id="{{ $cita->id }}">
                                        <strong>{{ $cita->fecha_inicio->format('H:i') }} - {{ $cita->fecha_fin->format('H:i') }}</strong>
                                        @if($cita->registrada())
                                            <span class="badge bg-success">Registrada</span>
                                        @elseif($cita->fecha_inicio->lte(now()))
                                            <span class="badge bg-warning text-dark">Por registrar</span>
                                        @else
                                            <span class="badge bg-{{ $cita->estado == 'confirmada' ? 'success' : ($cita->estado == 'cancelada' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($cita->estado) }}
                                            </span>
                                        @endif
                                        @if($cita->alumno)
                                            <br><small class="text-muted">Estudiante: <strong>{{ $cita->alumno->apellidos }} {{ $cita->alumno->nombres }}</strong></small>
                                            @if($cita->alumno->representante)
                                                <br><small class="text-muted">Representante: <strong>{{ $cita->alumno->representante->name }}</strong></small>
                                            @endif
                                        @endif
                                        <p class="mb-0 mt-1 small">{{ $cita->motivo }}</p>
                                        <small class="text-primary"><i class="bi bi-mouse me-1"></i>Clic para ver detalle o registrar</small>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Solicitudes de cita pendientes</h6>
                    <span class="badge bg-warning text-dark">{{ $citasPendientes->count() }}</span>
                </div>
                <div class="card-body">
                    @if($citasPendientes->isEmpty())
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            No tienes solicitudes de cita pendientes.
                        </div>
                    @else
                        <ul class="list-group">
                            @foreach($citasPendientes as $cita)
                                <li class="list-group-item list-group-item-action cita-clickable"
                                    role="button" data-cita-id="{{ $cita->id }}">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-12 col-md-3">
                                            <strong>{{ $cita->fecha_inicio->format('d/m/Y') }}</strong>
                                            <span class="text-muted d-block">{{ $cita->fecha_inicio->format('H:i') }} - {{ $cita->fecha_fin->format('H:i') }}</span>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            @if($cita->alumno)
                                                <strong>{{ $cita->alumno->apellidos }} {{ $cita->alumno->nombres }}</strong>
                                                @if($cita->alumno->representante)
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-person me-1"></i>{{ $cita->alumno->representante->name }}
                                                    </small>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <small class="text-muted">{{ $cita->motivo }}</small>
                                        </div>
                                        <div class="col-12 col-md-2 text-md-end">
                                            <span class="badge bg-warning">Pendiente</span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Citas vencidas por registrar</h6>
                    <span class="badge bg-danger">{{ $citasPorRegistrar->count() }}</span>
                </div>
                <div class="card-body">
                    @if($citasPorRegistrar->isEmpty())
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            No hay citas vencidas pendientes de registro.
                        </div>
                    @else
                        <div class="alert alert-warning py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            La hora de estas citas ya pasó. Haz clic en cada una para registrar si el representante asistió, si aclaró sus dudas y deja la evidencia por escrito. Este registro no podrá eliminarse.
                        </div>
                        <ul class="list-group">
                            @foreach($citasPorRegistrar as $cita)
                                <li class="list-group-item list-group-item-action cita-clickable"
                                    role="button" data-cita-id="{{ $cita->id }}">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-12 col-md-3">
                                            <strong>{{ $cita->fecha_inicio->format('d/m/Y') }}</strong>
                                            <span class="text-muted d-block">{{ $cita->fecha_inicio->format('H:i') }}</span>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            @if($cita->alumno)
                                                <strong>{{ $cita->alumno->apellidos }} {{ $cita->alumno->nombres }}</strong>
                                                @if($cita->alumno->representante)
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-person me-1"></i>{{ $cita->alumno->representante->name }}
                                                    </small>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <small class="text-muted">{{ $cita->motivo }}</small>
                                        </div>
                                        <div class="col-12 col-md-2 text-md-end">
                                            <span class="badge bg-danger text-white">Por registrar</span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Agendar Nueva Cita</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <label class="form-label">Clase</label>
                        <select name="clase_docente_id" class="form-select" onchange="this.form.submit()">
                            @foreach($clases as $c)
                                <option value="{{ $c->id }}" {{ $clase && $clase->id == $c->id ? 'selected' : '' }}>
                                    {{ $c->curso->nombre ?? 'N/A' }} - {{ $c->paralelo->nombre ?? 'N/A' }} - {{ $c->materia->nombre ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="anio" value="{{ $year }}">
                        <input type="hidden" name="mes" value="{{ $month }}">
                        @if($diaSeleccionado)
                            <input type="hidden" name="dia" value="{{ $diaSeleccionado }}">
                        @endif
                    </form>

                    <form method="POST" action="{{ route('docente.citas.store') }}">
                        @csrf
                        <input type="hidden" name="clase_docente_id" value="{{ $clase?->id }}">

                        <div class="mb-3">
                            <label class="form-label">Estudiante</label>
                            <select name="alumno_id" class="form-select" required>
                                <option value="">Seleccionar estudiante...</option>
                                @foreach($alumnos as $al)
                                    <option value="{{ $al->id }}">
                                        {{ $al->apellidos }} {{ $al->nombres }}
                                        @if($al->representante)
                                            (Rep: {{ $al->representante->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control"
                                   min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <select name="hora" class="form-select" required>
                                        <option value="">HH</option>
                                        @for($h = 8; $h <= 15; $h++)
                                            <option value="{{ $h }}">{{ sprintf('%02d', $h) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="minuto" class="form-select" required>
                                        <option value="">MM</option>
                                        <option value="0">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <select name="motivo" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                @foreach($motivos as $motivo)
                                    <option value="{{ $motivo }}">{{ $motivo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje (opcional)</label>
                            <textarea name="mensaje" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-calendar-plus me-1"></i>Agendar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
                                <label class="form-check-label" for="dudasCheck">
                                    Sí, aclaró sus dudas
                                </label>
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

        const registrada = c.registrada;
        const vencida = c.vencida;

        actaWrap.classList.add('d-none');
        futuroMsg.classList.add('d-none');
        registroWrap.classList.add('d-none');

        if (registrada) {
            const asistio = c.resultado === 'asistio';
            actaResultado.innerHTML =
                '<span class="badge bg-' + (asistio ? 'success' : 'danger') + ' text-white">' + (asistio ? 'El representante asistió' : 'El representante NO asistió') + '</span>' +
                '<span class="ms-2 badge bg-' + (c.dudas ? 'primary' : 'secondary') + '">' + (asistio ? (c.dudas ? 'Aclaró sus dudas' : 'No aclaró sus dudas') : 'Sin asistencia') + '</span>';
            actaEvidencia.textContent = c.evidencia || 'Sin evidencia registrada.';
            actaWrap.classList.remove('d-none');
        } else if (vencida) {
            registroForm.action = '/docente/citas/' + c.id + '/registrar';
            registroForm.reset();
            dudasWrap.classList.add('d-none');
            registroWrap.classList.remove('d-none');
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