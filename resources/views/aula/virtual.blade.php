@extends("layouts.{$layout}")

@section('titulo', $clase_actual['materia'] ?? 'Aula Virtual')

@section('contenido-aula')
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
        <a href="{{ route('clases.agenda', $clase_actual['id']) }}" class="card shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-check-fill fs-1 text-primary mb-3 d-block"></i>
                <h5>Agenda</h5>
                <p class="text-muted small">Actividades de la clase con unidades de planificación y citas de la semana.</p>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('clases.materiales', $clase_actual['id']) }}" class="card shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder2-open-fill fs-1 text-success mb-3 d-block"></i>
                <h5>Materiales</h5>
                <p class="text-muted small">Guías, documentos y recursos de estudio de la clase.</p>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ route('mensajes.index') }}" class="card shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body text-center py-5">
                <i class="bi bi-chat-dots-fill fs-1 text-info mb-3 d-block"></i>
                <h5>Mensajes</h5>
                <p class="text-muted small">Comunícate de forma privada con docentes, estudiantes y representantes.</p>
            </div>
        </a>
    </div>
    @php $citaUrl = null; foreach ($menu_aula_virtual ?? [] as $m) { if (($m['key'] ?? '') === 'citas' && ($m['url'] ?? null)) { $citaUrl = $m['url']; } } @endphp
    @if($citaUrl)
    <div class="col">
        <a href="{{ $citaUrl }}" class="card shadow-sm h-100 text-decoration-none text-dark">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-event-fill fs-1 text-warning mb-3 d-block"></i>
                <h5>Citas</h5>
                <p class="text-muted small">Solicita o gestiona reuniones con la familia y estudiantes.</p>
            </div>
        </a>
    </div>
    @endif
</div>
@endsection