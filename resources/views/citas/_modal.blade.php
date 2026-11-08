@php
    // URL base del controlador que registra el resultado, según el rol que
    // muestra las citas (docente, inspección, rector, vicerrector, DECE...).
    $registrarBase = rtrim($registrarUrlBase ?? url('inspeccion/citas'), '/');
@endphp

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const successEl = document.getElementById('swal-success');
    if (successEl) {
        Swal.fire({ icon: 'success', title: 'Listo', text: successEl.dataset.message, confirmButtonText: 'Aceptar' });
    }

    const citasContexto = @json($citasJson ?? []);
    const registrarBase = @json($registrarBase);

    const modalEl = document.getElementById('citaModal');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
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
            registroForm.action = registrarBase + '/' + c.id + '/registrar';
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
