{{-- 
  Vista Aula Virtual (Ingenium Classroom).
  El layout se inyecta dinámicamente desde InicioClaseController.
  Renderiza el entorno LMA con sidebar pedagógico y right-panel de estudiantes.
  
  Cambio de layout:
  @extends("layouts.{$layout}") → se resuelve a 'layouts.ingenium'
  porque el controlador pasa ['layout' => 'ingenium'].
--}}
@extends("layouts.{$layout}")

@section('titulo', $clase_actual['materia'] ?? 'Aula Virtual')

@section('contenido-aula')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-2">
        <div>
            <h4 class="mb-0">{{ $clase_actual['materia'] ?? '' }}</h4>
            <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
        </div>
        @if($clase_actual['unidad_actual'] ?? false)
        <div class="text-end">
            <small class="text-muted d-block">Unidad actual:</small>
            <strong>{{ $clase_actual['unidad_actual']['titulo'] }}</strong>
            <br>
            <small class="text-muted">
                {{ $clase_actual['unidad_actual']['fecha_inicio'] }} - {{ $clase_actual['unidad_actual']['fecha_fin'] }}
            </small>
        </div>
        @endif
    </div>

    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3">
        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-house-door-fill fs-1 text-primary mb-3 d-block"></i>
                    <h5>Inicio</h5>
                    <p class="text-muted small">Bienvenido al aula virtual. Seleccione una herramienta del menú lateral.</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-people-fill fs-1 text-success mb-3 d-block"></i>
                    <h5>Control del Aula</h5>
                    <p class="text-muted small">Gestione la participación y el comportamiento de los estudiantes.</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-clipboard-check fs-1 text-warning mb-3 d-block"></i>
                    <h5>Asistencia</h5>
                    <p class="text-muted small">Registre la asistencia diaria de los estudiantes.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
