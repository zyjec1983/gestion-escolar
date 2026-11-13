@extends('layouts.principal')

@section('titulo', 'Nueva atención DECE')

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-clipboard2-plus me-2"></i>Nueva atención individual</h4>
        <small class="text-muted">La ficha es confidencial y queda registrada en la bitácora del DECE.</small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.atenciones.store') }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    @include('dece.partials.buscador-alumno', [
                        'alumnoSeleccionado' => $alumnoSeleccionado,
                        'idBuscador' => 'buscadorAlumnoAtencion',
                    ])
                </div>

                <div class="col-md-6">
                    <label for="cita_id" class="form-label">Cita relacionada (opcional)</label>
                    <select name="cita_id" id="cita_id" class="form-select">
                        <option value="">Sin cita asociada</option>
                        @foreach($citas as $cita)
                            <option value="{{ $cita->id }}" @selected(old('cita_id') == $cita->id)>
                                {{ $cita->fecha_hora->format('d/m/Y H:i') }} — {{ $cita->motivo }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Las citas programadas se cargan al elegir el estudiante.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_atencion" value="{{ old('fecha_atencion', now()->format('Y-m-d\TH:i')) }}" class="form-control" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <select name="motivo" class="form-select" required>
                        <option value="">Seleccione…</option>
                        @foreach($motivos as $motivo)
                            <option value="{{ $motivo }}" @selected(old('motivo') === $motivo)>{{ $motivo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Relatoría de la entrevista <span class="text-danger">*</span></label>
                    <textarea name="historia_narrada" rows="6" class="form-control" required minlength="20"
                              placeholder="Describa el motivo de consulta, los hechos narrados y la intervención realizada.">{{ old('historia_narrada') }}</textarea>
                    <div class="form-text">Mínimo 20 caracteres. Esta información es confidencial.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Acuerdos y recomendaciones</label>
                    <textarea name="acuerdos" rows="3" class="form-control" placeholder="Compromisos inmediatos, recomendaciones y seguimiento.">{{ old('acuerdos') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Evidencias (PDF, JPG o PNG · máx. 10 MB)</label>
                    <input type="file" name="evidencias[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('dece.atenciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar ficha</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const citas = document.getElementById('cita_id');

        if (!citas) {
            return;
        }

        const urlBase = @json(url('/dece/buscar/alumnos'));
        let peticion = null;

        function limpiar() {
            citas.innerHTML = '<option value="">Sin cita asociada</option>';
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
                const respuesta = await fetch(urlBase + '/' + alumnoId + '/citas', {
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

                (Array.isArray(cuerpo.data) ? cuerpo.data : []).forEach(function (cita) {
                    const opcion = document.createElement('option');
                    opcion.value = cita.id;
                    opcion.textContent = cita.fecha + ' — ' + cita.motivo;
                    citas.appendChild(opcion);
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
