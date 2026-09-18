@extends('layouts.principal')

@section('titulo', 'Editar Planificación')

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-journal-bookmark-fill me-2"></i>Editar Planificación de Unidad</h4>
            <small class="text-muted">Los cambios se aplican a esta unidad y a sus réplicas en otros paralelos.</small>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-9">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Materia</label>
                            <input type="text" class="form-control" value="{{ $unidad->materia->nombre }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Aplicada a ({{ $grupo->count() }})</label>
                            <input type="text" class="form-control"
                                   value="{{ $grupo->map(fn ($u) => $u->claseDocente ? trim($u->claseDocente->curso?->nombre . ' ' . $u->claseDocente->paralelo?->nombre) : 'General')->unique()->implode(', ') }}"
                                   readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="planificacionEditForm" method="POST" action="{{ route('planificaciones.update', $unidad) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Título de la unidad *</label>
                                <input type="text" name="titulo"
                                       class="form-control @error('titulo') is-invalid @enderror"
                                       value="{{ old('titulo', $unidad->titulo) }}" required>
                                @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción / contenido *</label>
                                <textarea name="descripcion"
                                          class="form-control @error('descripcion') is-invalid @enderror" required>{{ old('descripcion', $unidad->descripcion) }}</textarea>
                                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Quimestre *</label>
                                <select name="quimestre" class="form-select @error('quimestre') is-invalid @enderror" required>
                                    @foreach($quimestres as $value => $label)
                                        <option value="{{ $value }}" {{ old('quimestre', $unidad->quimestre) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('quimestre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Parcial *</label>
                                <select name="parcial" class="form-select @error('parcial') is-invalid @enderror" required>
                                    @foreach($parciales as $value => $label)
                                        <option value="{{ $value }}" {{ old('parcial', $unidad->parcial) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('parcial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha de inicio *</label>
                                <input type="date" name="fecha_inicio" id="startDate"
                                       class="form-control @error('fecha_inicio') is-invalid @enderror"
                                       value="{{ old('fecha_inicio', $unidad->fecha_inicio?->format('Y-m-d')) }}" required>
                                @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha de finalización *</label>
                                <input type="date" name="fecha_fin" id="endDate"
                                       class="form-control @error('fecha_fin') is-invalid @enderror"
                                       value="{{ old('fecha_fin', $unidad->fecha_fin?->format('Y-m-d')) }}" required>
                                @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Duración total</label>
                                <input type="text" id="totalDays" class="form-control" readonly
                                       value="{{ $unidad->duracionDias() }} día(s) en total">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Guardar Cambios
                            </button>
                            <a href="{{ route('planificaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
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
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const totalDays = document.getElementById('totalDays');
    const form = document.getElementById('planificacionEditForm');
    const submitBtn = document.getElementById('submitBtn');

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
        Swal.fire({
            icon: 'question',
            title: '¿Guardar cambios?',
            text: 'Se actualizarán la unidad y sus réplicas en otros paralelos.',
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