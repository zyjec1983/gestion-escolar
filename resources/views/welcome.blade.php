{{--
    Portal institucional público.

    Qué hace: fusiona la portada del sistema con la plantilla
    "portal_institucional_franciscano_laravel_cms_ready.html". Todos los textos
    e imágenes se leen de PortalContenido (CMS clave/valor), el carrusel de
    HeroSlide, la historia de HistoriaHito, la comunidad de ComunidadMiembro,
    los útiles de UtilItem, las descargas de DescargaDocumento y las circulares
    de Circular.
    Conecta con: PortalController@index, App\Support\PortalContenido,
    App\Models\{HeroSlide, HistoriaHito, ComunidadMiembro, UtilItem,
    DescargaDocumento, Circular} y los módulos del panel de administración.
    Rutas: GET / (welcome), GET /login y GET /pre-registro.
--}}
@php
    $nombreInstitucion = $institucion->nombre ?? \App\Support\PortalContenido::valor('institucion.nombre_corto');
    $eslogan = \App\Support\PortalContenido::valor('institucion.eslogan');
    $logoUrl = !empty($institucion?->logo_path) ? $institucion->logoUrl() : null;
    $intervalo = $institucion?->carousel_interval ?? 4500;
    $texto = fn (string $clave): string => \App\Support\PortalContenido::valor($clave);
    $imagen = fn (string $clave): string => \App\Support\PortalContenido::imagen($clave);
    $anioActual = date('Y');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $nombreInstitucion }} · {{ $eslogan }}</title>
    @if($logoUrl)
    <link rel="icon" href="{{ $logoUrl }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --franciscan-navy: #0f2942;
            --franciscan-navy-light: #163a5f;
            --franciscan-gold: #d97706;
            --franciscan-amber: #f59e0b;
            --franciscan-cream: #faf8f5;
            --franciscan-slate: #f1f5f9;
            --text-dark: #1e293b;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: #ffffff;
            overflow-x: hidden;
        }
        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .bg-navy { background-color: var(--franciscan-navy) !important; }
        .bg-navy-light { background-color: var(--franciscan-navy-light) !important; }
        .bg-cream { background-color: var(--franciscan-cream) !important; }
        .text-navy { color: var(--franciscan-navy) !important; }
        .text-gold { color: var(--franciscan-gold) !important; }
        .border-gold { border-color: var(--franciscan-gold) !important; }

        .btn-gold {
            background: linear-gradient(135deg, var(--franciscan-gold) 0%, var(--franciscan-amber) 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #b45309 0%, var(--franciscan-gold) 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.3);
        }
        .btn-navy {
            background-color: var(--franciscan-navy);
            border-color: var(--franciscan-navy);
            color: #fff;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .btn-navy:hover {
            background-color: var(--franciscan-navy-light);
            border-color: var(--franciscan-navy-light);
            color: #fff;
            transform: translateY(-1px);
        }

        .navbar-franciscan {
            background-color: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(15, 41, 66, 0.08);
            transition: all 0.3s ease;
        }
        .navbar-franciscan .nav-link {
            color: var(--franciscan-navy);
            font-weight: 600;
            font-size: 0.94rem;
            padding: 0.65rem 1rem !important;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .navbar-franciscan .nav-link:hover,
        .navbar-franciscan .nav-link.active {
            color: var(--franciscan-gold);
            background-color: rgba(217, 119, 6, 0.07);
        }
        .dropdown-menu {
            border: 1px solid rgba(15, 41, 66, 0.08);
            border-radius: 0.75rem;
            box-shadow: 0 15px 35px rgba(15, 41, 66, 0.12);
            padding: 0.5rem;
        }
        .dropdown-item {
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.55rem 0.9rem;
            color: var(--text-dark);
        }
        .dropdown-item:hover, .dropdown-item.active {
            background-color: rgba(15, 41, 66, 0.06);
            color: var(--franciscan-navy);
            font-weight: 600;
        }

        .hero-slide-bg {
            height: 68vh;
            min-height: 460px;
            max-height: 680px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(95deg, rgba(15, 41, 66, 0.90) 0%, rgba(15, 41, 66, 0.72) 55%, rgba(15, 41, 66, 0.35) 100%);
        }
        .card-hover {
            transition: transform 0.28s ease, box-shadow 0.28s ease;
            border-radius: 1rem;
        }
        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(15, 41, 66, 0.10) !important;
        }
        .icon-box {
            width: 64px;
            height: 64px;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .timeline-wrapper {
            position: relative;
            padding-left: 2rem;
            border-left: 3px solid rgba(217, 119, 6, 0.35);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2.25rem;
        }
        .timeline-dot {
            position: absolute;
            left: -2.62rem;
            top: 0.25rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--franciscan-gold);
            border: 4px solid #fff;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.25);
        }
        .miembro-foto {
            width: 88px;
            height: 88px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 8px 18px rgba(15, 41, 66, 0.18);
        }
        .nav-pills .nav-link {
            color: var(--franciscan-navy);
            font-weight: 600;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .nav-pills .nav-link.active {
            background-color: var(--franciscan-navy);
            border-color: var(--franciscan-navy);
            color: #fff;
        }
        .descarga-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .descarga-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(15, 41, 66, 0.10);
        }
        .section-kicker {
            color: var(--franciscan-gold);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .78rem;
        }
        .hover-white:hover { color: #fff !important; }
        footer a { transition: color .2s ease; }
    </style>
</head>
<body>

    {{-- Barra superior informativa --}}
    <div class="bg-navy text-white py-2 small d-none d-md-block border-bottom border-white border-opacity-10">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-peace text-warning me-1"></i> {{ $texto('institucion.lema_franciscano') }}</span>
                <span class="text-white-50">|</span>
                <span><i class="bi bi-geo-alt text-warning me-1"></i> {{ $texto('institucion.ciudad') }}</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-telephone me-1 text-warning"></i> {{ $texto('institucion.telefono') }}</span>
                <span><i class="bi bi-envelope me-1 text-warning"></i> {{ $texto('institucion.correo') }}</span>
            </div>
        </div>
    </div>

    {{-- Navbar institucional --}}
    <nav class="navbar navbar-expand-lg navbar-franciscan sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="#home">
                @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo {{ $nombreInstitucion }}"
                     class="rounded-3 bg-white shadow-sm p-1" style="width:44px;height:44px;object-fit:contain;">
                @else
                <div class="rounded-3 bg-navy text-warning d-flex align-items-center justify-content-center shadow-sm"
                     style="width:44px;height:44px;">
                    <svg width="26" height="26" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="M5 7H27V11.5H18.3V26H13.7V11.5H5V7Z" fill="currentColor"/>
                        <circle cx="16" cy="4" r="2" fill="#ffffff"/>
                    </svg>
                </div>
                @endif
                <div class="lh-sm">
                    <span class="d-block fw-bold text-navy font-heading" style="font-size:1.05rem;">{{ $nombreInstitucion }}</span>
                    <small class="text-muted d-block" style="font-size:.72rem;">{{ $eslogan }}</small>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home"><i class="bi bi-house-door me-1"></i>Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navNosotros" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Nosotros</a>
                        <ul class="dropdown-menu" aria-labelledby="navNosotros">
                            <li>
                                <a class="dropdown-item" href="#historia">
                                    <i class="bi bi-book-half text-gold me-2"></i>Nuestra historia
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#comunidad">
                                    <i class="bi bi-people-fill text-navy me-2"></i>Comunidad educativa
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navServicios" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Servicios</a>
                        <ul class="dropdown-menu" aria-labelledby="navServicios">
                            <li>
                                <a class="dropdown-item" href="#utiles">
                                    <i class="bi bi-backpack2 text-gold me-2"></i>Lista de útiles
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item" href="#descargas">
                                    <i class="bi bi-cloud-arrow-down-fill text-navy me-2"></i>Descargas
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#circulares">
                                    <i class="bi bi-megaphone text-gold me-2"></i>Circulares
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                    <a href="{{ route('login') }}" class="btn btn-gold rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Ingresar / Portal</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main id="app-views">

        {{-- ===================== PORTADA / HOME ===================== --}}
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
                                        <a href="#historia" class="btn btn-outline-light btn-lg rounded-pill px-4">
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
                                        <a href="#historia" class="btn btn-outline-light btn-lg rounded-pill px-4">
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

            {{-- Proceso de admisiones --}}
            <section class="py-5 bg-white">
                <div class="container py-3">
                    <div class="text-center mb-5">
                        <span class="section-kicker">Admisiones en Línea</span>
                        <h2 class="fw-bold text-navy display-6 mt-1">¿Cómo funciona el proceso?</h2>
                        <p class="text-muted">Sigue estos tres sencillos pasos para solicitar un cupo en nuestra institución.</p>
                    </div>
                    <div class="row g-4">
                        @foreach([1, 2, 3] as $paso)
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100 p-4 text-center card-hover">
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
        </section>

        {{-- ===================== HISTORIA ===================== --}}
        <section id="historia">
            <div class="bg-navy text-white py-5">
                <div class="container py-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="#home" class="text-warning text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item text-white-50">Nosotros</li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Nuestra Historia</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold">{{ $texto('historia.titulo') }}</h1>
                    <p class="lead text-white-50 mb-0">{{ $texto('historia.subtitulo') }}</p>
                </div>
            </div>

            <div class="container py-5">
                <div class="row g-5 align-items-start">
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                            <img src="{{ $imagen('historia.imagen_principal') }}" class="img-fluid"
                                 alt="Campus educativo de {{ $nombreInstitucion }}">
                            <div class="card-body bg-cream p-4">
                                <h5 class="fw-bold text-navy mb-2"><i class="bi bi-quote text-gold me-1"></i>Lema Fundacional</h5>
                                <p class="fst-italic text-muted mb-2">{{ $texto('historia.cita_fundadora') }}</p>
                                <small class="fw-bold text-navy">— Beata Madre Caridad Brader</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <h3 class="fw-bold text-navy mb-3">Raíces Franciscanas y Misión en el Ecuador</h3>
                        <p class="text-muted lh-lg mb-4">{{ $texto('historia.contenido') }}</p>

                        @if($hitos->count() > 0)
                        <h4 class="fw-bold text-navy mb-4">Hitos Históricos</h4>
                        <div class="timeline-wrapper">
                            @foreach($hitos as $hito)
                            <div class="timeline-item {{ $loop->last ? 'mb-0' : '' }}">
                                <div class="timeline-dot"></div>
                                <span class="badge {{ $loop->even ? 'bg-navy text-white' : 'bg-warning text-dark' }} mb-1">
                                    {{ $hito->anio }}
                                </span>
                                <h6 class="fw-bold text-navy">{{ $hito->titulo }}</h6>
                                @if($hito->descripcion)
                                <p class="text-muted small mb-0">{{ $hito->descripcion }}</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== COMUNIDAD ===================== --}}
        <section id="comunidad">
            <div class="bg-navy text-white py-5">
                <div class="container py-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="#home" class="text-warning text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item text-white-50">Nosotros</li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Comunidad Educativa</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold">{{ $texto('comunidad.titulo') }}</h1>
                    <p class="lead text-white-50 mb-0">{{ $texto('comunidad.subtitulo') }}</p>
                </div>
            </div>

            <div class="container py-5">
                @forelse($miembros as $area => $integrantes)
                <div class="mb-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <h4 class="fw-bold text-navy mb-0">{{ $area ?: 'Comunidad educativa' }}</h4>
                        <span class="badge bg-light text-navy border">{{ $integrantes->count() }} integrante(s)</span>
                    </div>
                    <div class="row g-4">
                        @foreach($integrantes as $miembro)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100 p-4 text-center card-hover">
                                @if($miembro->fotoUrl())
                                <img src="{{ $miembro->fotoUrl() }}" alt="{{ $miembro->nombre }}" class="miembro-foto mx-auto mb-3">
                                @else
                                <div class="rounded-circle bg-navy text-warning d-inline-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width:76px;height:76px;">
                                    <i class="bi bi-person-hearts fs-2"></i>
                                </div>
                                @endif
                                <span class="badge bg-warning text-dark mx-auto mb-2">{{ $miembro->cargo }}</span>
                                <h5 class="fw-bold text-navy mb-1">{{ $miembro->nombre }}</h5>
                                @if($miembro->responsabilidad)
                                <p class="text-muted small mb-0">{{ $miembro->responsabilidad }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-people display-1 d-block mb-3 opacity-25"></i>
                    <p class="mb-0">La comunidad educativa se está actualizando.</p>
                </div>
                @endforelse
            </div>
        </section>

        {{-- ===================== ÚTILES ===================== --}}
        <section id="utiles">
            <div class="bg-navy text-white py-5">
                <div class="container py-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="#home" class="text-warning text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item text-white-50">Servicios</li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Lista de Útiles</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold">{{ $texto('utiles.titulo') }}</h1>
                    <p class="lead text-white-50 mb-0">{{ $texto('utiles.subtitulo') }}</p>
                </div>
            </div>

            <div class="container py-5">
                @if($utiles->count() > 0)
                <ul class="nav nav-pills gap-2 mb-4 flex-wrap" id="utiles-tabs" role="tablist">
                    @foreach(\App\Models\UtilItem::NIVELES as $clave => $etiqueta)
                    @if($utiles->has($clave))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4 {{ $loop->first ? 'active' : '' }}"
                                data-bs-toggle="pill" data-bs-target="#nivel-{{ $clave }}" type="button" role="tab">
                            {{ $etiqueta }}
                        </button>
                    </li>
                    @endif
                    @endforeach
                </ul>

                <div class="tab-content">
                    @foreach(\App\Models\UtilItem::NIVELES as $clave => $etiqueta)
                    @if($utiles->has($clave))
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="nivel-{{ $clave }}" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-cream py-3 px-4 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-navy mb-0">{{ $etiqueta }}</h5>
                                <span class="badge bg-navy">{{ $utiles[$clave]->count() }} artículos</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">#</th>
                                            <th>Material / Recurso</th>
                                            <th>Especificación</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="pe-4">Área / Uso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($utiles[$clave] as $item)
                                        <tr>
                                            <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                            <td class="fw-semibold">{{ $item->nombre }}</td>
                                            <td class="text-muted">{{ $item->especificacion ?: '—' }}</td>
                                            <td class="text-center">{{ $item->cantidad ?: '—' }}</td>
                                            <td class="pe-4 text-muted">{{ $item->area ?: '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-backpack2 display-1 d-block mb-3 opacity-25"></i>
                    <p class="mb-0">La lista de útiles se publicará próximamente.</p>
                </div>
                @endif
            </div>
        </section>

        {{-- ===================== DESCARGAS ===================== --}}
        <section id="descargas">
            <div class="bg-navy text-white py-5">
                <div class="container py-2">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="#home" class="text-warning text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item text-white-50">Servicios</li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Centro de Descargas</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold">{{ $texto('descargas.titulo') }}</h1>
                    <p class="lead text-white-50 mb-0">{{ $texto('descargas.subtitulo') }}</p>
                </div>
            </div>

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
        </section>

        {{-- ===================== CIRCULARES ===================== --}}
        <section id="circulares" class="py-5 bg-cream">
            <div class="container py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
                    <div>
                        <span class="section-kicker">Comunicación oficial</span>
                        <h2 class="fw-bold text-navy display-6 mt-1 mb-0">Circulares recientes</h2>
                    </div>
                    <a href="{{ route('login') }}" class="btn btn-outline-dark rounded-pill px-4">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Ver todas en el portal
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
                                <p class="text-muted small">{{ \Illuminate\Support\Str::limit($circular->descripcion, 120) }}</p>
                                @endif
                            </div>
                            @if($circular->archivo_url)
                            <div class="card-footer bg-white border-0 pt-0">
                                <a href="{{ $circular->archivo_url }}" target="_blank" rel="noopener"
                                   class="btn btn-outline-danger btn-sm rounded-pill">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                                </a>
                            </div>
                            @endif
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
    </main>

    {{-- ===================== PIE DE PÁGINA ===================== --}}
    <footer class="bg-navy text-white pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row g-4 pb-4 border-bottom border-white border-opacity-10">
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo {{ $nombreInstitucion }}"
                             class="rounded-3 bg-white p-1" style="width:38px;height:38px;object-fit:contain;">
                        @else
                        <div class="rounded-3 bg-warning text-dark d-flex align-items-center justify-content-center fw-bold"
                             style="width:38px;height:38px;">
                            <i class="bi bi-peace-fill fs-5"></i>
                        </div>
                        @endif
                        <h5 class="fw-bold mb-0">{{ $nombreInstitucion }}</h5>
                    </div>
                    <p class="text-white-50 small lh-lg mb-3">{{ $texto('institucion.footer_descripcion') }}</p>
                    <div class="d-flex gap-2">
                        <a href="#home" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#home" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#home" class="btn btn-sm btn-outline-light rounded-circle" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <h6 class="fw-bold text-warning text-uppercase small mb-3">Navegación</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="#home" class="text-white-50 text-decoration-none hover-white">Inicio / Home</a></li>
                        <li><a href="#historia" class="text-white-50 text-decoration-none hover-white">Nuestra Historia</a></li>
                        <li><a href="#comunidad" class="text-white-50 text-decoration-none hover-white">Comunidad Educativa</a></li>
                        <li><a href="#utiles" class="text-white-50 text-decoration-none hover-white">Lista de Útiles</a></li>
                        <li><a href="#descargas" class="text-white-50 text-decoration-none hover-white">Centro de Descargas</a></li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <h6 class="fw-bold text-warning text-uppercase small mb-3">Contacto Institucional</h6>
                    <ul class="list-unstyled small text-white-50 d-grid gap-2">
                        <li><i class="bi bi-geo-alt-fill text-warning me-2"></i>{{ $texto('institucion.direccion') }}</li>
                        <li><i class="bi bi-telephone-fill text-warning me-2"></i>{{ $texto('institucion.telefono') }}</li>
                        <li><i class="bi bi-envelope-fill text-warning me-2"></i>{{ $texto('institucion.correo') }}</li>
                        <li><i class="bi bi-clock-fill text-warning me-2"></i>{{ $texto('institucion.horario_atencion') }}</li>
                    </ul>
                </div>
            </div>

            <div class="pt-4 text-center">
                <p class="small text-white-50 mb-1">
                    &copy; {{ $anioActual }} {{ $texto('institucion.nombre_corto') }}. Todos los derechos reservados.
                </p>
                <p class="small fw-semibold text-warning mb-0">
                    Creado por Christian Rodriguez Rivadeneira
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
