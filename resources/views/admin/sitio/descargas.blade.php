@extends('layouts.principal')

@section('titulo', 'Descargas')

{{--
    Centro de descargas.

    Qué hace: agrega, edita y elimina los documentos del centro de descargas
    del portal. Es una vista independiente, sin pestañas.
    Conecta con: Admin\SitioController y App\Models\DescargaDocumento.
    Rutas: GET /admin/sitio/descargas (admin.sitio.descargas) y
            CRUD /admin/sitio/descargas.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-cloud-arrow-down me-2"></i>Descargas</h4>
        <p class="text-muted small mb-0">
            Documentos que la comunidad puede descargar desde el portal.
        </p>
    </div>

    @include('admin.sitio._avisos')

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
                                  class="d-inline" data-confirm="¿Eliminar este documento?">
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
@endsection
