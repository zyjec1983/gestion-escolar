@extends('layouts.principal')

@section('titulo', 'Mis actividades y materiales')

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Mis actividades y materiales</h4>
            <small class="text-muted">
                @if($alumno) <strong>{{ $alumno->apellidos }} {{ $alumno->nombres }}</strong> ·
                <span class="badge bg-secondary">Solo lectura</span> @endif
            </small>
        </div>
    </div>

    @if($materiales_recientes->isNotEmpty())
    <div class="alert alert-info alert-dismissible fade show" id="alertaMateriales" data-clave="mat-est-{{ $materiales_recientes->first()['created_at']->timestamp }}">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-cloud-arrow-up fs-4"></i>
            <div class="flex-grow-1">
                <strong><i class="bi bi-folder2-open me-1"></i>Nuevos materiales subidos</strong>
                <ul class="mb-2 mt-2 ps-3">
                    @foreach($materiales_recientes as $mat)
                        <li class="mb-1">
                            <a href="{{ $mat['url'] }}" target="_blank" rel="noopener" class="fw-semibold">{{ $mat['titulo'] }}</a>
                            <small class="text-muted">
                                · {{ $mat['materia'] }} · {{ $mat['subidor'] }} · {{ $mat['created_at']->format('d/m/Y') }}
                            </small>
                        </li>
                    @endforeach
                </ul>
                <small class="text-muted">
                    <i class="bi bi-eye me-1"></i>Solo lectura: puedes revisarlos desde
                    <a href="{{ route('clases.materiales', $materiales_recientes->first()['clase_id']) }}" class="alert-link">Materiales de clase</a>.
                </small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    </div>
    <script>
        (function () {
            var alerta = document.getElementById('alertaMateriales');
            if (!alerta) { return; }
            var clave = alerta.getAttribute('data-clave');
            if (sessionStorage.getItem('materialesAlerta') === clave) {
                alerta.classList.add('d-none');
            }
            var cerrar = alerta.querySelector('[data-bs-dismiss="alert"]');
            if (cerrar) {
                cerrar.addEventListener('click', function () {
                    sessionStorage.setItem('materialesAlerta', clave);
                });
            }
        })();
    </script>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('estudiante.agendas') }}"
           class="btn btn-sm {{ !$materiaSeleccionada ? 'btn-dark' : 'btn-outline-secondary' }}">Todas</a>
        @foreach($materias as $mat)
            <a href="{{ route('estudiante.agendas', ['materia_id' => $mat['id']]) }}"
               class="btn btn-sm {{ (string) $materiaSeleccionada === (string) $mat['id'] ? 'btn-dark' : 'btn-outline-secondary' }}">
                {{ $mat['nombre'] }}
            </a>
        @endforeach
    </div>

    @if($agendas->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                No hay agendas enviadas para este filtro.
            </div>
        </div>
    @else
        @foreach($agendas as $agenda)
            @php
                $clase = $agenda->claseDocente;
                $materia = $clase?->materia;
                $docente = $clase?->docente;
                $vencida = $agenda->fecha_entrega_limite && $agenda->fecha_entrega_limite->lt(\Illuminate\Support\Carbon::today());
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h6 class="mb-1">
                                <i class="bi bi-clipboard-check me-1"></i>{{ $agenda->titulo_agenda }}
                            </h6>
                            <div class="text-muted small mb-2">
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="bi bi-mortarboard me-1"></i>{{ $materia?->nombre }}
                                </span>
                                @if($docente)
                                    <span class="ms-2">
                                        <i class="bi bi-person me-1"></i>{{ $docente->name }} {{ $docente->apellidos }}
                                    </span>
                                @endif
                                @if($agenda->tipoActividad)
                                    <span class="ms-2"><i class="bi bi-tag me-1"></i>{{ $agenda->tipoActividad->nombre }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small">
                                @if($agenda->fecha_inicio)
                                    <span class="text-muted d-block">Inicio: {{ $agenda->fecha_inicio->format('d/m/Y H:i') }}</span>
                                @endif
                                @if($agenda->fecha_entrega_limite)
                                    <span class="badge bg-{{ $vencida ? 'danger' : 'success' }}">
                                        {{ $vencida ? 'Vencida' : 'Entrega' }}: {{ $agenda->fecha_entrega_limite->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Sin fecha límite</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($agenda->descripcion_instrucciones_html)
                        <div class="mt-2 small border-start border-3 border-secondary ps-3">
                            {!! $agenda->descripcion_instrucciones_html !!}
                        </div>
                    @endif

                    @if($agenda->archivosAdjuntos->isNotEmpty())
                        <div class="mt-3">
                            <small class="text-muted fw-bold"><i class="bi bi-paperclip me-1"></i>Material adjunto:</small>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($agenda->archivosAdjuntos as $adjunto)
                                    <a href="{{ asset($adjunto->url) }}" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark me-1"></i>{{ $adjunto->nombre }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <div class="d-flex align-items-center justify-content-between mt-5 mb-2">
        <h5 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Materiales por materia</h5>
        <span class="badge bg-secondary">Solo lectura</span>
    </div>
    <p class="text-muted small mb-3">
        Los materiales publicados por los docentes están disponibles mientras no sean retirados.
        Al darlos de baja se eliminan de forma física del servidor.
    </p>

    @forelse($materiales_por_materia ?? [] as $materiaNombre => $materialesMateria)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-mortarboard me-1"></i>{{ $materiaNombre }}</strong>
                <span class="badge bg-light text-dark border">{{ count($materialesMateria) }} material(es)</span>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($materialesMateria as $mat)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2 py-2">
                        <i class="bi bi-file-earmark-text text-primary"></i>
                        <div class="flex-grow-1">
                            <a href="{{ $mat['url'] }}" target="_blank" rel="noopener" class="fw-semibold">{{ $mat['titulo'] }}</a>
                            <small class="text-muted d-block">
                                {{ $mat['nombre_archivo'] }} · {{ $mat['subidor'] }} · {{ $mat['created_at']->format('d/m/Y') }}
                            </small>
                        </div>
                        <a href="{{ $mat['url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary text-nowrap">
                            <i class="bi bi-download me-1"></i>Descargar
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
                No hay materiales subidos para ti.
            </div>
        </div>
    @endforelse
</div>
@endsection