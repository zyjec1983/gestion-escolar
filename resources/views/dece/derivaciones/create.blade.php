@extends('layouts.principal')

@section('titulo', 'Nueva derivación')

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-arrow-up-right-circle me-2"></i>Nueva derivación</h4>
        <small class="text-muted">
            @if(in_array(auth()->user()->rol, ['DECE', 'ADMIN'], true))
                Puede remitir el caso internamente o a una entidad de la red de protección.
            @else
                Remita el caso al DECE para que el equipo de consejería lo atienda.
            @endif
        </small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.derivaciones.store') }}" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    @include('dece.partials.buscador-alumno', [
                        'alumnoSeleccionado' => $alumnoSeleccionado,
                        'idBuscador' => 'buscadorAlumnoDerivacion',
                    ])
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" id="tipo" class="form-select" required>
                        @foreach($tipos as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('tipo') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha de derivación <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_derivacion" value="{{ old('fecha_derivacion', now()->toDateString()) }}" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <input type="text" name="motivo" value="{{ old('motivo') }}" class="form-control" maxlength="150" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Descripción del caso <span class="text-danger">*</span></label>
                    <textarea name="descripcion" rows="5" class="form-control" required minlength="20">{{ old('descripcion') }}</textarea>
                </div>

                <div class="col-md-5 campo-externo d-none">
                    <label class="form-label">Entidad de destino</label>
                    <input type="text" name="entidad_externa" value="{{ old('entidad_externa') }}" class="form-control" maxlength="255"
                           placeholder="MSP, DINAPEN, Junta de Protección…">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Oficio</label>
                    <input type="text" name="oficio" value="{{ old('oficio') }}" class="form-control" maxlength="100">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha límite</label>
                    <input type="date" name="fecha_limite" value="{{ old('fecha_limite') }}" class="form-control">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-end gap-2">
            <div class="form-check me-auto">
                <input type="checkbox" name="imprimir" value="1" id="imprimir" class="form-check-input" @checked(old('imprimir'))>
                <label for="imprimir" class="form-check-label">
                    <i class="bi bi-printer me-1"></i>Registrar e imprimir
                </label>
            </div>
            <a href="{{ route('dece.derivaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Registrar derivación</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const tipo = document.getElementById('tipo');
        const externo = document.querySelector('.campo-externo');
        const alternar = () => externo.classList.toggle('d-none', tipo.value !== 'externa');
        tipo.addEventListener('change', alternar);
        alternar();
    })();
</script>
@endpush
