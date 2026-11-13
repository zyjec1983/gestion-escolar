@php
    $idBuscador = $idBuscador ?? 'buscadorAlumno'.substr(md5(uniqid('', true)), 0, 6);
    $campoNombre = $campoNombre ?? 'alumno_id';
    $urlBusqueda = $urlBusqueda ?? route('dece.buscar.alumnos');
    $etiquetaBuscador = $etiquetaBuscador ?? 'Estudiante';
    $requerido = $requerido ?? true;
    $mostrarFicha = $mostrarFicha ?? true;
    $matricula = $alumnoSeleccionado?->matriculas->sortByDesc('id')->first();
    $representante = $alumnoSeleccionado?->representante;
    $curso = trim(($matricula?->curso?->nombre ?? '').' '.($matricula?->paralelo?->nombre ?? ''));
@endphp

<div data-buscador-alumno>
    <label for="{{ $idBuscador }}" class="form-label">
        {{ $etiquetaBuscador }} @if($requerido)<span class="text-danger">*</span>@endif
    </label>
    <div class="position-relative">
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control" id="{{ $idBuscador }}"
                   value="{{ $alumnoSeleccionado?->nombreCompleto() }}"
                   placeholder="Escriba apellidos, nombres o cédula…"
                   autocomplete="off" role="combobox" aria-autocomplete="list"
                   aria-expanded="false" aria-controls="{{ $idBuscador }}Resultados"
                   @if($requerido) required @endif
                   data-buscador-input>
        </div>
        <input type="hidden" name="{{ $campoNombre }}" value="{{ $alumnoSeleccionado?->id }}" data-buscador-id>
        <div id="{{ $idBuscador }}Resultados" class="list-group position-absolute w-100 shadow-sm d-none"
             style="z-index: 1050; max-height: 280px; overflow-y: auto;"
             role="listbox" data-buscador-lista></div>
    </div>

    @if($mostrarFicha)
    <div class="card bg-light border-0 mt-2 {{ $alumnoSeleccionado ? '' : 'd-none' }}" data-buscador-ficha>
        <div class="card-body py-2">
            <div class="row g-1 small">
                <div class="col-md-6">
                    <span class="text-muted">Estudiante:</span>
                    <strong data-ficha-nombre>{{ $alumnoSeleccionado?->nombreCompleto() }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Cédula:</span>
                    <span data-ficha-cedula>{{ $alumnoSeleccionado?->numero_cedula ?: $alumnoSeleccionado?->codigo }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Curso:</span>
                    <span data-ficha-curso>{{ $curso }}</span>
                </div>
                <div class="col-12">
                    <span class="text-muted">Representante:</span>
                    <span data-ficha-representante>
                        {{ $representante?->nombreCompleto() ?? 'Sin representante registrado' }}@if($representante?->numero_cedula) · CI {{ $representante->numero_cedula }}@endif
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    (function () {
        const config = {
            url: @json($urlBusqueda),
            inputId: @json($idBuscador),
            listaId: @json($idBuscador.'Resultados'),
        };

        const input = document.getElementById(config.inputId);
        const lista = document.getElementById(config.listaId);

        if (!input || !lista) {
            return;
        }

        const raiz = input.closest('[data-buscador-alumno]');
        const oculto = raiz.querySelector('[data-buscador-id]');
        const ficha = raiz.querySelector('[data-buscador-ficha]');
        const formulario = input.closest('form');

        let resultados = [];
        let indiceActivo = -1;
        let temporizador = null;
        let peticion = null;

        function cerrar() {
            lista.classList.add('d-none');
            input.setAttribute('aria-expanded', 'false');
            indiceActivo = -1;
        }

        function marcar(valor) {
            if (valor < 0 || valor >= resultados.length) {
                return;
            }

            indiceActivo = valor;

            Array.from(lista.children).forEach(function (elemento, posicion) {
                elemento.classList.toggle('active', posicion === indiceActivo);
            });

            const activo = lista.children[indiceActivo];
            if (activo) {
                activo.scrollIntoView({ block: 'nearest' });
            }
        }

        function actualizarFicha(alumno) {
            if (!ficha) {
                return;
            }

            if (!alumno) {
                ficha.classList.add('d-none');
                return;
            }

            ficha.classList.remove('d-none');
            ficha.querySelector('[data-ficha-nombre]').textContent = alumno.nombre || '';
            ficha.querySelector('[data-ficha-cedula]').textContent = alumno.cedula || '—';
            ficha.querySelector('[data-ficha-curso]').textContent = alumno.curso || '—';

            const representante = alumno.representante;
            ficha.querySelector('[data-ficha-representante]').textContent = representante
                ? representante.nombre + (representante.cedula ? ' · CI ' + representante.cedula : '')
                : 'Sin representante registrado';
        }

        function seleccionar(alumno) {
            oculto.value = alumno.id;
            input.value = alumno.nombre;
            input.setCustomValidity('');
            actualizarFicha(alumno);
            cerrar();

            document.dispatchEvent(new CustomEvent('dece:alumno-seleccionado', { detail: alumno }));
        }

        function render() {
            lista.innerHTML = '';

            if (!resultados.length) {
                const vacio = document.createElement('div');
                vacio.className = 'list-group-item text-muted small';
                vacio.textContent = 'Sin coincidencias.';
                lista.appendChild(vacio);
                lista.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
                return;
            }

            resultados.forEach(function (alumno, posicion) {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'list-group-item list-group-item-action py-2';
                boton.setAttribute('role', 'option');

                const titulo = document.createElement('div');
                titulo.className = 'fw-semibold';
                titulo.textContent = alumno.nombre;

                const detalle = document.createElement('div');
                detalle.className = 'small text-muted';
                detalle.textContent = [
                    alumno.cedula ? 'CI ' + alumno.cedula : '',
                    alumno.curso || '',
                    alumno.representante ? 'Rep: ' + alumno.representante.nombre : '',
                ].filter(Boolean).join(' · ');

                boton.appendChild(titulo);
                boton.appendChild(detalle);
                boton.addEventListener('click', function () {
                    seleccionar(alumno);
                });
                boton.addEventListener('mouseenter', function () {
                    marcar(posicion);
                });

                lista.appendChild(boton);
            });

            lista.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');
        }

        async function buscar(termino) {
            if (peticion) {
                peticion.abort();
            }

            peticion = new AbortController();

            try {
                const respuesta = await fetch(config.url + '?q=' + encodeURIComponent(termino), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: peticion.signal,
                });

                if (!respuesta.ok) {
                    throw new Error('No se pudo completar la búsqueda.');
                }

                const cuerpo = await respuesta.json();
                resultados = Array.isArray(cuerpo.data) ? cuerpo.data : [];
                indiceActivo = -1;
                render();
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                cerrar();
            }
        }

        input.addEventListener('input', function () {
            input.setCustomValidity('');

            if (oculto.value) {
                oculto.value = '';
                actualizarFicha(null);
                document.dispatchEvent(new CustomEvent('dece:alumno-seleccionado', { detail: null }));
            }

            const termino = input.value.trim();
            clearTimeout(temporizador);

            if (termino.length < 2) {
                resultados = [];
                cerrar();
                return;
            }

            temporizador = setTimeout(function () {
                buscar(termino);
            }, 250);
        });

        input.addEventListener('keydown', function (event) {
            if (lista.classList.contains('d-none')) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                marcar(Math.min(indiceActivo + 1, resultados.length - 1));
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                marcar(Math.max(indiceActivo - 1, 0));
            } else if (event.key === 'Enter' && indiceActivo >= 0 && resultados[indiceActivo]) {
                event.preventDefault();
                seleccionar(resultados[indiceActivo]);
            } else if (event.key === 'Escape') {
                cerrar();
            }
        });

        document.addEventListener('click', function (event) {
            if (!raiz.contains(event.target)) {
                cerrar();
            }
        });

        if (formulario) {
            formulario.addEventListener('submit', function (event) {
                if (!input.required || raiz.closest('.d-none')) {
                    input.setCustomValidity('');
                    return;
                }

                if (input.value.trim() !== '' && !oculto.value) {
                    input.setCustomValidity('Seleccione un estudiante de la lista de coincidencias.');
                    input.reportValidity();
                    event.preventDefault();
                    return;
                }

                input.setCustomValidity('');
            });
        }
    })();
</script>
@endpush
