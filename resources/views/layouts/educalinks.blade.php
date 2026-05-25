{{-- 
  Layout Educalinks: Vista general del docente.
  Extiende app.blade.php y agrega sidebar principal con navegación 
  a módulos: Dashboard, Clases, Mensajes, Agenda, Observaciones.
--}}
@extends('layouts.app')

@section('sidebar')
<div class="d-flex flex-column p-3 text-white h-100" style="background: #1a1a2e;">
    <div class="d-flex justify-content-between align-items-center d-lg-none mb-3">
        <span class="fs-5 fw-bold">Unidad Educativa</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <a href="/" class="d-flex align-items-center mb-3 text-white text-decoration-none d-none d-lg-flex">
        <i class="bi bi-mortarboard-fill fs-4 me-2"></i>
        <span class="fs-5 fw-bold">Unidad Educativa</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="/dashboard" class="nav-link text-white {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="/clases" class="nav-link text-white {{ request()->is('clases*') ? 'active' : '' }}">
                <i class="bi bi-book me-2"></i> Mis Clases
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="bi bi-envelope me-2"></i> Mensajes
                <span class="badge bg-danger rounded-pill ms-2" id="notif-badge">0</span>
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="bi bi-calendar-check me-2"></i> Agenda
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="bi bi-exclamation-triangle me-2"></i> Observaciones
            </a>
        </li>
        <li>
            <a href="#" class="nav-link text-white">
                <i class="bi bi-file-text me-2"></i> Circulares
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-2"></i>
            <strong>{{ auth()->user()->name ?? 'Usuario' }}</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="#">Perfil</a></li>
            <li><a class="dropdown-item" href="#">Configuración</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="#">Cerrar Sesión</a>
            </li>
        </ul>
    </div>
</div>
@endsection
