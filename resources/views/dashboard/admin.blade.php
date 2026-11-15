@extends('layouts.principal')

@section('titulo', 'Panel de Administración')

{{--
    Panel exclusivo del rol ADMIN.

    Qué hace: concentra los accesos a los módulos administrables y muestra
    contadores institucionales, los últimos cambios y las circulares recientes.
    Conecta con: DashboardController@admin, HistorialCambio, Circular y los
    controladores del panel.
    Ruta: GET /dashboard (dashboard).
--}}
@push('styles')
<style>
    .admin-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .admin-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 .6rem 1.2rem rgba(0, 0, 0, .15);
    }
    .admin-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .75rem;
        font-size: 1.35rem;
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Panel de Administración</h4>
            <small class="text-muted">
                {{ $usuario['nombre_corto'] ?? $usuario['nombre'] ?? '' }} · {{ $usuario['periodo_lectivo'] ?? '' }}
            </small>
        </div>
        <a href="{{ route('admin.configuracion.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear me-1"></i>Configuración
        </a>
    </div>

    {{-- Contadores rápidos --}}
    <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-6">
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #1a1a2e, #4361ee); color:#fff;">
                <div class="card-body">
                    <div class="fs-4 fw-bold">{{ number_format($kpis['estudiantes'], 0, ',', '.') }}</div>
                    <div class="small text-white-50">Estudiantes</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0b3d2e, #2d6a4f); color:#fff;">
                <div class="card-body">
                    <div class="fs-4 fw-bold">{{ $kpis['usuarios'] }}</div>
                    <div class="small text-white-50">Cuentas</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #7a4a00, #f59f00); color:#fff;">
                <div class="card-body">
                    <div class="fs-4 fw-bold">{{ $kpis['circulares'] }}</div>
                    <div class="small text-white-50">Circulares</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4a1d7a, #7c3aed); color:#fff;">
                <div class="card-body">
                    <div class="fs-4 fw-bold">{{ $kpis['comunidad'] }}</div>
                    <div class="small text-white-50">Comunidad</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #7a0c0c, #e63946); color:#fff;">
                <div class="card-body">
                    <div class="fs-4 fw-bold">{{ $kpis['observaciones'] }}</div>
                    <div class="small text-white-50">Observaciones</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0c4a6e, #0ea5e9); color:#fff;">
                <div class="card-body">
                    <div class="fs-4 fw-bold">{{ $kpis['ventanas'] }}</div>
                    <div class="small text-white-50">Ventanas activas</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Accesos a los módulos administrables --}}
    <h6 class="text-muted text-uppercase small fw-semibold mb-3">Módulos de administración</h6>
    <div class="row g-3 mb-4 row-cols-1 row-cols-md-2 row-cols-xl-4">
        <div class="col">
            <a href="{{ route('admin.sitio.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-primary-subtle text-primary"><i class="bi bi-globe2"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Sitio web</h6>
                            <p class="text-muted small mb-0">Textos, imágenes, historia, útiles y descargas del portal.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.comunidad.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-success-subtle text-success"><i class="bi bi-people-fill"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Comunidad educativa</h6>
                            <p class="text-muted small mb-0">Cargo, foto, nombres, responsabilidad y área.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.configuracion.carrusel') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-warning-subtle text-warning"><i class="bi bi-images"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Carrusel del portal</h6>
                            <p class="text-muted small mb-0">Fotos y velocidad del carrusel de la página de inicio.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.configuracion.carrusel-login') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-info-subtle text-info"><i class="bi bi-box-arrow-in-right"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Carrusel del login</h6>
                            <p class="text-muted small mb-0">Imágenes independientes para el inicio de sesión.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.circulares.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-danger-subtle text-danger"><i class="bi bi-megaphone"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Circulares</h6>
                            <p class="text-muted small mb-0">Publique circulares y avise por correo y SMS.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.observaciones.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-secondary-subtle text-secondary"><i class="bi bi-exclamation-triangle"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Observaciones</h6>
                            <p class="text-muted small mb-0">Elimine observaciones enviadas por error.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.ventanas-notas.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-primary-subtle text-primary"><i class="bi bi-calendar-range"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Fechas de notas</h6>
                            <p class="text-muted small mb-0">Habilite o deshabilite la carga por parcial.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.usuarios.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-success-subtle text-success"><i class="bi bi-person-gear"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Usuarios</h6>
                            <p class="text-muted small mb-0">Cree cuentas y restablezca contraseñas.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.historial.index') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Historial de cambios</h6>
                            <p class="text-muted small mb-0">Auditoría de todas las acciones del panel.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.configuracion.apariencia') }}" class="text-decoration-none">
                <div class="card admin-card shadow-sm h-100">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="admin-icon bg-info-subtle text-info"><i class="bi bi-palette"></i></span>
                        <div>
                            <h6 class="mb-1 text-dark">Color y apariencia</h6>
                            <p class="text-muted small mb-0">Elija la paleta del menú y el tamaño de fuente.</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimos cambios</h6>
                    <a href="{{ route('admin.historial.index') }}" class="btn btn-sm btn-outline-primary">Ver todo</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($cambios_recientes as $cambio)
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <strong class="small">{{ $cambio->descripcion }}</strong><br>
                            <small class="text-muted">
                                {{ $cambio->usuario?->nombreCompleto() ?? 'Sistema' }} ·
                                {{ $cambio->created_at?->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <span class="badge bg-light text-dark border">{{ $cambio->modulo }}</span>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">Sin cambios registrados.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-megaphone me-2"></i>Circulares recientes</h6>
                    <a href="{{ route('admin.circulares.index') }}" class="btn btn-sm btn-outline-primary">Gestionar</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($circulares_recientes as $circular)
                    <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <strong class="small">{{ $circular->titulo }}</strong><br>
                            <small class="text-muted">{{ $circular->fecha_publicacion?->format('d/m/Y') }}</small>
                        </div>
                        @if($circular->archivo_url)
                        <a href="{{ $circular->archivo_url }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                        @endif
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">No hay circulares publicadas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
