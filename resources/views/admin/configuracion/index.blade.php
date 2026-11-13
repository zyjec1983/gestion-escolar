@extends('layouts.principal')

@section('titulo', 'Configuración')

@php
    // Valores por defecto para que la vista también pueda renderizarse aislada
    // (pruebas o vistas previas). En producción llegan desde ConfiguracionController.
    $tema = $tema ?? \App\Support\Tema::actual();
    $paletas = $paletas ?? \App\Support\Tema::paletas();
    $fuentes = $fuentes ?? \App\Support\Tema::fuentes();
@endphp

@push('styles')
<style>
    .config-tab .nav-link { color: #6c757d; font-weight: 600; border: 0; border-bottom: 2px solid transparent; }
    .config-tab .nav-link.active { color: #4361ee; border-bottom-color: #4361ee; background: transparent; }
    .slide-thumb { width: 100px; height: 60px; object-fit: cover; }
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-gear me-2"></i>Configuración del centro</h4>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <ul class="nav nav-tabs config-tab mb-4" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general" data-bs-toggle="tab" data-bs-target="#panel-general"
                    type="button" role="tab" aria-controls="panel-general" aria-selected="true">
                <i class="bi bi-sliders me-1"></i>General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-carrusel" data-bs-toggle="tab" data-bs-target="#panel-carrusel"
                    type="button" role="tab" aria-controls="panel-carrusel" aria-selected="false">
                <i class="bi bi-images me-1"></i>Carrusel del portal
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-apariencia" data-bs-toggle="tab" data-bs-target="#panel-apariencia"
                    type="button" role="tab" aria-controls="panel-apariencia" aria-selected="false">
                <i class="bi bi-palette me-1"></i>Apariencia
            </button>
        </li>
    </ul>

    <div class="tab-content" id="configTabsContent">
        {{-- ============ PESTAÑA GENERAL ============ --}}
        <div class="tab-pane fade show active" id="panel-general" role="tabpanel" aria-labelledby="tab-general" tabindex="0">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Zona horaria</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                Define la hora con la que la institución registra sus fechas: envíos de mensajes,
                                consultas y demás registros con hora. Si el programa se instala en otro país,
                                basta con elegir la zona de ese lugar.
                            </p>

                            <form method="POST" action="{{ route('admin.configuracion.zona-horaria') }}">
                                @csrf
                                <div class="mb-2">
                                    <label for="zona_horaria" class="form-label fw-semibold">Desfase respecto a UTC</label>
                                    <select name="zona_horaria" id="zona_horaria" class="form-select">
                                        @foreach($desfases as $desfase)
                                            @php($valor = valorDesfaseHorario($desfase['minutos']))
                                            <option value="{{ $valor }}" {{ $institucion->zona_horaria === $valor ? 'selected' : '' }}>
                                                {{ formatearDesfase($desfase['minutos']) }} — {{ $desfase['ciudades'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('zona_horaria')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <p class="text-muted small mb-3">
                                    Elige las horas que lleva tu centro respecto a UTC. Son 23 desfases, de
                                    UTC-11:00 a UTC+11:00. Si no hay horario de verano donde está el centro,
                                    el valor sirve todo el año igual.
                                </p>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Guardar zona horaria
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Estado actual</h6>
                        </div>
                        <div class="card-body small">
                            <dl class="row mb-0">
                                <dt class="col-5">Desfase configurado</dt>
                                <dd class="col-7">{{ formatearDesfase(minutosDesfaseZona($institucion->zona_horaria)) }}</dd>

                                <dt class="col-5">Hora actual del centro</dt>
                                <dd class="col-7 mb-0">{{ now()->format('d/m/Y H:i:s') }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-sliders me-1"></i>Registro</h6>
                        </div>
                        <div class="card-body small">
                            <p class="mb-0 text-muted">
                                Sistema de evaluación:
                                <strong>{{ $institucion->sistema_evaluacion === 'trimestre' ? 'Trimestre' : 'Quimestre' }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ PESTAÑA CARRUSEL DEL PORTAL ============ --}}
        <div class="tab-pane fade" id="panel-carrusel" role="tabpanel" aria-labelledby="tab-carrusel" tabindex="0">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-speedometer2 me-1"></i>Configuración del carrusel</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.hero-slides.velocidad') }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-auto">
                            <label for="carousel_interval" class="form-label">Velocidad de transición</label>
                            <div class="input-group">
                                <input type="number" name="carousel_interval" id="carousel_interval"
                                    class="form-control" value="{{ old('carousel_interval', $institucion->carousel_interval ?? 2000) }}"
                                    min="1000" max="30000" step="500">
                                <span class="input-group-text">ms</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-check-lg me-1"></i>Actualizar
                            </button>
                        </div>
                        <div class="col-auto">
                            <small class="text-muted">1 segundo = 1000 ms. Mínimo 1000, máximo 30000.</small>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-images me-1"></i>Slides ({{ $slides->count() }})</h6>
                    <a href="{{ route('admin.hero-slides.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo Slide
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($slides->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px;">Orden</th>
                                    <th style="width:120px;">Vista previa</th>
                                    <th>Título</th>
                                    <th>Estado</th>
                                    <th style="width:160px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($slides as $slide)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $slide->orden }}</span>
                                    </td>
                                    <td>
                                        @if($slide->imagen_path)
                                        <img src="{{ Storage::url($slide->imagen_path) }}" alt="{{ $slide->titulo }}"
                                            class="slide-thumb rounded">
                                        @else
                                        <span class="text-muted small">Sin imagen</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $slide->titulo ?? 'Sin título' }}</strong>
                                        @if($slide->subtitulo)
                                        <br><small class="text-muted">{{ $slide->subtitulo }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($slide->activo)
                                        <span class="badge bg-success">Activo</span>
                                        @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.hero-slides.edit', $slide->id) }}"
                                            class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide->id) }}"
                                            class="d-inline" onsubmit="return confirm('¿Eliminar este slide?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-images display-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No hay slides. Crea el primero.</p>
                        <a href="{{ route('admin.hero-slides.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo Slide
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============ PESTAÑA APARIENCIA ============ --}}
        {{-- Paletas y tamaño de fuente globales. Ruta: POST /admin/configuracion/apariencia --}}
        <div class="tab-pane fade" id="panel-apariencia" role="tabpanel" aria-labelledby="tab-apariencia" tabindex="0">
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
                                        <div class="fw-bold mb-2"><i class="bi bi-mortarboard-fill me-1"></i>Unidad Educativa</div>
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
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Abre la pestaña correcta cuando se llega desde un ancla (#panel-...).
        ['carrusel', 'apariencia'].forEach(function (nombre) {
            if (window.location.hash === '#panel-' + nombre) {
                var tab = document.getElementById('tab-' + nombre);
                if (tab) new bootstrap.Tab(tab).show();
            }
        });

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
@endsection
