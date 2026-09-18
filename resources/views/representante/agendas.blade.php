@extends('layouts.principal')

@section('titulo', 'Agendas de mis hijos')

@section('contenido')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Agendas de mis hijos</h4>
            <small class="text-muted">
                @if($alumno) Representado: <strong>{{ $alumno->apellidos }} {{ $alumno->nombres }}</strong> ·
                <span class="badge bg-secondary">Solo lectura</span> @endif
            </small>
        </div>
        @if(count($representados) > 1)
        <div class="col-auto">
            <form method="GET" action="{{ route('representante.agendas') }}">
                <select name="alumno_id" class="form-select" onchange="this.form.submit()">
                    @foreach($representados as $r)
                        <option value="{{ $r->id }}" {{ $alumno && $alumno->id == $r->id ? 'selected' : '' }}>
                            {{ $r->apellidos }} {{ $r->nombres }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('representante.agendas', ['alumno_id' => $alumno?->id]) }}"
           class="btn btn-sm {{ !$materiaSeleccionada ? 'btn-dark' : 'btn-outline-secondary' }}">Todas</a>
        @foreach($materias as $mat)
            <a href="{{ route('representante.agendas', ['alumno_id' => $alumno?->id, 'materia_id' => $mat['id']]) }}"
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
</div>
@endsection