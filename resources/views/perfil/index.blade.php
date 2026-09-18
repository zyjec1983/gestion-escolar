@extends('layouts.principal')

@section('titulo', 'Mi Perfil')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-person-circle me-2"></i>Mi Perfil</h4>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Datos de la cuenta</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">Nombre de usuario</div>
                            <strong>{{ auth()->user()->name }}</strong>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Rol</div>
                            <span class="badge bg-dark text-uppercase">{{ auth()->user()->rol }}</span>
                        </div>
                    </div>
                    <hr>
                    <form method="POST" action="{{ route('perfil.update') }}">
                        @csrf
                        @php
                            $nombresArr = collect(explode(' ', trim(auth()->user()->name ?? '')))->filter()->values();
                            $apellidosArr = collect(explode(' ', trim(auth()->user()->apellidos ?? '')))->filter()->values();
                        @endphp
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Primer nombre</label>
                                <input type="text" name="primer_nombre"
                                       class="form-control @error('primer_nombre') is-invalid @enderror"
                                       value="{{ old('primer_nombre', $nombresArr->first() ?? '') }}" required>
                                @error('primer_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Segundo nombre <span class="text-muted">(opcional)</span></label>
                                <input type="text" name="segundo_nombre"
                                       class="form-control @error('segundo_nombre') is-invalid @enderror"
                                       value="{{ old('segundo_nombre', $nombresArr->count() > 1 ? $nombresArr->slice(1)->implode(' ') : '') }}">
                                @error('segundo_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido paterno</label>
                                <input type="text" name="apellido_paterno"
                                       class="form-control @error('apellido_paterno') is-invalid @enderror"
                                       value="{{ old('apellido_paterno', $apellidosArr->first() ?? '') }}" required>
                                @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido materno <span class="text-muted">(opcional)</span></label>
                                <input type="text" name="apellido_materno"
                                       class="form-control @error('apellido_materno') is-invalid @enderror"
                                       value="{{ old('apellido_materno', $apellidosArr->count() > 1 ? $apellidosArr->slice(1)->implode(' ') : '') }}">
                                @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', auth()->user()->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Número celular</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono', auth()->user()->telefono) }}"
                                   placeholder="09XXXXXXXX">
                            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>Guardar cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Cambiar contraseña</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('perfil.password') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Contraseña actual</label>
                            <input type="password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Mínimo 8 caracteres" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="bi bi-key me-1"></i>Actualizar contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Guardado',
        text: @json(session('success')),
        confirmButtonText: 'Aceptar',
    });
    @endif

    @if($errors->any())
    @php
        $msgError = 'No se guardó: revisa los campos marcados en rojo.';
        if ($errors->hasAny(['current_password', 'password', 'password_confirmation'])) {
            $msgError = 'La contraseña no se guardó. Verifica que la contraseña actual sea la correcta y que la nueva tenga al menos 8 caracteres.';
        } elseif ($errors->has('email')) {
            $msgError = 'El correo no se guardó: revisa los campos marcados en rojo.';
        }
    @endphp
    Swal.fire({
        icon: 'error',
        title: 'No se guardó',
        text: @json($msgError),
        confirmButtonText: 'Entendido',
    });
    @endif
});
</script>
@endpush