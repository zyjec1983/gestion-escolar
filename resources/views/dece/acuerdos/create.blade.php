@extends('layouts.principal')

@section('titulo', 'Nuevo acuerdo DECE')

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Nuevo acuerdo de compromiso</h4>
        <small class="text-muted">Redacte el compromiso; las firmas se registran después, en la ficha del acuerdo.</small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.acuerdos.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    @include('dece.partials.buscador-alumno', [
                        'alumnoSeleccionado' => $alumnoSeleccionado,
                        'idBuscador' => 'buscadorAlumnoAcuerdo',
                    ])
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-select" required>
                        @foreach($tipos as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('tipo') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="atencion_id" class="form-label">Atención relacionada</label>
                    <select name="atencion_id" id="atencion_id" class="form-select">
                        <option value="">Sin atención asociada</option>
                        @foreach($atenciones as $atencion)
                            <option value="{{ $atencion->id }}" @selected(old('atencion_id') == $atencion->id)>
                                {{ $atencion->fecha_atencion->format('d/m/Y') }} — {{ $atencion->motivo }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Se cargan las atenciones del estudiante elegido.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Descripción del acuerdo <span class="text-danger">*</span></label>
                    <textarea name="descripcion" rows="4" class="form-control" required minlength="20">{{ old('descripcion') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Compromiso del estudiante</label>
                    <textarea name="compromiso_estudiante" rows="3" class="form-control">{{ old('compromiso_estudiante') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Compromiso del representante</label>
                    <textarea name="compromiso_representante" rows="3" class="form-control">{{ old('compromiso_representante') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de inicio <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', now()->toDateString()) }}" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha límite del compromiso <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_compromiso" value="{{ old('fecha_compromiso', now()->addMonth()->toDateString()) }}" class="form-control" required>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('dece.acuerdos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar acuerdo</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const atenciones = document.getElementById('atencion_id');

        if (!atenciones) {
            return;
        }

        const urlBase = @json(url('/dece/buscar/alumnos'));
        let peticion = null;

        function limpiar() {
            atenciones.innerHTML = '<option value="">Sin atención asociada</option>';
        }

        async function cargar(alumnoId) {
            limpiar();

            if (!alumnoId) {
                return;
            }

            if (peticion) {
                peticion.abort();
            }

            peticion = new AbortController();

            try {
                const respuesta = await fetch(urlBase + '/' + alumnoId + '/atenciones', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: peticion.signal,
                });

                if (!respuesta.ok) {
                    return;
                }

                const cuerpo = await respuesta.json();

                (Array.isArray(cuerpo.data) ? cuerpo.data : []).forEach(function (atencion) {
                    const opcion = document.createElement('option');
                    opcion.value = atencion.id;
                    opcion.textContent = atencion.fecha + ' — ' + atencion.motivo + ' (' + atencion.estado + ')';
                    atenciones.appendChild(opcion);
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    limpiar();
                }
            }
        }

        document.addEventListener('dece:alumno-seleccionado', function (evento) {
            cargar(evento.detail ? evento.detail.id : null);
        });
    })();
</script>
@endpush
