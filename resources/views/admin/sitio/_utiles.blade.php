{{--
    Lista de útiles escolares tipo Excel.

    Qué hace: primero pide el nivel o curso ("Octavo", "1ro Ciencias", etc.).
    Al crearlo abre una hoja con filas de cantidad, artículo y materia, con
    "Agregar fila" y "Terminar". Al terminar muestra la lista en modo lectura
    con "Editar lista", imprimir y copiar. También permite eliminar listas.
    Conecta con: Admin\SitioController, ListaUtil y UtilItem.
    Rutas: POST/PUT/POST/DELETE /admin/sitio/utiles/listas.
--}}
@push('styles')
<style>
    /* Hoja tipo Excel: bordes finos, números tabulares y celdas editables. */
    .utiles-excel-card { border: 1px solid #cfd4da; border-radius: .75rem; overflow: hidden; }
    .utiles-excel-table { border-collapse: collapse; margin: 0; }
    .utiles-excel-table th,
    .utiles-excel-table td { border: 1px solid #d7dbe0; vertical-align: middle; }
    .utiles-excel-table thead th { background: #f1f3f5; }
    .utiles-letras th {
        background: #e9edf2; color: #6c757d; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .72rem; font-weight: 600; text-align: center; padding: .25rem;
    }
    .utiles-colnum {
        background: #eef1f5; color: #6c757d; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .78rem; font-weight: 600; text-align: center; width: 46px;
    }
    .utiles-input {
        width: 100%; border: 0; border-radius: 0; background: transparent;
        padding: .55rem .65rem; font-size: .9rem; color: #212529;
    }
    .utiles-input:focus {
        outline: 0; background: #fff; box-shadow: inset 0 0 0 2px #198754;
    }
    .utiles-fila:hover td { background: #f8fbf9; }
    .utiles-total { background: #f8f9fa; font-weight: 600; }
    .utiles-acciones {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; padding: .85rem 1rem; background: #f8f9fa; border: 1px solid #cfd4da; border-top: 0;
    }
    .utiles-formula {
        display: flex; align-items: center; gap: .65rem; padding: .45rem .75rem;
        border: 1px solid #cfd4da; border-radius: .55rem; background: #fff;
    }
    .utiles-formula .utiles-celda {
        min-width: 58px; text-align: center; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .72rem; font-weight: 600; color: #495057; background: #eef1f5;
        border-radius: .3rem; padding: .2rem .35rem;
    }
    .utiles-meta { background: #f1f3f5; border: 1px solid #dee2e6; border-radius: .6rem; }
    .utiles-meta .utiles-meta-item { background: #fff; border: 1px solid #e3e6ea; border-radius: .4rem; }
    @media print {
        .sidebar-static, nav.navbar, footer, .nav-tabs, .utiles-no-print { display: none !important; }
        main { padding: 0 !important; background: #fff !important; }
        .utiles-zona-impresion { width: 100%; }
        .utiles-excel-card { border: 1px solid #999; box-shadow: none !important; }
        .utiles-excel-table th, .utiles-excel-table td { border-color: #999; }
    }
</style>
@endpush

@if(! $listaSeleccionada)
    {{-- PASO 1: crear el nivel o curso. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-success">1</span>
                <h6 class="mb-0"><i class="bi bi-table me-1"></i>Crear nivel o curso</h6>
            </div>
            <small class="text-muted font-monospace">Celda A1: Nivel</small>
        </div>
        <div class="card-body">
            <h5 class="fw-bold mb-1">Ingrese el nivel o curso escolar</h5>
            <p class="text-muted small mb-3">
                Defina el curso que abrirá la hoja de cálculo. Por ejemplo:
                <strong>Octavo</strong>, <strong>1ro Ciencias</strong> o <strong>2do Bachillerato</strong>.
            </p>

            <form method="POST" action="{{ route('admin.sitio.utiles.listas.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label class="form-label small" for="utilesNuevoNivel">Nivel o curso</label>
                    <input type="text" id="utilesNuevoNivel" name="nombre"
                           class="form-control form-control-lg @error('nombre') is-invalid @enderror"
                           placeholder="Ej. Octavo o 1ro Ciencias..." maxlength="120"
                           autocomplete="off" required>
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-plus-circle me-1"></i> Crear lista
                    </button>
                </div>
            </form>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <span class="small text-muted">Sugerencias rápidas:</span>
                @foreach(['Octavo', '1ro Ciencias', '10mo Básica', '2do Bachillerato'] as $sugerencia)
                <button type="button" class="btn btn-sm btn-outline-secondary" data-nivel-sugerido="{{ $sugerencia }}">
                    {{ $sugerencia }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    @if($listas->isNotEmpty())
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-list-check me-1"></i>Listas registradas</h6>
            <span class="badge bg-light text-dark border">{{ $listas->count() }} lista(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nivel / Curso</th>
                        <th style="width:120px;">Estado</th>
                        <th class="text-center" style="width:100px;">Artículos</th>
                        <th class="text-center" style="width:100px;">Unidades</th>
                        <th class="text-end" style="width:210px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($listas as $lista)
                    <tr>
                        <td>
                            <strong>{{ $lista->nombre }}</strong>
                            <div class="small text-muted">Actualizada: {{ $lista->updated_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>
                            @if($lista->finalizada)
                            <span class="badge bg-success">Terminada</span>
                            @else
                            <span class="badge bg-warning text-dark">En edición</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $lista->items->count() }}</td>
                        <td class="text-center">{{ $lista->totalUnidades() }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.sitio.utiles', ['lista' => $lista->id]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i>Abrir
                            </a>
                            <form method="POST" action="{{ route('admin.sitio.utiles.listas.destroy', $lista) }}"
                                  class="d-inline"
                                  data-confirm="¿Eliminar la lista de {{ $lista->nombre }}? También se eliminarán sus artículos.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar lista">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-table display-1 d-block mb-3 opacity-25"></i>
            <h6 class="fw-semibold">La cuadrícula de materiales está en espera</h6>
            <p class="small mb-0">Escriba el nivel o curso arriba y presione <strong>Crear lista</strong> para habilitar la tabla tipo Excel.</p>
        </div>
    </div>
    @endif
@else
    @php
        $enEdicion = ! $listaSeleccionada->finalizada;
        $filas = $listaSeleccionada->items->map(fn ($item) => [
            'cantidad' => $item->cantidad,
            'nombre' => $item->nombre,
            'materia' => $item->materia ?: $item->area,
            'especificacion' => $item->especificacion,
        ])->values();

        if ($enEdicion && $filas->isEmpty()) {
            $filas = collect([['cantidad' => '1', 'nombre' => '', 'materia' => '', 'especificacion' => '']]);
        }

        $totalUnidades = $filas->sum(fn ($fila) => (int) preg_replace('/\D.*$/', '', (string) $fila['cantidad']));
        $totalItems = $filas->filter(fn ($fila) => trim((string) $fila['nombre']) !== '')->count();
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 utiles-no-print">
        <a href="{{ route('admin.sitio.utiles') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Listas registradas
        </a>
        <div class="d-flex align-items-center gap-2">
            @if($listaSeleccionada->finalizada)
            <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Publicada en el portal</span>
            @else
            <span class="badge bg-warning text-dark"><i class="bi bi-pencil me-1"></i>En edición</span>
            @endif
        </div>
    </div>

    <div class="utiles-zona-impresion">
        <div class="utiles-formula mb-3 utiles-no-print">
            <span class="utiles-celda">A1</span>
            <span class="text-muted fst-italic fw-bold">fx</span>
            <span class="small text-muted text-truncate">
                {{ $enEdicion ? 'Escriba los artículos y presione Terminar para publicar la lista.' : 'Documento terminado. Presione Editar lista para realizar cambios.' }}
            </span>
        </div>

        @if($enEdicion)
        <form id="formListaUtiles" method="POST"
              action="{{ route('admin.sitio.utiles.listas.update', $listaSeleccionada) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="finalizada" value="1">
        @endif

        <div class="card utiles-excel-card shadow-sm">
            <div class="card-body border-bottom bg-gradient bg-light">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                Listado oficial de útiles
                            </span>
                            @if($listaSeleccionada->finalizada)
                            <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Lista finalizada</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">Nivel / Curso:</span>
                            @if($enEdicion)
                            <input type="text" name="nombre" id="utilesNombreLista"
                                   class="form-control form-control-lg fw-bold border-0 border-bottom rounded-0 px-0"
                                   value="{{ $listaSeleccionada->nombre }}" maxlength="120" required>
                            @else
                            <span class="fs-4 fw-bold" id="utilesNombreTitulo">{{ $listaSeleccionada->nombre }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="utiles-meta p-2">
                        <div class="d-flex gap-2">
                            <div class="utiles-meta-item px-3 py-2 text-center">
                                <div class="text-uppercase text-muted" style="font-size:.68rem;">Fecha</div>
                                <div class="small fw-semibold font-monospace" id="utilesFecha">{{ $listaSeleccionada->updated_at->format('d/m/Y') }}</div>
                            </div>
                            <div class="utiles-meta-item px-3 py-2 text-center">
                                <div class="text-uppercase text-muted" style="font-size:.68rem;">Ítems</div>
                                <div class="small fw-bold font-monospace" id="utilesTotalItems">{{ $totalItems }}</div>
                            </div>
                            <div class="utiles-meta-item px-3 py-2 text-center">
                                <div class="text-uppercase text-muted" style="font-size:.68rem;">Total unid.</div>
                                <div class="small fw-bold font-monospace text-success" id="utilesTotalUnidades">{{ $totalUnidades }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table utiles-excel-table mb-0" id="utilesExcelTable">
                    <thead>
                        <tr class="utiles-letras">
                            <th class="utiles-colnum"></th>
                            <th>A</th>
                            <th>B</th>
                            <th>C</th>
                            @if($enEdicion)<th class="utiles-no-print">D</th>@endif
                        </tr>
                        <tr>
                            <th class="utiles-colnum">#</th>
                            <th class="text-center">Cantidad</th>
                            <th>Artículo / Descripción</th>
                            <th>Materia / Asignatura</th>
                            @if($enEdicion)<th class="text-center utiles-no-print" style="width:52px;">&nbsp;</th>@endif
                        </tr>
                    </thead>
                    <tbody id="utilesFilas">
                        @forelse($filas as $i => $fila)
                        <tr class="utiles-fila">
                            <td class="utiles-colnum utiles-num">{{ $i + 1 }}</td>
                            <td>
                                @if($enEdicion)
                                <input type="text" name="items[{{ $i }}][cantidad]" data-campo="cantidad"
                                       value="{{ $fila['cantidad'] }}" class="utiles-input text-center font-monospace"
                                       maxlength="60" inputmode="numeric" placeholder="1">
                                @else
                                <span class="d-block text-center fw-semibold font-monospace">{{ $fila['cantidad'] ?: '—' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($enEdicion)
                                <input type="text" name="items[{{ $i }}][articulo]" data-campo="articulo"
                                       value="{{ $fila['nombre'] }}" class="utiles-input"
                                       maxlength="255" placeholder="Ej. Kit de primeros auxilios">
                                <input type="hidden" name="items[{{ $i }}][especificacion]"
                                       data-campo="especificacion" value="{{ $fila['especificacion'] }}">
                                @else
                                <span class="fw-semibold">{{ $fila['nombre'] ?: '—' }}</span>
                                @if($fila['especificacion'])
                                <div class="small text-muted">{{ $fila['especificacion'] }}</div>
                                @endif
                                @endif
                            </td>
                            <td>
                                @if($enEdicion)
                                <input type="text" name="items[{{ $i }}][materia]" data-campo="materia"
                                       value="{{ $fila['materia'] }}" class="utiles-input"
                                       list="utilesMaterias" maxlength="120" placeholder="Ej. Matemáticas">
                                @else
                                <span class="text-muted">{{ $fila['materia'] ?: '—' }}</span>
                                @endif
                            </td>
                            @if($enEdicion)
                            <td class="text-center utiles-no-print">
                                <button type="button" class="btn btn-sm btn-outline-danger utiles-quitar"
                                        title="Eliminar fila">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $enEdicion ? 5 : 4 }}" class="text-center text-muted py-4">
                                Esta lista todavía no tiene artículos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="utiles-total">
                            <td class="utiles-colnum">Σ</td>
                            <td class="text-center font-monospace text-success" id="utilesPieTotal">{{ $totalUnidades }}</td>
                            <td colspan="{{ $enEdicion ? 3 : 2 }}" class="text-muted">
                                Total acumulado de materiales registrados para
                                <strong class="text-body" id="utilesNombrePie">{{ $listaSeleccionada->nombre }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($enEdicion)
        </form>
        @endif

        <div class="utiles-acciones rounded-bottom utiles-no-print">
            @if($enEdicion)
            <div class="d-flex flex-wrap gap-2">
                <button type="button" id="utilesAgregarFila" class="btn btn-outline-success">
                    <i class="bi bi-plus-lg me-1"></i>Agregar fila
                </button>
                <button type="submit" form="formListaUtiles" id="utilesTerminar" class="btn btn-success">
                    <i class="bi bi-check2-circle me-1"></i>Terminar
                </button>
            </div>
            <small class="text-muted">Los cambios se guardan al presionar <strong>Terminar</strong>.</small>
            @else
            <div class="d-flex flex-wrap gap-2">
                <button type="button" id="utilesCopiar" class="btn btn-outline-secondary">
                    <i class="bi bi-clipboard me-1"></i>Copiar texto
                </button>
                <button type="button" id="utilesImprimir" class="btn btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
                <a href="{{ route('admin.sitio.utiles') }}" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nueva lista
                </a>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.sitio.utiles.listas.reabrir', $listaSeleccionada) }}"
                      data-confirm="¿Reabrir esta lista para editar sus artículos?">
                    @csrf
                    <button type="submit" id="utilesEditarLista" class="btn btn-warning">
                        <i class="bi bi-pencil-square me-1"></i>Editar lista
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.sitio.utiles.listas.destroy', $listaSeleccionada) }}"
                      data-confirm="¿Eliminar la lista de {{ $listaSeleccionada->nombre }}? También se eliminarán sus artículos.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar lista
                    </button>
                </form>
            </div>
            @endif
        </div>

        <datalist id="utilesMaterias">
            <option value="Primeros Auxilios">
            <option value="Matemáticas">
            <option value="Lengua y Literatura">
            <option value="Ciencias Naturales">
            <option value="Estudios Sociales">
            <option value="Inglés">
            <option value="Educación Física">
            <option value="Educación Cultural y Artística">
            <option value="Física">
            <option value="Química">
            <option value="Biología">
            <option value="Informática / Computación">
            <option value="Uso General">
        </datalist>
    </div>

    @if($enEdicion)
    <template id="utilesFilaPlantilla">
        <tr class="utiles-fila">
            <td class="utiles-colnum utiles-num"></td>
            <td>
                <input type="text" data-campo="cantidad" value="1"
                       class="utiles-input text-center font-monospace"
                       maxlength="60" inputmode="numeric" placeholder="1">
            </td>
            <td>
                <input type="text" data-campo="articulo" class="utiles-input"
                       maxlength="255" placeholder="Ej. Kit de primeros auxilios">
                <input type="hidden" data-campo="especificacion" value="">
            </td>
            <td>
                <input type="text" data-campo="materia" class="utiles-input"
                       list="utilesMaterias" maxlength="120" placeholder="Ej. Matemáticas">
            </td>
            <td class="text-center utiles-no-print">
                <button type="button" class="btn btn-sm btn-outline-danger utiles-quitar" title="Eliminar fila">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        </tr>
    </template>
    @endif
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Sugerencias rápidas del nombre del curso. */
    document.querySelectorAll('[data-nivel-sugerido]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var campo = document.getElementById('utilesNuevoNivel');
            if (!campo) return;
            campo.value = boton.dataset.nivelSugerido;
            campo.focus();
        });
    });

    var form = document.getElementById('formListaUtiles');
    var tbody = document.getElementById('utilesFilas');
    var plantilla = document.getElementById('utilesFilaPlantilla');
    var btnAgregar = document.getElementById('utilesAgregarFila');
    var btnCopiar = document.getElementById('utilesCopiar');
    var btnImprimir = document.getElementById('utilesImprimir');

    if (!tbody) return;

    function filas() {
        return Array.prototype.slice.call(tbody.querySelectorAll('tr.utiles-fila'));
    }

    function campo(fila, nombre) {
        return fila.querySelector('[data-campo="' + nombre + '"]');
    }

    function valor(fila, nombre) {
        var input = campo(fila, nombre);
        return input ? input.value : '';
    }

    function reindexarFilas() {
        filas().forEach(function (fila, indice) {
            var numero = fila.querySelector('.utiles-num');
            if (numero) numero.textContent = indice + 1;

            ['cantidad', 'articulo', 'materia', 'especificacion'].forEach(function (nombre) {
                var input = campo(fila, nombre);
                if (input) input.name = 'items[' + indice + '][' + nombre + ']';
            });
        });
    }

    function actualizarTotales() {
        var totalItems = 0;
        var totalUnidades = 0;

        filas().forEach(function (fila) {
            if (valor(fila, 'articulo').trim() !== ''
                || valor(fila, 'materia').trim() !== ''
                || valor(fila, 'cantidad').trim() !== '') {
                totalItems++;
            }

            var cantidad = parseInt(valor(fila, 'cantidad'), 10);
            if (!isNaN(cantidad) && cantidad > 0) totalUnidades += cantidad;
        });

        var etiquetaItems = document.getElementById('utilesTotalItems');
        var etiquetaUnidades = document.getElementById('utilesTotalUnidades');
        var etiquetaPie = document.getElementById('utilesPieTotal');

        if (etiquetaItems) etiquetaItems.textContent = totalItems;
        if (etiquetaUnidades) etiquetaUnidades.textContent = totalUnidades;
        if (etiquetaPie) etiquetaPie.textContent = totalUnidades;
    }

    function agregarFila() {
        if (!plantilla) return;

        var nueva = plantilla.content.firstElementChild.cloneNode(true);
        tbody.appendChild(nueva);
        reindexarFilas();
        actualizarTotales();

        var input = nueva.querySelector('[data-campo="articulo"]');
        if (input) input.focus();
    }

    function quitarFila(boton) {
        var fila = boton.closest('tr');
        if (!fila) return;

        if (filas().length === 1) {
            ['cantidad', 'articulo', 'materia', 'especificacion'].forEach(function (nombre) {
                var input = campo(fila, nombre);
                if (input) input.value = nombre === 'cantidad' ? '1' : '';
            });
            actualizarTotales();
            return;
        }

        window.confirmarAccion('¿Eliminar esta fila?', { title: 'Eliminar fila', icon: 'warning' })
            .then(function (confirmado) {
                if (!confirmado) return;
                fila.remove();
                reindexarFilas();
                actualizarTotales();
            });
    }

    tbody.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.utiles-quitar');
        if (boton) quitarFila(boton);
    });

    tbody.addEventListener('input', actualizarTotales);

    if (btnAgregar) btnAgregar.addEventListener('click', agregarFila);

    var nombreLista = document.getElementById('utilesNombreLista');
    if (nombreLista) {
        nombreLista.addEventListener('input', function () {
            var pie = document.getElementById('utilesNombrePie');
            if (pie) pie.textContent = nombreLista.value || 'Sin nombre';
        });
    }

    if (form) {
        form.addEventListener('submit', function (evento) {
            if (form.dataset.enviando === '1') return;
            evento.preventDefault();

            var conArticulo = 0;

            filas().forEach(function (fila) {
                var articulo = valor(fila, 'articulo').trim();
                var cantidad = campo(fila, 'cantidad');

                if (articulo !== '' && cantidad && cantidad.value.trim() === '') {
                    cantidad.value = '1';
                }
                if (articulo !== '') conArticulo++;
            });

            if (conArticulo === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta el artículo',
                    text: 'Agrega al menos un artículo antes de terminar la lista.',
                });
                return;
            }

            filas().forEach(function (fila) {
                if (valor(fila, 'articulo').trim() === '') fila.remove();
            });

            reindexarFilas();
            actualizarTotales();

            window.confirmarAccion('¿Terminar esta lista? Después podrás volver a editarla.', {
                title: 'Terminar lista',
                icon: 'question',
                confirmButtonText: 'Sí, terminar',
            }).then(function (confirmado) {
                if (!confirmado) return;
                form.dataset.enviando = '1';
                form.requestSubmit();
            });
        });
    }

    if (btnImprimir) btnImprimir.addEventListener('click', function () { window.print(); });

    if (btnCopiar) {
        btnCopiar.addEventListener('click', function () {
            var nombre = document.getElementById('utilesNombreTitulo');
            var fecha = document.getElementById('utilesFecha');
            var lineas = [];

            lineas.push('LISTADO DE ÚTILES ESCOLARES - ' + (nombre ? nombre.textContent.trim() : '').toUpperCase());
            lineas.push('Fecha: ' + (fecha ? fecha.textContent.trim() : ''));
            lineas.push('--------------------------------------------------');

            var suma = 0;

            filas().forEach(function (fila, indice) {
                var celdas = fila.querySelectorAll('td');
                var cantidad = celdas[1] ? celdas[1].innerText.trim() : '';
                var articulo = celdas[2] ? celdas[2].innerText.trim().split('\n')[0] : '';
                var materia = celdas[3] ? celdas[3].innerText.trim() : '';
                var numero = parseInt(cantidad, 10);

                if (!isNaN(numero) && numero > 0) suma += numero;
                lineas.push((indice + 1) + '. [' + cantidad + '] ' + articulo + ' — (' + materia + ')');
            });

            lineas.push('--------------------------------------------------');
            lineas.push('Total ítems: ' + filas().length + ' | Total unidades: ' + suma);

            var texto = lineas.join('\n');

            function avisar(ok) {
                Swal.fire({
                    icon: ok ? 'success' : 'error',
                    title: ok ? 'Listado copiado' : 'No se pudo copiar',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true,
                });
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(function () { avisar(true); })
                    .catch(function () { avisar(false); });
                return;
            }

            var area = document.createElement('textarea');
            area.value = texto;
            document.body.appendChild(area);
            area.select();

            try {
                document.execCommand('copy');
                avisar(true);
            } catch (error) {
                avisar(false);
            }

            document.body.removeChild(area);
        });
    }

    // En modo lectura no hay celdas editables: se conservan los totales del servidor.
    if (form) {
        reindexarFilas();
        actualizarTotales();
    }
});
</script>
@endpush
