<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Sistema de Gestión Escolar') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @php
        // Apariencia global: la elige el administrador y la comparten todos los roles.
        // Ruta: GET/POST /admin/configuracion (pestaña Apariencia).
        $tema = \App\Support\Tema::actual();
    @endphp
    <style>
        /* Variables de apariencia: aside, menú activo/hover y tamaño de fuente. */
        :root {
            --aside-bg: {{ $tema['aside'] }};
            --aside-active: {{ $tema['activo'] }};
            --aside-hover: {{ $tema['hover'] }};
            --aside-text: {{ $tema['texto'] }};
            --app-font-size: {{ $tema['fuente_pt'] }}pt;
        }
        /* El tamaño raíz escala todo Bootstrap igual que el zoom de Word. */
        html {
            font-size: var(--app-font-size);
        }
        body {
            color: #000;
            font-size: 1rem;
        }
        /* El área de contenido usa letra negra para que textos y formularios se lean. */
        main,
        main h1, main h2, main h3, main h4, main h5, main h6,
        main p, main span, main label, main li, main dt, main dd,
        main .form-label, main .form-check-label, main .form-text,
        main .form-control, main .form-select, main textarea,
        main .input-group-text, main .table {
            color: #000;
        }
        main .form-control,
        main .form-select,
        main textarea {
            background-color: #fff;
        }
        main .form-control::placeholder,
        main textarea::placeholder {
            color: #6c757d;
            opacity: 1;
        }
        main .text-muted {
            color: #495057 !important;
        }
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
        /* Menú lateral: fondo, letras blancas y negrilla al pasar o seleccionar. */
        #sidebarOffcanvas {
            background: var(--aside-bg);
        }
        #sidebarOffcanvas .nav-link {
            border-radius: 0.5rem;
            color: var(--aside-text) !important;
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease,
                        transform 0.2s ease, padding-left 0.2s ease;
        }
        #sidebarOffcanvas .nav-link:hover {
            background: var(--aside-hover) !important;
            color: #fff !important;
            font-weight: 700;
            transform: translateX(4px);
            padding-left: 1.4rem;
        }
        #sidebarOffcanvas .nav-link.active {
            background: var(--aside-active) !important;
            color: #fff !important;
            font-weight: 700;
        }
        #sidebarOffcanvas .nav-link.active:hover {
            background: var(--aside-hover) !important;
        }
        #sidebarOffcanvas .dropdown .dropdown-toggle {
            color: var(--aside-text) !important;
            transition: background-color 0.2s ease, transform 0.2s ease;
            border-radius: 0.5rem;
            padding: 0.35rem 0.5rem;
        }
        #sidebarOffcanvas .dropdown .dropdown-toggle:hover {
            background: var(--aside-hover) !important;
            color: #fff !important;
            font-weight: 700;
        }
        /* La columna de contenido debe poder encogerse dentro del flex; sin
           esto los textos con text-nowrap/truncate estiran toda la pagina. */
        .app-content-column {
            min-width: 0;
        }
        .app-content-column > main {
            min-width: 0;
        }
        main img,
        main video,
        main iframe {
            max-width: 100%;
        }
        main .card,
        main .modal-content,
        main .list-group-item,
        main .alert {
            overflow-wrap: anywhere;
        }
        @media (max-width: 575.98px) {
            main.p-4 {
                padding: 1rem !important;
            }
            .navbar.px-4 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            main h4 {
                font-size: 1.15rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <div class="offcanvas-lg offcanvas-start sidebar-static" tabindex="-1" id="sidebarOffcanvas">
            @yield('sidebar')
        </div>

        <div class="flex-grow-1 d-flex flex-column min-vh-100 app-content-column">
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

            const aviso = document.getElementById('swal-warning');
            if (aviso) {
                Swal.fire({
                    icon: 'warning',
                    title: aviso.dataset.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 6000,
                    timerProgressBar: true,
                });
            }
        });
    </script>
    @if(request()->is('mensajes*', 'observaciones*', 'docente/observaciones*'))
    <script>
        (function () {
            var modalAbierto = false;
            document.addEventListener('show.bs.modal', function () { modalAbierto = true; });
            document.addEventListener('hidden.bs.modal', function () { modalAbierto = false; });

            function hayModalAbierto() {
                return modalAbierto || !!document.querySelector('.modal.show');
            }

            function campoEnUso() {
                var el = document.activeElement;
                if (!el || el === document.body) return false;
                if (['INPUT', 'TEXTAREA', 'SELECT'].indexOf(el.tagName) !== -1) return true;
                return el.isContentEditable === true;
            }

            setInterval(function () {
                if (document.hidden) return;
                if (hayModalAbierto()) return;
                if (campoEnUso()) return;
                location.reload();
            }, 10000);
        })();
    </script>
    @endif
</body>
</html>
