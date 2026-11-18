<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Aplica el aside oculto antes de pintar para evitar el salto visual.
        try {
            if (localStorage.getItem('sidebarOculto') === '1') {
                document.documentElement.classList.add('sidebar-oculto');
            }
        } catch (error) {}
    </script>
    <title>@yield('titulo', 'Sistema de Gestión Escolar') - {{ $institucion->nombre ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @if(!empty($institucion?->logo_path))
    {{-- El logo institucional también es el favicon de las pestañas. --}}
    <link rel="icon" href="{{ $institucion->logoUrl() }}">
    @endif
    @php
        // Apariencia global: la elige el administrador y la comparten todos los roles.
        // Ruta: GET/POST /admin/configuracion/apariencia.
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
        /* El área de contenido usa letra negra para que textos y formularios se lean.
           No se fuerza color en span/label porque rompe los encabezados oscuros
           (por ejemplo "Estudiantes Inscritos", que usa bg-dark + text-white).
           Esos elementos heredan el negro del body y respetan sus clases. */
        main,
        main h1, main h2, main h3, main h4, main h5, main h6,
        main p, main li, main dt, main dd,
        main .form-label, main .form-text,
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
        @media (min-width: 768px) {
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
        @media (max-width: 767.98px) {
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
        /* Tablas: desplazamiento horizontal con barras visibles. El
           desplazamiento vertical lo hace la ventana completa.
           Aplica a todas las tablas envueltas en .table-responsive del panel. */
        main .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #adb5bd #f1f3f5;
        }
        main .table-responsive::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        main .table-responsive::-webkit-scrollbar-track {
            background: #f1f3f5;
            border-radius: 10px;
        }
        main .table-responsive::-webkit-scrollbar-thumb {
            background: #adb5bd;
            border-radius: 10px;
            border: 2px solid #f1f3f5;
        }
        main .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #6c757d;
        }
        /* Botón para ocultar el aside en tablets y laptops pequeñas. */
        @media (min-width: 768px) {
            html.sidebar-oculto #sidebarOffcanvas {
                display: none !important;
            }
            html.sidebar-oculto .app-content-column {
                width: 100%;
            }
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
                    <button class="btn btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="bi bi-list"></i>
                    </button>
                    <button class="btn btn-outline-secondary d-none d-md-inline-flex align-items-center me-2" type="button"
                            id="toggleSidebar" title="Ocultar menú lateral" aria-label="Ocultar menú lateral"
                            aria-controls="sidebarOffcanvas" aria-expanded="true">
                        <i class="bi bi-layout-sidebar"></i>
                    </button>
                    <span class="navbar-text ms-auto">
                        @auth
                            @php
                                // Perfil activo y selector de multirol.
                                $usuarioActual = auth()->user();
                                $perfilesDisponibles = $usuarioActual->perfiles();
                                $perfilActual = $usuarioActual->perfilActivo();
                            @endphp
                            <span class="d-inline-flex align-items-center gap-2">
                                <i class="bi bi-person-circle"></i> {{ nombreCorto($usuarioActual->name ?? '') }}

                                @if(count($perfilesDisponibles) > 1)
                                    <form method="POST" action="{{ route('perfil.activo') }}" class="d-inline">
                                        @csrf
                                        <select name="rol" class="form-select form-select-sm" style="max-width:220px;"
                                            onchange="this.form.submit()" title="Cambiar de perfil">
                                            @foreach($perfilesDisponibles as $perfil)
                                            <option value="{{ $perfil }}" @selected($perfil === $perfilActual)>
                                                {{ \App\Models\User::etiquetaRol($perfil) }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">{{ \App\Models\User::etiquetaRol($perfilActual) }}</span>
                                @endif
                            </span>
                        @endauth
                    </span>
                </div>
            </nav>

            <main class="flex-grow-1 p-4 bg-light">
                @yield('contenido')
            </main>

            <footer class="bg-white text-center py-3 border-top mt-auto">
                <small class="text-muted">&copy; {{ date('Y') }} Sistema de Gestión Escolar. Todos los derechos reservados.</small><br>
                <small class="text-muted">Christian Rodríguez Rivadeneira</small>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        /*
         * Confirmaciones con SweetAlert2 para todas las vistas.
         *
         * Qué hace: cualquier formulario con data-confirm="mensaje" pide
         * confirmación con SweetAlert antes de enviarse. Los enlaces o botones
         * con data-confirm fuera de un formulario también la piden.
         * Conecta con: layouts/app.blade.php y SweetAlert2.
         */
        window.confirmarAccion = function (mensaje, opciones) {
            opciones = opciones || {};
            return Swal.fire({
                icon: opciones.icon || 'warning',
                title: opciones.title || '¿Confirmar acción?',
                text: mensaje,
                showCancelButton: true,
                confirmButtonText: opciones.confirmButtonText || 'Sí, continuar',
                cancelButtonText: opciones.cancelButtonText || 'Cancelar',
                confirmButtonColor: '#2b579a',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                focusCancel: true,
            }).then(function (resultado) {
                return resultado.isConfirmed;
            });
        };

        document.addEventListener('submit', function (evento) {
            var formulario = evento.target;
            if (!(formulario instanceof HTMLFormElement)) return;

            var mensaje = formulario.dataset.confirm;
            if (!mensaje || formulario.dataset.confirmado === '1') return;

            evento.preventDefault();

            window.confirmarAccion(mensaje, {
                title: formulario.dataset.confirmTitle || '¿Confirmar acción?',
                confirmButtonText: formulario.dataset.confirmOk || 'Sí, continuar',
            }).then(function (confirmado) {
                if (!confirmado) return;

                formulario.dataset.confirmado = '1';

                if (typeof formulario.requestSubmit === 'function') {
                    formulario.requestSubmit();
                    window.setTimeout(function () {
                        delete formulario.dataset.confirmado;
                    }, 0);
                } else {
                    formulario.submit();
                }
            });
        });

        document.addEventListener('click', function (evento) {
            var elemento = evento.target.closest ? evento.target.closest('[data-confirm]') : null;
            if (!elemento || elemento.closest('form')) return;
            if (!(elemento instanceof HTMLAnchorElement || elemento instanceof HTMLButtonElement)) return;
            if (elemento.dataset.confirmado === '1') return;

            evento.preventDefault();

            window.confirmarAccion(elemento.dataset.confirm, {
                title: elemento.dataset.confirmTitle || '¿Confirmar acción?',
                confirmButtonText: elemento.dataset.confirmOk || 'Sí, continuar',
            }).then(function (confirmado) {
                if (!confirmado) return;

                elemento.dataset.confirmado = '1';
                elemento.click();
                window.setTimeout(function () {
                    delete elemento.dataset.confirmado;
                }, 0);
            });
        });
    </script>
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

            const avisoError = document.getElementById('swal-error');
            if (avisoError && avisoError.dataset.message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Atención',
                    text: avisoError.dataset.message,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#2b579a',
                });
            }

            @auth
            /*
             * Avisos generales con SweetAlert2.
             *
             * Qué hace: muestra los mensajes flash y los errores de validación
             * como toasts para todos los perfiles y oculta la alerta Bootstrap
             * equivalente del contenido. Si la vista ya tiene su propio aviso
             * SweetAlert (por ejemplo #swal-success-notas), no lo duplica.
             * Conecta con: layouts/app.blade.php y SweetAlert2.
             */
            @php
                $avisosSesion = array_values(array_filter([
                    session('success') ? ['texto' => session('success'), 'icono' => 'success', 'tiempo' => 3200] : null,
                    session('info') ? ['texto' => session('info'), 'icono' => 'info', 'tiempo' => 5000] : null,
                    session('warning') ? ['texto' => session('warning'), 'icono' => 'warning', 'tiempo' => 6000] : null,
                    session('error') ? ['texto' => session('error'), 'icono' => 'error', 'tiempo' => 8000] : null,
                    $errors->any() ? ['texto' => $errors->first(), 'icono' => 'error', 'tiempo' => 8000] : null,
                ]));
            @endphp
            const avisosSesion = @json($avisosSesion);

            avisosSesion.forEach(function (mensaje, indice) {
                if (!mensaje.texto) return;

                // La vista ya muestra este aviso con su propio SweetAlert.
                const avisoPropio = document.querySelector('[id*="swal"][id*="' + mensaje.icono + '"]');
                if (avisoPropio && avisoPropio.dataset.message) return;

                document.querySelectorAll('main .alert-success, main .alert-danger, main .alert-warning, main .alert-info')
                    .forEach(function (bloque) {
                        if (bloque.textContent.indexOf(mensaje.texto) !== -1) {
                            bloque.classList.add('d-none');
                        }
                    });

                window.setTimeout(function () {
                    Swal.fire({
                        icon: mensaje.icono,
                        title: mensaje.texto,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: mensaje.tiempo,
                        timerProgressBar: true,
                    });
                }, indice * 350);
            });
            @endauth
        });
    </script>
    <script>
        /*
         * Oculta o muestra el menú lateral en pantallas grandes.
         *
         * Qué hace: guarda la preferencia en localStorage para conservarla entre
         * páginas. En móviles el aside sigue funcionando como offcanvas.
         * Conecta con: layouts/app.blade.php y #sidebarOffcanvas.
         */
        document.addEventListener('DOMContentLoaded', function () {
            var boton = document.getElementById('toggleSidebar');
            if (!boton) return;

            function actualizarBoton() {
                var oculto = document.documentElement.classList.contains('sidebar-oculto');
                var titulo = oculto ? 'Mostrar menú lateral' : 'Ocultar menú lateral';

                boton.title = titulo;
                boton.setAttribute('aria-label', titulo);
                boton.setAttribute('aria-expanded', oculto ? 'false' : 'true');
                boton.innerHTML = '<i class="bi ' + (oculto ? 'bi-layout-sidebar-inset' : 'bi-layout-sidebar') + '"></i>';
            }

            boton.addEventListener('click', function () {
                document.documentElement.classList.toggle('sidebar-oculto');

                try {
                    localStorage.setItem(
                        'sidebarOculto',
                        document.documentElement.classList.contains('sidebar-oculto') ? '1' : '0'
                    );
                } catch (error) {}

                actualizarBoton();
            });

            actualizarBoton();
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
