@php
    // Pantalla intermedia para cuentas con varios perfiles.
    // Conecta con: LoginController::showPerfilForm/seleccionarPerfil y User::perfiles().
    $tema = \App\Support\Tema::actual();
    $nombreInstitucion = $institucion->nombre ?? 'Unidad Educativa';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Seleccione su perfil - {{ $nombreInstitucion }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @if(!empty($institucion?->logo_path))
    <link rel="icon" href="{{ $institucion->logoUrl() }}">
    @endif
    <style>
        :root {
            --perfil-brand: {{ $tema['aside'] }};
            --perfil-brand-hover: {{ $tema['hover'] }};
            --perfil-text: {{ $tema['texto'] }};
            --perfil-font-size: {{ $tema['fuente_pt'] }}pt;
        }
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #eef1f6;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: var(--perfil-font-size);
            color: #1f2937;
        }
        .perfil-card {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 18px 35px -12px rgba(0, 0, 0, .25);
            padding: 38px 34px;
        }
        .perfil-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--perfil-brand);
            font-weight: 700;
            margin-bottom: 22px;
        }
        .perfil-brand img {
            height: 40px;
            width: 40px;
            object-fit: contain;
            border-radius: 6px;
        }
        .perfil-titulo {
            font-weight: 800;
            font-size: 1.35rem;
            margin-bottom: 4px;
        }
        .perfil-subtitulo {
            color: #6b7280;
            font-size: .875rem;
            margin-bottom: 24px;
        }
        .perfil-opcion {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .perfil-opcion:hover {
            border-color: var(--perfil-brand);
            background: color-mix(in srgb, var(--perfil-brand) 6%, white);
        }
        .perfil-opcion input { margin: 0; }
        .perfil-opcion .nombre { font-weight: 600; }
        .perfil-opcion .descripcion { font-size: .78rem; color: #6b7280; }
        .btn-perfil {
            background: var(--perfil-brand);
            border: 1px solid var(--perfil-brand);
            color: var(--perfil-text);
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 5px;
            width: 100%;
            margin-top: 8px;
        }
        .btn-perfil:hover,
        .btn-perfil:focus {
            background: var(--perfil-brand-hover);
            border-color: var(--perfil-brand-hover);
            color: var(--perfil-text);
        }
    </style>
</head>
<body>
    <main class="perfil-card">
        <div class="perfil-brand">
            @if(!empty($institucion?->logo_path))
            <img src="{{ $institucion->logoUrl() }}" alt="Logo {{ $nombreInstitucion }}">
            @else
            <i class="bi bi-mortarboard-fill fs-3"></i>
            @endif
            <span>{{ $nombreInstitucion }}</span>
        </div>

        <h1 class="perfil-titulo">Seleccione su perfil</h1>
        <p class="perfil-subtitulo">
            Su cuenta tiene varios perfiles habilitados. Elija con cuál desea ingresar.
            Puede cambiarlo luego desde la barra superior.
        </p>

        <form method="POST" action="{{ route('perfil.activo') }}">
            @csrf

            @foreach($perfiles as $indice => $perfil)
            <label class="perfil-opcion" for="perfil-{{ $perfil }}">
                <input type="radio" name="rol" id="perfil-{{ $perfil }}" value="{{ $perfil }}"
                    {{ $indice === 0 ? 'checked' : '' }} required>
                <span>
                    <span class="nombre d-block">{{ \App\Models\User::etiquetaRol($perfil) }}</span>
                    <span class="descripcion d-block">Accederá con los módulos de este perfil.</span>
                </span>
            </label>
            @endforeach

            <button type="submit" class="btn-perfil">
                <i class="bi bi-box-arrow-in-right me-1"></i>Continuar
            </button>
        </form>
    </main>
</body>
</html>
