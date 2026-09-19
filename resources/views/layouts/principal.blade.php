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
    @php
        $rol = auth()->user()->rol;
        $esDocente = $rol === 'DOCENTE';
        $esRep = $rol === 'REPRESENTANTE';
        $esEstudiante = $rol === 'ESTUDIANTE';
    @endphp
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="/dashboard" class="nav-link text-white {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        @if($esDocente || $esEstudiante)
        <li class="nav-item">
            <a href="/clases" class="nav-link text-white {{ request()->is('clases*') ? 'active' : '' }}">
                <i class="bi bi-book me-2"></i> Mis Clases
            </a>
        </li>
        @endif
        @if($esDocente)
        <li class="nav-item mt-2">
            <a href="{{ route('docente.asistencia.index') }}" class="nav-link text-white {{ request()->is('docente/asistencia*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check me-2"></i> Asistencia
            </a>
        </li>
        @endif
        @if($esDocente || $esRep)
        <li class="nav-item">
            <a href="{{ $esDocente ? route('docente.observaciones.index') : route('representante.observaciones') }}"
               class="nav-link text-white {{ request()->is('docente/observaciones*') || request()->is('observaciones') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle me-2"></i> Observaciones
            </a>
        </li>
        @endif
        @if($esRep)
        <li class="nav-item">
            <a href="{{ route('representante.agendas') }}"
               class="nav-link text-white {{ request()->is('agendas') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i> Agendas de mis hijos
            </a>
        </li>
        @endif
        @if($esDocente || $esRep)
        @php
            $citasPendientes = $esDocente
                ? \App\Models\Cita::where('docente_id', auth()->id())->pendientes()->count()
                : \App\Models\Cita::where('solicitante_type', \App\Models\User::class)
                    ->where('solicitante_id', auth()->id())
                    ->pendientes()
                    ->count();
        @endphp
        <li class="nav-item">
            <a href="{{ $esDocente ? route('docente.citas.index') : route('representante.citas') }}"
               class="nav-link text-white d-flex justify-content-between align-items-center {{ request()->is('docente/citas*') || request()->is('citas') ? 'active' : '' }}">
                <span><i class="bi bi-calendar-event me-2"></i> Citas</span>
                @if($citasPendientes > 0)
                    <span class="badge rounded-pill bg-warning text-dark">{{ $citasPendientes }}</span>
                @endif
            </a>
        </li>
        @endif
        @php
            $noLeidosMensajes = \App\Models\Mensaje::where('destinatario_id', auth()->id())
                ->where('borrado_destinatario', false)
                ->where('leido', false)->count();
        @endphp
        <li class="nav-item">
            <a href="{{ route('mensajes.index') }}" class="nav-link text-white d-flex justify-content-between align-items-center {{ request()->is('mensajes*') ? 'active' : '' }}">
                <span><i class="bi bi-envelope me-2"></i> Mensajes</span>
                @if($noLeidosMensajes > 0)
                    <span class="badge rounded-pill bg-danger">{{ $noLeidosMensajes }}</span>
                @endif
            </a>
        </li>
        @if($esDocente)
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
        @endif
        @if(in_array($rol, ['ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA', 'INSPECCION', 'DECE']))
        <li class="nav-item mt-2">
            <a href="{{ route('directivo.citas.historial') }}" class="nav-link text-white {{ request()->is('directivo/citas*') ? 'active' : '' }}">
                <i class="bi bi-clipboard2-data me-2"></i> Historial de citas
            </a>
        </li>
        @endif
        @if(in_array($rol, ['DOCENTE', 'ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA']))
        <li class="nav-item mt-2">
            <a href="{{ route('planificaciones.index') }}" class="nav-link text-white {{ request()->is('planificaciones*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark me-2"></i> Planificación
            </a>
        </li>
        @endif
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-2"></i>
            <strong>{{ nombreCorto(auth()->user()->name ?? '') }}</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="{{ route('perfil.index') }}">Perfil</a></li>
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
