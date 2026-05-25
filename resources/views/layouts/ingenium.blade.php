{{-- 
  Layout Ingenium: Entorno de Aula Virtual (LMA).
  Extiende el layout educalinks, manteniendo la navegación principal,
  pero reemplazando el sidebar con herramientas pedagógicas (menú del aula)
  y agregando un right-panel fijo con el listado de estudiantes inscritos.
  
  El cambio de layout ocurre en InicioClaseController:
  - return view('aula.virtual', ['layout' => 'ingenium'])
    ->with(['menu' => $menuItems, 'estudiantes' => $estudiantesInscritos]);
    
  En Blade: @extends("layouts.{$layout}") se resuelve a layouts.ingenium
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
            <a href="#" class="nav-link text-white {{ $loop->first ? 'active' : '' }}">
                <i class="bi bi-{{ $loop->index === 0 ? 'house-door' : ($loop->index === 1 ? 'people' : ($loop->index === 2 ? 'clipboard-check' : ($loop->index === 3 ? 'calendar' : ($loop->index === 4 ? 'chat-dots' : ($loop->index === 5 ? 'people-fill' : ($loop->index === 6 ? 'folder' : ($loop->index === 7 ? 'pencil-square' : 'star'))))))) }} me-2"></i>
                {{ $item }}
            </a>
        </li>
        @endforeach
    </ul>
    <hr>
    <a href="/clases" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Clases
    </a>
</div>
@endsection

@section('contenido')
<div class="row">
    <div class="col-md-9">
        @yield('contenido-aula')
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people-fill me-1"></i> Estudiantes Inscritos</span>
                <div>
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
                            <i class="bi bi-person-circle me-2 text-secondary"></i>
                            <small>{{ $est['apellidos_nombres'] }}</small>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
