@extends('layouts.principal')

@section('titulo', 'Editar usuario')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-gear me-2"></i>Editar usuario</h4>
            <p class="text-muted small mb-0">{{ $usuario->nombreCompleto() }} · {{ $usuario->email }}</p>
        </div>
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver al listado
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-vcard me-1"></i>Datos personales</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nombres</label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $usuario->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="apellidos">Apellidos</label>
                                <input type="text" id="apellidos" name="apellidos" class="form-control @error('apellidos') is-invalid @enderror"
                                    value="{{ old('apellidos', $usuario->apellidos) }}">
                                @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="email">Correo electrónico</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $usuario->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="telefono">Teléfono (SMS)</label>
                                <input type="text" id="telefono" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                    value="{{ old('telefono', $usuario->telefono) }}">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3"><i class="bi bi-shield-lock me-1"></i>Restablecer contraseña</h6>
                        <p class="text-muted small">Deje los campos vacíos para conservar la contraseña actual.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="password">Nueva contraseña</label>
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                    autocomplete="new-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                                    autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-badge me-1"></i>Rol principal</h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label" for="rol">Rol con el que se creó la cuenta</label>
                        <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror" required>
                            @foreach($roles as $opcion)
                            <option value="{{ $opcion }}" @selected(old('rol', $usuario->rol) === $opcion)>
                                {{ \App\Models\User::etiquetaRol($opcion) }}
                            </option>
                            @endforeach
                        </select>
                        @error('rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">
                            ADMIN solo puede ser rol principal. Los demás roles pueden sumarse como adicionales.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-1"></i>Roles adicionales</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Marque todos los perfiles que esta persona puede usar. Al iniciar sesión elegirá con cuál entrar.
                        </p>

                        @foreach($rolesAdicionales as $opcion)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="roles_adicionales[]"
                                id="rol-{{ $opcion }}" value="{{ $opcion }}"
                                @checked(in_array($opcion, old('roles_adicionales', $adicionalesActuales), true))>
                            <label class="form-check-label" for="rol-{{ $opcion }}">
                                {{ \App\Models\User::etiquetaRol($opcion) }}
                            </label>
                        </div>
                        @endforeach

                        <div class="alert alert-info small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Si la persona tiene clases asignadas, el perfil de Docencia se agrega automáticamente.
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-1"></i>Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
