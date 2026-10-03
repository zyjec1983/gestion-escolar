@extends('layouts.admin')

@section('titulo', 'Configuración')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-gear me-2"></i>Configuración del centro</h4>
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