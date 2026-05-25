<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Unidad Educativa') - Sistema de Gestión Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: #1a1a2e;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="bi bi-mortarboard-fill me-2"></i>Unidad Educativa
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPublic">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarPublic">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="/"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('pre-registro*') ? 'active' : '' }}" href="/pre-registro"><i class="bi bi-pencil-square me-1"></i>Solicitud de Matrícula</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="/login" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('contenido')
    </main>

    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h6 class="fw-bold"><i class="bi bi-mortarboard-fill me-2"></i>Unidad Educativa</h6>
                    <p class="small text-secondary mb-0">Formando el futuro de nuestra sociedad.</p>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <h6 class="fw-bold">Enlaces</h6>
                    <ul class="list-unstyled small">
                        <li><a href="/" class="text-secondary text-decoration-none">Inicio</a></li>
                        <li><a href="/pre-registro" class="text-secondary text-decoration-none">Solicitud de Matrícula</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold">Contacto</h6>
                    <ul class="list-unstyled small text-secondary">
                        <li><i class="bi bi-geo-alt me-1"></i>Dirección principal</li>
                        <li><i class="bi bi-telephone me-1"></i>(04) 2XX-XXXX</li>
                        <li><i class="bi bi-envelope me-1"></i>info@unidadeducativa.edu.ec</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center text-secondary small">
                &copy; {{ date('Y') }} Unidad Educativa. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var heroCarousel = document.getElementById('heroCarousel');
        if (heroCarousel) {
            var interval = parseInt(heroCarousel.dataset.interval) || 2000;
            new bootstrap.Carousel(heroCarousel, { interval: interval });
        }
    });
    </script>
    @stack('scripts')
</body>
</html>
