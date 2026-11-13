@extends('layouts.principal')

@section('titulo', 'Acta de acuerdo')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Acuerdo #{{ $acuerdo->id }}</h4>
            <small class="text-muted">{{ $acuerdo->tipoLabel() }} · {{ $acuerdo->alumno?->nombreCompleto() }}</small>
        </div>
        <div class="d-flex gap-2">
            @if($acuerdo->firmadoCompleto())
                <a href="{{ route('dece.acuerdos.pdf', $acuerdo) }}" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Descargar acta
                </a>
            @else
                <button class="btn btn-outline-secondary" disabled title="Faltan firmas">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Acta pendiente de firmas
                </button>
            @endif
            <a href="{{ route('dece.acuerdos.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    @include('dece.partials.flash')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Contenido del acuerdo</div>
                <div class="card-body">
                    <p style="white-space: pre-line;">{{ $acuerdo->descripcion }}</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-uppercase text-muted mb-1">Compromiso del estudiante</div>
                                <div style="white-space: pre-line;">{{ $acuerdo->compromiso_estudiante ?: 'No definido.' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-uppercase text-muted mb-1">Compromiso del representante</div>
                                <div style="white-space: pre-line;">{{ $acuerdo->compromiso_representante ?: 'No definido.' }}</div>
                            </div>
                        </div>
                    </div>

                    <dl class="row mt-4 mb-0">
                        <dt class="col-sm-4">Fecha de inicio</dt>
                        <dd class="col-sm-8">{{ $acuerdo->fecha_inicio->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Fecha límite</dt>
                        <dd class="col-sm-8">{{ $acuerdo->fecha_compromiso->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">@include('dece.partials.estado-badge', ['estado' => $acuerdo->estado, 'label' => $acuerdo->estadoLabel()])</dd>
                        <dt class="col-sm-4">Profesional</dt>
                        <dd class="col-sm-8">{{ $acuerdo->profesional?->nombreCompleto() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Seguimientos</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Fecha</th><th>Estado</th><th>Observación</th><th>Registrado por</th></tr>
                        </thead>
                        <tbody>
                            @forelse($acuerdo->seguimientos as $seguimiento)
                            <tr>
                                <td class="text-nowrap">{{ $seguimiento->fecha->format('d/m/Y') }}</td>
                                <td>{{ $seguimiento->estadoLabel() }}</td>
                                <td class="small">{{ $seguimiento->observacion ?: '—' }}</td>
                                <td class="small">{{ $seguimiento->registrador?->nombreCompleto() ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Sin seguimientos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Firmas digitales</div>
                <div class="list-group list-group-flush">
                    @foreach([
                        'estudiante' => ['Estudiante', $acuerdo->firmado_estudiante_en],
                        'representante' => ['Representante', $acuerdo->firmado_representante_en],
                        'profesional' => ['Profesional del DECE', $acuerdo->firmado_profesional_en],
                    ] as $clave => [$label, $fecha])
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $label }}</div>
                            <small class="text-muted">{{ $fecha ? $fecha->format('d/m/Y H:i') : 'Pendiente' }}</small>
                        </div>
                        @if($fecha)
                            <span class="badge bg-success"><i class="bi bi-check2"></i></span>
                        @else
                            <form method="POST" action="{{ route('dece.acuerdos.firmar', $acuerdo) }}">
                                @csrf
                                <input type="hidden" name="firma" value="{{ $clave }}">
                                <button class="btn btn-sm btn-outline-primary">Firmar</button>
                            </form>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Registrar seguimiento</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dece.acuerdos.seguimiento', $acuerdo) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" value="{{ now()->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" required>
                                @foreach($estadosSeguimiento as $valor => $label)
                                    <option value="{{ $valor }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observación</label>
                            <textarea name="observacion" rows="3" class="form-control"></textarea>
                        </div>
                        <button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Registrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
