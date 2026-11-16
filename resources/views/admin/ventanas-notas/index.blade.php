@extends('layouts.principal')

@section('titulo', 'Fecha de Cargar notas')

{{--
    Fecha de Cargar notas.

    Qué hace: crea, edita, habilita/deshabilita y elimina ventanas por parcial.
    Mientras un parcial tenga una ventana configurada, el docente solo puede
    guardar notas dentro de las fechas y con la ventana activa.
    Conecta con: Admin\VentanaNotaController, App\Models\VentanaNota y
    ConfiguracionNotas.
    Rutas: CRUD /admin/ventanas-notas y POST /admin/ventanas-notas/{id}/estado.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-calendar-range me-2"></i>Fecha de Cargar notas</h4>
            <p class="text-muted small mb-0">
                Habilite o deshabilite la carga de notas por parcial. Si un parcial no tiene ventana,
                se mantiene el comportamiento anterior (carga permitida).
            </p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formNuevaVentana"
                aria-expanded="false" aria-controls="formNuevaVentana">
            <i class="bi bi-plus-circle me-1"></i>Nueva ventana
        </button>
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

    <div class="collapse mb-4" id="formNuevaVentana">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-calendar-plus me-1"></i>Nueva ventana de carga</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.ventanas-notas.store') }}">
                    @csrf
                    @include('admin.ventanas-notas._form', ['ventana' => null])
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse"
                                data-bs-target="#formNuevaVentana">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar ventana
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-list-check me-1"></i>Ventanas configuradas</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Parcial</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventanas as $ventana)
                    <tr>
                        <td>
                            <strong>
                                @if($ventana->parcial === null)
                                    Todos los parciales
                                @else
                                    {{ collect($opciones)->firstWhere('parcial', $ventana->parcial)['label'] ?? ('Parcial '.$ventana->parcial) }}
                                @endif
                            </strong>
                        </td>
                        <td><small>{{ $ventana->fecha_inicio?->format('d/m/Y H:i') }}</small></td>
                        <td><small>{{ $ventana->fecha_fin?->format('d/m/Y H:i') }}</small></td>
                        <td>
                            @if($ventana->activo)
                            <span class="badge bg-success">Habilitada</span>
                            @else
                            <span class="badge bg-secondary">Deshabilitada</span>
                            @endif
                            @if($ventana->activo && now()->between($ventana->fecha_inicio, $ventana->fecha_fin))
                            <span class="badge bg-info text-dark">Vigente</span>
                            @endif
                        </td>
                        <td><small>{{ $ventana->descripcion ?: '—' }}</small></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editarVentana{{ $ventana->id }}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.ventanas-notas.estado', $ventana) }}" class="d-inline"
                                  data-confirm="{{ $ventana->activo ? '¿Deshabilitar la carga de notas de esta ventana?' : '¿Habilitar la carga de notas de esta ventana?' }}">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $ventana->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $ventana->activo ? 'Deshabilitar' : 'Habilitar' }}">
                                    <i class="bi {{ $ventana->activo ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.ventanas-notas.destroy', $ventana) }}"
                                class="d-inline" data-confirm="¿Eliminar esta ventana de notas?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($ventanas->hasPages())
        <div class="card-footer bg-white">{{ $ventanas->links() }}</div>
        @endif
    </div>
</div>

@foreach($ventanas as $ventana)
<div class="modal fade" id="editarVentana{{ $ventana->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.ventanas-notas.update', $ventana) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i>Editar ventana</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @include('admin.ventanas-notas._form', ['ventana' => $ventana])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
