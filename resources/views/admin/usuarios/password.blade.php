@extends('layouts.principal')

@section('titulo', 'Restablecer contraseña')

{{--
    Restablecimiento de contraseña olvidada.

    Qué hace: el administrador define una nueva clave para la cuenta sin tocar
    el resto de los datos. Queda registrado en el historial de cambios.
    Conecta con: Admin\UserController@updatePassword y App\Models\User.
    Ruta: POST /admin/usuarios/{user}/contrasena (admin.usuarios.password.update).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-key me-2"></i>Restablecer contraseña</h4>
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

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-info-circle me-1"></i>
                        Comparta la nueva clave con la persona por un canal seguro.
                    </div>

                    <form method="POST" action="{{ route('admin.usuarios.password.update', $usuario) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva contraseña</label>
                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password" required autofocus>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Mínimo 8 caracteres.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" autocomplete="new-password" required>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-1"></i>Restablecer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
