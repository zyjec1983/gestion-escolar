@extends('layouts.aula')

@section('titulo', ($clase_actual['materia'] ?? '') . ' - Agenda')

@section('contenido-aula')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>{{ $clase_actual['materia'] ?? '' }}</h4>
        <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
    </div>
    @if($clase_actual['unidad_actual'] ?? false)
    <div class="text-end">
        <small class="text-muted d-block">Unidad actual:</small>
        <strong>{{ $clase_actual['unidad_actual']['titulo'] }}</strong>
    </div>
    @endif
</div>

@if(session('success'))
    <div id="swal-success" data-message="{{ session('success') }}" class="d-none"></div>
@endif

@if($es_docente)
<form id="frmAgenda" method="POST" action="{{ route('clases.agenda.store', $clase->id) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="agenda_id" id="agendaId" value="">

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong class="small"><i class="bi bi-plus-circle me-1"></i><span id="formTitulo">Nueva asignación</span></strong>
            <span>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-cancelar-edicion d-none"><i class="bi bi-x-lg me-1"></i>Cancelar edición</button>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#formAgenda">Mostrar / ocultar</button>
            </span>
        </div>
        <div class="collapse show" id="formAgenda">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-7">
                        <label class="form-label small mb-1">Título de la actividad <span class="text-danger">*</span></label>
                        <input type="text" name="titulo_agenda" id="agTitulo" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Tipo de actividad <span class="text-danger">*</span></label>
                        <select name="tipo_actividad_id" id="agTipo" class="form-select form-select-sm" required>
                            <option value="">Seleccione...</option>
                            @foreach($tipo_actividades as $ta)
                                <option value="{{ $ta->codigo }}">{{ $ta->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Unidad de planificación</label>
                        <select name="unidad_id" id="agUnidad" class="form-select form-select-sm">
                            <option value="">(Sin unidad)</option>
                            @foreach($unidades as $unidad)
                                <option value="{{ $unidad->id }}">
                                    {{ $unidad->titulo }}
                                    @if($unidad->quimestre && $unidad->parcial) (Q{{ $unidad->quimestre }}·P{{ $unidad->parcial }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Insumo de evaluación</label>
                        <select name="insumo_evaluacion_id" id="agInsumo" class="form-select form-select-sm">
                            <option value="">(Ninguno)</option>
                            @foreach($insumos as $insumo)
                                <option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Fecha de inicio <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="fecha_inicio" id="agFecha" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Fecha de entrega límite</label>
                        <input type="datetime-local" name="fecha_entrega_limite" id="agEntrega" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small mb-1">Enlace (link del recurso)</label>
                        <input type="url" name="enlace" id="agEnlace" class="form-control form-control-sm" placeholder="https://...">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="w-100">
                            <input type="file" name="adjunto" id="agAdjunto" class="form-control form-control-sm">
                            <small class="text-muted" id="avisoAdjunto">Máximo 5 MB.</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Instrucciones / descripción</label>
                        <textarea name="descripcion_instrucciones_html" id="agDescripcion" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="text-center my-4">
    <button type="submit" form="frmAgenda" id="btnEnviarAgenda" class="btn btn-success btn-lg px-5 shadow-sm">
        <i class="bi bi-send me-2"></i><span id="btnEnviarTexto">Enviar asignación</span>
    </button>
    <p class="text-muted small mt-2 mb-0">Solo se enviará a los estudiantes marcados en el panel derecho.</p>
</div>
@endif

<div class="list-group mb-3">
    @forelse($agendas as $agenda)
    <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="flex-grow-1" style="min-width: 0;">
                <span class="badge bg-primary mb-1">{{ $agenda->tipoActividad?->nombre ?? 'Actividad' }}</span>
                @if($agenda->unidad)
                    <span class="badge bg-light text-dark border text-wrap text-start">Unidad: {{ $agenda->unidad->titulo }}</span>
                @endif
                @if($agenda->insumoEvaluacion)
                    <span class="badge bg-light text-dark border text-wrap text-start">Insumo: {{ $agenda->insumoEvaluacion->nombre }}</span>
                @endif
                <strong class="d-block mt-1">{{ $agenda->titulo_agenda }}</strong>
                <small class="text-muted">
                    <i class="bi bi-calendar-event me-1"></i>{{ $agenda->fecha_inicio?->format('d/m/Y H:i') }}
                    @if($agenda->fecha_entrega_limite)
                        <span class="ms-2"><i class="bi bi-clock me-1"></i>Entrega: {{ $agenda->fecha_entrega_limite->format('d/m/Y H:i') }}</span>
                    @endif
                </small>
                <small class="d-block text-muted">
                    <i class="bi bi-people me-1"></i>{{ $agenda->alumnos->count() }} estudiante(s)
                </small>
                @if($agenda->descripcion_instrucciones_html)
                    <div class="small text-muted mt-1">{!! nl2br(e($agenda->descripcion_instrucciones_html)) !!}</div>
                @endif
                @if($agenda->enlace)
                    <div class="mt-1">
                        <a href="{{ $agenda->enlace }}" target="_blank" rel="noopener" class="small"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir enlace</a>
                    </div>
                @endif
                @if($agenda->archivosAdjuntos->count())
                    <div class="mt-1">
                        @foreach($agenda->archivosAdjuntos as $adj)
                            <a href="{{ $adj->url }}" target="_blank" class="small me-2"><i class="bi bi-paperclip me-1"></i>{{ $adj->nombre }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($es_docente)
            <div class="d-flex flex-column gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary btn-editar-agenda"
                        data-url="{{ route('clases.agenda.edit', [$clase->id, $agenda->id]) }}">
                    <i class="bi bi-pencil-square"></i> Editar
                </button>
                <form method="POST" action="{{ route('clases.agenda.destroy', [$clase->id, $agenda->id]) }}" class="d-inline confirm-swal" data-titulo="Eliminar actividad" data-mensaje="¿Eliminar la actividad '{{ $agenda->titulo_agenda }}'?">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Eliminar</button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="list-group-item text-center text-muted py-5">
        <i class="bi bi-clipboard-check fs-2 d-block mb-2"></i>
        No hay asignaciones para esta clase.
    </div>
    @endforelse
</div>

@if($es_docente)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('frmAgenda');
    var editando = false;

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    var selTodos = document.getElementById('selTodosEstudiantes');
    if (selTodos) {
        selTodos.addEventListener('change', function () {
            document.querySelectorAll('.se-alumno').forEach(function (cb) { cb.checked = selTodos.checked; });
        });
    }

    function marcarAlumnos(ids) {
        document.querySelectorAll('.se-alumno').forEach(function (cb) {
            cb.checked = ids.indexOf(String(cb.value)) !== -1;
        });
        if (selTodos) selTodos.checked = document.querySelectorAll('.se-alumno:not(:checked)').length === 0;
    }

    var confirmando = false;
    form.addEventListener('submit', function (e) {
        var sel = document.querySelectorAll('.se-alumno:checked');
        if (!sel.length) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Sin destinatarios', text: 'Seleccione al menos un estudiante en el panel derecho.' });
            return;
        }
        if (confirmando) { confirmando = false; return; }

        e.preventDefault();

        var titulo = document.getElementById('agTitulo').value.trim() || '(sin título)';
        var tipo = document.getElementById('agTipo').selectedOptions[0] ? document.getElementById('agTipo').selectedOptions[0].text : '-';
        var nombres = Array.from(sel).slice(0, 5).map(function (c) { return c.dataset.nombre; }).join(', ');
        if (sel.length > 5) nombres += ', +' + (sel.length - 5) + ' más';

        Swal.fire({
            title: editando ? '¿Guardar los cambios de la asignación?' : '¿Enviar la asignación?',
            html: '<div class="text-start small">'
                + '<p class="mb-1"><b>Título:</b> ' + escHtml(titulo) + '</p>'
                + '<p class="mb-1"><b>Tipo:</b> ' + escHtml(tipo) + '</p>'
                + '<p class="mb-1"><b>Destinatarios (' + sel.length + '):</b><br>' + escHtml(nombres) + '</p>'
                + '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: editando ? 'Sí, guardar' : 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then(function (res) {
            if (res.isConfirmed) {
                confirmando = true;
                form.requestSubmit();
                setTimeout(function () { confirmando = false; }, 1500);
            }
        });
    });

    document.querySelectorAll('.btn-editar-agenda').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch(btn.dataset.url)
                .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                .then(function (d) {
                    document.getElementById('agendaId').value = d.id;
                    document.getElementById('agTitulo').value = d.titulo_agenda || '';
                    document.getElementById('agTipo').value = d.tipo_actividad_id || '';
                    document.getElementById('agUnidad').value = d.unidad_id || '';
                    document.getElementById('agInsumo').value = d.insumo_evaluacion_id || '';
                    document.getElementById('agFecha').value = d.fecha_inicio || '';
                    document.getElementById('agEntrega').value = d.fecha_entrega_limite || '';
                    document.getElementById('agDescripcion').value = d.descripcion_instrucciones_html || '';
                    document.getElementById('agEnlace').value = d.enlace || '';
                    document.getElementById('agAdjunto').value = '';
                    document.getElementById('avisoAdjunto').textContent = d.tiene_adjunto
                        ? 'Tiene un adjunto. Si sube otro, lo reemplazará.'
                        : 'Máximo 5 MB.';
                    marcarAlumnos((d.alumnos || []).map(String));
                    editando = true;
                    document.getElementById('formTitulo').textContent = 'Editar asignación';
                    document.getElementById('btnEnviarTexto').textContent = 'Guardar asignación';
                    document.querySelector('.btn-cancelar-edicion').classList.remove('d-none');
                    document.getElementById('formAgenda').classList.add('show');
                    document.getElementById('frmAgenda').scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(function () { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la asignación.' }); });
        });
    });

    document.querySelector('.btn-cancelar-edicion').addEventListener('click', function () {
        document.getElementById('agendaId').value = '';
        document.getElementById('frmAgenda').reset();
        marcarAlumnos(Array.from(document.querySelectorAll('.se-alumno')).map(function (cb) { return cb.value; }));
        editando = false;
        document.getElementById('formTitulo').textContent = 'Nueva asignación';
        document.getElementById('btnEnviarTexto').textContent = 'Enviar asignación';
        document.getElementById('avisoAdjunto').textContent = 'Máximo 5 MB.';
        this.classList.add('d-none');
    });

    document.querySelectorAll('form.confirm-swal').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: f.dataset.titulo || '¿Eliminar?',
                text: f.dataset.mensaje || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (res) { if (res.isConfirmed) f.submit(); });
        });
    });
});
</script>
@endif
@endsection