@extends('layouts.principal')

@section('titulo', 'Configuración general')

{{--
    Configuración general del centro.

    Qué hace: define la zona horaria con la que se registran las fechas y
    muestra el sistema de evaluación activo. Es una vista independiente, sin
    pestañas, dentro del menú "Apariencia del sistema".
    Conecta con: Admin\ConfiguracionController, App\Models\Institucion y
    layouts/principal.blade.php.
    Ruta: GET /admin/configuracion (admin.configuracion.index).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-sliders me-2"></i>Configuración general</h4>
        <p class="text-muted small mb-0">
            Zona horaria de la institución y sistema de evaluación activo.
        </p>
    </div>

    @include('admin.configuracion._mensajes')

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Zona horaria</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Define la hora con la que la institución registra sus fechas: envíos de mensajes,
                        consultas y demás registros con hora. Si el programa se instala en otro país,
                        basta con elegir la zona de ese lugar.
                    </p>

                    <form method="POST" action="{{ route('admin.configuracion.zona-horaria') }}">
                        @csrf
                        <div class="mb-2">
                            <label for="zona_horaria" class="form-label fw-semibold">Desfase respecto a UTC</label>
                            <select name="zona_horaria" id="zona_horaria" class="form-select">
                                @foreach($desfases as $desfase)
                                    @php($valor = valorDesfaseHorario($desfase['minutos']))
                                    <option value="{{ $valor }}" {{ $institucion->zona_horaria === $valor ? 'selected' : '' }}>
                                        {{ formatearDesfase($desfase['minutos']) }} — {{ $desfase['ciudades'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('zona_horaria')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <p class="text-muted small mb-3">
                            Elige las horas que lleva tu centro respecto a UTC. Son 23 desfases, de
                            UTC-11:00 a UTC+11:00. Si no hay horario de verano donde está el centro,
                            el valor sirve todo el año igual.
                        </p>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar zona horaria
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Estado actual</h6>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Desfase configurado</dt>
                        <dd class="col-7">{{ formatearDesfase(minutosDesfaseZona($institucion->zona_horaria)) }}</dd>

                        <dt class="col-5">Hora actual del centro</dt>
                        <dd class="col-7 mb-0">{{ now()->format('d/m/Y H:i:s') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-sliders me-1"></i>Registro</h6>
                </div>
                <div class="card-body small">
                    <p class="mb-0 text-muted">
                        Sistema de evaluación:
                        <strong>{{ $institucion->sistema_evaluacion === 'trimestre' ? 'Trimestre' : 'Quimestre' }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
