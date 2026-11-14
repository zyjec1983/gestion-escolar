{{--
    Portada del portal institucional (pestaña Home).

    Qué hace: muestra el carrusel administrable, la franja de admisiones, la
    identidad institucional (misión, visión y valores), los accesos a cada
    pestaña del portal, el proceso de admisión y las circulares recientes.
    Conecta con: PortalController@index, HeroSlide, Circular,
    App\Support\PortalContenido y portal/layout.blade.php.
    Ruta: GET / (welcome). Las demás pestañas tienen su propia vista en
    resources/views/portal/.
--}}
@extends('portal.layout')

@section('titulo', $nombreInstitucion)

@section('contenido')

    {{-- ===================== CARRUSEL PRINCIPAL ===================== --}}
    <section id="home">
        <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel"
             data-bs-interval="{{ $intervalo }}">
            @if($slides->count() > 0)
            <div class="carousel-indicators">
                @foreach($slides as $i => $slide)
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $i }}"
                        class="{{ $i === 0 ? 'active' : '' }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                        aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
            @endif

            <div class="carousel-inner">
                @forelse($slides as $i => $slide)
                @php($fondo = $slide->imagen_path ? \Illuminate\Support\Facades\Storage::url($slide->imagen_path) : $imagen('historia.imagen_principal'))
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    <div class="hero-slide-bg" style="background-image: url('{{ $fondo }}');">
                        <div class="hero-overlay"></div>
                        <div class="container h-100 position-relative d-flex align-items-center">
                            <div class="col-lg-8 text-white py-5">
                                @if($slide->etiqueta)
                                <span class="badge bg-warning text-dark fw-semibold px-3 py-2 rounded-pill mb-3">
                                    <i class="bi bi-stars me-1"></i> {{ $slide->etiqueta }}
                                </span>
                                @endif
                                @if($slide->titulo)
                                <h1 class="display-4 fw-bold mb-3">{{ $slide->titulo }}</h1>
                                @endif
                                @if($slide->subtitulo)
                                <p class="lead text-white-50 mb-4">{{ $slide->subtitulo }}</p>
                                @endif
                                <div class="d-flex flex-wrap gap-3">
                                    @if($institucion?->pre_registro_activo)
                                    <a href="{{ url('/pre-registro') }}" class="btn btn-gold btn-lg rounded-pill px-4">
                                        <i class="bi bi-pencil-square me-2"></i>Solicitar Matrícula
                                    </a>
                                    @endif
                                    <a href="{{ route('portal.historia') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">
                                        Conocer Nuestra Historia
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="carousel-item active">
                    <div class="hero-slide-bg" style="background-image: url('{{ $imagen('historia.imagen_principal') }}');">
                        <div class="hero-overlay"></div>
                        <div class="container h-100 position-relative d-flex align-items-center">
                            <div class="col-lg-8 text-white py-5">
                                <span class="badge bg-warning text-dark fw-semibold px-3 py-2 rounded-pill mb-3">
                                    <i class="bi bi-stars me-1"></i> {{ $texto('institucion.periodo_lectivo') }}
                                </span>
                                <h1 class="display-4 fw-bold mb-3">{{ $nombreInstitucion }}</h1>
                                <p class="lead text-white-50 mb-4">{{ $texto('institucion.eslogan') }}</p>
                                <div class="d-flex flex-wrap gap-3">
                                    @if($institucion?->pre_registro_activo)
                                    <a href="{{ url('/pre-registro') }}" class="btn btn-gold btn-lg rounded-pill px-4">
                                        <i class="bi bi-pencil-square me-2"></i>Solicitar Matrícula
                                    </a>
                                    @endif
                                    <a href="{{ route('portal.historia') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">
                                        Conocer Nuestra Historia
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>

            @if($slides->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
            @endif
        </div>

        {{-- Franja de admisiones --}}
        <section class="py-4 bg-navy text-white border-top border-warning border-3">
            <div class="container">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8 text-center text-lg-start">
                        <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill mb-2">
                            @if($institucion?->pre_registro_activo)
                            <span class="badge bg-success rounded-pill">ACTIVO</span>
                            @else
                            <span class="badge bg-secondary rounded-pill">CERRADO</span>
                            @endif
                            <small class="fw-semibold text-warning">{{ $texto('institucion.periodo_lectivo') }}</small>
                        </div>
                        <h4 class="fw-bold mb-0">¿Deseas formar parte de nuestra Familia Franciscana?</h4>
                    </div>
                    <div class="col-lg-4 text-center text-lg-end">
                        @if($institucion?->pre_registro_activo)
                        <a href="{{ url('/pre-registro') }}" class="btn btn-gold btn-lg rounded-pill px-5 shadow">
                            <i class="bi bi-pencil-square me-2"></i>Solicitar Matrícula
                        </a>
                        @else
                        <button class="btn btn-secondary btn-lg rounded-pill px-5" disabled>
                            <i class="bi bi-clock me-2"></i>Inscripciones cerradas
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Misión, visión y valores --}}
        <section class="py-5 bg-cream">
            <div class="container py-3">
                <div class="text-center mx-auto mb-5" style="max-width: 720px;">
                    <span class="section-kicker">Identidad Institucional</span>
                    <h2 class="fw-bold text-navy display-6 mt-1">Horizonte Pedagógico y Pastoral</h2>
                    <div class="mx-auto mt-2 bg-warning rounded-pill" style="width:70px;height:4px;"></div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100 p-4 card-hover border-top border-4 border-navy">
                            <div class="card-body d-flex flex-column">
                                <div class="icon-box bg-navy text-warning mb-4"><i class="bi bi-compass"></i></div>
                                <h4 class="fw-bold text-navy mb-3">Misión</h4>
                                <p class="text-muted mb-0 lh-lg" style="font-size:.94rem;">{{ $texto('institucion.mision') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100 p-4 card-hover border-top border-4 border-gold">
                            <div class="card-body d-flex flex-column">
                                <div class="icon-box bg-warning bg-opacity-15 text-gold mb-4"><i class="bi bi-stars"></i></div>
                                <h4 class="fw-bold text-navy mb-3">Visión</h4>
                                <p class="text-muted mb-0 lh-lg" style="font-size:.94rem;">{{ $texto('institucion.vision') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100 p-4 card-hover border-top border-4 border-navy">
                            <div class="card-body d-flex flex-column">
                                <div class="icon-box bg-navy text-warning mb-4"><i class="bi bi-heart-fill"></i></div>
                                <h4 class="fw-bold text-navy mb-3">Valores Franciscanos</h4>
                                <p class="text-muted mb-3 lh-lg" style="font-size:.94rem;">{{ $texto('institucion.valores') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Accesos a las pestañas del portal --}}
        <section class="py-5 bg-white">
            <div class="container py-3">
                <div class="text-center mx-auto mb-5" style="max-width: 720px;">
                    <span class="section-kicker">Explora el portal</span>
                    <h2 class="fw-bold text-navy display-6 mt-1">Todo en su propia sección</h2>
                    <p class="text-muted mb-0">Cada pestaña del portal tiene su página con la información completa.</p>
                </div>

                <div class="row g-4">
                    @foreach([
                        ['ruta' => 'portal.historia', 'icono' => 'bi-book-half', 'titulo' => 'Nuestra historia', 'desc' => 'Raíces franciscanas, lema fundacional e hitos históricos.'],
                        ['ruta' => 'portal.comunidad', 'icono' => 'bi-people-fill', 'titulo' => 'Comunidad educativa', 'desc' => 'Autoridades, docentes y personal agrupados por área.'],
                        ['ruta' => 'portal.utiles', 'icono' => 'bi-backpack2', 'titulo' => 'Lista de útiles', 'desc' => 'Materiales por nivel: inicial, básica media y superior.'],
                        ['ruta' => 'portal.descargas', 'icono' => 'bi-cloud-arrow-down-fill', 'titulo' => 'Centro de descargas', 'desc' => 'Horarios, uniformes, convivencia y documentos oficiales.'],
                        ['ruta' => 'portal.circulares', 'icono' => 'bi-megaphone', 'titulo' => 'Circulares', 'desc' => 'Comunicación oficial de la institución para las familias.'],
                    ] as $acceso)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route($acceso['ruta']) }}" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 p-4 card-hover">
                                <div class="icon-box bg-navy text-warning mb-3"><i class="bi {{ $acceso['icono'] }}"></i></div>
                                <h5 class="fw-bold text-navy">{{ $acceso['titulo'] }}</h5>
                                <p class="text-muted small mb-0">{{ $acceso['desc'] }}</p>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Proceso de admisiones --}}
        <section class="py-5 bg-cream">
            <div class="container py-3">
                <div class="text-center mb-5">
                    <span class="section-kicker">Admisiones en Línea</span>
                    <h2 class="fw-bold text-navy display-6 mt-1">¿Cómo funciona el proceso?</h2>
                    <p class="text-muted">Sigue estos tres sencillos pasos para solicitar un cupo en nuestra institución.</p>
                </div>
                <div class="row g-4">
                    @foreach([1, 2, 3] as $paso)
                    <div class="col-md-4">
                        <div class="card border-0 bg-white h-100 p-4 text-center card-hover">
                            <div class="rounded-circle bg-navy text-warning d-inline-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm"
                                 style="width:64px;height:64px;">
                                <span class="fs-4 fw-bold">{{ $paso }}</span>
                            </div>
                            <h5 class="fw-bold text-navy">{{ $texto("admisiones.paso{$paso}_titulo") }}</h5>
                            <p class="text-muted small mb-0">{{ $texto("admisiones.paso{$paso}_desc") }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="text-center mt-5">
                    @if($institucion?->pre_registro_activo)
                    <a href="{{ url('/pre-registro') }}" class="btn btn-navy btn-lg rounded-pill px-5 shadow-sm">
                        <i class="bi bi-arrow-right-circle me-2 text-warning"></i>Comenzar Solicitud
                    </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- Circulares recientes (resumen; el listado completo tiene su página) --}}
        <section class="py-5 bg-white">
            <div class="container py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
                    <div>
                        <span class="section-kicker">Comunicación oficial</span>
                        <h2 class="fw-bold text-navy display-6 mt-1 mb-0">Circulares recientes</h2>
                    </div>
                    <a href="{{ route('portal.circulares') }}" class="btn btn-outline-dark rounded-pill px-4">
                        <i class="bi bi-megaphone me-1"></i>Ver todas las circulares
                    </a>
                </div>

                @if($circulares->count() > 0)
                <div class="row g-4">
                    @foreach($circulares as $circular)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100 card-hover">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-navy">{{ $circular->numero ?: 'Circular' }}</span>
                                    <small class="text-muted">{{ $circular->fecha_publicacion?->format('d/m/Y') }}</small>
                                </div>
                                <h6 class="fw-bold text-navy">{{ $circular->titulo }}</h6>
                                @if($circular->descripcion ?? null)
                                <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($circular->descripcion, 120) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-megaphone display-1 d-block mb-3 opacity-25"></i>
                    <p class="mb-0">No hay circulares publicadas por el momento.</p>
                </div>
                @endif
            </div>
        </section>
    </section>

@endsection
