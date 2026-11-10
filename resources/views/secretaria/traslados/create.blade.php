@extends('layouts.principal')

@section('titulo', 'Nuevo registro de traslado')

@push('styles')
<style>
    .traslado-hero {
        background: linear-gradient(135deg, #0f766e 0%, #115e59 60%, #0f172a 100%);
    }
    .seccion-titulo {
        font-size: .78rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
    }
    .check-item {
        border-bottom: 1px solid #f1f5f9;
        padding: .55rem 0;
    }
    .check-item:last-child { border-bottom: 0; }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="traslado-hero rounded-4 p-3 p-md-4 mb-4 text-white shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-white text-dark mb-2">Paso 1 de 2</span>
                <h4 class="mb-1"><i class="bi bi-folder-plus me-2"></i>Nuevo registro de traslado</h4>
                <div class="small text-white-50">Registre el expediente y luego adjunte los documentos en la ficha del estudiante.</div>
            </div>
            <a href="{{ route('traslados.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al listado
            </a>
        </div>
    </div>

    @if($errors->any())
        <div id="swal-error" data-message="{{ $errors->first() }}" hidden></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Ficha del expediente</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('traslados.store') }}" id="formTraslado">
                        @csrf

                        <div class="mb-4">
                            <div class="seccion-titulo mb-2">Tipo de trámite</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo" id="tipoIngreso" value="ingreso"
                                           @checked(old('tipo', 'ingreso') === 'ingreso')>
                                    <label class="btn btn-outline-success w-100 py-3" for="tipoIngreso">
                                        <i class="bi bi-box-arrow-in-right d-block fs-4"></i>
                                        <strong>Ingreso</strong>
                                        <span class="d-block small">Viene de otra institución</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo" id="tipoSalida" value="salida"
                                           @checked(old('tipo') === 'salida')>
                                    <label class="btn btn-outline-danger w-100 py-3" for="tipoSalida">
                                        <i class="bi bi-box-arrow-right d-block fs-4"></i>
                                        <strong>Salida / retiro</strong>
                                        <span class="d-block small">Va hacia otra institución</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="seccion-titulo mb-2">Estudiante</div>
                            <div class="mb-3 d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modo_alumno" id="modoExistente" value="existente"
                                           @checked(old('modo_alumno', 'existente') === 'existente')>
                                    <label class="form-check-label" for="modoExistente">Estudiante ya registrado</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modo_alumno" id="modoNuevo" value="nuevo"
                                           @checked(old('modo_alumno') === 'nuevo')>
                                    <label class="form-check-label" for="modoNuevo">Estudiante nuevo</label>
                                </div>
                            </div>

                            <div id="bloqueExistente">
                                @php($matriculaSeleccionada = $alumnoSeleccionado?->matriculas->first())
                                <label for="buscarAlumno" class="form-label small fw-semibold">Buscar estudiante</label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                        <input type="search" class="form-control" id="buscarAlumno"
                                               placeholder="Escriba apellidos, nombres, cédula o código..."
                                               autocomplete="off" role="combobox" aria-expanded="false"
                                               aria-controls="resultadosAlumno" aria-autocomplete="list"
                                               aria-describedby="ayudaBuscarAlumno">
                                    </div>
                                    <input type="hidden" name="alumno_id" id="alumno_id" value="{{ $alumnoSeleccionado?->id }}">
                                    <div id="resultadosAlumno" class="list-group position-absolute w-100 shadow-sm d-none"
                                         role="listbox" aria-label="Estudiantes encontrados"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto;"></div>
                                </div>
                                <div id="alumnoSeleccionado"
                                     class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3 mt-2 mb-0 {{ $alumnoSeleccionado ? '' : 'd-none' }}">
                                    <div class="small">
                                        <i class="bi bi-person-check me-1"></i>
                                        <strong id="alumnoSeleccionadoNombre">{{ $alumnoSeleccionado?->nombreCompleto() }}</strong>
                                        <span id="alumnoSeleccionadoDetalle" class="text-muted">
                                            @if($alumnoSeleccionado)
                                                — {{ $alumnoSeleccionado->numero_cedula ?: $alumnoSeleccionado->codigo }}{{ $matriculaSeleccionada ? ' · '.trim(($matriculaSeleccionada->curso?->nombre ?? '').' '.($matriculaSeleccionada->paralelo?->nombre ?? '')) : '' }}
                                            @endif
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="limpiarAlumno">
                                        <i class="bi bi-arrow-repeat me-1"></i>Cambiar
                                    </button>
                                </div>
                                <div class="form-text" id="ayudaBuscarAlumno">
                                    Si el estudiante ya tiene matrícula activa, se usará esa matrícula.
                                </div>
                            </div>

                            <div id="bloqueNuevo" class="d-none">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="nombres" class="form-label small fw-semibold">Nombres</label>
                                        <input type="text" class="form-control" id="nombres" name="nombres" value="{{ old('nombres') }}" maxlength="255">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="apellidos" class="form-label small fw-semibold">Apellidos</label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" value="{{ old('apellidos') }}" maxlength="255">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="numero_cedula" class="form-label small fw-semibold">Cédula / identificación</label>
                                        <input type="text" class="form-control" id="numero_cedula" name="numero_cedula" value="{{ old('numero_cedula') }}" maxlength="20">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="fecha_nacimiento" class="form-label small fw-semibold">Fecha de nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="genero" class="form-label small fw-semibold">Género</label>
                                        <select class="form-select" id="genero" name="genero">
                                            <option value="">Seleccione...</option>
                                            <option value="masculino" @selected(old('genero') === 'masculino')>Masculino</option>
                                            <option value="femenino" @selected(old('genero') === 'femenino')>Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="representante_user_id" class="form-label small fw-semibold">Representante legal</label>
                                        <select class="form-select" id="representante_user_id" name="representante_user_id">
                                            <option value="">Seleccione un representante...</option>
                                            @foreach($representantes as $representante)
                                                <option value="{{ $representante->id }}" @selected((string) old('representante_user_id') === (string) $representante->id)>
                                                    {{ $representante->apellidos }} {{ $representante->name }}
                                                    @if($representante->numero_cedula) — {{ $representante->numero_cedula }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Si el representante no existe, regístrelo primero desde Matrículas o Actualización de datos.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="seccion-titulo mb-2">Datos del traslado</div>
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="fecha_traslado" class="form-label small fw-semibold">Fecha del traslado</label>
                                    <input type="date" class="form-control" id="fecha_traslado" name="fecha_traslado" value="{{ old('fecha_traslado', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-12 col-md-4" id="bloqueMomento">
                                    <label for="momento" class="form-label small fw-semibold">Momento del traslado</label>
                                    <select class="form-select" id="momento" name="momento">
                                        <option value="inicio" @selected(old('momento') === 'inicio')>Antes de iniciar el año</option>
                                        <option value="transcurso" @selected(old('momento', 'transcurso') === 'transcurso')>En transcurso del año</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="anio_lectivo" class="form-label small fw-semibold">Año lectivo</label>
                                    <input type="text" class="form-control" id="anio_lectivo" name="anio_lectivo"
                                           value="{{ old('anio_lectivo', $periodo?->nombre ?? now()->year) }}" maxlength="20" required>
                                </div>
                                <div class="col-12">
                                    <label for="institucion" class="form-label small fw-semibold" id="institucionLabel">Institución de origen</label>
                                    <input type="text" class="form-control" id="institucion" name="institucion" value="{{ old('institucion') }}" maxlength="255" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="curso_id" class="form-label small fw-semibold">Curso / grado</label>
                                    <select class="form-select" id="curso_id" name="curso_id">
                                        <option value="">Seleccione...</option>
                                        @foreach($cursos as $curso)
                                            <option value="{{ $curso->id }}" @selected((string) old('curso_id') === (string) $curso->id)>
                                                {{ $curso->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="paralelo_id" class="form-label small fw-semibold">Paralelo</label>
                                    <select class="form-select" id="paralelo_id" name="paralelo_id">
                                        <option value="">Seleccione...</option>
                                        @foreach($paralelos as $paralelo)
                                            <option value="{{ $paralelo->id }}" @selected((string) old('paralelo_id') === (string) $paralelo->id)>
                                                {{ $paralelo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="observaciones" class="form-label small fw-semibold">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" maxlength="2000">{{ old('observaciones') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <a href="{{ route('traslados.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn text-white px-4" style="background:#0f766e;">
                                <i class="bi bi-folder-check me-1"></i>Guardar expediente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card shadow-sm sticky-xl-top" style="top:1rem;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Check-list documental</h6>
                    <span class="badge bg-light text-dark border" id="contadorChecklist">0/0</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2" id="ayudaChecklist">
                        Los requisitos se actualizan según el tipo y el momento del traslado.
                    </p>
                    <div id="listaChecklist"></div>
                </div>
                <div class="card-footer bg-white small text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Los documentos se adjuntan en el paso 2, dentro de la ficha del expediente.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const checklists = @json($checklists);
        const cursosOctavo = @json($cursosOctavo).map(Number);
        const lista = document.getElementById('listaChecklist');
        const contador = document.getElementById('contadorChecklist');
        const ayuda = document.getElementById('ayudaChecklist');
        const curso = document.getElementById('curso_id');
        const momento = document.getElementById('momento');
        const bloqueMomento = document.getElementById('bloqueMomento');
        const institucionLabel = document.getElementById('institucionLabel');
        const modoExistente = document.getElementById('modoExistente');
        const modoNuevo = document.getElementById('modoNuevo');
        const bloqueExistente = document.getElementById('bloqueExistente');
        const bloqueNuevo = document.getElementById('bloqueNuevo');
        const alumno = document.getElementById('alumno_id');
        const formTraslado = document.getElementById('formTraslado');
        const buscarAlumno = document.getElementById('buscarAlumno');
        const resultadosAlumno = document.getElementById('resultadosAlumno');
        const alumnoSeleccionado = document.getElementById('alumnoSeleccionado');
        const alumnoSeleccionadoNombre = document.getElementById('alumnoSeleccionadoNombre');
        const alumnoSeleccionadoDetalle = document.getElementById('alumnoSeleccionadoDetalle');
        const limpiarAlumno = document.getElementById('limpiarAlumno');
        const paralelo = document.getElementById('paralelo_id');
        const urlBuscarAlumnos = @json(route('traslados.buscar-alumnos'));
        const camposNuevos = ['nombres', 'apellidos', 'numero_cedula', 'representante_user_id'];

        let resultados = [];
        let indiceActivo = -1;
        let temporizador = null;
        let peticion = null;

        function tipoSeleccionado() {
            return document.getElementById('tipoSalida').checked ? 'salida' : 'ingreso';
        }

        function claveChecklist() {
            if (tipoSeleccionado() === 'salida') {
                return 'salida';
            }
            const esOctavo = cursosOctavo.includes(parseInt(curso.value, 10));
            const base = momento.value === 'inicio' ? 'inicio' : 'transcurso';
            return esOctavo ? base + '_octavo' : base;
        }

        function renderChecklist() {
            const items = checklists[claveChecklist()] || [];
            lista.innerHTML = '';
            let obligatorios = 0;

            items.forEach(function (item) {
                if (item.obligatorio) obligatorios++;
                const div = document.createElement('div');
                div.className = 'check-item d-flex justify-content-between align-items-start gap-2';
                div.innerHTML = '<div><span class="small">' + item.label + '</span>'
                    + (item.descripcion ? '<div class="text-muted" style="font-size:.72rem;">' + item.descripcion + '</div>' : '')
                    + '</div>'
                    + '<span class="badge ' + (item.obligatorio ? 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' : 'bg-light text-muted border') + '">'
                    + (item.obligatorio ? 'Obligatorio' : 'Opcional') + '</span>';
                lista.appendChild(div);
            });

            contador.textContent = '0/' + obligatorios + ' recibidos';
            ayuda.textContent = tipoSeleccionado() === 'salida'
                ? 'Salida: se exige solicitud de retiro y autorización del representante. El paz y salvo se valida en la ficha.'
                : (momento.value === 'transcurso'
                    ? 'En transcurso del año: la libreta parcial y la asistencia son obligatorias si el período lectivo está activo.'
                    : 'Antes de iniciar el año: no se exige libreta parcial ni asistencia del año en curso.');
        }

        function toggleTipo() {
            const salida = tipoSeleccionado() === 'salida';
            bloqueMomento.classList.toggle('d-none', salida);
            institucionLabel.textContent = salida ? 'Institución de destino' : 'Institución de origen';
            renderChecklist();
        }

        function toggleModo() {
            const nuevo = modoNuevo.checked;
            bloqueExistente.classList.toggle('d-none', nuevo);
            bloqueNuevo.classList.toggle('d-none', !nuevo);

            if (nuevo) {
                buscarAlumno.setCustomValidity('');
                cerrarResultados();
            }

            camposNuevos.forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.required = nuevo;
            });
        }

        function escaparHtml(texto) {
            const div = document.createElement('div');
            div.textContent = texto == null ? '' : String(texto);
            return div.innerHTML;
        }

        function cerrarResultados() {
            resultadosAlumno.classList.add('d-none');
            resultadosAlumno.innerHTML = '';
            buscarAlumno.setAttribute('aria-expanded', 'false');
            buscarAlumno.removeAttribute('aria-activedescendant');
            resultados = [];
            indiceActivo = -1;
        }

        function renderResultados() {
            if (!resultados.length) {
                resultadosAlumno.innerHTML = '<div class="list-group-item small text-muted">'
                    + 'Sin coincidencias. Verifique el nombre o registre al estudiante como nuevo.</div>';
                resultadosAlumno.classList.remove('d-none');
                buscarAlumno.setAttribute('aria-expanded', 'true');
                return;
            }

            resultadosAlumno.innerHTML = resultados.map(function (item, indice) {
                const detalle = [item.cedula, item.curso].filter(Boolean).join(' · ');
                return '<button type="button" class="list-group-item list-group-item-action py-2"'
                    + ' role="option" id="resultadoAlumno' + indice + '" data-indice="' + indice + '"'
                    + ' aria-selected="false">'
                    + '<div class="fw-semibold small">' + escaparHtml(item.nombre) + '</div>'
                    + '<div class="text-muted" style="font-size:.75rem;">'
                    + escaparHtml(detalle || 'Sin matrícula en el período activo') + '</div>'
                    + '</button>';
            }).join('');

            resultadosAlumno.classList.remove('d-none');
            buscarAlumno.setAttribute('aria-expanded', 'true');
        }

        function moverActivo(delta) {
            if (!resultados.length) return;

            indiceActivo = (indiceActivo + delta + resultados.length) % resultados.length;

            Array.from(resultadosAlumno.querySelectorAll('[data-indice]')).forEach(function (el) {
                const activo = Number(el.dataset.indice) === indiceActivo;
                el.classList.toggle('active', activo);
                el.setAttribute('aria-selected', activo ? 'true' : 'false');

                if (activo) {
                    el.scrollIntoView({ block: 'nearest' });
                    buscarAlumno.setAttribute('aria-activedescendant', el.id);
                }
            });
        }

        function seleccionarAlumno(datos) {
            alumno.value = datos.id;
            alumnoSeleccionadoNombre.textContent = datos.nombre;
            const detalle = [datos.cedula, datos.curso].filter(Boolean).join(' · ');
            alumnoSeleccionadoDetalle.textContent = detalle ? ' — ' + detalle : '';
            alumnoSeleccionado.classList.remove('d-none');
            buscarAlumno.value = '';
            buscarAlumno.setCustomValidity('');
            cerrarResultados();

            if (datos.curso_id) {
                curso.value = datos.curso_id;
            }
            if (datos.paralelo_id) {
                paralelo.value = datos.paralelo_id;
            }

            renderChecklist();
        }

        function limpiarSeleccion() {
            alumno.value = '';
            alumnoSeleccionado.classList.add('d-none');
            alumnoSeleccionadoNombre.textContent = '';
            alumnoSeleccionadoDetalle.textContent = '';
            buscarAlumno.focus();
        }

        async function consultarAlumnos(termino) {
            if (peticion) {
                peticion.abort();
            }
            peticion = new AbortController();

            try {
                const respuesta = await fetch(urlBuscarAlumnos + '?q=' + encodeURIComponent(termino), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: peticion.signal,
                });

                if (!respuesta.ok) {
                    throw new Error('No se pudo buscar estudiantes.');
                }

                const cuerpo = await respuesta.json();
                resultados = Array.isArray(cuerpo.data) ? cuerpo.data : [];
                indiceActivo = -1;
                renderResultados();
            } catch (error) {
                if (error.name === 'AbortError') return;
                cerrarResultados();
            }
        }

        buscarAlumno.addEventListener('input', function () {
            buscarAlumno.setCustomValidity('');
            const termino = buscarAlumno.value.trim();
            clearTimeout(temporizador);

            if (termino.length < 2) {
                cerrarResultados();
                return;
            }

            temporizador = setTimeout(function () {
                consultarAlumnos(termino);
            }, 250);
        });

        buscarAlumno.addEventListener('keydown', function (evento) {
            const abierto = !resultadosAlumno.classList.contains('d-none') && resultados.length > 0;

            if (evento.key === 'ArrowDown' && abierto) {
                evento.preventDefault();
                moverActivo(1);
            } else if (evento.key === 'ArrowUp' && abierto) {
                evento.preventDefault();
                moverActivo(-1);
            } else if (evento.key === 'Enter' && abierto && indiceActivo >= 0) {
                evento.preventDefault();
                seleccionarAlumno(resultados[indiceActivo]);
            } else if (evento.key === 'Escape') {
                cerrarResultados();
            }
        });

        // Evita que el blur del input cierre la lista antes del clic.
        resultadosAlumno.addEventListener('mousedown', function (evento) {
            evento.preventDefault();
        });

        resultadosAlumno.addEventListener('click', function (evento) {
            const boton = evento.target.closest('[data-indice]');
            if (!boton) return;

            const datos = resultados[Number(boton.dataset.indice)];
            if (datos) {
                seleccionarAlumno(datos);
            }
        });

        document.addEventListener('click', function (evento) {
            if (!resultadosAlumno.contains(evento.target) && evento.target !== buscarAlumno) {
                cerrarResultados();
            }
        });

        limpiarAlumno.addEventListener('click', limpiarSeleccion);

        formTraslado.addEventListener('submit', function (evento) {
            if (modoNuevo.checked) return;

            if (!alumno.value) {
                evento.preventDefault();
                buscarAlumno.setCustomValidity('Seleccione un estudiante de la lista.');
                buscarAlumno.reportValidity();
                buscarAlumno.focus();
            }
        });

        document.querySelectorAll('input[name="tipo"]').forEach(function (radio) {
            radio.addEventListener('change', toggleTipo);
        });
        modoExistente.addEventListener('change', toggleModo);
        modoNuevo.addEventListener('change', toggleModo);
        momento.addEventListener('change', renderChecklist);
        curso.addEventListener('change', renderChecklist);

        toggleTipo();
        toggleModo();
    })();
</script>
@endpush
