@extends('layouts.principal')

@section('titulo', 'Nueva Planificación')

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-journal-bookmark-fill me-2"></i>Crear Planificación de Unidad</h4>
            <small class="text-muted">La unidad se aplica al nivel y paralelos seleccionados; puedes replicarla a otros paralelos del mismo nivel.</small>
        </div>
    </div>

    @if($combos->isEmpty())
        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No tienes clases asignadas. Solicita a Secretaría que registre tus materias, niveles y paralelos antes de crear una planificación.
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="planificacionForm" method="POST" action="{{ route('planificaciones.store') }}">
                        @csrf
                        <input type="hidden" name="materia_id" id="materiaId">
                        <input type="hidden" name="curso_id" id="cursoId">

                        <div class="mb-3">
                            <label class="form-label">Materia y nivel origen *</label>
                            <select id="comboSelect" class="form-select @error('materia_id') is-invalid @enderror" required @disabled($combos->isEmpty())>
                                <option value="">Seleccione la materia y nivel...</option>
                                @foreach($combos as $combo)
                                    <optgroup label="{{ $combo['curso'] }}">
                                        <option value="{{ $combo['clave'] }}">{{ $combo['materia'] }} - {{ $combo['curso'] }}</option>
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('materia_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @error('curso_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3" id="replicationBox" style="display:none;">
                            <label class="form-label">Aplicar o replicar esta planificación para:</label>
                            <div id="paraleloChecks" class="d-flex flex-wrap gap-3">
                                <!-- checkboxes dinámicos -->
                            </div>
                            @error('paralelo_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Título de la unidad *</label>
                                <input type="text" name="titulo" id="unitTitle"
                                       class="form-control @error('titulo') is-invalid @enderror"
                                       value="{{ old('titulo') }}" placeholder="Ej: Unit 1 - Argumentative Essays & Structure" required>
                                @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción / contenido *</label>
                                <textarea name="descripcion" id="unitDescription"
                                          class="form-control @error('descripcion') is-invalid @enderror"
                                          placeholder="Indique brevemente de qué tratará la unidad y los temas clave..." required>{{ old('descripcion') }}</textarea>
                                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Quimestre *</label>
                                <select name="quimestre" id="quimestreSelect" class="form-select @error('quimestre') is-invalid @enderror" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($quimestres as $value => $label)
                                        <option value="{{ $value }}" {{ old('quimestre') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('quimestre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Parcial *</label>
                                <select name="parcial" id="parcialSelect" class="form-select @error('parcial') is-invalid @enderror" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($parciales as $value => $label)
                                        <option value="{{ $value }}" {{ old('parcial') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('parcial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha de inicio *</label>
                                <input type="date" name="fecha_inicio" id="startDate"
                                       class="form-control @error('fecha_inicio') is-invalid @enderror"
                                       value="{{ old('fecha_inicio') }}" required>
                                @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha de finalización *</label>
                                <input type="date" name="fecha_fin" id="endDate"
                                       class="form-control @error('fecha_fin') is-invalid @enderror"
                                       value="{{ old('fecha_fin') }}" required>
                                @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Duración total</label>
                                <input type="text" id="totalDays" class="form-control" readonly
                                       placeholder="Se calculará automáticamente al ingresar las fechas">
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="btn btn-primary w-100 mt-4" @disabled($combos->isEmpty())>
                            <i class="bi bi-save me-1"></i>Guardar y Vincular Planificación
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
    const combos = @json($combos);
    const comboMap = {};
    combos.forEach(function (c) { comboMap[c.clave] = c; });

    const comboSelect = document.getElementById('comboSelect');
    const replicationBox = document.getElementById('replicationBox');
    const paraleloChecks = document.getElementById('paraleloChecks');
    const materiaId = document.getElementById('materiaId');
    const cursoId = document.getElementById('cursoId');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const totalDays = document.getElementById('totalDays');
    const form = document.getElementById('planificacionForm');
    const submitBtn = document.getElementById('submitBtn');

    function llenarParalelos() {
        const combo = comboMap[comboSelect.value];
        paraleloChecks.innerHTML = '';

        if (!combo) {
            replicationBox.style.display = 'none';
            materiaId.value = '';
            cursoId.value = '';
            return;
        }

        materiaId.value = combo.materia_id;
        cursoId.value = combo.curso_id;

        combo.paralelos.forEach(function (p, index) {
            const label = document.createElement('label');
            label.className = 'form-check';
            label.style.cursor = 'pointer';
            const isChecked = index === 0 ? 'checked' : '';
            label.innerHTML =
                '<input class="form-check-input" type="checkbox" name="paralelo_ids[]" ' +
                'value="' + p.id + '" ' + isChecked + ' data-clase="' + (p.clase_docente_id || '') + '">' +
                '<span class="form-check-label ms-1">' + p.nombre + '</span>';
            paraleloChecks.appendChild(label);
        });

        replicationBox.style.display = 'block';
    }

    comboSelect.addEventListener('change', llenarParalelos);

    function calcularDias() {
        const startVal = startDate.value;
        const endVal = endDate.value;

        if (startVal && endVal) {
            const start = new Date(startVal + 'T00:00:00');
            const end = new Date(endVal + 'T00:00:00');

            if (end >= start) {
                const diff = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
                totalDays.value = diff + ' día(s) en total';
            } else {
                totalDays.value = 'La fecha final debe ser posterior al inicio';
            }
        } else {
            totalDays.value = '';
        }
    }

    startDate.addEventListener('change', calcularDias);
    endDate.addEventListener('change', calcularDias);

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const numParalelos = document.querySelectorAll('input[name="paralelo_ids[]"]:checked').length;
        if (numParalelos === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona al menos un paralelo',
                text: 'Debes marcar uno o más paralelos para aplicar la planificación.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        Swal.fire({
            icon: 'question',
            title: '¿Guardar planificación?',
            text: 'Se aplicará a ' + numParalelos + ' paralelo(s) del nivel seleccionado.',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                submitBtn.disabled = true;
                form.submit();
            }
        });
    });
});
</script>
@endpush