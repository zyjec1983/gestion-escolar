@extends('layouts.principal')

@section('titulo', 'Mensajes')

@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-envelope me-2"></i>Mensajes</h4>
        <p class="text-muted mb-0">Bandeja de mensajes con docentes, estudiantes y representantes.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-danger btn-sm" type="button" id="btnEliminarSel" disabled>
            <i class="bi bi-trash me-1"></i> Eliminar seleccionados
        </button>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevo">
            <i class="bi bi-pencil-square me-1"></i> Redactar
        </button>
    </div>
</div>

@if(session('exito'))
    <div id="swal-success" data-message="{{ session('exito') }}" class="d-none"></div>
@endif

<ul class="nav nav-pills mb-2">
    <li class="nav-item">
        <span class="nav-link active">
            Conversaciones
            @if($no_leidos_total > 0)
                <span class="badge bg-danger ms-1">{{ $no_leidos_total }}</span>
            @endif
        </span>
    </li>
    <li class="nav-item align-self-center ms-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="selTodos">
            <label class="form-check-label small" for="selTodos">Seleccionar todos</label>
        </div>
    </li>
</ul>

<div class="card shadow-sm">
    <div class="row g-0">
        <div class="col-12 col-lg-5 col-xl-4 border-end" id="colLista">
            <div class="lista-hilos" style="max-height: 72vh; overflow-y: auto; display: grid; grid-template-columns: 40px 1fr; align-content: start;">
                @forelse($hilos as $hilo)
                    <div class="thread-cell text-center py-3 {{ $hilo['no_leidos'] > 0 ? 'bg-light' : '' }} {{ request()->query('hilo') == $hilo['id'] ? 'thread-active' : '' }}" style="grid-column: 1;">
                        <input type="checkbox" class="form-check-input sel-hilo" value="{{ $hilo['id'] }}" onclick="event.stopPropagation()">
                    </div>
                    <div class="thread-cell py-2 thread-click {{ request()->query('hilo') == $hilo['id'] ? 'thread-active' : '' }} {{ $hilo['no_leidos'] > 0 ? 'bg-light' : '' }}"
                         data-id="{{ $hilo['id'] }}" data-url="{{ route('mensajes.conversacion', $hilo['id']) }}" style="grid-column: 2; cursor: pointer;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle fs-4 me-2 text-secondary"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small text-muted mb-1">{{ $hilo['fecha'] }}</div>
                                <div class="d-flex justify-content-between">
                                    <strong class="text-truncate {{ $hilo['no_leidos'] > 0 ? 'fw-bold' : 'fw-normal' }}">{{ $hilo['interlocutor'] }}</strong>
                                </div>
                                <div class="text-truncate small">
                                    <span class="{{ $hilo['no_leidos'] > 0 ? 'fw-bold' : 'fw-normal' }}">{{ $hilo['ultimo_asunto'] }}</span>
                                    @if($hilo['contexto_nombre']) <span class="badge bg-light text-dark border ms-1">Sobre {{ $hilo['contexto_nombre'] }}</span> @endif
                                </div>
                                @if($hilo['ultimo_cuerpo'])
                                    <div class="text-truncate text-muted small">{{ $hilo['ultimo_cuerpo'] }}</div>
                                @endif
                            </div>
                            @if($hilo['no_leidos'] > 0)
                                <span class="badge rounded-pill bg-danger ms-2">{{ $hilo['no_leidos'] }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5" style="grid-column: 1 / -1;">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No hay mensajes en esta carpeta.
                    </div>
                @endforelse
            </div>
        </div>
        <div class="col-12 col-lg-7 col-xl-8 d-none d-lg-flex flex-column border-start" id="colPanel" data-hilo-abierto="{{ $datosHilo ? '1' : '0' }}" style="height: 72vh;">
            <div class="border-bottom p-1 bg-light d-lg-none">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnVolverLista">
                    <i class="bi bi-arrow-left me-1"></i> Conversaciones
                </button>
            </div>
            <div id="panelLectura" class="flex-grow-1 d-flex flex-column overflow-hidden" style="min-height: 0;">
                @if($datosHilo)
                    @include('mensajes._conversacion', $datosHilo + ['retorno' => route('mensajes.index', ['hilo' => $datosHilo['anchor_id']])])
                @else
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted p-4">
                        <div class="text-center">
                            <i class="bi bi-envelope-open fs-1 mb-2 d-block"></i>
                            Seleccione un mensaje para leer la conversación.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="frmEliminar" method="POST" action="{{ route('mensajes.destroy') }}">
    @csrf
    @method('DELETE')
</form>

<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('mensajes.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i>Nuevo mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($compositor['rol'] === 'DOCENTE')
                        <div class="row g-2 mb-2">
                            <div class="col-md-5">
                                <label class="form-label">Curso / Nivel</label>
                                <select id="compClase" class="form-select">
                                    <option value="">Seleccione curso...</option>
                                    @foreach($compositor['clases'] as $clase)
                                        <option value="{{ $clase['id'] }}">{{ $clase['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estudiante</label>
                                <select id="compEstudiante" class="form-select" disabled>
                                    <option value="">Seleccione estudiante...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Representante legal</label>
                                <div id="compRepresentanteBox" class="border rounded px-2 py-1 bg-light small" style="min-height: 38px;">
                                    <strong id="compRepresentanteNombre" class="text-muted">—</strong>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Enviar a:</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="tipo_destinatario" id="compARep" value="representante" checked>
                                    <label class="form-check-label" for="compARep">Representante legal</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" class="form-check-input" name="tipo_destinatario" id="compAEst" value="estudiante">
                                    <label class="form-check-label" for="compAEst">Estudiante</label>
                                </div>
                            </div>
                        </div>
                    @elseif($compositor['rol'] === 'INSPECCION')
                        <div class="mb-2">
                            <label for="compAlcance" class="form-label fw-semibold">Alcance de envío</label>
                            <select name="alcance" id="compAlcance" class="form-select">
                                <option value="">Seleccione alcance...</option>
                                <option value="estudiante">Un estudiante y su representante</option>
                                <option value="curso">Todos los de un curso</option>
                                <option value="nivel">Todos de un nivel educativo</option>
                                <option value="comunidad">Toda la comunidad educativa</option>
                            </select>
                        </div>

                        <div id="boxEstudianteIns" class="d-none">
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label for="insCurso" class="form-label">Curso</label>
                                    <select id="insCurso" class="form-select"></select>
                                </div>
                                <div class="col-md-4">
                                    <label for="insParalelo" class="form-label">Paralelo</label>
                                    <select id="insParalelo" class="form-select" disabled></select>
                                </div>
                                <div class="col-md-4">
                                    <label for="insAlumno" class="form-label">Estudiante</label>
                                    <select name="alumno_id" id="insAlumno" class="form-select" disabled></select>
                                </div>
                            </div>
                        </div>

                        <div id="boxCursoIns" class="d-none mb-2">
                            <label for="insCursoBatch" class="form-label">Curso</label>
                            <select name="curso_id" id="insCursoBatch" class="form-select">
                                <option value="">Seleccione curso...</option>
                                @foreach($compositor['cursos'] as $c)
                                    <option value="{{ $c['id'] }}">{{ $c['nombre'] }} ({{ $c['total_alumnos'] }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="boxNivelIns" class="d-none mb-2">
                            <label for="insNivel" class="form-label">Nivel educativo</label>
                            <select name="nivel_educativo" id="insNivel" class="form-select">
                                <option value="basica">Básica ({{ $compositor['cursos']->where('es_bachillerato', false)->sum('total_alumnos') }} estudiantes)</option>
                                <option value="bachillerato">Bachillerato ({{ $compositor['cursos']->where('es_bachillerato', true)->sum('total_alumnos') }} estudiantes)</option>
                            </select>
                        </div>

                        <div id="boxComunidadIns" class="d-none mb-2">
                            <div class="alert alert-info py-2 px-3 small mb-0">
                                Llegará a todos los estudiantes, representantes, docentes y autoridades del centro.
                            </div>
                        </div>
                    @else
                        <div class="row g-2 mb-2">
                            <div class="col-md-5">
                                <label class="form-label">Materia</label>
                                <select id="compMateria" class="form-select">
                                    <option value="">Seleccione materia...</option>
                                    @foreach($compositor['materias'] as $materia)
                                        <option value="{{ $materia['id'] }}">{{ $materia['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Docente</label>
                                <select id="compDocente" class="form-select" disabled>
                                    <option value="">Seleccione docente...</option>
                                </select>
                            </div>
                        </div>
                    @endif
                    <input type="hidden" name="destinatario_id" id="composerDestinatario" value="">
                    <input type="hidden" name="contexto_estudiante_id" id="composerContexto" value="">
                    <input type="hidden" name="retorno" value="{{ route('mensajes.index', ['hilo' => request()->query('hilo')]) }}">
                    <small class="text-muted" id="compAyuda"></small>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Asunto</label>
                        <input type="text" name="asunto" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea name="cuerpo_contenido" rows="4" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjunto (opcional, imágenes o PDF, máx. 5 MB)</label>
                        <input type="file" name="adjunto" class="form-control" accept=".pdf,image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('panelLectura');

    function reiniciarDestinatario() {
        document.getElementById('composerDestinatario').value = '';
        document.getElementById('composerContexto').value = '';
        var ayuda = document.getElementById('compAyuda');
        if (ayuda) ayuda.textContent = '';
    }

    function enlazarPanel() {
        panel.querySelectorAll('form.p-eliminar-hilo').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var msg = form.dataset.confirm || '';
                if (msg && !confirm(msg)) e.preventDefault();
            });
        });
    }

    function esMovil() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function mostrarPanel() {
        if (!esMovil()) return;
        document.getElementById('colLista').classList.add('d-none');
        var pane = document.getElementById('colPanel');
        pane.classList.remove('d-none');
        pane.classList.add('d-flex');
    }

    function volverALista() {
        var pane = document.getElementById('colPanel');
        pane.classList.add('d-none');
        pane.classList.remove('d-flex');
        document.getElementById('colLista').classList.remove('d-none');
    }

    function abrirConversacion(info) {
        var cell = info.closest ? info.closest('.thread-click') : info;
        if (!cell) return;
        var url = cell.dataset.url;
        var hiloId = cell.dataset.id || '';

        document.querySelectorAll('.thread-active').forEach(function (el) { el.classList.remove('thread-active'); });

        fetch(url)
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) {
                panel.innerHTML = html;
                enlazarPanel();
                document.querySelectorAll('.thread-cell').forEach(function (celda) {
                    var cb = celda.querySelector('input.sel-hilo');
                    if (cb && String(cb.value) === String(hiloId)) celda.classList.add('thread-active');
                });
                if (window.history && window.history.replaceState) {
                    var urlObj = new URL(window.location.href);
                    urlObj.searchParams.set('hilo', hiloId);
                    window.history.replaceState({}, '', urlObj.toString());
                }
                mostrarPanel();
            })
            .catch(function () {
                panel.innerHTML = '<div class="text-center text-muted p-5"><i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>No se pudo cargar la conversación.</div>';
                mostrarPanel();
            });
    }

    document.querySelectorAll('.thread-click').forEach(function (cell) {
        cell.addEventListener('click', function (e) {
            if (e.target.closest('a') || e.target.closest('input')) return;
            abrirConversacion(cell);
        });
    });

    var eliminarHiloForm = document.getElementById('frmEliminar');
    document.getElementById('btnEliminarSel').addEventListener('click', function () {
        var seleccion = document.querySelectorAll('.sel-hilo:checked');
        if (!seleccion.length) return;
        if (!confirm('¿Eliminar ' + seleccion.length + ' mensaje(s) de tu bandeja? El otro participante conservará su copia.')) return;
        eliminarHiloForm.querySelectorAll('input[name="ids[]"]').forEach(function (i) { i.remove(); });
        seleccion.forEach(function (cb) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'ids[]';
            inp.value = cb.value;
            eliminarHiloForm.appendChild(inp);
        });
        eliminarHiloForm.submit();
    });

    var selTodos = document.getElementById('selTodos');
    selTodos.addEventListener('change', function () {
        document.querySelectorAll('.sel-hilo').forEach(function (cb) { cb.checked = selTodos.checked; });
        actualizarBotonEliminar();
    });

    document.querySelectorAll('.sel-hilo').forEach(function (cb) {
        cb.addEventListener('change', actualizarBotonEliminar);
    });

    function actualizarBotonEliminar() {
        var n = document.querySelectorAll('.sel-hilo:checked').length;
        document.getElementById('btnEliminarSel').disabled = n === 0;
    }

    enlazarPanel();

    var btnVolver = document.getElementById('btnVolverLista');
    if (btnVolver) btnVolver.addEventListener('click', volverALista);

    if (document.getElementById('colPanel').dataset.hiloAbierto === '1') {
        mostrarPanel();
    }

    var comp = @json($compositor);
    var selClase = document.getElementById('compClase');
    if (selClase) {
        var selEstudiante = document.getElementById('compEstudiante');
        var repNombre = document.getElementById('compRepresentanteNombre');
        var radioRep = document.getElementById('compARep');
        var radioEst = document.getElementById('compAEst');
        var estudianteActual = null;

        function claseSeleccionada() {
            return (comp.clases || []).find(function (c) { return String(c.id) === selClase.value; });
        }

        function actualizarDestinatario() {
            if (!estudianteActual) return reiniciarDestinatario();
            var esRep = radioRep.checked;
            var id = esRep ? estudianteActual.representante_id : estudianteActual.estudiante_user_id;
            if (!id) {
                if (esRep) {
                    radioEst.checked = true;
                    id = estudianteActual.estudiante_user_id;
                } else {
                    radioRep.checked = true;
                    id = estudianteActual.representante_id;
                }
            }
            document.getElementById('composerDestinatario').value = id || '';
            document.getElementById('composerContexto').value = estudianteActual.id;
            var ayuda = document.getElementById('compAyuda');
            if (ayuda) {
                ayuda.textContent = id
                    ? 'Se enviará a: ' + (radioRep.checked ? (estudianteActual.representante_nombre || 'Representante') : estudianteActual.label) + ' (Estudiante)'
                    : 'Este contacto no tiene usuario registrado para recibir mensajes.';
            }
        }

        selClase.addEventListener('change', function () {
            selEstudiante.innerHTML = '<option value="">Seleccione estudiante...</option>';
            selEstudiante.disabled = true;
            selEstudiante.value = '';
            repNombre.textContent = '—';
            repNombre.classList.add('text-muted');
            estudianteActual = null;
            reiniciarDestinatario();
            var clase = claseSeleccionada();
            if (!clase) return;
            (clase.estudiantes || []).forEach(function (est) {
                var opt = document.createElement('option');
                opt.value = est.id;
                opt.textContent = est.label;
                selEstudiante.appendChild(opt);
            });
            selEstudiante.disabled = false;
            var ayuda = document.getElementById('compAyuda');
            if (ayuda) ayuda.textContent = 'Seleccione un estudiante para elegir destinatario.';
        });

        selEstudiante.addEventListener('change', function () {
            if (!selEstudiante.value) {
                estudianteActual = null;
                return reiniciarDestinatario();
            }
            var clase = claseSeleccionada();
            estudianteActual = (clase.estudiantes || []).find(function (e) { return String(e.id) === selEstudiante.value; }) || null;
            repNombre.textContent = estudianteActual.representante_nombre || 'Sin representante registrado';
            repNombre.classList.toggle('text-muted', !estudianteActual.representante_nombre);
            radioEst.disabled = !estudianteActual.estudiante_user_id;
            if (!radioEst.disabled && !radioRep.checked && !estudianteActual.representante_id) {
                radioEst.checked = true;
            }
            actualizarDestinatario();
        });

        radioRep.addEventListener('change', actualizarDestinatario);
        radioEst.addEventListener('change', actualizarDestinatario);
    }

    var selAlcanceIns = document.getElementById('compAlcance');
    if (selAlcanceIns) {
        var insCurso = document.getElementById('insCurso');
        var insParalelo = document.getElementById('insParalelo');
        var insAlumno = document.getElementById('insAlumno');
        var boxesIns = {
            estudiante: document.getElementById('boxEstudianteIns'),
            curso: document.getElementById('boxCursoIns'),
            nivel: document.getElementById('boxNivelIns'),
            comunidad: document.getElementById('boxComunidadIns'),
        };

        function llenarOpciones(select, items, textoVacio) {
            select.innerHTML = '';
            var vacia = document.createElement('option');
            vacia.value = '';
            vacia.textContent = textoVacio;
            select.appendChild(vacia);
            items.forEach(function (it) {
                var opt = document.createElement('option');
                opt.value = it.id;
                opt.textContent = it.nombre;
                select.appendChild(opt);
            });
        }

        function cursoIns() {
            return (comp.cursos || []).find(function (c) { return String(c.id) === insCurso.value; }) || null;
        }

        selAlcanceIns.addEventListener('change', function () {
            var alcance = selAlcanceIns.value;
            Object.keys(boxesIns).forEach(function (k) {
                if (boxesIns[k]) boxesIns[k].classList.add('d-none');
            });
            if (boxesIns[alcance]) boxesIns[alcance].classList.remove('d-none');

            var ayuda = document.getElementById('compAyuda');
            var textos = {
                estudiante: 'Seleccione el estudiante: el mensaje llegará a él y a su representante.',
                curso: 'Se enviará a todos los alumnos del curso y a sus representantes.',
                nivel: 'Se enviará a todos los alumnos del nivel y a sus representantes.',
                comunidad: 'Se enviará a toda la comunidad educativa del centro.',
            };
            if (ayuda) ayuda.textContent = textos[alcance] || '';

            if (alcance === 'estudiante') {
                if (!insCurso.options.length) {
                    llenarOpciones(insCurso, (comp.cursos || []).filter(function (c) { return c.total_alumnos > 0; }), 'Seleccione curso...');
                }
                insParalelo.disabled = true;
                insAlumno.disabled = true;
            }
        });

        insCurso.addEventListener('change', function () {
            insParalelo.innerHTML = '<option value="">Todos los paralelos</option>';
            insAlumno.innerHTML = '<option value="">Todos los estudiantes</option>';
            insAlumno.disabled = true;
            var curso = cursoIns();
            if (!curso) { insParalelo.disabled = true; return; }

            llenarOpciones(insParalelo, curso.paralelos.filter(function (p) { return p.alumnos.length; }), 'Todos los paralelos');
            insParalelo.disabled = false;
        });

        insParalelo.addEventListener('change', function () {
            var curso = cursoIns();
            if (!curso) return;
            var paralelos = curso.paralelos.filter(function (p) { return p.alumnos.length; });
            var elegido = paralelos.find(function (p) { return String(p.id) === insParalelo.value; });
            var alumnos = elegido ? elegido.alumnos : [].concat.apply([], paralelos.map(function (p) { return p.alumnos; }));

            insAlumno.innerHTML = '<option value="">Seleccione estudiante...</option>';
            alumnos.forEach(function (a) {
                var opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.label;
                insAlumno.appendChild(opt);
            });
            insAlumno.disabled = alumnos.length === 0;
        });
    }

    var selMateria = document.getElementById('compMateria');
    if (selMateria) {
        var selDocente = document.getElementById('compDocente');

        selMateria.addEventListener('change', function () {
            selDocente.innerHTML = '<option value="">Seleccione docente...</option>';
            selDocente.disabled = true;
            selDocente.value = '';
            reiniciarDestinatario();
            if (!selMateria.value) return;
            var materia = (comp.materias || []).find(function (m) { return String(m.id) === selMateria.value; });
            (materia.docentes || []).forEach(function (d) {
                var opt = document.createElement('option');
                opt.value = d.id;
                opt.dataset.contexto = d.contexto_estudiante_id || '';
                opt.textContent = d.nombre;
                selDocente.appendChild(opt);
            });
            selDocente.disabled = false;
            var ayuda = document.getElementById('compAyuda');
            if (ayuda) ayuda.textContent = 'Seleccione el docente de la materia.';
        });

        selDocente.addEventListener('change', function () {
            if (!selDocente.value) return reiniciarDestinatario();
            document.getElementById('composerDestinatario').value = selDocente.value;
            var opt = selDocente.options[selDocente.selectedIndex];
            document.getElementById('composerContexto').value = opt.dataset.contexto || '';
            var ayuda = document.getElementById('compAyuda');
            if (ayuda) ayuda.textContent = 'Se enviará a: ' + opt.textContent;
        });
    }

    window.abrirConversacion = abrirConversacion;
});
</script>
<style>
    .thread-active { background-color: #e7f1ff !important; }
</style>
@endpush