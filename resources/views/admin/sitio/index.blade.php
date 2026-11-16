@extends('layouts.principal')

@section('titulo', 'Textos e imágenes')

{{--
    Textos e imágenes del portal.

    Qué hace: edita los campos de cada grupo del portal público (identidad,
    inicio, historia, comunidad, útiles, descargas y admisiones). Es una vista
    independiente, sin pestañas.
    Conecta con: Admin\SitioController, PortalContenido y SiteContent.
    Ruta: GET /admin/sitio (admin.sitio.index).
--}}
@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-1"><i class="bi bi-fonts me-2"></i>Textos e imágenes</h4>
        <p class="text-muted small mb-0">
            Todo lo que aparece en el portal público: textos, imágenes y enlaces de cada sección.
        </p>
    </div>

    @include('admin.sitio._avisos')

    @foreach($grupos as $claveGrupo => $grupo)
    @continue($claveGrupo === 'preregistro')
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi {{ $grupo['icono'] }} me-1"></i>{{ $grupo['titulo'] }}</h6>
            <small class="text-muted">{{ $grupo['descripcion'] }}</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sitio.contenido', $claveGrupo) }}"
                  enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    @foreach(collect($campos)->filter(fn ($campo) => $campo['grupo'] === $claveGrupo) as $clave => $campo)
                    @php($valor = \App\Support\PortalContenido::valor($clave))

                    @if($campo['tipo'] === 'imagen')
                    @php($urlImagen = \App\Support\PortalContenido::imagen($clave))
                    <div class="col-md-6">
                        <label class="form-label">{{ $campo['etiqueta'] }}</label>
                        @if($urlImagen)
                        <div class="mb-2">
                            <img src="{{ $urlImagen }}" alt="{{ $campo['etiqueta'] }}"
                                 class="img-fluid rounded border" style="max-height:140px;object-fit:cover;">
                        </div>
                        @endif
                        <input type="file" name="imagenes[{{ $clave }}]" class="form-control"
                               accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        <input type="text" name="campos[{{ $clave }}]" class="form-control mt-2"
                               value="{{ $valor }}" placeholder="O escriba una URL externa">
                        @if(!empty($campo['ayuda']))
                        <div class="form-text">{{ $campo['ayuda'] }}</div>
                        @endif
                        @if($valor)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="quitar[{{ $clave }}]"
                                   id="quitar_{{ $loop->index }}_{{ $claveGrupo }}" value="1">
                            <label class="form-check-label" for="quitar_{{ $loop->index }}_{{ $claveGrupo }}">
                                Quitar la imagen actual
                            </label>
                        </div>
                        @endif
                    </div>

                    @elseif($campo['tipo'] === 'booleano')
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="campos[{{ $clave }}]"
                                   id="campo_{{ $claveGrupo }}_{{ $loop->index }}" value="1"
                                   @checked(\App\Models\SiteContent::bool($clave, (bool) ($campo['por_defecto'] ?? false)))>
                            <label class="form-check-label" for="campo_{{ $claveGrupo }}_{{ $loop->index }}">
                                {{ $campo['etiqueta'] }}
                            </label>
                        </div>
                    </div>

                    @elseif($campo['tipo'] === 'largo')
                    <div class="col-12">
                        <label class="form-label">{{ $campo['etiqueta'] }}</label>
                        <textarea name="campos[{{ $clave }}]" class="form-control" rows="3"
                                  maxlength="10000">{{ $valor }}</textarea>
                    </div>

                    @else
                    <div class="col-md-6">
                        <label class="form-label">{{ $campo['etiqueta'] }}</label>
                        <input type="text" name="campos[{{ $clave }}]" class="form-control"
                               maxlength="10000" value="{{ $valor }}">
                    </div>
                    @endif
                    @endforeach
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Guardar {{ strtolower($grupo['titulo']) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection
