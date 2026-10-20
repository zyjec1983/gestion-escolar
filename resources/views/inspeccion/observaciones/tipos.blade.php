@extends('layouts.principal')

@section('titulo', 'Tipos de observación')

@section('contenido')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Hay errores:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Catálogo de tipos de observación
            </h4>
            <p class="text-muted mb-0">El historial se conserva aunque un tipo sea deshabilitado. No se elimina.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            @if ($esAutoridad)
                <form method="POST" action="{{ route('inspeccion.observaciones.tipos.alternar') }}" class="d-flex align-items-center gap-2 bg-white border rounded px-3 py-2 shadow-sm">
                    @csrf
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="edicionGlobal" name="valor" value="1" onchange="this.form.submit()" @checked($edicionGlobal)>
                        <label class="form-check-label small" for="edicionGlobal">
                            Edición habilitada para Inspección
                        </label>
                    </div>
                </form>
            @else
                <span class="badge bg-{{ $edicionGlobal ? 'success' : 'warning' }} text-dark py-2 px-3">
                    <i class="bi bi-{{ $edicionGlobal ? 'unlock' : 'lock' }} me-1"></i>
                    {{ $edicionGlobal ? 'Edición habilitada temporalmente' : 'Edición bloqueada por Rectorado/Vicerrectorado' }}
                </span>
            @endif
            <a href="{{ route('inspeccion.observaciones.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a Observaciones
            </a>
        </div>
    </div>

    @if ($puedeEditar)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-plus-circle me-1"></i> Añadir / editar tipo de observación</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('inspeccion.observaciones.tipos.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="255" required>
                    </div>
                    <div class="col-md-4">
                        <label for="codigo" class="form-label">Código (opcional)</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" value="{{ old('codigo') }}" maxlength="30" placeholder="Ej. OBS_UNIFORME">
                    </div>
                    <div class="col-md-4">
                        <label for="clasificacion" class="form-label">Clasificación <span class="text-danger">*</span></label>
                        <select name="clasificacion" id="clasificacion" class="form-select" required>
                            <option value="">Seleccione...</option>
                            @foreach ($clasificaciones as $key => $label)
                                <option value="{{ $key }}" @selected(old('clasificacion') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label for="articulo_ley" class="form-label">Sustento legal / artículo de ley</label>
                        <input type="text" class="form-control" id="articulo_ley" name="articulo_ley" value="{{ old('articulo_ley') }}" maxlength="1000" placeholder="Ej. Art. 330 del Reglamento General a la LOEI">
                    </div>
                    <div class="col-md-4">
                        <label for="icono" class="form-label">Icono (Bootstrap Icons, opcional)</label>
                        <input type="text" class="form-control" id="icono" name="icono" value="{{ old('icono') }}" maxlength="60" placeholder="Ej. person-workspace, phone, clock-history">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Guardar tipo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-list-check me-1"></i> Catálogo activo/inactivo</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Clasificación</th>
                        <th>Sustento legal</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tipos as $tipo)
                        <tr class="{{ $tipo->activo ? '' : 'table-secondary' }}">
                            <td class="text-muted small">{{ $tipo->codigo }}</td>
                            <td class="fw-semibold">{{ $tipo->nombre }}</td>
                            <td>
                                @php
                                    $cl = strtolower($tipo->clasificacion);
                                    $badge = match ($cl) {
                                        'positiva' => 'bg-success',
                                        'muy_grave' => 'bg-danger',
                                        'grave' => 'bg-warning text-dark',
                                        'leve' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $tipo->etiquetaClasificacion() }}</span>
                            </td>
                            <td class="small text-muted">{{ $tipo->articulo_ley ?: '—' }}</td>
                            <td>
                                @if ($tipo->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-light text-dark border">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($puedeEditar)
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="{{ route('inspeccion.observaciones.tipos.toggle', $tipo->id) }}" onsubmit="return confirm('{{ $tipo->activo ? 'Deshabilitar este tipo hará que deje de aparecer en los selects. ¿Continuar?' : 'Habilitar este tipo hará que vuelva a aparecer en los selects. ¿Continuar?' }}')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-{{ $tipo->activo ? 'warning' : 'success' }}">
                                                <i class="bi bi-{{ $tipo->activo ? 'toggle-off' : 'toggle-on' }} me-1"></i> {{ $tipo->activo ? 'Deshabilitar' : 'Habilitar' }}
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">Solo lectura</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay tipos de observación configurados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
