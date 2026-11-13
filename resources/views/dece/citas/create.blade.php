@extends('layouts.principal')

@section('titulo', 'Agendar cita DECE')

@section('contenido')
@php
    $representante = $alumnoSeleccionado?->representante;
@endphp

<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Agendar cita con representante</h4>
        <small class="text-muted">Al guardar, el representante recibe la citación en su bandeja de mensajes.</small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.citas.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    @include('dece.partials.buscador-alumno', [
                        'alumnoSeleccionado' => $alumnoSeleccionado,
                        'idBuscador' => 'buscadorAlumnoCita',
                        'mostrarFicha' => false,
                    ])
                </div>

                <div class="col-md-6">
                    <label class="form-label">Datos del representante</label>
                    <div class="card bg-light border-0" data-representante-card>
                        <div class="card-body py-2">
                            <div class="fw-semibold" data-rep-nombre>
                                {{ $representante?->nombreCompleto() ?? 'Sin representante registrado' }}
                            </div>
                            <div class="small text-muted">
                                CI: <span data-rep-cedula>{{ $representante?->numero_cedula ?? '—' }}</span>
                                · Tel: <span data-rep-telefono>{{ $representante?->telefono ?? '—' }}</span>
                            </div>
                            <div class="small text-muted" data-rep-email>{{ $representante?->email ?? '' }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="representante_user_id" value="{{ $alumnoSeleccionado?->representante_user_id }}" data-rep-id>
                    <div class="form-text">Se toma automáticamente de la ficha del estudiante.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora', now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')) }}" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Duración (minutos) <span class="text-danger">*</span></label>
                    <input type="number" name="duracion_minutos" value="{{ old('duracion_minutos', 30) }}" min="10" max="240" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Profesional</label>
                    <select name="profesional_id" class="form-select">
                        <option value="">Asignarme la cita</option>
                        @foreach($profesionales as $profesional)
                            <option value="{{ $profesional->id }}" @selected(old('profesional_id') == $profesional->id)>{{ $profesional->nombreCompleto() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <input type="text" name="motivo" value="{{ old('motivo') }}" maxlength="150" class="form-control" required
                           placeholder="Ej.: Entrevista por bajo rendimiento académico">
                </div>

                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="form-control" placeholder="Indicaciones para la entrevista.">{{ old('observaciones') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('dece.citas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-calendar-check me-1"></i>Programar y notificar</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const nombre = document.querySelector('[data-rep-nombre]');
        const cedula = document.querySelector('[data-rep-cedula]');
        const telefono = document.querySelector('[data-rep-telefono]');
        const email = document.querySelector('[data-rep-email]');
        const identificador = document.querySelector('[data-rep-id]');

        if (!nombre || !identificador) {
            return;
        }

        document.addEventListener('dece:alumno-seleccionado', function (evento) {
            const representante = evento.detail ? evento.detail.representante : null;

            identificador.value = representante ? representante.id : '';
            nombre.textContent = representante ? representante.nombre : 'Sin representante registrado';
            cedula.textContent = representante?.cedula || '—';
            telefono.textContent = representante?.telefono || '—';
            email.textContent = representante?.email || '';
        });
    })();
</script>
@endpush
