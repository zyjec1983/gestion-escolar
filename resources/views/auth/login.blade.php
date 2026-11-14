@php
    // Apariencia y datos institucionales compartidos por el administrador.
    // Conecta con: App\Support\Tema, App\Models\Institucion (AppServiceProvider).
    $tema = \App\Support\Tema::actual();
    $nombreInstitucion = $institucion->nombre ?? 'Unidad Educativa';
    $subtituloInstitucion = $institucion->subtitulo ?? 'Institución Educativa';
    $intervalo = $institucion->carousel_interval ?? 4000;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión - {{ $nombreInstitucion }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @if(!empty($institucion?->logo_path))
    {{-- El logo institucional es también el favicon de las pestañas. --}}
    <link rel="icon" href="{{ $institucion->logoUrl() }}">
    @endif
    <style>
        /* Paleta activa elegida en Configuración → Apariencia. */
        :root {
            --login-brand: {{ $tema['aside'] }};
            --login-brand-hover: {{ $tema['hover'] }};
            --login-brand-active: {{ $tema['activo'] }};
            --login-text: {{ $tema['texto'] }};
            --login-font-size: {{ $tema['fuente_pt'] }}pt;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: var(--login-font-size);
            color: #1f2937;
            background: #eef1f6;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .login-shell {
            width: 100%;
            max-width: 1040px;
        }
        .login-card {
            display: grid;
            grid-template-columns: 7fr 5fr;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 18px 35px -12px rgba(0, 0, 0, .25);
            min-height: 540px;
        }
        /* Columna izquierda: carrusel administrable. */
        .login-visual {
            position: relative;
            background: color-mix(in srgb, var(--login-brand) 10%, white);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .login-visual .carousel,
        .login-visual .carousel-inner,
        .login-visual .carousel-item {
            height: 100%;
        }
        .login-visual .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .login-visual .carousel-caption {
            background: linear-gradient(180deg, transparent, rgba(0, 0, 0, .65));
            left: 0;
            right: 0;
            bottom: 0;
            padding: 24px;
            text-align: left;
        }
        .login-visual .carousel-caption h5 {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .login-visual .carousel-caption p {
            margin-bottom: 0;
            font-size: .9rem;
            opacity: .92;
        }
        /* Respaldo cuando el administrador aún no ha subido imágenes de login. */
        .login-visual-fallback {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
            gap: 14px;
            color: var(--login-brand);
            position: relative;
        }
        .login-visual-fallback::before,
        .login-visual-fallback::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 3px solid var(--login-brand);
            opacity: .18;
        }
        .login-visual-fallback::before {
            width: 120px;
            height: 120px;
            top: 32px;
            right: 36px;
        }
        .login-visual-fallback::after {
            width: 64px;
            height: 64px;
            bottom: 40px;
            left: 44px;
            border-color: var(--login-brand-hover);
        }
        .login-visual-fallback .fallback-logo {
            max-height: 110px;
            max-width: 70%;
            object-fit: contain;
            position: relative;
            z-index: 1;
        }
        .login-visual-fallback .fallback-icon {
            font-size: 4rem;
            position: relative;
            z-index: 1;
        }
        .login-visual-fallback h2 {
            font-weight: 800;
            font-size: 1.35rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        .login-visual-fallback p {
            color: #4b5563;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        /* Columna derecha: formulario. */
        .login-form-panel {
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form-panel .brand-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            color: var(--login-brand);
            font-weight: 700;
        }
        .login-form-panel .brand-mini img {
            height: 38px;
            width: 38px;
            object-fit: contain;
            border-radius: 6px;
        }
        .login-title {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        .login-subtitle {
            color: #6b7280;
            font-size: .875rem;
            margin-bottom: 26px;
        }
        .login-field {
            position: relative;
            margin-bottom: 20px;
        }
        .login-field label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .login-field input {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #d1d5db;
            background: transparent;
            padding: 6px 0 9px;
            font-size: .95rem;
            color: #111827;
            transition: border-color .2s;
        }
        .login-field input:focus {
            outline: none;
            border-bottom-color: var(--login-brand);
            box-shadow: 0 1px 0 0 var(--login-brand);
        }
        .login-field .toggle-password {
            position: absolute;
            right: 0;
            bottom: 8px;
            border: 0;
            background: transparent;
            color: #6b7280;
            padding: 2px 4px;
            line-height: 1;
        }
        .login-field .toggle-password:hover { color: var(--login-brand); }
        .btn-login {
            background: var(--login-brand);
            border: 1px solid var(--login-brand);
            color: var(--login-text);
            font-weight: 600;
            padding: 10px 26px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background .2s, border-color .2s;
        }
        .btn-login:hover,
        .btn-login:focus {
            background: var(--login-brand-hover);
            border-color: var(--login-brand-hover);
            color: var(--login-text);
        }
        .login-links a {
            color: var(--login-brand);
            text-decoration: none;
            font-size: .82rem;
        }
        .login-links a:hover { text-decoration: underline; }
        .login-footer {
            text-align: center;
            color: #6b7280;
            font-size: .78rem;
            margin-top: 18px;
        }
        @media (max-width: 991.98px) {
            .login-card { grid-template-columns: 1fr; min-height: 0; }
            .login-visual { min-height: 260px; }
            .login-form-panel { padding: 34px 26px; }
        }
        @media (max-width: 575.98px) {
            body { padding: 12px; }
            .login-visual { min-height: 200px; }
            .login-form-panel { padding: 26px 20px; }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <div class="login-card">
            {{-- ============ COLUMNA IZQUIERDA: CARRUSEL ============ --}}
            <section class="login-visual" aria-label="Presentación institucional">
                @if($slides->count() > 0)
                <div id="loginCarousel" class="carousel slide" data-bs-ride="carousel"
                    data-bs-interval="{{ max(1000, (int) $intervalo) }}">
                    @if($slides->count() > 1)
                    <div class="carousel-indicators">
                        @foreach($slides as $i => $slide)
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="{{ $i }}"
                            class="{{ $i === 0 ? 'active' : '' }}" aria-label="Imagen {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                    @endif
                    <div class="carousel-inner">
                        @foreach($slides as $i => $slide)
                        <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($slide->imagen_path) }}"
                                alt="{{ $slide->titulo ?? 'Imagen institucional' }}">
                            @if($slide->titulo || $slide->subtitulo)
                            <div class="carousel-caption d-none d-md-block">
                                @if($slide->titulo)<h5>{{ $slide->titulo }}</h5>@endif
                                @if($slide->subtitulo)<p>{{ $slide->subtitulo }}</p>@endif
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="login-visual-fallback">
                    @if(!empty($institucion?->logo_path))
                    <img src="{{ $institucion->logoUrl() }}" alt="Logo {{ $nombreInstitucion }}" class="fallback-logo">
                    @else
                    <i class="bi bi-mortarboard-fill fallback-icon"></i>
                    @endif
                    <h2>{{ $nombreInstitucion }}</h2>
                    <p>{{ $subtituloInstitucion }}</p>
                </div>
                @endif
            </section>

            {{-- ============ COLUMNA DERECHA: FORMULARIO ============ --}}
            <section class="login-form-panel">
                <div class="brand-mini">
                    @if(!empty($institucion?->logo_path))
                    <img src="{{ $institucion->logoUrl() }}" alt="Logo {{ $nombreInstitucion }}">
                    @else
                    <i class="bi bi-mortarboard-fill fs-3"></i>
                    @endif
                    <span>{{ $nombreInstitucion }}</span>
                </div>

                <h1 class="login-title">Iniciar sesión</h1>
                <p class="login-subtitle">Ingrese sus credenciales para acceder al sistema.</p>

                @if($errors->any())
                <div class="alert alert-danger py-2 small mb-3">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                </div>
                @endif

                @if(session('success'))
                <div class="alert alert-success py-2 small mb-3">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="login-field">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            placeholder="usuario@institucion.edu.ec" autocomplete="username" required autofocus>
                    </div>

                    <div class="login-field">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Contraseña"
                            autocomplete="current-password" required style="padding-right:34px;">
                        <button type="button" class="toggle-password" id="togglePassword"
                            title="Mostrar u ocultar contraseña" aria-label="Mostrar u ocultar contraseña">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember" value="1">
                            <label class="form-check-label small" for="remember">Recordarme</label>
                        </div>
                        <div class="login-links">
                            <a href="{{ route('pre-registro.create') }}">Solicitar matrícula</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Ingresar</span>
                        <i class="bi bi-box-arrow-in-right"></i>
                    </button>
                </form>

                <div class="login-footer">
                    &copy; {{ date('Y') }} {{ $nombreInstitucion }}. Todos los derechos reservados.
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar u ocultar la contraseña sin enviar el formulario.
        document.addEventListener('DOMContentLoaded', function () {
            var boton = document.getElementById('togglePassword');
            var campo = document.getElementById('password');
            var icono = document.getElementById('eyeIcon');

            if (boton && campo && icono) {
                boton.addEventListener('click', function () {
                    var oculta = campo.type === 'password';
                    campo.type = oculta ? 'text' : 'password';
                    icono.classList.toggle('bi-eye', !oculta);
                    icono.classList.toggle('bi-eye-slash', oculta);
                });
            }
        });
    </script>
</body>
</html>
