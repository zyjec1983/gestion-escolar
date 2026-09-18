<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Sistema de Gestión Escolar') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar-static {
            width: 260px;
            min-width: 260px;
        }
        @media (min-width: 992px) {
            .offcanvas-lg.sidebar-static {
                position: static !important;
                transform: none !important;
                visibility: visible !important;
                z-index: auto;
            }
            .offcanvas-lg.sidebar-static .offcanvas-body {
                overflow-y: visible;
            }
        }
        @media (max-width: 991.98px) {
            .sidebar-static {
                width: auto;
                min-width: auto;
            }
        }
        #sidebarOffcanvas .nav-link {
            border-radius: 0.5rem;
            transition: background-color 0.2s ease, color 0.2s ease,
                        transform 0.2s ease, padding-left 0.2s ease;
        }
        #sidebarOffcanvas .nav-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            transform: translateX(4px);
            padding-left: 1.4rem;
        }
        #sidebarOffcanvas .nav-link.active:hover {
            background: var(--bs-primary);
        }
        #sidebarOffcanvas .dropdown .dropdown-toggle {
            transition: background-color 0.2s ease, transform 0.2s ease;
            border-radius: 0.5rem;
            padding: 0.35rem 0.5rem;
        }
        #sidebarOffcanvas .dropdown .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.12);
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <div class="offcanvas-lg offcanvas-start sidebar-static" tabindex="-1" id="sidebarOffcanvas">
            @yield('sidebar')
        </div>

        <div class="flex-grow-1 d-flex flex-column min-vh-100">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="navbar-text ms-auto">
                        @auth
                            <i class="bi bi-person-circle"></i> {{ nombreCorto(auth()->user()->name ?? '') }}
                            <span class="badge bg-secondary ms-2">{{ auth()->user()->rol }}</span>
                        @endauth
                    </span>
                </div>
            </nav>

            <main class="flex-grow-1 p-4 bg-light">
                @yield('contenido')
            </main>

            <footer class="bg-white text-center py-3 border-top mt-auto">
                <small class="text-muted">&copy; {{ date('Y') }} Sistema de Gestión Escolar. Todos los derechos reservados.</small>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('swal-success');
            if (el) {
                Swal.fire({
                    icon: 'success',
                    title: el.dataset.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            }
        });
    </script>
    @if(request()->is('mensajes*'))
    <script>
        setInterval(function () {
            if (document.hidden) return;
            var el = document.activeElement;
            var escribiendo = el && ['INPUT', 'TEXTAREA', 'SELECT'].indexOf(el.tagName) !== -1;
            if (!escribiendo) {
                location.reload();
            }
        }, 15000);
    </script>
    @endif
</body>
</html>
