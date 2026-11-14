@extends('layouts.principal')

@section('titulo', 'Usuarios')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-people-fill me-2"></i>Usuarios</h4>
            <p class="text-muted small mb-0">
                Asigne el rol principal y los roles adicionales de cada cuenta. Una persona puede usar varios perfiles.
            </p>
        </div>
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.usuarios.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="buscar">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="buscar" name="buscar" class="form-control"
                            value="{{ $buscar }}" placeholder="Nombre, apellido o correo">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="rol">Perfil</label>
                    <select id="rol" name="rol" class="form-select">
                        <option value="">Todos los perfiles</option>
                        @foreach($roles as $opcion)
                        <option value="{{ $opcion }}" @selected($rol === $opcion)>{{ \App\Models\User::etiquetaRol($opcion) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol principal</th>
                        <th>Perfiles disponibles</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td>
                            <strong>{{ $usuario->nombreCompleto() }}</strong>
                            @if($usuario->telefono)
                            <br><small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $usuario->telefono }}</small>
                            @endif
                        </td>
                        <td><small>{{ $usuario->email }}</small></td>
                        <td>
                            <span class="badge bg-primary">{{ \App\Models\User::etiquetaRol($usuario->rol) }}</span>
                        </td>
                        <td>
                            @foreach($usuario->perfiles() as $perfil)
                            <span class="badge bg-light text-dark border me-1 mb-1">{{ \App\Models\User::etiquetaRol($perfil) }}</span>
                            @endforeach
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i>Editar
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No hay usuarios que coincidan con los filtros.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
        <div class="card-footer bg-white">
            {{ $usuarios->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
