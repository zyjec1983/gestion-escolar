@extends('layouts.principal')

@section('titulo', "Solicitud {$preRegistro->codigo_solicitud}")

@section('contenido')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.pre-registros.index') }}" class="text-decoration-none text-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h5 class="d-inline mb-0">Solicitud {{ $preRegistro->codigo_solicitud }}</h5>
            @switch($preRegistro->estado)
                @case('pendiente') <span class="badge bg-warning text-dark ms-2">Pendiente</span> @break
                @case('aprobado') <span class="badge bg-success ms-2">Aprobado</span> @break
                @case('rechazado') <span class="badge bg-danger ms-2">Rechazado</span> @break
            @endswitch
            @if($preRegistro->tipo_solicitud)
            <span class="badge bg-info text-dark ms-1">{{ str_replace('_', ' ', ucfirst($preRegistro->tipo_solicitud)) }}</span>
            @endif
        </div>
        <div>
            <a href="{{ route('admin.pre-registros.pdf', $preRegistro->id) }}" class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="bi bi-file-earmark-pdf me-1"></i>Ver PDF
            </a>
            @if($preRegistro->registrado_por)
            <small class="text-muted ms-2"><i class="bi bi-shield-lock me-1"></i>Registro manual</small>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-person-fill me-2 text-primary"></i>Datos del Representante</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th class="w-35 text-muted">Documento</th><td>{{ strtoupper($preRegistro->rep_tipo_documento) }}: {{ $preRegistro->rep_numero_documento }}</td></tr>
                        <tr><th class="text-muted">Nombres</th><td>{{ $preRegistro->rep_nombres }}</td></tr>
                        <tr><th class="text-muted">Apellidos</th><td>{{ $preRegistro->rep_apellidos }}</td></tr>
                        <tr><th class="text-muted">Email</th><td>{{ $preRegistro->rep_email }}</td></tr>
                        <tr><th class="text-muted">Fecha de nacimiento</th><td>{{ $preRegistro->rep_fecha_nacimiento?->format('d/m/Y') ?? 'No registrada' }}</td></tr>
                        <tr><th class="text-muted">Teléfono</th><td>{{ $preRegistro->rep_telefono ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-person-vcard me-2 text-primary"></i>Datos del Estudiante</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th class="w-35 text-muted">Documento</th><td>{{ strtoupper($preRegistro->alumno_tipo_documento) }}: {{ $preRegistro->alumno_numero_documento }}</td></tr>
                        <tr><th class="text-muted">Cédula</th><td>{{ $preRegistro->alumno_numero_cedula ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Apellidos</th><td>{{ $preRegistro->alumno_apellidos }}</td></tr>
                        <tr><th class="text-muted">Nombres</th><td>{{ $preRegistro->alumno_nombres }}</td></tr>
                        <tr><th class="text-muted">Tipo Sangre</th><td>{{ $preRegistro->alumno_tipo_sangre ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Fecha Nac.</th><td>{{ $preRegistro->alumno_fecha_nacimiento?->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Género</th><td>{{ $preRegistro->alumno_genero ? ucfirst($preRegistro->alumno_genero) : '-' }}</td></tr>
                        <tr><th class="text-muted">Etnia</th><td>{{ $preRegistro->alumno_etnia ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Nacionalidad</th><td>{{ $preRegistro->alumno_nacionalidad ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-book me-2 text-primary"></i>Selección Académica</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Curso:</strong> {{ $preRegistro->curso->nombre ?? '-' }}</div>
                        <div class="col-md-2"><strong>Paralelo:</strong> {{ $preRegistro->paralelo?->nombre ?? '-' }}</div>
                        <div class="col-md-3"><strong>Período:</strong> {{ $preRegistro->periodo->nombre ?? '-' }}</div>
                        <div class="col-md-2"><strong>Especialización:</strong> {{ $preRegistro->especializacion ?? '-' }}</div>
                        <div class="col-md-2"><strong>Matrícula:</strong> {{ $preRegistro->tipo_solicitud ? str_replace('_', ' ', $preRegistro->tipo_solicitud) : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($preRegistro->alumno_fecha_nacimiento || $preRegistro->alumno_telefono_convencional || $preRegistro->alumno_celular)
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Datos Complementarios del Estudiante</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th class="w-35 text-muted">Telf. Convencional</th><td>{{ $preRegistro->alumno_telefono_convencional ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Celular</th><td>{{ $preRegistro->alumno_celular ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Email Activo</th><td>{{ $preRegistro->alumno_email_activo ?? '-' }}</td></tr>
                        <tr><th class="text-muted">N° Hermanos</th><td>{{ $preRegistro->alumno_numero_hermanos ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Lugar entre Hermanos</th><td>{{ $preRegistro->alumno_lugar_entre_hermanos ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Inst. Procedencia</th><td>{{ $preRegistro->alumno_institucion_procedencia ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Vive con</th><td>{{ $preRegistro->alumno_con_quien_vive ?? '-' }} ({{ $preRegistro->alumno_parentesco_convivencia ?? '-' }})</td></tr>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($preRegistro->alumno_padece_enfermedad || $preRegistro->alumno_toma_medicina || $preRegistro->alumno_tiene_nee || $preRegistro->alumno_tipo_discapacidad)
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-heart-pulse me-2 text-primary"></i>Ficha Médica</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th class="w-35 text-muted">Enfermedad</th><td>{{ $preRegistro->alumno_padece_enfermedad ? ($preRegistro->alumno_nombre_enfermedad ?? 'Sí') : 'No' }}</td></tr>
                        <tr><th class="text-muted">Tratamiento</th><td>{{ $preRegistro->alumno_tiene_tratamiento ? 'Sí' : 'No' }}</td></tr>
                        <tr><th class="text-muted">Medicina</th><td>{{ $preRegistro->alumno_toma_medicina ? ($preRegistro->alumno_nombre_medicina ?? 'Sí') : 'No' }}</td></tr>
                        <tr><th class="text-muted">NEE</th><td>{{ $preRegistro->alumno_tiene_nee ? ($preRegistro->alumno_nombre_nee ?? 'Sí') : 'No' }}</td></tr>
                        <tr><th class="text-muted">Discapacidad</th><td>{{ $preRegistro->alumno_tipo_discapacidad ?? 'No' }}</td></tr>
                        <tr><th class="text-muted">Carnet Disc.</th><td>{{ $preRegistro->alumno_tiene_carnet_discapacidad ? ($preRegistro->alumno_numero_carnet ?? 'Sí') : 'No' }}</td></tr>
                        <tr><th class="text-muted">Inf. Psicoped.</th><td>{{ $preRegistro->alumno_tiene_informe_psicopedagogico ? 'Sí' : 'No' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($preRegistro->padre_nombres_apellidos || $preRegistro->madre_nombres_apellidos)
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-people me-2 text-primary"></i>Datos Familiares</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-secondary">Padre</h6>
                            <table class="table table-sm">
                                <tr><th class="w-30 text-muted">Nombre</th><td>{{ $preRegistro->padre_nombres_apellidos ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Cédula</th><td>{{ $preRegistro->padre_cedula ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Teléfono</th><td>{{ $preRegistro->padre_telefono ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Profesión</th><td>{{ $preRegistro->padre_profesion ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Facturador</th><td>{{ $preRegistro->padre_es_facturador ? 'Sí' : 'No' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary">Madre</h6>
                            <table class="table table-sm">
                                <tr><th class="w-30 text-muted">Nombre</th><td>{{ $preRegistro->madre_nombres_apellidos ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Cédula</th><td>{{ $preRegistro->madre_cedula ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Teléfono</th><td>{{ $preRegistro->madre_telefono ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Profesión</th><td>{{ $preRegistro->madre_profesion ?? '-' }}</td></tr>
                                <tr><th class="text-muted">Facturador</th><td>{{ $preRegistro->madre_es_facturador ? 'Sí' : 'No' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($preRegistro->facturador_nombre)
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Facturación</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th class="w-30 text-muted">Nombre</th><td>{{ $preRegistro->facturador_nombre }}</td></tr>
                        <tr><th class="text-muted">Cédula</th><td>{{ $preRegistro->facturador_cedula }}</td></tr>
                        <tr><th class="text-muted">Teléfono</th><td>{{ $preRegistro->facturador_telefono }}</td></tr>
                        <tr><th class="text-muted">Dirección</th><td>{{ $preRegistro->facturador_direccion }}</td></tr>
                        <tr><th class="text-muted">Email</th><td>{{ $preRegistro->facturador_email }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($preRegistro->alterno_nombre)
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-person-check me-2 text-primary"></i>Representante Alterno</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th class="w-30 text-muted">Nombre</th><td>{{ $preRegistro->alterno_nombre }}</td></tr>
                        <tr><th class="text-muted">Parentesco</th><td>{{ $preRegistro->alterno_parentesco ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Cédula</th><td>{{ $preRegistro->alterno_cedula ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Teléfono</th><td>{{ $preRegistro->alterno_telefono ?? '-' }}</td></tr>
                        <tr><th class="text-muted">Email</th><td>{{ $preRegistro->alterno_email ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($preRegistro->vivienda_provincia || $preRegistro->vivienda_sector)
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-house-door me-2 text-primary"></i>Datos de Vivienda</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Provincia:</strong> {{ $preRegistro->vivienda_provincia ?? '-' }}</div>
                        <div class="col-md-3"><strong>Cantón:</strong> {{ $preRegistro->vivienda_canton ?? '-' }}</div>
                        <div class="col-md-3"><strong>Parroquia:</strong> {{ $preRegistro->vivienda_parroquia ?? '-' }}</div>
                        <div class="col-md-3"><strong>Sector:</strong> {{ $preRegistro->vivienda_sector ?? '-' }}</div>
                        <div class="col-md-6 mt-2"><strong>Referencia:</strong> {{ $preRegistro->vivienda_referencia ?? '-' }}</div>
                        <div class="col-md-2 mt-2"><strong>Casa Propia:</strong> {{ $preRegistro->vivienda_casa_propia ? 'Sí' : 'No' }}</div>
                        <div class="col-md-2 mt-2"><strong>Agua:</strong> {{ $preRegistro->vivienda_tiene_agua ? 'Sí' : 'No' }}</div>
                        <div class="col-md-2 mt-2"><strong>SS.HH.:</strong> {{ $preRegistro->vivienda_tiene_sshh ? 'Sí' : 'No' }}</div>
                        <div class="col-md-2 mt-2"><strong>Luz:</strong> {{ $preRegistro->vivienda_tiene_luz ? 'Sí' : 'No' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($preRegistro->alumno_observaciones)
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-chat-dots me-2 text-primary"></i>Observaciones</div>
                <div class="card-body">
                    <p class="mb-0">{{ $preRegistro->alumno_observaciones }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    @if($preRegistro->estado === 'pendiente')
    <div class="row g-3 mt-4">
        <div class="col-md-6">
            <form method="POST" action="{{ route('admin.pre-registros.aprobar', $preRegistro->id) }}">
                @csrf
                <label class="form-label small">Fecha de nacimiento del representante <span class="text-danger">*</span></label>
                <input type="date" name="rep_fecha_nacimiento"
                       class="form-control mb-1 @error('rep_fecha_nacimiento') is-invalid @enderror"
                       value="{{ old('rep_fecha_nacimiento', $preRegistro->rep_fecha_nacimiento?->format('Y-m-d')) }}" required>
                <small class="text-muted">Debe ser mayor de edad. Las solicitudes anteriores a este dato se completan aqui.</small>
                @error('rep_fecha_nacimiento') <small class="text-danger d-block">{{ $message }}</small> @enderror
                <button type="submit" class="btn btn-success w-100 py-3 mt-2" onclick="return confirm('¿Aprobar esta solicitud? Se creará el usuario representante automáticamente.')">
                    <i class="bi bi-check-circle me-2"></i>Aprobar Solicitud
                </button>
            </form>
        </div>
        <div class="col-md-6">
            <button type="button" class="btn btn-outline-danger w-100 py-3" data-bs-toggle="modal" data-bs-target="#rechazarModal">
                <i class="bi bi-x-circle me-2"></i>Rechazar Solicitud
            </button>
        </div>
    </div>

    <div class="modal fade" id="rechazarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.pre-registros.rechazar', $preRegistro->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title">Rechazar Solicitud</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="comentario_rechazo" class="form-control" rows="4" required placeholder="Indique la razón por la cual se rechaza esta solicitud..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Rechazar Solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @elseif($preRegistro->comentario_rechazo)
    <div class="card shadow-sm mt-4 border-danger">
        <div class="card-header bg-danger text-white fw-bold"><i class="bi bi-x-circle me-2"></i>Motivo del Rechazo</div>
        <div class="card-body">
            <p class="mb-0">{{ $preRegistro->comentario_rechazo }}</p>
            @if($preRegistro->aprobadoPor)
            <small class="text-muted">Rechazado por: {{ $preRegistro->aprobadoPor->name }}</small>
            @endif
        </div>
    </div>
    @elseif($preRegistro->estado === 'aprobado')
    <div class="card shadow-sm mt-4 border-success">
        <div class="card-header bg-success text-white"><i class="bi bi-check-circle me-2"></i>Solicitud Aprobada</div>
        <div class="card-body">
            <p class="mb-0 text-success fw-bold">Esta solicitud fue aprobada y el representante ya tiene acceso al sistema.</p>
            @if($preRegistro->aprobadoPor)
            <small class="text-muted">Aprobado por: {{ $preRegistro->aprobadoPor->name }} - {{ $preRegistro->fecha_aprobacion?->format('d/m/Y H:i') }}</small>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
