@extends('layouts.principal')

@section('titulo', 'Expediente de traslado')

@push('styles')
<style>
    .expediente-hero {
        background: linear-gradient(135deg, #0f766e 0%, #115e59 60%, #0f172a 100%);
    }
    .requisito-recibido { border-left: 4px solid #198754; }
    .requisito-faltante { border-left: 4px solid #dc3545; }
    .requisito-opcional { border-left: 4px solid #adb5bd; }
    .doc-chip {
        font-variant-numeric: tabular-nums;
    }
    .tabla-expediente th { white-space: nowrap; }
    @media (max-width: 767.98px) {
        .tabla-expediente thead { display: none; }
        .tabla-expediente tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            padding: .5rem;
            margin-bottom: .75rem;
        }
        .tabla-expediente tbody td {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border: 0;
            padding: .35rem .5rem;
        }
        .tabla-expediente tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            font-size: .75rem;
            text-transform: uppercase;
        }
    }
</style>
@endpush

@section('contenido')
@php
    $cerrado = $expediente->estaCerrado();
    $esIngreso = $expediente->tipo === \App\Models\ExpedienteTraslado::TIPO_INGRESO;
@endphp
<div class="container-fluid">
    <div class="expediente-hero rounded-4 p-3 p-md-4 mb-4 text-white shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-{{ $expediente->tipoBadge() }}">{{ mb_strtoupper($expediente->tipoLabel()) }}</span>
                    <span class="badge bg-{{ $expediente->estadoBadge() }}">{{ $expediente->estadoLabel() }}</span>
                    @if($cerrado)
                        <span class="badge bg-dark border border-light"><i class="bi bi-lock me-1"></i>Inmutable</span>
                    @endif
                </div>
                <h4 class="mb-1">{{ $expediente->nombreAlumno() }}</h4>
                <div class="small text-white-50">
                    {{ $expediente->alumno?->numero_cedula ?: $expediente->alumno?->codigo }}
                    · {{ $expediente->institucion }}
                    · {{ $expediente->fecha_traslado?->format('d/m/Y') }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('traslados.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Listado
                </a>
                <a href="{{ route('traslados.historial.pdf', $expediente) }}" class="btn btn-light btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Historial PDF
                </a>
                @if($expediente->tipo === 'salida' && $expediente->puedeEmitirCertificadoSalida())
                    <a href="{{ route('traslados.certificado.pdf', $expediente) }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-check me-1"></i>Certificado de salida
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif
    @if($errors->any())
        <div id="swal-error" data-message="{{ $errors->first() }}" hidden></div>
    @endif

    @if($cerrado)
        <div class="alert alert-dark border-0 shadow-sm d-flex gap-3">
            <i class="bi bi-lock-fill fs-4"></i>
            <div class="small">
                <strong>Expediente cerrado e inmutable (RN-03).</strong>
                El historial externo, los documentos y las homologaciones ya no pueden modificarse. La matrícula quedó en estado definitivo.
                @if($expediente->validado_at)
                    Validado el {{ $expediente->validado_at->format('d/m/Y H:i') }}
                    @if($expediente->validador) por {{ $expediente->validador->nombreCompleto() }} @endif.
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Documentos</div>
                    <div class="fs-5 fw-bold {{ $expediente->documentos_completos ? 'text-success' : 'text-danger' }}">
                        {{ $expediente->documentos_completos ? 'Completos' : 'Pendientes' }}
                    </div>
                    <div class="small text-muted">{{ count($subidos) }} tipo(s) recibido(s)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Faltantes obligatorios</div>
                    <div class="fs-5 fw-bold">{{ count($faltantes) }}</div>
                    <div class="small text-muted">RN-04: 100% para definitiva</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Matrícula</div>
                    <div class="fs-5 fw-bold">
                        {{ $expediente->matricula?->estado === 'provisional' ? 'Provisional' : ($expediente->matricula ? 'Definitiva' : 'Sin matrícula') }}
                    </div>
                    <div class="small text-muted">{{ $expediente->matricula?->codigo_matricula ?? 'No vinculada' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Paz y salvo</div>
                    <div class="fs-5 fw-bold {{ $expediente->paz_y_salvo_financiero && $expediente->paz_y_salvo_biblioteca ? 'text-success' : 'text-warning-emphasis' }}">
                        {{ $expediente->paz_y_salvo_financiero && $expediente->paz_y_salvo_biblioteca ? 'Completo' : 'Pendiente' }}
                    </div>
                    <div class="small text-muted">Financiero / biblioteca</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="tabsExpediente" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-documentos" data-bs-toggle="tab" data-bs-target="#panel-documentos" type="button" role="tab">
                <i class="bi bi-folder-check me-1"></i>Documentos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-homologacion" data-bs-toggle="tab" data-bs-target="#panel-homologacion" type="button" role="tab">
                <i class="bi bi-calculator me-1"></i>Homologación
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-cierre" data-bs-toggle="tab" data-bs-target="#panel-cierre" type="button" role="tab">
                <i class="bi bi-shield-check me-1"></i>Paz y salvo / cierre
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-bitacora" data-bs-toggle="tab" data-bs-target="#panel-bitacora" type="button" role="tab">
                <i class="bi bi-journal-text me-1"></i>Bitácora
            </button>
        </li>
    </ul>

    <div class="tab-content" id="contenidoExpediente">
        <div class="tab-pane fade show active" id="panel-documentos" role="tabpanel">
            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Check-list documental</h6>
                            <span class="badge bg-{{ $expediente->documentos_completos ? 'success' : 'warning text-dark' }}">
                                {{ $expediente->documentos_completos ? 'Expediente completo' : 'Expediente por regularizar' }}
                            </span>
                        </div>
                        <div class="card-body">
                            @if($expediente->tipo === 'ingreso' && $expediente->momento === 'transcurso' && ! $periodoVigente)
                                <div class="alert alert-info small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    RN-01: el período lectivo no está activo; la libreta parcial y la asistencia no se exigen en este momento.
                                </div>
                            @endif

                            @foreach($requisitos as $requisito)
                                @php
                                    $recibido = in_array($requisito['clave'], $subidos, true);
                                    $documentosTipo = $expediente->documentos->where('tipo_documento', $requisito['clave']);
                                    $clase = $recibido ? 'requisito-recibido' : ($requisito['obligatorio'] ? 'requisito-faltante' : 'requisito-opcional');
                                @endphp
                                <div class="card mb-2 border-0 bg-light {{ $clase }}">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-semibold small">
                                                    {{ $requisito['label'] }}
                                                    @if($requisito['obligatorio'])
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size:.75rem;">{{ $requisito['descripcion'] }}</div>
                                            </div>
                                            <span class="badge {{ $recibido ? 'bg-success' : ($requisito['obligatorio'] ? 'bg-danger' : 'bg-secondary') }}">
                                                {{ $recibido ? 'Recibido' : ($requisito['obligatorio'] ? 'Faltante' : 'Opcional') }}
                                            </span>
                                        </div>

                                        @if($documentosTipo->isNotEmpty())
                                            <div class="mt-2 d-flex flex-column gap-1">
                                                @foreach($documentosTipo as $documento)
                                                    <div class="d-flex flex-wrap align-items-center gap-2 small">
                                                        <a href="{{ $documento->url() }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                            <i class="bi bi-paperclip me-1"></i>{{ $documento->nombre_original }}
                                                        </a>
                                                        <span class="badge bg-light text-dark border doc-chip">{{ $documento->pesoLegible() }}</span>
                                                        <span class="text-muted">{{ $documento->created_at?->format('d/m/Y H:i') }}</span>
                                                        @if(! $cerrado)
                                                            <form method="POST" action="{{ route('traslados.documentos.destroy', [$expediente, $documento]) }}" class="d-inline form-confirmar"
                                                                  data-confirmar="¿Eliminar este documento del expediente? También se borrará el archivo almacenado.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0">Eliminar</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-upload me-2"></i>Adjuntar documento</h6>
                        </div>
                        <div class="card-body">
                            @if($cerrado)
                                <div class="alert alert-secondary small mb-0">
                                    <i class="bi bi-lock me-1"></i>El expediente está cerrado; no se pueden adjuntar ni eliminar documentos.
                                </div>
                            @else
                                <form method="POST" action="{{ route('traslados.documentos.store', $expediente) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="tipo_documento" class="form-label small fw-semibold">Tipo de documento</label>
                                        <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                                            <option value="">Seleccione...</option>
                                            @foreach($requisitos as $requisito)
                                                <option value="{{ $requisito['clave'] }}">
                                                    {{ $requisito['label'] }}{{ in_array($requisito['clave'], $subidos, true) ? ' (ya recibido)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="archivo" class="form-label small fw-semibold">Archivo</label>
                                        <input type="file" name="archivo" id="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <div class="form-text">PDF, JPG o PNG · máximo 10 MB. El archivo queda asociado al expediente del estudiante.</div>
                                    </div>
                                    <button type="submit" class="btn w-100 text-white" style="background:#0f766e;">
                                        <i class="bi bi-cloud-arrow-up me-1"></i>Subir al expediente
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Datos del expediente</h6>
                        </div>
                        <div class="card-body small">
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted">Institución</dt>
                                <dd class="col-7">{{ $expediente->institucion }}</dd>
                                <dt class="col-5 text-muted">Año lectivo</dt>
                                <dd class="col-7">{{ $expediente->anio_lectivo }}</dd>
                                <dt class="col-5 text-muted">Momento</dt>
                                <dd class="col-7">{{ $expediente->momentoLabel() }}</dd>
                                <dt class="col-5 text-muted">Curso / paralelo</dt>
                                <dd class="col-7">
                                    {{ $expediente->curso?->nombre ?? '—' }}
                                    {{ $expediente->paralelo?->nombre ?? '' }}
                                </dd>
                                <dt class="col-5 text-muted">Creado por</dt>
                                <dd class="col-7">{{ $expediente->creador?->nombreCompleto() ?? '—' }} · {{ $expediente->created_at?->format('d/m/Y H:i') }}</dd>
                                @if($expediente->validado_at)
                                    <dt class="col-5 text-muted">Validado por</dt>
                                    <dd class="col-7">{{ $expediente->validador?->nombreCompleto() ?? '—' }} · {{ $expediente->validado_at->format('d/m/Y H:i') }}</dd>
                                @endif
                            </dl>

                            @if(! $cerrado)
                                <hr>
                                <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#editarExpediente">
                                    <i class="bi bi-pencil-square me-1"></i>Editar datos generales
                                </button>
                                <div class="collapse mt-3" id="editarExpediente">
                                    <form method="POST" action="{{ route('traslados.update', $expediente) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="mb-2">
                                            <label class="form-label small">Fecha del traslado</label>
                                            <input type="date" class="form-control form-control-sm" name="fecha_traslado" value="{{ $expediente->fecha_traslado?->toDateString() }}" required>
                                        </div>
                                        @if($esIngreso)
                                            <div class="mb-2">
                                                <label class="form-label small">Momento del traslado</label>
                                                <select class="form-select form-select-sm" name="momento">
                                                    <option value="inicio" @selected($expediente->momento === 'inicio')>Antes de iniciar el año</option>
                                                    <option value="transcurso" @selected($expediente->momento === 'transcurso')>En transcurso del año</option>
                                                </select>
                                            </div>
                                        @endif
                                        <div class="mb-2">
                                            <label class="form-label small">Institución</label>
                                            <input type="text" class="form-control form-control-sm" name="institucion" value="{{ $expediente->institucion }}" maxlength="255" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Año lectivo</label>
                                            <input type="text" class="form-control form-control-sm" name="anio_lectivo" value="{{ $expediente->anio_lectivo }}" maxlength="20" required>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label small">Curso</label>
                                                <select class="form-select form-select-sm" name="curso_id">
                                                    <option value="">—</option>
                                                    @foreach(\App\Models\Curso::enOrdenEscolar()->get() as $curso)
                                                        <option value="{{ $curso->id }}" @selected($expediente->curso_id === $curso->id)>{{ $curso->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small">Paralelo</label>
                                                <select class="form-select form-select-sm" name="paralelo_id">
                                                    <option value="">—</option>
                                                    @foreach(\App\Models\Paralelo::orderBy('nombre')->get() as $paralelo)
                                                        <option value="{{ $paralelo->id }}" @selected($expediente->paralelo_id === $paralelo->id)>{{ $paralelo->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Observaciones</label>
                                            <textarea class="form-control form-control-sm" name="observaciones" rows="2" maxlength="2000">{{ $expediente->observaciones }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary w-100">Guardar cambios</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="panel-homologacion" role="tabpanel">
            <div class="alert alert-info border-0 shadow-sm small">
                <i class="bi bi-info-circle me-1"></i>
                RN-05: las notas externas conservan la escala y la ponderación originales de la institución de origen.
                Una vez cerrado el expediente, este historial es de solo lectura (RN-03).
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i>Promociones de años anteriores</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle table-hover tabla-expediente mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Año</th>
                                            <th>Nivel</th>
                                            <th class="text-end">Promedio</th>
                                            <th>Escala</th>
                                            @if(! $cerrado)<th></th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expediente->promociones as $promocion)
                                            <tr>
                                                <td data-label="Año">{{ $promocion->anio_lectivo }}</td>
                                                <td data-label="Nivel">{{ $promocion->nivel }}</td>
                                                <td class="text-end fw-semibold" data-label="Promedio">{{ number_format((float) $promocion->promedio, 2) }}</td>
                                                <td data-label="Escala"><span class="badge bg-light text-dark border">{{ $promocion->escala }}</span></td>
                                                @if(! $cerrado)
                                                    <td class="text-end" data-label="Acción">
                                                        <form method="POST" action="{{ route('traslados.promociones.destroy', [$expediente, $promocion]) }}" class="d-inline form-confirmar"
                                                              data-confirmar="¿Eliminar esta promoción del historial externo?">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $cerrado ? 4 : 5 }}" class="text-center text-muted py-3">Sin promociones registradas.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(! $cerrado)
                                <form method="POST" action="{{ route('traslados.promociones.store', $expediente) }}" class="row g-2">
                                    @csrf
                                    <div class="col-6">
                                        <label class="form-label small">Año lectivo</label>
                                        <input type="text" class="form-control form-control-sm" name="anio_lectivo" placeholder="2024 - 2025" maxlength="20" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Nivel / grado</label>
                                        <input type="text" class="form-control form-control-sm" name="nivel" placeholder="9no EGB" maxlength="100" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Promedio</label>
                                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="promedio" placeholder="8.75" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Escala de origen</label>
                                        <input type="text" class="form-control form-control-sm" name="escala" value="0-10" maxlength="50" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Observación</label>
                                        <input type="text" class="form-control form-control-sm" name="observacion" maxlength="1000">
                                    </div>
                                    <div class="col-12 d-grid">
                                        <button type="submit" class="btn btn-sm text-white" style="background:#0f766e;">
                                            <i class="bi bi-plus-lg me-1"></i>Agregar promoción
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Notas parciales del año en curso</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle table-hover tabla-expediente mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asignatura</th>
                                            <th>Período</th>
                                            <th class="text-end">Nota</th>
                                            <th class="text-end">Pond.</th>
                                            <th class="text-end">Asist.</th>
                                            <th>Escala</th>
                                            @if(! $cerrado)<th></th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expediente->notasParciales as $notaExterna)
                                            <tr>
                                                <td data-label="Asignatura">{{ $notaExterna->asignatura }}</td>
                                                <td data-label="Período">{{ $notaExterna->periodo }}</td>
                                                <td class="text-end fw-semibold" data-label="Nota">{{ number_format((float) $notaExterna->nota, 2) }}</td>
                                                <td class="text-end" data-label="Ponderación">{{ $notaExterna->ponderacion !== null ? number_format((float) $notaExterna->ponderacion, 2).'%' : '—' }}</td>
                                                <td class="text-end" data-label="Asistencia">{{ $notaExterna->asistencia_porcentaje !== null ? number_format((float) $notaExterna->asistencia_porcentaje, 2).'%' : '—' }}</td>
                                                <td data-label="Escala"><span class="badge bg-light text-dark border">{{ $notaExterna->escala }}</span></td>
                                                @if(! $cerrado)
                                                    <td class="text-end" data-label="Acción">
                                                        <form method="POST" action="{{ route('traslados.notas.destroy', [$expediente, $notaExterna]) }}" class="d-inline form-confirmar"
                                                              data-confirmar="¿Eliminar esta nota parcial del historial externo?">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $cerrado ? 6 : 7 }}" class="text-center text-muted py-3">Sin notas parciales registradas.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(! $cerrado)
                                <form method="POST" action="{{ route('traslados.notas.store', $expediente) }}" class="row g-2">
                                    @csrf
                                    <div class="col-6">
                                        <label class="form-label small">Asignatura</label>
                                        <input type="text" class="form-control form-control-sm" name="asignatura" maxlength="150" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Período</label>
                                        <input type="text" class="form-control form-control-sm" name="periodo" placeholder="P1 Trimestre 1" maxlength="80" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Nota</label>
                                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="nota" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Ponderación %</label>
                                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="ponderacion">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Asistencia %</label>
                                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="asistencia_porcentaje">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Escala de origen</label>
                                        <input type="text" class="form-control form-control-sm" name="escala" value="0-10" maxlength="50" required>
                                    </div>
                                    <div class="col-12 d-grid">
                                        <button type="submit" class="btn btn-sm text-white" style="background:#0f766e;">
                                            <i class="bi bi-plus-lg me-1"></i>Agregar nota parcial
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="panel-cierre" role="tabpanel">
            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Paz y salvo manual</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">
                                Mientras no existan los módulos financiero y de biblioteca, Secretaría registra manualmente la verificación.
                                RN-02 bloquea el certificado de salida si alguno queda pendiente.
                            </p>
                            @if($cerrado)
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Financiero</span>
                                        <span class="badge {{ $expediente->paz_y_salvo_financiero ? 'bg-success' : 'bg-danger' }}">{{ $expediente->paz_y_salvo_financiero ? 'Conforme' : 'Pendiente' }}</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Biblioteca / inventario</span>
                                        <span class="badge {{ $expediente->paz_y_salvo_biblioteca ? 'bg-success' : 'bg-danger' }}">{{ $expediente->paz_y_salvo_biblioteca ? 'Conforme' : 'Pendiente' }}</span>
                                    </div>
                                </div>
                                @if($expediente->paz_y_salvo_observacion)
                                    <div class="alert alert-light border small mt-3 mb-0">{{ $expediente->paz_y_salvo_observacion }}</div>
                                @endif
                            @else
                                <form method="POST" action="{{ route('traslados.paz-salvo.update', $expediente) }}">
                                    @csrf
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="pazFinanciero" name="paz_y_salvo_financiero" value="1"
                                               @checked($expediente->paz_y_salvo_financiero)>
                                        <label class="form-check-label small" for="pazFinanciero">Sin deudas financieras pendientes</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" role="switch" id="pazBiblioteca" name="paz_y_salvo_biblioteca" value="1"
                                               @checked($expediente->paz_y_salvo_biblioteca)>
                                        <label class="form-check-label small" for="pazBiblioteca">Sin material bibliográfico o inventario pendiente</label>
                                    </div>
                                    <div class="mb-3">
                                        <label for="paz_y_salvo_observacion" class="form-label small">Observación</label>
                                        <textarea class="form-control form-control-sm" id="paz_y_salvo_observacion" name="paz_y_salvo_observacion" rows="2" maxlength="1000">{{ $expediente->paz_y_salvo_observacion }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary w-100">Guardar paz y salvo</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Validación y cierre</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Documentación obligatoria</span>
                                    <span class="badge {{ $expediente->documentos_completos ? 'bg-success' : 'bg-danger' }}">
                                        {{ $expediente->documentos_completos ? '100% completa' : count($faltantes).' faltante(s)' }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Estado del expediente</span>
                                    <span class="badge bg-{{ $expediente->estadoBadge() }}">{{ $expediente->estadoLabel() }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Matrícula</span>
                                    <span class="badge bg-{{ $expediente->matricula?->estado === 'provisional' ? 'warning text-dark' : 'success' }}">
                                        {{ $expediente->matricula?->estado === 'provisional' ? 'Provisional' : 'Definitiva' }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>Certificado de salida (RN-02)</span>
                                    @if($expediente->tipo !== 'salida')
                                        <span class="badge bg-light text-muted border">No aplica</span>
                                    @elseif($expediente->puedeEmitirCertificadoSalida())
                                        <span class="badge bg-success">Habilitado</span>
                                    @else
                                        <span class="badge bg-danger">Bloqueado</span>
                                    @endif
                                </li>
                            </ul>

                            @if($cerrado)
                                <div class="alert alert-secondary small mb-0">
                                    <i class="bi bi-lock me-1"></i>El expediente ya fue validado y cerrado. No admite más cambios.
                                </div>
                            @else
                                <div class="alert alert-warning small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    RN-04: no se puede forzar la matrícula definitiva. Se exige el 100% de los documentos obligatorios.
                                </div>
                                <form method="POST" action="{{ route('traslados.cerrar', $expediente) }}" class="form-confirmar"
                                      data-confirmar="¿Validar y cerrar el expediente? El historial externo quedará inmutable y no podrá modificarse.">
                                    @csrf
                                    <button type="submit" class="btn btn-dark w-100" {{ $expediente->documentos_completos ? '' : 'disabled' }}>
                                        <i class="bi bi-shield-check me-1"></i>Validar y cerrar expediente
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="panel-bitacora" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Bitácora del expediente</h6>
                    <span class="badge bg-secondary">{{ $expediente->auditorias->count() }} asiento(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 tabla-expediente">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Acción</th>
                                    <th>Detalle</th>
                                    <th>Usuario</th>
                                    <th class="pe-3">Trazabilidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expediente->auditorias as $auditoria)
                                    <tr>
                                        <td class="ps-3 small text-nowrap" data-label="Fecha">
                                            {{ $auditoria->created_at?->format('d/m/Y') }}<br>
                                            <span class="text-muted">{{ $auditoria->created_at?->format('H:i:s') }}</span>
                                        </td>
                                        <td data-label="Acción"><span class="badge bg-light text-dark border">{{ $auditoria->accionLabel() }}</span></td>
                                        <td class="small" data-label="Detalle">{{ $auditoria->detalle ?: '—' }}</td>
                                        <td class="small" data-label="Usuario">
                                            {{ $auditoria->usuario_nombre }}<br>
                                            <span class="badge bg-secondary">{{ $auditoria->usuario_rol }}</span>
                                        </td>
                                        <td class="small pe-3" data-label="Trazabilidad">
                                            <i class="bi bi-hdd-network me-1"></i>{{ $auditoria->ip ?: '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Sin movimientos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.form-confirmar').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    title: 'Confirmar acción',
                    text: form.dataset.confirmar || '¿Desea continuar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0f766e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
