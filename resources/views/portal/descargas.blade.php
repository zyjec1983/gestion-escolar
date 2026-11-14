{{--
    Página "Centro de descargas" del portal.

    Qué hace: muestra los documentos oficiales agrupados por categoría
    (horarios, uniformes, convivencia, etc.) con su descripción, peso y enlace
    de descarga. Los archivos se administran desde Administración -> Sitio web
    -> Descargas.
    Conecta con: PortalController@descargas, DescargaDocumento,
    App\Support\PortalContenido y portal/layout.blade.php.
    Ruta: GET /descargas (portal.descargas).
--}}
@extends('portal.layout')

@section('titulo', 'Centro de Descargas')

@section('contenido')
    @include('portal._page-header', [
        'categoria' => 'Servicios',
        'pagina' => 'Centro de Descargas',
        'titulo' => $texto('descargas.titulo'),
        'subtitulo' => $texto('descargas.subtitulo'),
    ])

    <div class="container py-5">
        @if($descargas->count() > 0)
        <ul class="nav nav-pills gap-2 mb-4 justify-content-center justify-content-md-start flex-wrap" role="tablist">
            @foreach(\App\Models\DescargaDocumento::CATEGORIAS as $clave => $etiqueta)
            @if($descargas->has($clave))
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4 {{ $loop->first ? 'active' : '' }}"
                        data-bs-toggle="pill" data-bs-target="#descarga-{{ $clave }}" type="button" role="tab">
                    {{ $etiqueta }}
                </button>
            </li>
            @endif
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach(\App\Models\DescargaDocumento::CATEGORIAS as $clave => $etiqueta)
            @if($descargas->has($clave))
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="descarga-{{ $clave }}" role="tabpanel">
                <div class="row g-4">
                    @foreach($descargas[$clave] as $documento)
                    <div class="col-md-6 col-lg-4">
                        <div class="descarga-card h-100 p-4 d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <span class="icon-box bg-cream text-navy" style="width:48px;height:48px;font-size:1.2rem;">
                                    <i class="bi bi-file-earmark-text"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold text-navy mb-1">{{ $documento->titulo }}</h6>
                                    <small class="text-muted">
                                        {{ $documento->fecha_texto ?: '' }}
                                        {{ $documento->peso ? '· '.$documento->peso : '' }}
                                    </small>
                                </div>
                            </div>
                            @if($documento->descripcion)
                            <p class="text-muted small flex-grow-1">{{ $documento->descripcion }}</p>
                            @endif
                            @if($documento->archivoUrl())
                            <a href="{{ $documento->archivoUrl() }}" target="_blank" rel="noopener"
                               class="btn btn-navy btn-sm rounded-pill mt-2">
                                <i class="bi bi-download me-1"></i>Descargar
                            </a>
                            @else
                            <span class="badge bg-light text-muted border align-self-start">Sin archivo adjunto</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-cloud-arrow-down display-1 d-block mb-3 opacity-25"></i>
            <p class="mb-0">Los documentos oficiales se publicarán próximamente.</p>
        </div>
        @endif
    </div>
@endsection
