{{--
    Campos compartidos por el alta y la edición de un integrante.

    Qué hace: dibuja cargo, nombres, responsabilidad, área, orden, estado y
    fotografía. Se reutiliza en el formulario de alta y en los modales de
    edición de la comunidad educativa.
    Conecta con: Admin\ComunidadController y App\Models\ComunidadMiembro.
--}}
@php($miembro = $miembro ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Cargo <span class="text-danger">*</span></label>
        <input type="text" name="cargo" class="form-control" required maxlength="120"
            value="{{ old('cargo', $miembro->cargo ?? '') }}" placeholder="Ej. Rectora, Docente de Matemáticas">
    </div>
    <div class="col-md-6">
        <label class="form-label">Nombres y apellidos <span class="text-danger">*</span></label>
        <input type="text" name="nombre" class="form-control" required maxlength="255"
            value="{{ old('nombre', $miembro->nombre ?? '') }}" placeholder="Ej. Hna. María del Carmen Pérez">
    </div>
    <div class="col-md-6">
        <label class="form-label">Área o grupo <span class="text-danger">*</span></label>
        <input type="text" name="area" class="form-control" required maxlength="120" list="areasComunidad"
            value="{{ old('area', $miembro->area ?? '') }}" placeholder="Ej. Rectorado, DECE, Matemáticas, Inglés">
        <datalist id="areasComunidad">
            @foreach(['Rectorado', 'Vicerrectorado', 'Secretaría', 'Inspección', 'DECE', 'Pastoral', 'Matemáticas', 'Lengua y Literatura', 'Ciencias Naturales', 'Inglés', 'Estudiantes', 'Representantes'] as $areaSugerida)
            <option value="{{ $areaSugerida }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="col-md-3">
        <label class="form-label">Orden</label>
        <input type="number" name="orden" class="form-control" min="0" max="999"
            value="{{ old('orden', $miembro->orden ?? 0) }}">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="activo" id="activo{{ $miembro->id ?? 'nuevo' }}"
                value="1" @checked(old('activo', $miembro->activo ?? true))>
            <label class="form-check-label" for="activo{{ $miembro->id ?? 'nuevo' }}">Visible en el portal</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Responsabilidad</label>
        <textarea name="responsabilidad" class="form-control" rows="3" maxlength="2000"
            placeholder="Describa brevemente su rol dentro de la comunidad educativa">{{ old('responsabilidad', $miembro->responsabilidad ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Fotografía</label>
        <div class="d-flex align-items-center gap-3">
            @if($miembro && $miembro->fotoUrl())
            <img src="{{ $miembro->fotoUrl() }}" alt="{{ $miembro->nombre }}"
                class="rounded-circle border" style="width:56px;height:56px;object-fit:cover;">
            @endif
            <input type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp">
        </div>
        <div class="form-text">PNG, JPG o WEBP. Máximo 4 MB.</div>
        @if($miembro && $miembro->fotoUrl())
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="quitar_foto" id="quitar_foto{{ $miembro->id }}"
                value="1" @checked(old('quitar_foto'))>
            <label class="form-check-label" for="quitar_foto{{ $miembro->id }}">Quitar la fotografía actual</label>
        </div>
        @endif
    </div>
</div>
