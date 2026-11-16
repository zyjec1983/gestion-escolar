@extends('layouts.principal')

@section('titulo', 'Historia')

{{--
    Historia institucional.

    Qué hace: agrega, edita y elimina los hitos de la línea de tiempo del
    portal. Es una vista independiente, sin pestañas.
    Conecta con: Admin\SitioController y App\Models\HistoriaHito.
    Rutas: GET /admin/sitio/historia (admin.sitio.historia) y
            CRUD /admin/sitio/historia.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-book-half me-2"></i>Historia</h4>
        <p class="text-muted small mb-0">
            Línea de tiempo que se muestra en la sección Historia del portal.
        </p>
    </div>

    @include('admin.sitio._avisos')

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
                                  class="d-inline" data-confirm="¿Eliminar este hito?">
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
@endsection
