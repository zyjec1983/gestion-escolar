@extends('layouts.principal')

@section('titulo', 'Comunidad educativa')

{{--
    Administración de la Comunidad Educativa.

    Qué hace: permite agregar, editar y eliminar integrantes con cargo, foto,
    nombres, responsabilidad y área. El portal agrupa las tarjetas por área.
    Conecta con: Admin\ComunidadController y welcome.blade.php.
    Rutas: POST/PUT/DELETE /admin/comunidad.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-people-fill me-2"></i>Comunidad educativa</h4>
            <p class="text-muted small mb-0">
                Cargue cargo, fotografía, nombres y responsabilidad. Las tarjetas se agrupan por área
                (Rectorado, DECE, Matemáticas, Inglés, etc.).
            </p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formNuevoMiembro"
                aria-expanded="false" aria-controls="formNuevoMiembro">
            <i class="bi bi-person-plus me-1"></i>Agregar integrante
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

    {{-- Formulario de alta de un nuevo integrante. --}}
    <div class="collapse mb-4" id="formNuevoMiembro">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-person-plus me-1"></i>Nuevo integrante</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.comunidad.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('admin.comunidad._form', ['miembro' => null])
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse"
                                data-bs-target="#formNuevoMiembro">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar integrante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @forelse($miembros->groupBy('area') as $area => $integrantes)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-diagram-3 me-1"></i>{{ $area ?: 'Sin área' }}</h6>
            <span class="badge bg-light text-dark border">{{ $integrantes->count() }} integrante(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:64px;">Foto</th>
                        <th>Cargo / nombres</th>
                        <th>Responsabilidad</th>
                        <th style="width:90px;">Orden</th>
                        <th style="width:100px;">Estado</th>
                        <th class="text-end" style="width:150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($integrantes->sortBy('orden') as $miembro)
                    <tr>
                        <td>
                            @if($miembro->fotoUrl())
                            <img src="{{ $miembro->fotoUrl() }}" alt="{{ $miembro->nombre }}"
                                class="rounded-circle border" style="width:44px;height:44px;object-fit:cover;">
                            @else
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border text-muted"
                                style="width:44px;height:44px;"><i class="bi bi-person"></i></span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $miembro->nombre }}</strong><br>
                            <small class="text-muted">{{ $miembro->cargo }}</small>
                        </td>
                        <td><small>{{ \Illuminate\Support\Str::limit($miembro->responsabilidad, 120) ?: '—' }}</small></td>
                        <td><span class="badge bg-secondary">{{ $miembro->orden }}</span></td>
                        <td>
                            @if($miembro->activo)
                            <span class="badge bg-success">Visible</span>
                            @else
                            <span class="badge bg-secondary">Oculto</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editarMiembro{{ $miembro->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.comunidad.destroy', $miembro) }}"
                                class="d-inline" onsubmit="return confirm('¿Eliminar a {{ $miembro->nombre }} de la comunidad?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
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

    {{-- Modales de edición: uno por integrante. --}}
    @foreach($integrantes as $miembro)
    <div class="modal fade" id="editarMiembro{{ $miembro->id }}" tabindex="-1"
         aria-labelledby="editarMiembroLabel{{ $miembro->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.comunidad.update', $miembro) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarMiembroLabel{{ $miembro->id }}">
                            <i class="bi bi-pencil-square me-1"></i>Editar integrante
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.comunidad._form', ['miembro' => $miembro])
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
    @empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-people display-1 text-muted mb-3 d-block"></i>
            <p class="text-muted mb-3">Todavía no hay integrantes registrados.</p>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#formNuevoMiembro">
                <i class="bi bi-person-plus me-1"></i>Agregar el primero
            </button>
        </div>
    </div>
    @endforelse
</div>
@endsection
