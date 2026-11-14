@extends('layouts.principal')

@section('titulo', 'Nueva cuenta')

{{--
    Formulario de creación de cuentas.

    Qué hace: el administrador registra nombres, correo, teléfono, rol
    principal, roles adicionales y contraseña inicial.
    Conecta con: Admin\UserController@store y App\Models\User.
    Ruta: POST /admin/usuarios (admin.usuarios.store).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-plus me-2"></i>Nueva cuenta</h4>
            <p class="text-muted small mb-0">Cree el acceso de un integrante de la comunidad educativa.</p>
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

    <form method="POST" action="{{ route('admin.usuarios.store') }}">
        @csrf

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-vcard me-1"></i>Datos personales</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="apellidos" class="form-label">Apellidos</label>
                                <input type="text" id="apellidos" name="apellidos" class="form-control @error('apellidos') is-invalid @enderror"
                                    value="{{ old('apellidos') }}">
                                @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-7">
                                <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label for="telefono" class="form-label">Teléfono / celular</label>
                                <input type="text" id="telefono" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                    value="{{ old('telefono') }}" placeholder="Para avisos por SMS">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-shield-lock me-1"></i>Acceso y perfiles</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol principal <span class="text-danger">*</span></label>
                            <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror" required>
                                <option value="">Seleccione un rol</option>
                                @foreach($roles as $opcion)
                                <option value="{{ $opcion }}" @selected(old('rol') === $opcion)>
                                    {{ \App\Models\User::etiquetaRol($opcion) }}
                                </option>
                                @endforeach
                            </select>
                            @error('rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Roles adicionales</label>
                            <p class="text-muted small mb-2">Una persona puede usar varios perfiles.</p>
                            @foreach($rolesAdicionales as $opcion)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles_adicionales[]"
                                    id="rol_{{ $opcion }}" value="{{ $opcion }}"
                                    @checked(in_array($opcion, old('roles_adicionales', []), true))>
                                <label class="form-check-label" for="rol_{{ $opcion }}">
                                    {{ \App\Models\User::etiquetaRol($opcion) }}
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña inicial <span class="text-danger">*</span></label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-0">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Crear cuenta
            </button>
        </div>
    </form>
</div>
@endsection
