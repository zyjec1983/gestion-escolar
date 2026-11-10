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
        @if($rol === 'SECRETARIA')
        @php
            $matriculasActivas = request()->is('admin/pre-registros*') || request()->is('actualizacion*') || request()->is('traslados*');
            $academicoActivo = request()->is('inspeccion/asistencia*') || request()->is('inspeccion/observaciones*') || request()->is('planificaciones*') || request()->is('notas-extraordinarias*');
            $citasSecretariaActivas = request()->is('inspeccion/citas*');
        @endphp
        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Secretaría</span>
        </li>
        <li class="nav-item mt-1">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $matriculasActivas ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuSecretariaMatriculas" aria-expanded="{{ $matriculasActivas ? 'true' : 'false' }}" aria-controls="submenuSecretariaMatriculas">
                <span><i class="bi bi-clipboard-check me-2"></i> Matrículas y admisiones</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $matriculasActivas ? 'show' : '' }}" id="submenuSecretariaMatriculas">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('admin.pre-registros.index') }}" class="nav-link text-white small py-1 {{ request()->is('admin/pre-registros*') ? 'active' : '' }}">
                            <i class="bi bi-inbox me-2"></i> Solicitudes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('actualizacion.index', ['pendientes' => 1]) }}" class="nav-link text-white small py-1 {{ request()->is('actualizacion*') && request()->boolean('pendientes') ? 'active' : '' }}">
                            <i class="bi bi-person-exclamation me-2"></i> Fichas por regularizar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('actualizacion.index') }}" class="nav-link text-white small py-1 {{ request()->is('actualizacion*') && !request()->boolean('pendientes') ? 'active' : '' }}">
                            <i class="bi bi-pencil-square me-2"></i> Actualización de datos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('traslados.index') }}" class="nav-link text-white small py-1 {{ request()->is('traslados*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-left-right me-2"></i> Traslados e historial
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item mt-1">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $academicoActivo ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuSecretariaAcademico" aria-expanded="{{ $academicoActivo ? 'true' : 'false' }}" aria-controls="submenuSecretariaAcademico">
                <span><i class="bi bi-journal-check me-2"></i> Académico y auditoría</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $academicoActivo ? 'show' : '' }}" id="submenuSecretariaAcademico">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.asistencia.estadisticas') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/asistencia/estadisticas*') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-line me-2"></i> Reportes de asistencia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.observaciones.reporte.alumno') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/observaciones/reporte*') ? 'active' : '' }}">
                            <i class="bi bi-person-lines-fill me-2"></i> Observaciones por alumno
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.observaciones.estadisticas') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/observaciones/estadisticas*') ? 'active' : '' }}">
                            <i class="bi bi-graph-up me-2"></i> Estadísticas de observaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('planificaciones.index') }}" class="nav-link text-white small py-1 {{ request()->is('planificaciones') || request()->is('planificaciones/*') && !request()->is('planificaciones/bitacora*') ? 'active' : '' }}">
                            <i class="bi bi-journal-bookmark me-2"></i> Planificación y auditoría
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('planificaciones.bitacora') }}" class="nav-link text-white small py-1 {{ request()->is('planificaciones/bitacora*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Bitácora de planificaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('notas-extraordinarias.index') }}" class="nav-link text-white small py-1 {{ request()->is('notas-extraordinarias') && !request()->is('notas-extraordinarias/bitacora*') ? 'active' : '' }}">
                            <i class="bi bi-shield-exclamation me-2"></i> Corrección de notas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('notas-extraordinarias.bitacora') }}" class="nav-link text-white small py-1 {{ request()->is('notas-extraordinarias/bitacora*') ? 'active' : '' }}">
                            <i class="bi bi-journal-text me-2"></i> Bitácora de notas
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item mt-1">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $citasSecretariaActivas ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuSecretariaCitas" aria-expanded="{{ $citasSecretariaActivas ? 'true' : 'false' }}" aria-controls="submenuSecretariaCitas">
                <span><i class="bi bi-calendar-event me-2"></i> Citas</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $citasSecretariaActivas ? 'show' : '' }}" id="submenuSecretariaCitas">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.citas.agendar') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/citas/agendar') ? 'active' : '' }}">
                            <i class="bi bi-calendar-plus me-2"></i> Agendar cita
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.citas.historial') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/citas/historial*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard2-data me-2"></i> Historial de citas
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @endif
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
        @if(in_array($rol, ['INSPECCION', 'ADMIN', 'RECTORA', 'VICERRECTORA', 'DECE']))
        @php
            $citasInspeccion = request()->is('inspeccion/citas*');
        @endphp
        <li class="nav-item mt-2">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $citasInspeccion ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuCitasInspeccion" aria-expanded="{{ $citasInspeccion ? 'true' : 'false' }}" aria-controls="submenuCitasInspeccion">
                <span><i class="bi bi-calendar-event me-2"></i> Citas</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $citasInspeccion ? 'show' : '' }}" id="submenuCitasInspeccion">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.citas.agendar') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/citas/agendar') ? 'active' : '' }}">
                            <i class="bi bi-calendar-plus me-2"></i> Agendar cita
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.citas.historial') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/citas/historial*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard2-data me-2"></i> Historial de citas
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @endif
        @if($rol === 'INSPECCION')
        @php
            $asistenciaActivas = request()->is('inspeccion/asistencia*');
        @endphp
        <li class="nav-item mt-2">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $asistenciaActivas ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuAsistencia" aria-expanded="{{ $asistenciaActivas ? 'true' : 'false' }}" aria-controls="submenuAsistencia">
                <span><i class="bi bi-calendar-check me-2"></i> Asistencia</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $asistenciaActivas ? 'show' : '' }}" id="submenuAsistencia">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.asistencia.index') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/asistencia') ? 'active' : '' }}">
                            <i class="bi bi-clipboard-check me-2"></i> Tomar asistencia
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.asistencia.estadisticas') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/asistencia/estadisticas*') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-line me-2"></i> Estadísticas
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @endif
        @if(in_array($rol, ['INSPECCION', 'RECTORA', 'VICERRECTORA', 'DECE']))
        @php
            $observacionesActivas = request()->is('inspeccion/observaciones*');
        @endphp
        <li class="nav-item mt-2">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $observacionesActivas ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuObservaciones" aria-expanded="{{ $observacionesActivas ? 'true' : 'false' }}" aria-controls="submenuObservaciones">
                <span><i class="bi bi-exclamation-triangle me-2"></i> Observaciones</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $observacionesActivas ? 'show' : '' }}" id="submenuObservaciones">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.observaciones.index') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/observaciones') || request()->is('inspeccion/observaciones/index') ? 'active' : '' }}">
                            <i class="bi bi-send me-2"></i> Enviar observación
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.observaciones.reporte.alumno') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/observaciones/reporte/alumno*') ? 'active' : '' }}">
                            <i class="bi bi-person me-2"></i> Reporte por alumno
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.observaciones.estadisticas') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/observaciones/estadisticas*') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-line me-2"></i> Estadísticas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inspeccion.observaciones.tipos.index') }}" class="nav-link text-white small py-1 {{ request()->is('inspeccion/observaciones/tipos*') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill me-2"></i> Tipos de observación
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @endif
        @if(in_array($rol, ['ADMIN', 'RECTORA', 'VICERRECTORA']))
        <li class="nav-item mt-2">
            <a href="{{ route('admin.pre-registros.index') }}"
               class="nav-link text-white {{ request()->is('admin/pre-registros*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check me-2"></i> Solicitudes de matrícula
            </a>
        </li>
        @if($rol === 'ADMIN')
        <li class="nav-item">
            <a href="{{ route('traslados.index') }}"
               class="nav-link text-white {{ request()->is('traslados*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right me-2"></i> Traslados e historial
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('notas-extraordinarias.index') }}"
               class="nav-link text-white {{ request()->is('notas-extraordinarias') && !request()->is('notas-extraordinarias/bitacora*') ? 'active' : '' }}">
                <i class="bi bi-shield-exclamation me-2"></i> Corrección de notas
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('notas-extraordinarias.bitacora') }}"
               class="nav-link text-white {{ request()->is('notas-extraordinarias/bitacora*') ? 'active' : '' }}">
                <i class="bi bi-journal-text me-2"></i> Bitácora de notas
            </a>
        </li>
        @endif
        @endif
        @if(in_array($rol, ['ADMIN', 'RECTORA']))
        <li class="nav-item">
            <a href="{{ route('actualizacion.index') }}"
               class="nav-link text-white {{ request()->is('actualizacion*') ? 'active' : '' }}">
                <i class="bi bi-pencil-square me-2"></i> Actualización de datos
            </a>
        </li>
        @endif
        @if($rol === 'ADMIN')
        <li class="nav-item mt-2">
            <a href="{{ route('admin.configuracion.index') }}" class="nav-link text-white {{ request()->is('admin/configuracion*') || request()->is('admin/hero-slides*') ? 'active' : '' }}">
                <i class="bi bi-gear me-2"></i> Configuración
            </a>
        </li>
        @endif
        @if(in_array($rol, ['DOCENTE', 'ADMIN', 'RECTORA', 'VICERRECTORA']))
        <li class="nav-item mt-2">
            <a href="{{ route('planificaciones.index') }}" class="nav-link text-white {{ request()->is('planificaciones') || request()->is('planificaciones/*') && !request()->is('planificaciones/bitacora*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark me-2"></i> {{ $rol === 'DOCENTE' ? 'Planificación' : 'Planificación y auditoría' }}
            </a>
        </li>
        @if($rol !== 'DOCENTE')
        <li class="nav-item">
            <a href="{{ route('planificaciones.bitacora') }}" class="nav-link text-white {{ request()->is('planificaciones/bitacora*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text me-2"></i> Bitácora de planificaciones
            </a>
        </li>
        @endif
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
