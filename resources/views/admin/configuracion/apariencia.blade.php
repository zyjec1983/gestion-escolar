@extends('layouts.principal')

@section('titulo', 'Apariencia')

{{--
    Apariencia global del sistema.

    Qué hace: elige una de las 10 paletas del menú lateral y el tamaño de fuente
    tipo Word. El cambio se aplica a todos los roles y es una vista
    independiente, sin pestañas.
    Conecta con: Admin\ConfiguracionController@updateApariencia, App\Support\Tema
    y layouts/app.blade.php.
    Rutas: GET /admin/configuracion/apariencia y
            POST /admin/configuracion/apariencia.
--}}
@push('styles')
<style>
    .paleta-card { cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .paleta-card:hover { transform: translateY(-2px); box-shadow: 0 .35rem .8rem rgba(0,0,0,.12); }
    .paleta-card input:checked + .paleta-muestra { outline: 3px solid #0d6efd; outline-offset: 2px; }
    .paleta-muestra { display: flex; height: 54px; border-radius: .5rem; overflow: hidden; border: 1px solid #dee2e6; }
    .paleta-muestra span { flex: 1; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; }
    .apariencia-preview { border: 1px solid #dee2e6; border-radius: .5rem; overflow: hidden; }
    .apariencia-preview .preview-aside { background: var(--aside-bg); color: #fff; padding: .75rem; }
    .apariencia-preview .preview-activo { background: var(--aside-active); color: #fff; font-weight: 700; padding: .35rem .5rem; border-radius: .35rem; }
    .apariencia-preview .preview-main { background: #fff; color: #000; padding: .75rem; }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-palette me-2"></i>Apariencia</h4>
        <p class="text-muted small mb-0">
            Color del menú lateral y tamaño de fuente para todos los roles.
        </p>
    </div>

    @include('admin.configuracion._mensajes')

    <form method="POST" action="{{ route('admin.configuracion.apariencia') }}">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-palette me-1"></i>Color del menú lateral</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Elige una de las 10 combinaciones. El cambio se aplica a todos los roles
                            (alumnos, representantes, docentes, inspectores, DECE, secretaría, rectorado,
                            vicerrectorado y administración). Las letras del menú son blancas y se
                            muestran en negrilla al pasar el cursor o al seleccionar una opción.
                        </p>

                        <div class="row g-3">
                            @foreach($paletas as $clave => $paleta)
                            <div class="col-sm-6 col-xl-4">
                                <label class="paleta-card d-block mb-0">
                                    <input type="radio" name="tema" value="{{ $clave }}"
                                           class="d-none" {{ $tema['clave'] === $clave ? 'checked' : '' }}
                                           data-aside="{{ $paleta['aside'] }}"
                                           data-activo="{{ $paleta['activo'] }}"
                                           data-hover="{{ $paleta['hover'] }}">
                                    <div class="paleta-muestra" title="{{ $paleta['nombre'] }}">
                                        <span style="background: {{ $paleta['aside'] }};">Menú</span>
                                        <span style="background: {{ $paleta['activo'] }};">Activo</span>
                                    </div>
                                    <div class="small fw-semibold mt-1">{{ $paleta['nombre'] }}</div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('tema')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror

                        <hr class="my-4">

                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="fuente_pt" class="form-label fw-semibold">Tamaño de fuente</label>
                                <select name="fuente_pt" id="fuente_pt" class="form-select">
                                    @foreach($fuentes as $valor => $etiqueta)
                                        <option value="{{ $valor }}" {{ $tema['fuente_pt'] === (string) $valor ? 'selected' : '' }}>
                                            {{ $etiqueta }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    Igual que en Microsoft Word: 11 pt es el tamaño predeterminado.
                                </div>
                                @error('fuente_pt')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-lg me-1"></i>Guardar apariencia
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-eye me-1"></i>Vista previa</h6>
                    </div>
                    <div class="card-body">
                        <div class="apariencia-preview" id="aparienciaPreview">
                            <div class="preview-aside">
                                <div class="fw-bold mb-2"><i class="bi bi-mortarboard-fill me-1"></i>{{ $institucion->nombre }}</div>
                                <div class="preview-activo mb-1">Dashboard</div>
                                <div class="small">Mensajes</div>
                                <div class="small">Configuración</div>
                            </div>
                            <div class="preview-main">
                                <div class="fw-semibold mb-1">Contenido principal</div>
                                <p class="small mb-2">Texto y formularios con letra negra para una lectura clara.</p>
                                <input type="text" class="form-control form-control-sm" value="Ejemplo de formulario" readonly>
                            </div>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            La apariencia se guarda en la configuración global y se mantiene al
                            iniciar sesión con cualquier rol.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Vista previa inmediata del aside y del tamaño de fuente seleccionados.
        var preview = document.getElementById('aparienciaPreview');
        var fuente = document.getElementById('fuente_pt');

        function pintarPreview() {
            if (!preview) return;
            var elegida = document.querySelector('input[name="tema"]:checked');
            if (elegida) {
                preview.style.setProperty('--aside-bg', elegida.dataset.aside);
                preview.style.setProperty('--aside-active', elegida.dataset.activo);
                preview.style.setProperty('--aside-hover', elegida.dataset.hover);
            }
            if (fuente) {
                preview.style.fontSize = fuente.value + 'pt';
            }
        }

        document.querySelectorAll('input[name="tema"]').forEach(function (radio) {
            radio.addEventListener('change', pintarPreview);
        });
        if (fuente) fuente.addEventListener('change', pintarPreview);
        pintarPreview();
    });
</script>
@endpush
