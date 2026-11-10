@extends('layouts.principal')

@section('titulo', 'Bitácora de notas extraordinarias')

@push('styles')
<style>
    .bitacora-hero {
        background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
    }
    .cambio-chip {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .motivo-texto {
        max-width: 320px;
        white-space: pre-line;
    }
</style>
@endpush

@section('contenido')
@php
    $etiquetas = [
        'fii' => 'FII', 'ffi' => 'FFI', 'fig' => 'FIG', 'ffg' => 'FFG',
        'proyecto_integrador' => 'Proyecto integrador', 'evaluacion_parcial' => 'Evaluación parcial',
        'nota_examen' => 'Nota de examen', 'estado' => 'Estado',
    ];
@endphp
<div class="container-fluid">
    <div class="bitacora-hero rounded-4 p-3 p-md-4 mb-4 text-white shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-white text-dark mb-2">Registro inalterable</span>
                <h4 class="mb-1"><i class="bi bi-journal-text me-2"></i>Bitácora de notas extraordinarias</h4>
                <div class="small text-white-50">
                    Historial completo de correcciones: usuario, rol, fecha exacta, IP, agente de usuario y valores anteriores y nuevos.
                </div>
            </div>
            <a href="{{ route('notas-extraordinarias.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a correcciones
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('notas-extraordinarias.bitacora') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-8">
                    <label for="buscar" class="form-label small fw-semibold">Buscar por estudiante, materia o usuario</label>
                    <input type="text" class="form-control" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="100"
                           placeholder="Ej.: ZAMBRANO, Matemática, ROCIO...">
                </div>
                <div class="col-12 col-md-4 d-grid">
                    <button type="submit" class="btn btn-teal text-white" style="background:#0f766e;">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0"><i class="bi bi-lock me-2"></i>Asientos registrados</h6>
            <span class="badge bg-secondary">{{ $auditorias->total() }} en total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Fecha y hora</th>
                            <th>Estudiante / contexto</th>
                            <th>Cambio</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                            <th class="pe-3">Trazabilidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditorias as $auditoria)
                            <tr>
                                <td class="ps-3 small text-nowrap">
                                    {{ $auditoria->created_at?->format('d/m/Y') }}<br>
                                    <span class="text-muted">{{ $auditoria->created_at?->format('H:i:s') }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $auditoria->alumno_nombre }}</div>
                                    <small class="text-muted d-block">
                                        {{ $auditoria->materia ?: 'Sin materia' }}
                                        @if($auditoria->curso) · {{ $auditoria->curso }} @endif
                                        @if($auditoria->paralelo) {{ $auditoria->paralelo }} @endif
                                    </small>
                                    <span class="badge bg-light text-dark border mt-1">
                                        {{ collect($opciones ?? [])->firstWhere('parcial', $auditoria->parcial)['label'] ?? ('Parcial '.$auditoria->parcial) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @foreach(($auditoria->datos_anteriores ?? []) as $campo => $anterior)
                                            @php
                                                $nuevo = $auditoria->datos_nuevos[$campo] ?? null;
                                                $cambio = (string) $anterior !== (string) $nuevo;
                                            @endphp
                                            @if($campo === 'estado')
                                                @if($cambio)
                                                    <span class="cambio-chip small">
                                                        Estado: <span class="text-muted">{{ $anterior }}</span>
                                                        <i class="bi bi-arrow-right mx-1"></i>{{ $nuevo }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="cambio-chip small">
                                                    <strong>{{ $etiquetas[$campo] ?? $campo }}:</strong>
                                                    <span class="{{ $cambio ? 'text-muted text-decoration-line-through' : 'text-muted' }}">
                                                        {{ is_numeric($anterior) ? number_format((float) $anterior, 2) : ($anterior ?? '—') }}
                                                    </span>
                                                    @if($cambio)
                                                        <i class="bi bi-arrow-right mx-1"></i>
                                                        <span class="fw-semibold text-success">
                                                            {{ is_numeric($nuevo) ? number_format((float) $nuevo, 2) : ($nuevo ?? '—') }}
                                                        </span>
                                                    @endif
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td class="motivo-texto small">{{ $auditoria->motivo }}</td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $auditoria->usuario_nombre }}</div>
                                    <span class="badge bg-secondary">{{ $auditoria->usuario_rol }}</span>
                                </td>
                                <td class="small pe-3">
                                    <div class="text-nowrap"><i class="bi bi-hdd-network me-1"></i>{{ $auditoria->ip ?: '—' }}</div>
                                    <button class="btn btn-link btn-sm p-0 text-muted" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#ua{{ $auditoria->id }}">
                                        Ver agente de usuario
                                    </button>
                                    <div class="collapse" id="ua{{ $auditoria->id }}">
                                        <div class="text-break text-muted">{{ $auditoria->user_agent ?: 'No registrado' }}</div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                    Todavía no hay correcciones extraordinarias registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($auditorias->hasPages())
            <div class="card-footer bg-white">
                {{ $auditorias->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
