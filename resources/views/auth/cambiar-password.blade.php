@php
    // Cambio obligatorio de la contraseña inicial.
    // Conecta con: ProfileController@showCambioObligatorio y
    // App\Http\Middleware\EnsurePasswordIsChanged.
    $nombreInstitucion = $institucion->nombre ?? 'Unidad Educativa';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña - {{ $nombreInstitucion }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @if(!empty($institucion?->logo_path))
    <link rel="icon" href="{{ $institucion->logoUrl() }}">
    @endif
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef1f6;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            padding: 24px;
        }
        .cambio-card {
            width: 100%;
            max-width: 520px;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .12);
        }
        .cambio-logo {
            height: 54px;
            width: 54px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="card cambio-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                @if(!empty($institucion?->logo_path))
                <img src="{{ $institucion->logoUrl() }}" alt="Logo {{ $nombreInstitucion }}" class="cambio-logo">
                @else
                <i class="bi bi-mortarboard-fill fs-1 text-primary"></i>
                @endif
                <div>
                    <h1 class="h5 mb-1">Cambiar contraseña</h1>
                    <p class="text-muted small mb-0">{{ $nombreInstitucion }}</p>
                </div>
            </div>

            <div class="alert alert-warning py-2 small">
                <i class="bi bi-shield-lock me-1"></i>
                Su cuenta usa una contraseña temporal. Por seguridad debe cambiarla antes de continuar.
            </div>

            @if($errors->any())
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('perfil.password.obligatorio.update') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           autocomplete="new-password" required autofocus>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">
                        Mínimo 8 caracteres, con al menos una letra y un número.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-key me-1"></i>Actualizar contraseña
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>
