@extends('layouts.aula')

@section('titulo', ($clase_actual['materia'] ?? '') . ' - ' . $foro->titulo)

@section('contenido-aula')
@php
    $estadoInfo = match($estado) {
        'activo' => ['Activo', 'bg-success'],
        'no_iniciado' => ['Por iniciar', 'bg-warning text-dark'],
        'cerrado' => ['Cerrado', 'bg-secondary'],
    };
    $esEstudiante = !$es_docente && auth()->user()->rol === 'ESTUDIANTE';
    $puedeParticipar = $esEstudiante && $estado === 'activo';
    $coloresAvatar = ['bg-primary', 'bg-success', 'bg-info', 'bg-danger', 'bg-secondary', 'bg-dark'];
    $nombreUsuario = fn ($u) => trim((string) $u->apellidos . ' ' . (string) $u->name);
    $primerNombre = fn ($u) => explode(' ', trim((string) $u->name))[0] ?? trim((string) $u->name);
    $iniciales = fn ($u) => strtoupper(mb_substr((string) $u->apellidos, 0, 1) . mb_substr((string) $u->name, 0, 1));
@endphp

<nav class="mb-2">
    <a href="{{ route('clases.foros', $clase->id) }}" class="btn btn-link btn-sm text-decoration-none px-0">
        <i class="bi bi-arrow-left me-1"></i>Volver a Foros</a>
</nav>

@if(session('success'))
    <div id="swal-success" data-message="{{ session('success') }}" class="d-none"></div>
@endif
@if(session('error'))
    <div id="swal-error" data-message="{{ session('error') }}" class="d-none"></div>
@endif

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 align-items-sm-center mb-2">
    <div>
        <span class="badge {{ $estadoInfo[1] }}">{{ $estadoInfo[0] }}</span>
    </div>
    <small class="text-muted">
        <i class="bi bi-calendar-range me-1"></i>
        {{ $foro->fecha_inicio?->format('d/m/Y H:i') }} — {{ $foro->fecha_cierre?->format('d/m/Y H:i') }}
    </small>
</div>

{{-- Cabecera del tema --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-1">
            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">DOC</div>
            <div>
                <div class="fw-bold small">Prof. {{ $clase->materia?->nombre }} <small class="text-muted fw-normal">(Docente)</small></div>
                <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }} · {{ $clase->materia?->nombre }}</small>
            </div>
            @if($es_docente)
            <div class="ms-auto">
                <form method="POST" action="{{ route('foros.destroy', $foro->id) }}"
                      class="confirm-swal d-inline" data-titulo="Eliminar foro" data-mensaje="¿Eliminar este tema de foro con todas sus intervenciones?">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
                </form>
            </div>
            @endif
        </div>

        <h4 class="fw-bold mt-3 mb-2">{{ $foro->titulo }}</h4>

        <div class="bg-light border rounded p-3">
            <div class="small fw-bold text-uppercase text-muted mb-1">Pregunta de debate</div>
            <p class="mb-0">{{ $foro->pregunta }}</p>
        </div>

        @if(count($foro->links ?? []))
        <div class="mt-3">
            <div class="small fw-bold text-uppercase text-muted mb-1">
                <i class="bi bi-link-45deg me-1"></i>Revisa estos enlaces antes de responder
            </div>
            <ul class="list-unstyled mb-0">
                @foreach($foro->links as $link)
                <li class="mb-1">
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="small">
                        <i class="bi bi-box-arrow-up-right me-1"></i>{{ $link['nombre'] ?: $link['url'] }}</a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

{{-- Aviso de periodo para estudiantes --}}
@if($esEstudiante && $estado === 'no_iniciado')
    <div class="alert alert-warning">
        <i class="bi bi-hourglass-split me-1"></i>Este foro inicia el {{ $foro->fecha_inicio?->format('d/m/Y H:i') }}. Aún no puedes participar.
    </div>
@elseif($esEstudiante && $estado === 'cerrado')
    <div class="alert alert-secondary">
        <i class="bi bi-lock-fill me-1"></i>Este foro cerró el {{ $foro->fecha_cierre?->format('d/m/Y H:i') }}: ya no se admiten intervenciones.
    </div>
@endif

{{-- Respuesta principal del estudiante --}}
@if($esEstudiante)
<div class="card shadow-sm mb-3 border-primary">
    <div class="card-body">
        <h6 class="fw-bold mb-2"><i class="bi bi-person-badge me-1"></i>Tu respuesta principal al docente</h6>
        @if($ya_respondio)
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle-fill me-1"></i>Ya publicaste tu respuesta principal al docente. Solo se permite una.
            </div>
        @elseif($puedeParticipar)
            <form method="POST" action="{{ route('foros.intervenciones.store', $foro->id) }}">
                @csrf
                <input type="hidden" name="tipo" value="respuesta">
                <textarea name="cuerpo" class="form-control foro-textarea mb-2" rows="3" maxlength="5000" required
                          placeholder="Escribe tu respuesta argumentada al docente con tus propias palabras..."></textarea>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Publicar respuesta</button>
                </div>
            </form>
        @else
            <textarea class="form-control mb-2" rows="3" disabled placeholder="No disponible en este momento."></textarea>
        @endif
    </div>
</div>
@endif

{{-- Resumen de participación --}}
@if($es_docente || $estado === 'cerrado')
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-bold small">
        <i class="bi bi-bar-chart me-1"></i>Resumen de participación por estudiante
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Estudiante</th>
                        <th class="text-center">Respuestas</th>
                        <th class="text-center">Réplicas</th>
                        <th class="text-center">Conclusiones</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participacion as $row)
                    <tr>
                        <td class="small">{{ $row['nombre'] }}</td>
                        <td class="text-center">{{ $row['respuestas'] }}</td>
                        <td class="text-center">{{ $row['replicas'] }}</td>
                        <td class="text-center">{{ $row['conclusiones'] }}</td>
                        <td class="text-center"><span class="badge bg-dark">{{ $row['total'] }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted small py-3">No hay estudiantes inscritos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Hilo de respuestas --}}
<h6 class="fw-bold text-uppercase text-muted small mb-2">Aportes e interacciones de estudiantes</h6>

<div class="d-flex flex-column gap-3">
    @forelse($principales as $respuesta)
        @php
            $usuario = $respuesta->user;
            $replicas = $replicas_por_padre->get($respuesta->id, collect());
            $colorAvatar = $coloresAvatar[$respuesta->user_id % count($coloresAvatar)];
            $esDelUsuario = $usuario->id === auth()->id();
        @endphp
        <div class="card shadow-sm" id="respuesta-{{ $respuesta->id }}">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold {{ $colorAvatar }}" style="width: 36px; height: 36px;">
                        {{ $iniciales($usuario) }}
                    </div>
                    <div>
                        <div class="small fw-bold">
                            {{ $nombreUsuario($usuario) }}
                            @if($esDelUsuario)<span class="badge bg-primary ms-1">Tú</span>@endif
                        </div>
                        <small class="text-muted">{{ $respuesta->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>

                <p class="mb-2 text-break">{{ $respuesta->cuerpo }}</p>

                @if($puedeParticipar)
                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-body"
                        data-bs-toggle="collapse" data-bs-target="#replicaBox{{ $respuesta->id }}" aria-expanded="false">
                    <i class="bi bi-reply me-1"></i>Responder a {{ $primerNombre($usuario) }}
                </button>
                <div class="collapse" id="replicaBox{{ $respuesta->id }}">
                    <form method="POST" action="{{ route('foros.intervenciones.store', $foro->id) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="tipo" value="replica">
                        <input type="hidden" name="padre_id" value="{{ $respuesta->id }}">
                        <textarea name="cuerpo" class="form-control foro-textarea mb-2" rows="2" maxlength="5000" required
                                  placeholder="Escribe tu réplica a {{ $primerNombre($usuario) }} con tus propias palabras..."></textarea>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-sm btn-dark"><i class="bi bi-send me-1"></i>Publicar réplica</button>
                        </div>
                    </form>
                </div>
                @endif

                @if($replicas->isNotEmpty())
                <div class="border-start border-2 ms-3 ps-3 mt-3 d-flex flex-column gap-2">
                    @foreach($replicas as $replica)
                    <div class="bg-light rounded p-2">
                        <div class="small fw-bold">
                            {{ $nombreUsuario($replica->user) }}
                            <small class="text-muted fw-normal">respondió · {{ $replica->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <p class="small mb-0 text-break">{{ $replica->cuerpo }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
                Aún no hay respuestas de estudiantes.
                @if($puedeParticipar) ¡Sé la primera persona en responder! @endif
            </div>
        </div>
    @endforelse
</div>

{{-- Conclusión final --}}
@if($esEstudiante)
<div class="card shadow-sm mt-3">
    <div class="card-body">
        <h6 class="fw-bold mb-1"><i class="bi bi-journal-check me-1"></i>Conclusión final</h6>
        <p class="small text-muted">Sintetiza aquí tu postura definitiva luego de revisar la pregunta y las respuestas de tus compañeros. Solo se permite una conclusión.</p>
        @if($ya_concluyo)
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle-fill me-1"></i>Conclusión final guardada:
                <p class="mb-0 mt-1">{{ $mi_conclusion }}</p>
            </div>
        @elseif($puedeParticipar)
            <form method="POST" action="{{ route('foros.intervenciones.store', $foro->id) }}">
                @csrf
                <input type="hidden" name="tipo" value="conclusion">
                <textarea name="cuerpo" class="form-control foro-textarea mb-2" rows="3" maxlength="5000" required
                          placeholder="Escribe tu conclusión final con tus propias palabras..."></textarea>
                <div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-dark"><i class="bi bi-save me-1"></i>Guardar conclusión</button>
                </div>
            </form>
        @else
            <textarea class="form-control mb-2" rows="3" disabled placeholder="No disponible en este momento."></textarea>
        @endif
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var swalError = document.getElementById('swal-error');
        if (swalError) {
            Swal.fire({
                icon: 'error',
                title: 'Atención',
                text: swalError.dataset.message,
                confirmButtonText: 'Entendido'
            });
        }

        document.querySelectorAll('form.confirm-swal').forEach(function (f) {
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: f.dataset.titulo || '¿Eliminar?',
                    text: f.dataset.mensaje || '',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function (res) { if (res.isConfirmed) f.submit(); });
            });
        });

        document.querySelectorAll('textarea.foro-textarea').forEach(function (ta) {
            ['paste', 'copy', 'cut', 'drop'].forEach(function (ev) {
                ta.addEventListener(ev, function (e) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Acción prohibida',
                        text: 'El copiar y pegar está prohibido en este foro: redacta tu intervención con tus propias palabras.',
                        confirmButtonText: 'Entendido'
                    });
                });
            });
        });
    });
</script>
@endsection