@extends('layouts.app')

@section('sidebar')
<div class="d-flex flex-column p-3 text-white h-100" style="background: #1a1a2e;">
    <div class="d-flex justify-content-between align-items-center d-lg-none mb-3">
        <span class="fs-5 fw-bold">Admin</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <a href="/dashboard" class="d-flex align-items-center mb-3 text-white text-decoration-none d-none d-lg-flex">
        <i class="bi bi-mortarboard-fill fs-4 me-2"></i>
        <span class="fs-5 fw-bold">Unidad Educativa</span>
    </a>
    <span class="badge bg-warning text-dark mb-2">Administrador</span>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="/admin/pre-registros" class="nav-link text-white {{ request()->is('admin/pre-registros*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text me-2"></i> Solicitudes
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.pre-registros.create') }}" class="nav-link text-white">
                <i class="bi bi-plus-circle me-2"></i> Nueva Solicitud
            </a>
        </li>
        <li class="nav-item mt-2">
            <a href="{{ route('admin.hero-slides.index') }}" class="nav-link text-white {{ request()->is('admin/hero-slides*') ? 'active' : '' }}">
                <i class="bi bi-images me-2"></i> Carrusel Hero
            </a>
        </li>
    </ul>
    <hr>
    <div class="mb-3">
        <form method="POST" action="{{ route('admin.pre-registros.toggle') }}">
            @csrf
            @php $inst = \App\Models\Institucion::first(); @endphp
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="toggleInscripciones" onchange="this.form.submit()" {{ $inst?->pre_registro_activo ? 'checked' : '' }}>
                <label class="form-check-label text-white small" for="toggleInscripciones">
                    Inscripciones: <strong>{{ $inst?->pre_registro_activo ? 'Activas' : 'Inactivas' }}</strong>
                </label>
            </div>
        </form>
    </div>
    <div class="mb-3">
        <label class="d-block text-white small mb-1">Sistema de Evaluación</label>
        <form method="POST" action="{{ route('admin.configuracion.sistema-evaluacion') }}">
            @csrf
            <select name="sistema_evaluacion" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                <option value="quimestre" {{ $inst?->sistema_evaluacion === 'trimestre' ? '' : 'selected' }}>Quimestre (2 por año)</option>
                <option value="trimestre" {{ $inst?->sistema_evaluacion === 'trimestre' ? 'selected' : '' }}>Trimestre (3 por año)</option>
            </select>
        </form>
    </div>
    <hr>
    <div class="dropdown mt-auto">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-2"></i>
            <strong>{{ nombreCorto(auth()->user()->name ?? '') }}</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="/dashboard">Ir al Dashboard</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="/logout">
                    @csrf
                    <button class="dropdown-item" type="submit">Cerrar Sesión</button>
                </form>
            </li>
        </ul>
    </div>
</div>
@endsection
