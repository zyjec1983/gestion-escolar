{{-- 
  Layout Aula: Entorno de Aula Virtual (LMA).
  Extiende el layout principal, manteniendo la navegaci├│n principal,
  pero reemplazando el sidebar con herramientas pedag├│gicas (men├║ del aula)
  y agregando un right-panel fijo con el listado de estudiantes inscritos.
  
  El cambio de layout ocurre en InicioClaseController:
  - return view('aula.virtual', ['layout' => 'aula'])
    ->with(['menu' => $menuItems, 'estudiantes' => $estudiantesInscritos]);
    
  En Blade: @extends("layouts.{$layout}") se resuelve a layouts.aula
--}}
@extends('layouts.app')

@section('sidebar')
<div class="d-flex flex-column p-3 text-white h-100" style="background: #0d1b2a;">
    <div class="d-flex justify-content-between align-items-center d-lg-none mb-3">
        <span class="fs-6 fw-bold">Aula Virtual</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="d-flex align-items-center mb-3 text-white text-decoration-none d-none d-lg-flex">
        <i class="bi bi-laptop fs-4 me-2"></i>
        <span class="fs-6 fw-bold">Aula Virtual</span>
    </div>
    <small class="text-secondary mb-2">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        @foreach($menu_aula_virtual ?? [] as $item)
        <li class="nav-item">
            @if(($item['url'] ?? null) && (!isset($es_docente) || $item['url'] !== '#docente'))
                <a href="{{ $item['url'] }}" class="nav-link text-white {{ ($item['active'] ?? false) ? 'active' : '' }}">
                    <i class="bi bi-{{ $item['icon'] ?? 'star' }} me-2"></i> {{ $item['label'] }}
                </a>
            @else
                <span class="nav-link text-secondary d-flex align-items-center" style="cursor: not-allowed;">
                    <i class="bi bi-{{ $item['icon'] ?? 'star' }} me-2"></i> {{ $item['label'] }}
                    <small class="ms-auto text-secondary" title="Pr├│ximamente">Pr├│ximamente</small>
                </span>
            @endif
        </li>
        @endforeach
    </ul>
    <hr>
    <a href="{{ $url_volver ?? '/clases' }}" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Clases
    </a>
</div>
@endsection

@section('contenido')
<div class="row">
    <div class="{{ in_array(auth()->user()->rol, ['ESTUDIANTE', 'REPRESENTANTE']) ? 'col-12' : 'col-md-9' }}">
        @yield('contenido-aula')
    </div>
    @if(!in_array(auth()->user()->rol, ['ESTUDIANTE', 'REPRESENTANTE']))
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people-fill me-1"></i> Estudiantes Inscritos</span>
                <div class="d-flex align-items-center gap-2">
                    @if($checkbox_estudiantes ?? false)
                        <span class="form-check form-switch m-0">
                            <input type="checkbox" class="form-check-input" id="selTodosEstudiantes" form="frmAgenda" checked>
                            <label class="form-check-label small text-nowrap" for="selTodosEstudiantes">Todos</label>
                        </span>
                    @endif
                    <span class="badge bg-light text-dark">{{ count($estudiantes_inscritos ?? []) }}</span>
                    <button class="btn btn-sm btn-outline-light d-md-none ms-1 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#estudiantesPanel" aria-expanded="true">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="collapse show d-md-block" id="estudiantesPanel">
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @foreach($estudiantes_inscritos ?? [] as $est)
                        <li class="list-group-item d-flex align-items-center py-2">
                            @if($checkbox_estudiantes ?? false)
                                <input type="checkbox" class="form-check-input me-2 se-alumno" name="alumnos[]" form="frmAgenda" value="{{ $est['alumno_id'] }}" data-nombre="{{ $est['apellidos_nombres'] }}" checked>
                                <small class="flex-grow-1">{{ $est['apellidos_nombres'] }}</small>
                            @else
                                <i class="bi bi-person-circle me-2 text-secondary"></i>
                                <small>{{ $est['apellidos_nombres'] }}</small>
                            @endif
                            @if($est['discapacidad'] ?? false)
                                <span class="badge bg-primary ms-auto" title="Discapacidad">D</span>
                            @elseif($est['nee'] ?? false)
                                <span class="badge bg-warning text-dark ms-auto" title="Necesidades educativas especiales">NEE</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
    @endif
        </div>
    </div>
</div>
@endsection