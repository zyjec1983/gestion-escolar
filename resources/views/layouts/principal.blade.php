@extends('layouts.app')

@php
    $nombreInstitucion = $institucion->nombre ?? 'Unidad Educativa';
@endphp

@push('styles')
<style>
    .chevron-icon { transition: transform 0.2s ease; }
    [aria-expanded="true"] .chevron-icon { transform: rotate(180deg); }
</style>
@endpush

@section('sidebar')
{{-- Aside principal: el color lo define la apariencia global (layouts/app). --}}
<div class="d-flex flex-column p-3 text-white h-100" style="background: var(--aside-bg);">
    <div class="d-flex justify-content-between align-items-center d-md-none mb-3">
        <span class="fs-5 fw-bold">{{ $nombreInstitucion }}</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <a href="/dashboard" class="d-flex align-items-center mb-3 text-white text-decoration-none d-none d-md-flex">
        @if(!empty($institucion?->logo_path))
        <img src="{{ $institucion->logoUrl() }}" alt="Logo {{ $nombreInstitucion }}"
            class="bg-white rounded p-1 me-2" style="height:36px;width:36px;object-fit:contain;">
        @else
        <i class="bi bi-mortarboard-fill fs-4 me-2"></i>
        @endif
        <span class="fs-5 fw-bold">{{ $nombreInstitucion }}</span>
    </a>
    <hr>
    @php
        // El menú y los permisos se resuelven con el perfil activo (multirol).
        $rol = auth()->user()->perfilActivo();
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
        @if($rol === 'ADMIN')
        {{--
            Menú exclusivo del rol ADMIN, ordenado por función en cinco secciones.

            Qué hace: agrupa los módulos administrables para que no existan
            pestañas repetidas ni accesos sueltos al final del menú.
            - Portal y contenido: Contenido institucional (chevron con
              Institución, Textos e imágenes, Pre-registro e Historia), Comunidad
              educativa y Servicios y descargas (chevron con Útiles escolares y
              Descargas). Cada sección es una vista independiente, sin pestañas.
            - Apariencia: "Apariencia del sistema" con chevron para General,
              Carrusel del portal, Carrusel del login y Apariencia.
            - Usuarios y seguridad: cuentas, roles y contraseñas.
            - Control académico: matrículas, fechas de carga de notas, circulares,
              observaciones y registros académicos. Aquí se agregará luego
              "Materias y asignaturas" y la asignación de materias a los docentes.
            - Auditoría: historial de cambios.
            Conecta con: DashboardController@admin, Admin\SitioController,
            ComunidadController, HeroSlideController, CircularController,
            ObservacionAdminController, VentanaNotaController, UserController,
            HistorialController, ConfiguracionController y los módulos de
            Secretaría (pre-registros, traslados, notas extraordinarias y
            actualización de datos).
            Rutas: /admin/sitio, /admin/comunidad, /admin/configuracion,
                   /admin/circulares, /admin/observaciones, /admin/ventanas-notas,
                   /admin/usuarios, /admin/historial, /admin/pre-registros,
                   /traslados, /notas-extraordinarias y /actualizacion.
        --}}
        @php
            // Estado del sitio y de configuración para que el menú lateral
            // marque y despliegue el grupo correcto.
            $enTextos = request()->is('admin/sitio');
            $enPreregistro = request()->is('admin/sitio/preregistro*');
            $enHistoria = request()->is('admin/sitio/historia*');
            $enUtiles = request()->is('admin/sitio/utiles*');
            $enDescargas = request()->is('admin/sitio/descargas*');
            $enInstitucion = request()->is('admin/configuracion/institucion*');
            $enContenidoInstitucional = $enTextos || $enPreregistro || $enHistoria || $enInstitucion;
            $enServicios = $enUtiles || $enDescargas;

            // Cada sección de configuración es una vista independiente.
            $enConfigGeneral = request()->is('admin/configuracion');
            $enConfigCarrusel = request()->is('admin/configuracion/carrusel');
            $enConfigCarruselLogin = request()->is('admin/configuracion/carrusel-login*');
            $enConfigApariencia = request()->is('admin/configuracion/apariencia*');
            $enAparienciaSistema = $enConfigGeneral || $enConfigCarrusel
                || $enConfigCarruselLogin || $enConfigApariencia;

            $registrosAcademicosActivos = request()->is('notas-extraordinarias*')
                || request()->is('traslados*')
                || request()->is('actualizacion*');
        @endphp

        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Portal y contenido</span>
        </li>
        <li class="nav-item mt-1">
            <a role="button"
               class="nav-link text-white d-flex justify-content-between align-items-center {{ $enContenidoInstitucional ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuContenidoInstitucional"
               aria-expanded="{{ $enContenidoInstitucional ? 'true' : 'false' }}"
               aria-controls="submenuContenidoInstitucional">
                <span><i class="bi bi-file-text me-2"></i> Contenido institucional</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $enContenidoInstitucional ? 'show' : '' }}" id="submenuContenidoInstitucional">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('admin.configuracion.institucion') }}"
                           class="nav-link text-white small py-1 {{ $enInstitucion ? 'active' : '' }}">
                            <i class="bi bi-building me-2"></i> Institución
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.sitio.index') }}"
                           class="nav-link text-white small py-1 {{ $enTextos ? 'active' : '' }}">
                            <i class="bi bi-fonts me-2"></i> Textos e imágenes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.sitio.preregistro') }}"
                           class="nav-link text-white small py-1 {{ $enPreregistro ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-text me-2"></i> Pre-registro
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.sitio.historia') }}"
                           class="nav-link text-white small py-1 {{ $enHistoria ? 'active' : '' }}">
                            <i class="bi bi-book-half me-2"></i> Historia
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.comunidad.index') }}"
               class="nav-link text-white {{ request()->is('admin/comunidad*') ? 'active' : '' }}">
                <i class="bi bi-people-fill me-2"></i> Comunidad educativa
            </a>
        </li>
        <li class="nav-item">
            <a role="button"
               class="nav-link text-white d-flex justify-content-between align-items-center {{ $enServicios ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuServiciosDescargas"
               aria-expanded="{{ $enServicios ? 'true' : 'false' }}"
               aria-controls="submenuServiciosDescargas">
                <span><i class="bi bi-folder2-open me-2"></i> Servicios y descargas</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $enServicios ? 'show' : '' }}" id="submenuServiciosDescargas">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('admin.sitio.utiles') }}"
                           class="nav-link text-white small py-1 {{ $enUtiles ? 'active' : '' }}">
                            <i class="bi bi-backpack2 me-2"></i> Útiles escolares
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.sitio.descargas') }}"
                           class="nav-link text-white small py-1 {{ $enDescargas ? 'active' : '' }}">
                            <i class="bi bi-cloud-arrow-down me-2"></i> Descargas
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Apariencia</span>
        </li>
        <li class="nav-item mt-1">
            <a role="button"
               class="nav-link text-white d-flex justify-content-between align-items-center {{ $enAparienciaSistema ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuAparienciaSistema"
               aria-expanded="{{ $enAparienciaSistema ? 'true' : 'false' }}"
               aria-controls="submenuAparienciaSistema">
                <span><i class="bi bi-palette me-2"></i> Apariencia del sistema</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $enAparienciaSistema ? 'show' : '' }}" id="submenuAparienciaSistema">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('admin.configuracion.index') }}"
                           class="nav-link text-white small py-1 {{ $enConfigGeneral ? 'active' : '' }}">
                            <i class="bi bi-sliders me-2"></i> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.configuracion.carrusel') }}"
                           class="nav-link text-white small py-1 {{ $enConfigCarrusel ? 'active' : '' }}">
                            <i class="bi bi-images me-2"></i> Carrusel del portal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.configuracion.carrusel-login') }}"
                           class="nav-link text-white small py-1 {{ $enConfigCarruselLogin ? 'active' : '' }}">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Carrusel del login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.configuracion.apariencia') }}"
                           class="nav-link text-white small py-1 {{ $enConfigApariencia ? 'active' : '' }}">
                            <i class="bi bi-palette2 me-2"></i> Apariencia
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Usuarios y seguridad</span>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('admin.usuarios.index') }}"
               class="nav-link text-white {{ request()->is('admin/usuarios*') ? 'active' : '' }}">
                <i class="bi bi-person-gear me-2"></i> Usuarios y roles
            </a>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Control académico</span>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('admin.pre-registros.index') }}"
               class="nav-link text-white {{ request()->is('admin/pre-registros*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check me-2"></i> Matrículas e inscripciones
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.ventanas-notas.index') }}"
               class="nav-link text-white {{ request()->is('admin/ventanas-notas*') ? 'active' : '' }}">
                <i class="bi bi-calendar-range me-2"></i> Fecha de Cargar notas
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.circulares.index') }}"
               class="nav-link text-white {{ request()->is('admin/circulares*') ? 'active' : '' }}">
                <i class="bi bi-megaphone me-2"></i> Circulares y comunicados
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.observaciones.index') }}"
               class="nav-link text-white {{ request()->is('admin/observaciones*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle me-2"></i> Moderación de observaciones
            </a>
        </li>
        <li class="nav-item mt-1">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $registrosAcademicosActivos ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuAdminRegistros" aria-expanded="{{ $registrosAcademicosActivos ? 'true' : 'false' }}" aria-controls="submenuAdminRegistros">
                <span><i class="bi bi-journal-check me-2"></i> Registros académicos</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $registrosAcademicosActivos ? 'show' : '' }}" id="submenuAdminRegistros">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
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
                    <li class="nav-item">
                        <a href="{{ route('traslados.index') }}" class="nav-link text-white small py-1 {{ request()->is('traslados*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-left-right me-2"></i> Traslados e historial
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('actualizacion.index') }}" class="nav-link text-white small py-1 {{ request()->is('actualizacion*') ? 'active' : '' }}">
                            <i class="bi bi-pencil-square me-2"></i> Actualización de datos
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Auditoría</span>
        </li>
        <li class="nav-item mt-1">
            <a href="{{ route('admin.historial.index') }}"
               class="nav-link text-white {{ request()->is('admin/historial*') ? 'active' : '' }}">
                <i class="bi bi-clock-history me-2"></i> Historial de cambios
            </a>
        </li>
        @else
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
            <a href="{{ route('representante.datos.index') }}"
               class="nav-link text-white {{ request()->is('mis-datos*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard me-2"></i> Mis datos
            </a>
        </li>
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
        @if(in_array($rol, ['INSPECCION', 'RECTORA', 'VICERRECTORA', 'DECE']))
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
        @if(in_array($rol, ['DECE', 'RECTORA', 'VICERRECTORA']))
        @php
            $deceActivo = request()->is('dece*');
        @endphp
        <li class="nav-item mt-3">
            <span class="text-uppercase text-white-50 small fw-semibold px-2">Consejería estudiantil</span>
        </li>
        <li class="nav-item mt-1">
            <a role="button" class="nav-link text-white d-flex justify-content-between align-items-center {{ $deceActivo ? 'active' : '' }}"
               data-bs-toggle="collapse" href="#submenuDece" aria-expanded="{{ $deceActivo ? 'true' : 'false' }}" aria-controls="submenuDece">
                <span><i class="bi bi-heart-pulse me-2"></i> DECE</span>
                <i class="bi bi-chevron-down chevron-icon"></i>
            </a>
            <div class="collapse {{ $deceActivo ? 'show' : '' }}" id="submenuDece">
                <ul class="nav nav-pills flex-column ms-3 mt-1">
                    <li class="nav-item">
                        <a href="{{ route('dece.expedientes.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/expedientes*') ? 'active' : '' }}">
                            <i class="bi bi-folder2-open me-2"></i> Expedientes
                        </a>
                    </li>
                    @if(in_array($rol, ['DECE']))
                    <li class="nav-item">
                        <a href="{{ route('dece.atenciones.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/atenciones*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard2-pulse me-2"></i> Atenciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dece.citas.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/citas*') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-week me-2"></i> Agenda de citas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dece.acuerdos.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/acuerdos*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-check me-2"></i> Acuerdos y actas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dece.nee.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/nee*') ? 'active' : '' }}">
                            <i class="bi bi-universal-access me-2"></i> NEE y diagnósticos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dece.talleres.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/talleres*') ? 'active' : '' }}">
                            <i class="bi bi-people me-2"></i> Talleres
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{ route('dece.derivaciones.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/derivaciones*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-up-right-circle me-2"></i> Derivaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('dece.reportes.index') }}" class="nav-link text-white small py-1 {{ request()->is('dece/reportes*') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-line me-2"></i> Reportes
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        @endif
        @if(in_array($rol, ['DOCENTE', 'INSPECCION']))
        <li class="nav-item mt-2">
            <a href="{{ route('dece.derivaciones.create') }}" class="nav-link text-white {{ request()->is('dece/derivaciones*') ? 'active' : '' }}">
                <i class="bi bi-arrow-up-right-circle me-2"></i> Derivar al DECE
            </a>
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
        @if(in_array($rol, ['RECTORA', 'VICERRECTORA']))
        <li class="nav-item mt-2">
            <a href="{{ route('admin.pre-registros.index') }}"
               class="nav-link text-white {{ request()->is('admin/pre-registros*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check me-2"></i> Solicitudes de matrícula
            </a>
        </li>
        @endif
        @if($rol === 'RECTORA')
        <li class="nav-item">
            <a href="{{ route('actualizacion.index') }}"
               class="nav-link text-white {{ request()->is('actualizacion*') ? 'active' : '' }}">
                <i class="bi bi-pencil-square me-2"></i> Actualización de datos
            </a>
        </li>
        @endif
        @if(in_array($rol, ['DOCENTE', 'RECTORA', 'VICERRECTORA']))
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
            @if(count(auth()->user()->perfiles()) > 1)
            <li><a class="dropdown-item" href="{{ route('perfil.seleccionar') }}">Cambiar de perfil</a></li>
            @endif
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
