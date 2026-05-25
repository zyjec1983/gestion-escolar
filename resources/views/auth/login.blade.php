@extends('layouts.public')

@section('titulo', 'Iniciar Sesión')

@section('contenido')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-mortarboard-fill display-6 text-primary"></i>
                        <h5 class="mt-2 fw-bold">Iniciar Sesión</h5>
                        <p class="text-muted small">Ingrese sus credenciales</p>
                    </div>

                    @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        {{ $errors->first('email') }}
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="alert alert-success py-2 small">
                        {{ session('success') }}
                    </div>
                    @endif

                    <form method="POST" action="/login">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label small" for="remember">Recordarme</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <small class="text-muted">¿No tienes cuenta? <a href="/pre-registro" class="text-decoration-none">Solicitar matrícula</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
