@extends('layouts.principal')

@section('titulo', 'Nueva cuenta')

{{--
    Formulario de creación de cuentas.

    Qué hace: el administrador registra la cédula, nombres, apellidos, título,
    tipo de contrato, correo, teléfono, rol, roles adicionales y contraseña
    temporal. El botón "Guardar y pasar a asignar materias" continúa en el
    distributivo docente cuando la cuenta tiene perfil DOCENTE.
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

    <form method="POST" action="{{ route('admin.usuarios.store') }}">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-person-vcard me-1"></i>Datos personales y profesionales</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="numero_cedula" class="form-label">Cédula de identidad <span class="text-danger">*</span></label>
                                <input type="text" id="numero_cedula" name="numero_cedula" class="form-control font-monospace @error('numero_cedula') is-invalid @enderror"
                                    value="{{ old('numero_cedula') }}" maxlength="10" placeholder="Ej. 1718459203" required>
                                @error('numero_cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">También sirve para iniciar sesión.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="name" class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="Ej. Carlos Alberto" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" id="apellidos" name="apellidos" class="form-control @error('apellidos') is-invalid @enderror"
                                    value="{{ old('apellidos') }}" placeholder="Ej. Mendoza Ruiz" required>
                                @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-7">
                                <label for="titulo" class="form-label">Título profesional / especialidad</label>
                                <input type="text" id="titulo" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                                    value="{{ old('titulo') }}" placeholder="Ej. Lcdo. en Ciencias de la Educación - Física y Matemática">
                                @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label for="horas_contrato" class="form-label">Tipo de contrato / jornada</label>
                                <select id="horas_contrato" name="horas_contrato" class="form-select @error('horas_contrato') is-invalid @enderror">
                                    <option value="40" @selected(old('horas_contrato', '40') == 40)>
                                        Tiempo completo — 40 h reloj (30 h plantel + 10 h autónomas)
                                    </option>
                                    <option value="20" @selected(old('horas_contrato') == 20)>
                                        Medio tiempo — 20 h reloj (15 h plantel + 5 h autónomas)
                                    </option>
                                </select>
                                @error('horas_contrato')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo institucional <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" placeholder="carlos.mendoza@colegio.edu.ec" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono / celular</label>
                                <input type="text" id="telefono" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                    value="{{ old('telefono') }}" placeholder="0991234567">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="rol" class="form-label">Rol en el sistema <span class="text-danger">*</span></label>
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
                            <div class="col-md-6">
                                <label for="estado" class="form-label">Estado</label>
                                <input type="text" id="estado" class="form-control bg-light fw-semibold text-secondary"
                                    value="ACTIVO" readonly>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="bi bi-shield-lock me-1"></i>Credenciales de acceso</h6>
                            <span class="badge bg-primary-subtle text-primary border">Contraseña temporal</span>
                        </div>
                        <p class="text-muted small">El acceso se hace con el correo o la cédula. Comparta la contraseña temporal y pida cambiarla al primer ingreso.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña temporal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password"
                                        class="form-control font-monospace @error('password') is-invalid @enderror"
                                        autocomplete="new-password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword"
                                        title="Mostrar u ocultar la contraseña" aria-label="Mostrar u ocultar la contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="generarPassword"
                                        title="Generar una contraseña aleatoria">
                                        <i class="bi bi-shuffle me-1"></i>Generar
                                    </button>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control font-monospace" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-1"></i>Roles adicionales</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Una persona puede usar varios perfiles. Marque todos los que necesite; al iniciar sesión elegirá con cuál entrar.
                        </p>
                        @foreach($rolesAdicionales as $opcion)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="roles_adicionales[]"
                                id="rol_{{ $opcion }}" value="{{ $opcion }}"
                                @checked(in_array($opcion, old('roles_adicionales', []), true))>
                            <label class="form-check-label" for="rol_{{ $opcion }}">
                                {{ \App\Models\User::etiquetaRol($opcion) }}
                            </label>
                        </div>
                        @endforeach

                        <div class="alert alert-info small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            El rol DOCENTE habilita el distributivo de materias y la carga horaria.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-check-lg me-1"></i>Crear cuenta
            </button>
            <button type="submit" name="destino" value="asignaciones" class="btn btn-primary">
                <i class="bi bi-person-workspace me-1"></i>Guardar y pasar a asignar materias
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var password = document.getElementById('password');
        var confirmacion = document.getElementById('password_confirmation');
        var botonVer = document.getElementById('togglePassword');
        var botonGenerar = document.getElementById('generarPassword');

        if (botonVer) {
            botonVer.addEventListener('click', function () {
                var visible = password.type === 'text';

                password.type = visible ? 'password' : 'text';
                confirmacion.type = visible ? 'password' : 'text';
                botonVer.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }

        if (botonGenerar) {
            botonGenerar.addEventListener('click', function () {
                var mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                var minusculas = 'abcdefghijkmnopqrstuvwxyz';
                var numeros = '23456789';
                var simbolos = '*#$%&?';
                var todos = mayusculas + minusculas + numeros + simbolos;
                var clave = [
                    mayusculas[Math.floor(Math.random() * mayusculas.length)],
                    minusculas[Math.floor(Math.random() * minusculas.length)],
                    numeros[Math.floor(Math.random() * numeros.length)],
                    simbolos[Math.floor(Math.random() * simbolos.length)],
                ];

                while (clave.length < 12) {
                    clave.push(todos[Math.floor(Math.random() * todos.length)]);
                }

                clave = clave.sort(function () { return Math.random() - 0.5; }).join('');

                password.value = clave;
                confirmacion.value = clave;
                password.type = 'text';
                confirmacion.type = 'text';
                botonVer.querySelector('i').className = 'bi bi-eye-slash';
            });
        }
    });
</script>
@endpush
