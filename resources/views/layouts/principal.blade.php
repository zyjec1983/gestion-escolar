@extends('layouts.app')

@push('styles')
<style>
    .chevron-icon { transition: transform 0.2s ease; }
    [aria-expanded="true"] .chevron-icon { transform: rotate(180deg); }
</style>
@endpush

@section('sidebar')
<div class="d-flex flex-column p-3 text-white h-100" style="background: #1a1a2e;">
    <div class="d-flex justify-content-between align-items-center d-lg-none mb-3">
        <span class="fs-5 fw-bold">Unidad Educativa</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <a href="/dashboard" class="d-flex align-items-center mb-3 text-white text-decoration-none d-none d-lg-flex">
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
        <li class="nav-item">
            <a href="/clases" class="nav-link text-white {{ request()->is('clases*') ? 'active' : '' }}">
                <i class="bi bi-book me-2"></i> Mis Clases
            </a>
        </li>
        <li class="nav-item mt-2">
            <a href="{{ route('docente.asistencia.index') }}" class="nav-link text-white {{ request()->is('docente/asistencia*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check me-2"></i> Asistencia
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('docente.observaciones.index') }}" class="nav-link text-white {{ request()->is('docente/observaciones*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle me-2"></i> Observaciones
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('docente.citas.index') }}" class="nav-link text-white {{ request()->is('docente/citas*') ? 'active' : '' }}">
                <i class="bi bi-calendar-event me-2"></i> Citas
            </a>
        </li>
        @php
            $notasActivo = request()->is('docente/notas*') || request()->is('docente/actas*');
        @endphp
        <li class="nav-item mt-2">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $notasActivo ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuNotas" aria-expanded="{{ $notasActivo ? 'true' : 'false' }}" aria-controls="submenuNotas">
                <span><i class="bi bi-file-earmark-text me-2"></i> Notas</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $notasActivo ? 'show' : '' }}" id="submenuNotas">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('docente.notas.index') }}" class="nav-link text-white small py-1 {{ request()->is('docente/notas') ? 'active' : '' }}">
                            <i class="bi bi-pencil-square me-2"></i> Cargar Notas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('docente.actas.index') }}" class="nav-link text-white small py-1 {{ request()->is('docente/actas*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text me-2"></i> Actas
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-2"></i>
            <strong>{{ nombreCorto(auth()->user()->name ?? '') }}</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="#">Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item" type="submit">Cerrar Sesión</button>
                </form>
            </li>
        </ul>
    </div>
</div>
@endsection
