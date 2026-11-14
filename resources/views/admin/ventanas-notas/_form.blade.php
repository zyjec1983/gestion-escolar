{{--
    Campos compartidos por el alta y la edición de una ventana de notas.

    Qué hace: dibuja parcial, fecha/hora de apertura y cierre, descripción y
    estado. Se reutiliza en el formulario de alta y en el modal de edición.
    Conecta con: Admin\VentanaNotaController y App\Models\VentanaNota.
--}}
@php
    $ventana = $ventana ?? null;
    // "general" aplica a todos los parciales; el resto son ventanas puntuales.
    $parcialSeleccionado = (string) old('parcial', $ventana && $ventana->parcial !== null ? $ventana->parcial : 'general');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Parcial <span class="text-danger">*</span></label>
        <select name="parcial" class="form-select" required>
            <option value="general" @selected($parcialSeleccionado === 'general')>
                Todos los parciales (ventana general)
            </option>
            @foreach($opciones as $opcion)
            <option value="{{ $opcion['parcial'] }}"
                @selected($parcialSeleccionado === (string) $opcion['parcial'])>
                {{ $opcion['label'] }}
            </option>
            @endforeach
        </select>
        <small class="text-muted">La ventana general aplica a cualquier parcial que no tenga una propia.</small>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="activo" id="ventana_activo{{ $ventana->id ?? 'nuevo' }}"
                value="1" @checked(old('activo', $ventana->activo ?? true))>
            <label class="form-check-label" for="ventana_activo{{ $ventana->id ?? 'nuevo' }}">
                Carga de notas habilitada
            </label>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Apertura <span class="text-danger">*</span></label>
        <input type="datetime-local" name="fecha_inicio" class="form-control" required
            value="{{ old('fecha_inicio', $ventana?->fecha_inicio?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Cierre <span class="text-danger">*</span></label>
        <input type="datetime-local" name="fecha_fin" class="form-control" required
            value="{{ old('fecha_fin', $ventana?->fecha_fin?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Descripción</label>
        <input type="text" name="descripcion" class="form-control" maxlength="255"
            value="{{ old('descripcion', $ventana->descripcion ?? '') }}"
            placeholder="Ej. Carga del primer quimestre para Bachillerato">
    </div>
</div>
