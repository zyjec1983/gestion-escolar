{{--
    Plantilla base del portal institucional público.

    Qué hace: concentra el <head>, los estilos del template franciscano, la
    barra superior, el navbar, el pie y los scripts. Cada pestaña del portal
    (inicio, historia, comunidad, útiles, descargas y circulares) tiene su
    propia vista que extiende este layout y solo aporta su contenido.
    Conecta con: PortalController, App\Support\PortalContenido, App\Models\
    Institucion y las vistas portal/*.
    Rutas: GET /, /historia, /comunidad, /utiles, /descargas, /circulares.
--}}
@php
    // Datos compartidos por el layout y todas las pestañas del portal. Se
    // resuelven aquí para que cualquier vista que extienda esta plantilla
    // (incluido el pre-registro) funcione sin depender del controlador.
    $nombreInstitucion = $institucion->nombre ?? \App\Support\PortalContenido::valor('institucion.nombre_corto');
    $eslogan = \App\Support\PortalContenido::valor('institucion.eslogan');
    $logoUrl = !empty($institucion?->logo_path) ? $institucion->logoUrl() : null;
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
    <title>@yield('titulo', $nombreInstitucion) · {{ $eslogan }}</title>
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
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

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

    {{-- Navbar institucional: cada enlace lleva a su propia vista. --}}
    <nav class="navbar navbar-expand-lg navbar-franciscan sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="{{ route('welcome') }}">
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
                        <a class="nav-link {{ request()->routeIs('welcome') ? 'active' : '' }}" href="{{ route('welcome') }}">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('portal.historia', 'portal.comunidad') ? 'active' : '' }}"
                           href="#" id="navNosotros" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Nosotros</a>
                        <ul class="dropdown-menu" aria-labelledby="navNosotros">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('portal.historia') ? 'active' : '' }}"
                                   href="{{ route('portal.historia') }}">
                                    <i class="bi bi-book-half text-gold me-2"></i>Nuestra historia
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('portal.comunidad') ? 'active' : '' }}"
                                   href="{{ route('portal.comunidad') }}">
                                    <i class="bi bi-people-fill text-navy me-2"></i>Comunidad educativa
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('portal.utiles', 'portal.descargas', 'portal.circulares') ? 'active' : '' }}"
                           href="#" id="navServicios" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Servicios</a>
                        <ul class="dropdown-menu" aria-labelledby="navServicios">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('portal.utiles') ? 'active' : '' }}"
                                   href="{{ route('portal.utiles') }}">
                                    <i class="bi bi-backpack2 text-gold me-2"></i>Lista de útiles
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('portal.descargas') ? 'active' : '' }}"
                                   href="{{ route('portal.descargas') }}">
                                    <i class="bi bi-cloud-arrow-down-fill text-navy me-2"></i>Descargas
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('portal.circulares') ? 'active' : '' }}"
                                   href="{{ route('portal.circulares') }}">
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

    <main id="app-views" class="flex-grow-1">
        @yield('contenido')
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
                        <a href="{{ route('welcome') }}" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="{{ route('welcome') }}" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="{{ route('welcome') }}" class="btn btn-sm btn-outline-light rounded-circle" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <h6 class="fw-bold text-warning text-uppercase small mb-3">Navegación</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="{{ route('welcome') }}" class="text-white-50 text-decoration-none hover-white">Inicio / Home</a></li>
                        <li><a href="{{ route('portal.historia') }}" class="text-white-50 text-decoration-none hover-white">Nuestra Historia</a></li>
                        <li><a href="{{ route('portal.comunidad') }}" class="text-white-50 text-decoration-none hover-white">Comunidad Educativa</a></li>
                        <li><a href="{{ route('portal.utiles') }}" class="text-white-50 text-decoration-none hover-white">Lista de Útiles</a></li>
                        <li><a href="{{ route('portal.descargas') }}" class="text-white-50 text-decoration-none hover-white">Centro de Descargas</a></li>
                        <li><a href="{{ route('portal.circulares') }}" class="text-white-50 text-decoration-none hover-white">Circulares</a></li>
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
    @stack('scripts')
</body>
</html>
