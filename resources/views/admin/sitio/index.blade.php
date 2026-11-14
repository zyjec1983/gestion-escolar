@extends('layouts.principal')

@section('titulo', 'Sitio web')

{{--
    Administración del sitio web institucional.

    Qué hace: edita todos los textos e imágenes del portal (identidad,
    historia, comunidad, útiles, descargas, admisiones y login), además de la
    línea de tiempo, la lista de útiles y el centro de descargas.
    Conecta con: Admin\SitioController, PortalContenido, SiteContent,
    HistoriaHito, UtilItem y DescargaDocumento.
    Rutas: GET /admin/sitio y POST/PUT/DELETE /admin/sitio/*.
--}}
@php
    $tabActiva = in_array(request('tab'), ['textos', 'historia', 'utiles', 'descargas'], true)
        ? request('tab')
        : 'textos';
@endphp

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-globe2 me-2"></i>Sitio web</h4>
        <p class="text-muted small mb-0">
            Todo lo que aparece en el portal público se administra desde aquí: textos, imágenes,
            historia, comunidad, útiles y documentos.
        </p>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <ul class="nav nav-tabs config-tab mb-4" id="sitioTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tabActiva === 'textos' ? 'active' : '' }}" id="tab-textos"
                    data-bs-toggle="tab" data-bs-target="#panel-textos" type="button" role="tab">
                <i class="bi bi-fonts me-1"></i>Textos e imágenes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tabActiva === 'historia' ? 'active' : '' }}" id="tab-historia"
                    data-bs-toggle="tab" data-bs-target="#panel-historia" type="button" role="tab">
                <i class="bi bi-book-half me-1"></i>Historia
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tabActiva === 'utiles' ? 'active' : '' }}" id="tab-utiles"
                    data-bs-toggle="tab" data-bs-target="#panel-utiles" type="button" role="tab">
                <i class="bi bi-backpack2 me-1"></i>Útiles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tabActiva === 'descargas' ? 'active' : '' }}" id="tab-descargas"
                    data-bs-toggle="tab" data-bs-target="#panel-descargas" type="button" role="tab">
                <i class="bi bi-cloud-arrow-down me-1"></i>Descargas
            </button>
        </li>
    </ul>

    <div class="tab-content" id="sitioTabsContent">
        {{-- ============ TEXTOS E IMÁGENES ============ --}}
        <div class="tab-pane fade {{ $tabActiva === 'textos' ? 'show active' : '' }}" id="panel-textos"
             role="tabpanel" aria-labelledby="tab-textos" tabindex="0">
            @foreach($grupos as $claveGrupo => $grupo)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi {{ $grupo['icono'] }} me-1"></i>{{ $grupo['titulo'] }}</h6>
                    <small class="text-muted">{{ $grupo['descripcion'] }}</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sitio.contenido', $claveGrupo) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            @foreach(collect($campos)->filter(fn ($campo) => $campo['grupo'] === $claveGrupo) as $clave => $campo)
                            @php($valor = \App\Support\PortalContenido::valor($clave))

                            @if($campo['tipo'] === 'imagen')
                            @php($urlImagen = \App\Support\PortalContenido::imagen($clave))
                            <div class="col-md-6">
                                <label class="form-label">{{ $campo['etiqueta'] }}</label>
                                @if($urlImagen)
                                <div class="mb-2">
                                    <img src="{{ $urlImagen }}" alt="{{ $campo['etiqueta'] }}"
                                         class="img-fluid rounded border" style="max-height:140px;object-fit:cover;">
                                </div>
                                @endif
                                <input type="file" name="imagenes[{{ $clave }}]" class="form-control"
                                       accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                <input type="text" name="campos[{{ $clave }}]" class="form-control mt-2"
                                       value="{{ $valor }}" placeholder="O escriba una URL externa">
                                @if(!empty($campo['ayuda']))
                                <div class="form-text">{{ $campo['ayuda'] }}</div>
                                @endif
                                @if($valor)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="quitar[{{ $clave }}]"
                                           id="quitar_{{ $loop->index }}_{{ $claveGrupo }}" value="1">
                                    <label class="form-check-label" for="quitar_{{ $loop->index }}_{{ $claveGrupo }}">
                                        Quitar la imagen actual
                                    </label>
                                </div>
                                @endif
                            </div>

                            @elseif($campo['tipo'] === 'booleano')
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="campos[{{ $clave }}]"
                                           id="campo_{{ $claveGrupo }}_{{ $loop->index }}" value="1"
                                           @checked(\App\Models\SiteContent::bool($clave, (bool) ($campo['por_defecto'] ?? false)))>
                                    <label class="form-check-label" for="campo_{{ $claveGrupo }}_{{ $loop->index }}">
                                        {{ $campo['etiqueta'] }}
                                    </label>
                                </div>
                            </div>

                            @elseif($campo['tipo'] === 'largo')
                            <div class="col-12">
                                <label class="form-label">{{ $campo['etiqueta'] }}</label>
                                <textarea name="campos[{{ $clave }}]" class="form-control" rows="3"
                                          maxlength="10000">{{ $valor }}</textarea>
                            </div>

                            @else
                            <div class="col-md-6">
                                <label class="form-label">{{ $campo['etiqueta'] }}</label>
                                <input type="text" name="campos[{{ $clave }}]" class="form-control"
                                       maxlength="10000" value="{{ $valor }}">
                            </div>
                            @endif
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Guardar {{ strtolower($grupo['titulo']) }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ============ HISTORIA ============ --}}
        <div class="tab-pane fade {{ $tabActiva === 'historia' ? 'show active' : '' }}" id="panel-historia"
             role="tabpanel" aria-labelledby="tab-historia" tabindex="0">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-plus-circle me-1"></i>Agregar hito histórico</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sitio.historia.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-2">
                            <label class="form-label small">Año</label>
                            <input type="text" name="anio" class="form-control" required maxlength="40" placeholder="1952">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Título</label>
                            <input type="text" name="titulo" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Descripción</label>
                            <input type="text" name="descripcion" class="form-control" maxlength="2000">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Orden</label>
                            <input type="number" name="orden" class="form-control" min="0" max="999" value="0">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" title="Agregar"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Hitos registrados</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:90px;">Año</th>
                                <th>Título</th>
                                <th>Descripción</th>
                                <th style="width:80px;">Orden</th>
                                <th class="text-end" style="width:140px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hitos as $hito)
                            <tr>
                                <td><span class="badge bg-primary">{{ $hito->anio }}</span></td>
                                <td><strong>{{ $hito->titulo }}</strong></td>
                                <td><small>{{ \Illuminate\Support\Str::limit($hito->descripcion, 120) }}</small></td>
                                <td>{{ $hito->orden }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#editarHito{{ $hito->id }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('admin.sitio.historia.destroy', $hito) }}"
                                          class="d-inline" onsubmit="return confirm('¿Eliminar este hito?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay hitos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach($hitos as $hito)
            <div class="modal fade" id="editarHito{{ $hito->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.sitio.historia.update', $hito) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Editar hito</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label small">Año</label>
                                    <input type="text" name="anio" class="form-control" required value="{{ $hito->anio }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Título</label>
                                    <input type="text" name="titulo" class="form-control" required value="{{ $hito->titulo }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="3">{{ $hito->descripcion }}</textarea>
                                </div>
                                <div>
                                    <label class="form-label small">Orden</label>
                                    <input type="number" name="orden" class="form-control" min="0" max="999" value="{{ $hito->orden }}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ============ ÚTILES ============ --}}
        <div class="tab-pane fade {{ $tabActiva === 'utiles' ? 'show active' : '' }}" id="panel-utiles"
             role="tabpanel" aria-labelledby="tab-utiles" tabindex="0">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-plus-circle me-1"></i>Agregar útil</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sitio.utiles.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label small">Nivel</label>
                            <select name="nivel" class="form-select" required>
                                @foreach($niveles as $claveNivel => $etiquetaNivel)
                                <option value="{{ $claveNivel }}">{{ $etiquetaNivel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Artículo</label>
                            <input type="text" name="nombre" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Especificación</label>
                            <input type="text" name="especificacion" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Cantidad</label>
                            <input type="text" name="cantidad" class="form-control" maxlength="60">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Área</label>
                            <input type="text" name="area" class="form-control" maxlength="80">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Orden</label>
                            <input type="number" name="orden" class="form-control" min="0" max="999" value="0">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" title="Agregar"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            @forelse($utiles as $nivel => $items)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">{{ $niveles[$nivel] ?? $nivel }}</h6>
                    <span class="badge bg-light text-dark border">{{ $items->count() }} artículo(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Artículo</th>
                                <th>Especificación</th>
                                <th>Cantidad</th>
                                <th>Área</th>
                                <th style="width:70px;">Orden</th>
                                <th class="text-end" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td><strong>{{ $item->nombre }}</strong></td>
                                <td><small>{{ $item->especificacion ?: '—' }}</small></td>
                                <td><small>{{ $item->cantidad ?: '—' }}</small></td>
                                <td><small>{{ $item->area ?: '—' }}</small></td>
                                <td>{{ $item->orden }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#editarUtil{{ $item->id }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('admin.sitio.utiles.destroy', $item) }}"
                                          class="d-inline" onsubmit="return confirm('¿Eliminar este artículo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="card shadow-sm"><div class="card-body text-center text-muted py-4">No hay útiles registrados.</div></div>
            @endforelse

            @foreach($utiles->flatten() as $item)
            <div class="modal fade" id="editarUtil{{ $item->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.sitio.utiles.update', $item) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Editar útil</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label small">Nivel</label>
                                    <select name="nivel" class="form-select" required>
                                        @foreach($niveles as $claveNivel => $etiquetaNivel)
                                        <option value="{{ $claveNivel }}" @selected($item->nivel === $claveNivel)>{{ $etiquetaNivel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Artículo</label>
                                    <input type="text" name="nombre" class="form-control" required value="{{ $item->nombre }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Especificación</label>
                                    <input type="text" name="especificacion" class="form-control" value="{{ $item->especificacion }}">
                                </div>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small">Cantidad</label>
                                        <input type="text" name="cantidad" class="form-control" value="{{ $item->cantidad }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Área</label>
                                        <input type="text" name="area" class="form-control" value="{{ $item->area }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Orden</label>
                                        <input type="number" name="orden" class="form-control" min="0" max="999" value="{{ $item->orden }}">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ============ DESCARGAS ============ --}}
        <div class="tab-pane fade {{ $tabActiva === 'descargas' ? 'show active' : '' }}" id="panel-descargas"
             role="tabpanel" aria-labelledby="tab-descargas" tabindex="0">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-plus-circle me-1"></i>Agregar documento</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sitio.descargas.store') }}"
                          enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-2">
                            <label class="form-label small">Categoría</label>
                            <select name="categoria" class="form-select" required>
                                @foreach($categorias as $claveCategoria => $etiquetaCategoria)
                                <option value="{{ $claveCategoria }}">{{ $etiquetaCategoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Título</label>
                            <input type="text" name="titulo" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Descripción</label>
                            <input type="text" name="descripcion" class="form-control" maxlength="2000">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Fecha</label>
                            <input type="text" name="fecha_texto" class="form-control" maxlength="80" placeholder="2026">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small">Peso</label>
                            <input type="text" name="peso" class="form-control" maxlength="30" placeholder="1.2 MB">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Archivo</label>
                            <input type="file" name="archivo" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" title="Agregar"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            @forelse($descargas as $categoria => $documentos)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">{{ $categorias[$categoria] ?? $categoria }}</h6>
                    <span class="badge bg-light text-dark border">{{ $documentos->count() }} documento(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                                <th>Archivo</th>
                                <th>Estado</th>
                                <th class="text-end" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentos as $documento)
                            <tr>
                                <td><strong>{{ $documento->titulo }}</strong></td>
                                <td><small>{{ \Illuminate\Support\Str::limit($documento->descripcion, 80) ?: '—' }}</small></td>
                                <td><small>{{ $documento->fecha_texto ?: '—' }}</small></td>
                                <td>
                                    @if($documento->archivoUrl())
                                    <a href="{{ $documento->archivoUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    @else
                                    <span class="text-muted small">Sin archivo</span>
                                    @endif
                                </td>
                                <td>
                                    @if($documento->activo)
                                    <span class="badge bg-success">Visible</span>
                                    @else
                                    <span class="badge bg-secondary">Oculto</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#editarDescarga{{ $documento->id }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('admin.sitio.descargas.destroy', $documento) }}"
                                          class="d-inline" onsubmit="return confirm('¿Eliminar este documento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @empty
            <div class="card shadow-sm"><div class="card-body text-center text-muted py-4">No hay documentos registrados.</div></div>
            @endforelse

            @foreach($descargas->flatten() as $documento)
            <div class="modal fade" id="editarDescarga{{ $documento->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.sitio.descargas.update', $documento) }}"
                              enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Editar documento</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label small">Categoría</label>
                                    <select name="categoria" class="form-select" required>
                                        @foreach($categorias as $claveCategoria => $etiquetaCategoria)
                                        <option value="{{ $claveCategoria }}" @selected($documento->categoria === $claveCategoria)>{{ $etiquetaCategoria }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Título</label>
                                    <input type="text" name="titulo" class="form-control" required value="{{ $documento->titulo }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="2">{{ $documento->descripcion }}</textarea>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-4">
                                        <label class="form-label small">Fecha</label>
                                        <input type="text" name="fecha_texto" class="form-control" value="{{ $documento->fecha_texto }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Peso</label>
                                        <input type="text" name="peso" class="form-control" value="{{ $documento->peso }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Orden</label>
                                        <input type="number" name="orden" class="form-control" min="0" max="999" value="{{ $documento->orden }}">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Reemplazar archivo</label>
                                    <input type="file" name="archivo" class="form-control">
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activo" value="1"
                                           id="descarga_activo{{ $documento->id }}" @checked($documento->activo)>
                                    <label class="form-check-label" for="descarga_activo{{ $documento->id }}">Visible en el portal</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
