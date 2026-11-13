@php
    $idBuscador = $idBuscador ?? 'buscadorFiltro'.substr(md5(uniqid('', true)), 0, 6);
    $nombre = $nombre ?? 'buscar';
    $valor = $valor ?? '';
    $etiqueta = $etiqueta ?? 'Buscar';
    $placeholder = $placeholder ?? 'Escriba para ver coincidencias…';
    $url = $url ?? route('dece.buscar.alumnos');
    $campoId = $campoId ?? null;
    $valorId = $valorId ?? '';
    $urlSeleccion = $urlSeleccion ?? null;
    $minimo = $minimo ?? 2;
@endphp

<div class="position-relative" data-buscador-filtro data-id="{{ $idBuscador }}"
     data-url="{{ $url }}" data-minimo="{{ $minimo }}"
     @if($urlSeleccion) data-url-seleccion="{{ $urlSeleccion }}" @endif>
    <label for="{{ $idBuscador }}" class="form-label">{{ $etiqueta }}</label>
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control" id="{{ $idBuscador }}" name="{{ $nombre }}"
               value="{{ $valor }}" placeholder="{{ $placeholder }}" autocomplete="off"
               role="combobox" aria-autocomplete="list" aria-expanded="false"
               aria-controls="{{ $idBuscador }}Resultados" data-filtro-input>
    </div>
    @if($campoId)
        <input type="hidden" name="{{ $campoId }}" value="{{ $valorId }}" data-filtro-id>
    @endif
    <div id="{{ $idBuscador }}Resultados" class="list-group position-absolute w-100 shadow-sm d-none"
         style="z-index: 1050; max-height: 280px; overflow-y: auto;"
         role="listbox" data-filtro-lista></div>
</div>

@push('scripts')
<script>
    (function () {
        const config = {
            id: @json($idBuscador),
            minimo: {{ (int) $minimo }},
        };

        const raiz = document.querySelector('[data-buscador-filtro][data-id="' + config.id + '"]');

        if (!raiz) {
            return;
        }

        const input = raiz.querySelector('[data-filtro-input]');
        const lista = raiz.querySelector('[data-filtro-lista]');
        const oculto = raiz.querySelector('[data-filtro-id]');
        const formulario = raiz.closest('form');
        const url = raiz.dataset.url;
        const urlSeleccion = raiz.dataset.urlSeleccion || null;

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

        function detalle(item) {
            if (item.detalle) {
                return item.detalle;
            }

            return [
                item.cedula ? 'CI ' + item.cedula : '',
                item.curso || '',
            ].filter(Boolean).join(' · ');
        }

        function seleccionar(item) {
            cerrar();

            if (item.url) {
                window.location.href = item.url;
                return;
            }

            if (urlSeleccion) {
                window.location.href = urlSeleccion.replace('__ID__', item.id);
                return;
            }

            input.value = item.nombre || item.label || '';
            if (oculto) {
                oculto.value = item.id;
            }

            if (formulario) {
                formulario.requestSubmit();
            }
        }

        function render() {
            lista.innerHTML = '';

            if (!resultados.length) {
                const vacio = document.createElement('div');
                vacio.className = 'list-group-item text-muted small';
                vacio.textContent = 'Sin coincidencias. Puede escribir el texto completo y pulsar el botón de filtro.';
                lista.appendChild(vacio);
                lista.classList.remove('d-none');
                input.setAttribute('aria-expanded', 'true');
                return;
            }

            resultados.forEach(function (item, posicion) {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'list-group-item list-group-item-action py-2';
                boton.setAttribute('role', 'option');

                const titulo = document.createElement('div');
                titulo.className = 'fw-semibold';
                titulo.textContent = item.nombre || item.label || '';

                boton.appendChild(titulo);

                const texto = detalle(item);
                if (texto) {
                    const linea = document.createElement('div');
                    linea.className = 'small text-muted';
                    linea.textContent = texto;
                    boton.appendChild(linea);
                }

                boton.addEventListener('click', function () {
                    seleccionar(item);
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
                const separador = url.includes('?') ? '&' : '?';
                const respuesta = await fetch(url + separador + 'q=' + encodeURIComponent(termino), {
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
            if (oculto && oculto.value) {
                oculto.value = '';
            }

            const termino = input.value.trim();
            clearTimeout(temporizador);

            if (termino.length < config.minimo) {
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
    })();
</script>
@endpush
