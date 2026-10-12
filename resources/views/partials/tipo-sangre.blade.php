@php
    $valorActual = old($name, $value ?? null);
    $tipos = \App\Models\Alumno::TIPOS_SANGRE;
@endphp
<select name="{{ $name }}" id="{{ $name }}" class="form-select @error($name) is-invalid @enderror">
    <option value="">— sin definir —</option>
    @foreach ($tipos as $tipo)
        <option value="{{ $tipo }}" @selected($valorActual === $tipo)>{{ $tipo }}</option>
    @endforeach
    @if ($valorActual !== null && $valorActual !== '' && ! in_array($valorActual, $tipos, true))
        <option value="{{ $valorActual }}" selected>{{ $valorActual }} (valor anterior)</option>
    @endif
</select>
@error($name)
    <small class="text-danger d-block">{{ $message }}</small>
@enderror
