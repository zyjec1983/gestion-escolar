@extends('layouts.principal')

@section('titulo', 'Ficha de Matrícula')

@section('contenido')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Ficha de Matrícula</h4>
        <a href="{{ route('admin.pre-registros.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ENCABEZADO INSTITUCIONAL --}}
    <div class="card shadow-sm mb-4 text-center">
        <div class="card-body py-3">
            <h5 class="fw-bold mb-1">UNIDAD EDUCATIVA "FRANCISCANO SALITRE"</h5>
            <h6 class="text-muted mb-1">HERMANAS FRANCISCANAS DE MARÍA INMACULADA</h6>
            <small class="d-block mb-1">DIR. AVENIDA 27 DE NOVIEMBRE Y AV. REPÚBLICA DE AUSTRIA</small>
            <small class="d-block mb-1">TELF. 2792-252 &nbsp;|&nbsp; AMIE: 09H04858 &nbsp;|&nbsp; EMAIL: franciscanosalitre@hotmail.com</small>
            <small class="d-block">Salitre - Ecuador</small>
            <hr class="my-2">
            <h5 class="fw-bold mb-1">FICHA DE MATRÍCULA {{ $matricula->periodo->nombre ?? '2026-2027' }}</h5>
            <small class="text-muted">DATOS GENERALES, BÁSICOS Y ACTUALIZADOS DEL ESTUDIANTE</small><br>
            <small class="text-muted">(La ficha debe ser llenada en computadora o a mano con letra legible y tener una presentación nítida sin tachones, no enmendaduras, no arrugadas, no dobladas)</small>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.matriculas.update', $matricula->id) }}">
        @csrf
        @method('PUT')

        {{-- SECCIÓN: Datos del Estudiante --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-person-vcard me-2"></i>Datos del Estudiante</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"># Cédula</label>
                        <input type="text" name="numero_cedula" class="form-control" value="{{ old('numero_cedula', $matricula->alumno->numero_cedula) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Apellidos y Nombres *</label>
                        <input type="text" id="apellidos_nombres_input" class="form-control" value="{{ old('apellidos_nombres', $matricula->alumno->apellidos . ' ' . $matricula->alumno->nombres) }}" required>
                        <input type="hidden" name="nombres" id="nombres_hidden" value="{{ old('nombres', $matricula->alumno->nombres) }}">
                        <input type="hidden" name="apellidos" id="apellidos_hidden" value="{{ old('apellidos', $matricula->alumno->apellidos) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo de Sangre</label>
                        @include('partials.tipo-sangre', ['name' => 'tipo_sangre', 'value' => $matricula->alumno->tipo_sangre])
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Nac. (año, mes, día)</label>
                        <input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', $matricula->alumno->fecha_nacimiento?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Género</label>
                        <select name="genero" class="form-select">
                            <option value="">--</option>
                            <option value="masculino" {{ old('genero', $matricula->alumno->genero) == 'masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="femenino" {{ old('genero', $matricula->alumno->genero) == 'femenino' ? 'selected' : '' }}>Femenino</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Etnia</label>
                        <input type="text" name="etnia" class="form-control" value="{{ old('etnia', $matricula->alumno->etnia) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Nacionalidad</label>
                        <input type="text" name="nacionalidad" class="form-control" value="{{ old('nacionalidad', $matricula->alumno->nacionalidad) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Telf. Convencional Alumno</label>
                        <input type="text" name="telefono_convencional" class="form-control" value="{{ old('telefono_convencional', $matricula->alumno->telefono_convencional) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cel. Alumno</label>
                        <input type="text" name="celular" class="form-control" value="{{ old('celular', $matricula->alumno->celular) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email correo activo del Alumno</label>
                        <input type="email" name="email_activo" class="form-control" value="{{ old('email_activo', $matricula->alumno->email_activo) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">N° Hermanos</label>
                        <input type="number" name="numero_hermanos" class="form-control" min="0" value="{{ old('numero_hermanos', $matricula->alumno->numero_hermanos) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Lugar entre Hermanos</label>
                        <input type="number" name="lugar_que_ocupa_entre_hermanos" class="form-control" min="0" value="{{ old('lugar_que_ocupa_entre_hermanos', $matricula->alumno->lugar_que_ocupa_entre_hermanos) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Institución Educativa de Procedencia</label>
                        <input type="text" name="institucion_procedencia" class="form-control" value="{{ old('institucion_procedencia', $matricula->alumno->institucion_procedencia) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">¿Con quién vive el estudiante?</label>
                        <input type="text" name="con_quien_vive" class="form-control" value="{{ old('con_quien_vive', $matricula->alumno->con_quien_vive) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Parentesco</label>
                        <input type="text" name="parentesco_convivencia" class="form-control" value="{{ old('parentesco_convivencia', $matricula->alumno->parentesco_convivencia) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección domiciliaria del estudiante</label>
                        <input type="text" name="direccion_domiciliaria" class="form-control" value="{{ old('direccion_domiciliaria', $matricula->alumno->direccion_domiciliaria) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: Ficha Médica y NEE --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-heart-pulse me-2"></i>Ficha Médica y Necesidades Específicas</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">¿Padece alguna enfermedad?</label>
                        <div class="d-flex gap-4">
                            <div class="form-check"><input class="form-check-input" type="radio" name="padece_enfermedad" value="1" {{ old('padece_enfermedad', $matricula->alumno->padece_enfermedad) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="padece_enfermedad" value="0" {{ !old('padece_enfermedad', $matricula->alumno->padece_enfermedad) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-md-6" id="grupo_enfermedad">
                        <label class="form-label">Indique el nombre de la enfermedad</label>
                        <input type="text" name="nombre_enfermedad" class="form-control" value="{{ old('nombre_enfermedad', $matricula->alumno->nombre_enfermedad) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Tiene un tratamiento a seguir por esa condición?</label>
                        <div class="d-flex gap-4">
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_tratamiento_medico" value="1" {{ old('tiene_tratamiento_medico', $matricula->alumno->tiene_tratamiento_medico) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_tratamiento_medico" value="0" {{ !old('tiene_tratamiento_medico', $matricula->alumno->tiene_tratamiento_medico) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                            <small class="text-muted align-self-center">adjunte documento físico</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Toma alguna medicina?</label>
                        <div class="d-flex gap-4">
                            <div class="form-check"><input class="form-check-input" type="radio" name="toma_medicina" value="1" {{ old('toma_medicina', $matricula->alumno->toma_medicina) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="toma_medicina" value="0" {{ !old('toma_medicina', $matricula->alumno->toma_medicina) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-md-6" id="grupo_medicina">
                        <label class="form-label">Indique el nombre de la medicina</label>
                        <input type="text" name="nombre_medicina" class="form-control" value="{{ old('nombre_medicina', $matricula->alumno->nombre_medicina) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Tiene Necesidades Educativas Específicas?</label>
                        <div class="d-flex gap-4">
                            <div class="form-check"><input class="form-check-input" type="radio" name="necesidades_educativas_especificas_nee" value="1" {{ old('necesidades_educativas_especificas_nee', $matricula->alumno->necesidades_educativas_especificas_nee) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="necesidades_educativas_especificas_nee" value="0" {{ !old('necesidades_educativas_especificas_nee', $matricula->alumno->necesidades_educativas_especificas_nee) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-md-6" id="grupo_nee">
                        <label class="form-label">Indique el nombre de la NEE</label>
                        <input type="text" name="nombre_nee" class="form-control" value="{{ old('nombre_nee', $matricula->alumno->nombre_nee) }}">
                    </div>
                    <div class="col-12"><hr></div>
                    <div class="col-md-4">
                        <label class="form-label">¿Qué tipo de discapacidad tiene?</label>
                        <input type="text" name="tipo_discapacidad" class="form-control" value="{{ old('tipo_discapacidad', $matricula->alumno->tipo_discapacidad) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">¿Tiene carnet de discapacidad?</label>
                        <div class="d-flex gap-4">
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_carnet" value="1" {{ old('tiene_carnet', $matricula->alumno->tiene_carnet) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_carnet" value="0" {{ !old('tiene_carnet', $matricula->alumno->tiene_carnet) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-md-4" id="grupo_carnet">
                        <label class="form-label"># Carnet de discapacidad</label>
                        <input type="text" name="numero_carnet" class="form-control" value="{{ old('numero_carnet', $matricula->alumno->numero_carnet) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Tiene informe psicopedagógico actualizado?</label>
                        <div class="d-flex gap-4">
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_informe_psicopedagogico_actualizado" value="1" {{ old('tiene_informe_psicopedagogico_actualizado', $matricula->alumno->tiene_informe_psicopedagogico_actualizado) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_informe_psicopedagogico_actualizado" value="0" {{ !old('tiene_informe_psicopedagogico_actualizado', $matricula->alumno->tiene_informe_psicopedagogico_actualizado) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                            <small class="text-muted align-self-center">adjunte documento físico</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones_generales" class="form-control" rows="2">{{ old('observaciones_generales', $matricula->alumno->observaciones_generales) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: Datos de Vivienda --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-house-door me-2"></i>Datos de Vivienda</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion_domiciliaria" class="form-control" value="{{ old('direccion_domiciliaria', $matricula->alumno->direccion_domiciliaria) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sector</label>
                        <input type="text" name="sector" class="form-control" value="{{ old('sector', $matricula->alumno->sector) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"># Casa</label>
                        <input type="text" name="numero_casa" class="form-control" value="{{ old('numero_casa', $matricula->alumno->numero_casa) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Referencia del domicilio</label>
                        <input type="text" name="referencia_domicilio" class="form-control" value="{{ old('referencia_domicilio', $matricula->alumno->referencia_domicilio) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provincia</label>
                        <input type="text" name="provincia" class="form-control" value="{{ old('provincia', $matricula->alumno->provincia) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantón</label>
                        <input type="text" name="canton" class="form-control" value="{{ old('canton', $matricula->alumno->canton) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parroquia</label>
                        <input type="text" name="parroquia" class="form-control" value="{{ old('parroquia', $matricula->alumno->parroquia) }}">
                    </div>
                    <div class="col-12"><hr></div>
                    <div class="col-3">
                        <label class="form-label d-block">Casa Propia</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="casa_propia" value="1" {{ old('casa_propia', $matricula->alumno->casa_propia) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="casa_propia" value="0" {{ !old('casa_propia', $matricula->alumno->casa_propia) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-3">
                        <label class="form-label d-block">Agua</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_agua" value="1" {{ old('tiene_agua', $matricula->alumno->tiene_agua) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_agua" value="0" {{ !old('tiene_agua', $matricula->alumno->tiene_agua) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-3">
                        <label class="form-label d-block">SS.HH.</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_sshh_letrina" value="1" {{ old('tiene_sshh_letrina', $matricula->alumno->tiene_sshh_letrina) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_sshh_letrina" value="0" {{ !old('tiene_sshh_letrina', $matricula->alumno->tiene_sshh_letrina) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-3">
                        <label class="form-label d-block">Luz</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_luz_electrica" value="1" {{ old('tiene_luz_electrica', $matricula->alumno->tiene_luz_electrica) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="tiene_luz_electrica" value="0" {{ !old('tiene_luz_electrica', $matricula->alumno->tiene_luz_electrica) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: Datos Familiares --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-people me-2"></i>Datos Familiares</div>
            <div class="card-body">
                <div class="alert alert-warning small py-2 mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>EL TELÉFONO DE CONTACTO DEL REPRESENTANTE LEGAL DEBE SER VÁLIDO. ASÍ MISMO EL CORREO ELECTRÓNICO DEBE ESTAR VIGENTE, YA QUE LAS FACTURAS SE ENVIARÁN AL CORREO ELECTRÓNICO.</strong>
                </div>

                <h6 class="text-secondary border-bottom pb-2 mb-3">Padre</h6>
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label">Nombres y apellidos del Padre</label><input type="text" name="padre_nombres_apellidos" class="form-control" value="{{ old('padre_nombres_apellidos', $matricula->alumno->padre_nombres_apellidos) }}"></div>
                    <div class="col-md-2"><label class="form-label">Cédula</label><input type="text" name="padre_cedula" class="form-control" value="{{ old('padre_cedula', $matricula->alumno->padre_cedula) }}"></div>
                    <div class="col-md-3"><label class="form-label">Teléf. Vigente</label><input type="text" name="padre_telefono" class="form-control" value="{{ old('padre_telefono', $matricula->alumno->padre_telefono) }}"></div>
                    <div class="col-md-2">
                        <label class="form-label">Facturador</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="padre_es_facturador" value="1" {{ old('padre_es_facturador', $matricula->alumno->padre_es_facturador) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="padre_es_facturador" value="0" {{ !old('padre_es_facturador', $matricula->alumno->padre_es_facturador) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-md-4"><label class="form-label">Profesión del Padre o actividad que realiza</label><input type="text" name="padre_profesion" class="form-control" value="{{ old('padre_profesion', $matricula->alumno->padre_profesion) }}"></div>
                    <div class="col-md-5"><label class="form-label">Dirección domicilio</label><input type="text" name="padre_direccion" class="form-control" value="{{ old('padre_direccion', $matricula->alumno->padre_direccion) }}"></div>
                    <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="padre_email" class="form-control" value="{{ old('padre_email', $matricula->alumno->padre_email) }}"></div>
                </div>

                <h6 class="text-secondary border-bottom pb-2 mb-3 mt-4">Madre</h6>
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label">Nombres y apellidos de la Madre</label><input type="text" name="madre_nombres_apellidos" class="form-control" value="{{ old('madre_nombres_apellidos', $matricula->alumno->madre_nombres_apellidos) }}"></div>
                    <div class="col-md-2"><label class="form-label">Cédula</label><input type="text" name="madre_cedula" class="form-control" value="{{ old('madre_cedula', $matricula->alumno->madre_cedula) }}"></div>
                    <div class="col-md-3"><label class="form-label">Teléf. Vigente</label><input type="text" name="madre_telefono" class="form-control" value="{{ old('madre_telefono', $matricula->alumno->madre_telefono) }}"></div>
                    <div class="col-md-2">
                        <label class="form-label">Facturador</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="madre_es_facturador" value="1" {{ old('madre_es_facturador', $matricula->alumno->madre_es_facturador) ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="madre_es_facturador" value="0" {{ !old('madre_es_facturador', $matricula->alumno->madre_es_facturador) ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="col-md-4"><label class="form-label">Profesión de la Madre o actividad que realiza</label><input type="text" name="madre_profesion" class="form-control" value="{{ old('madre_profesion', $matricula->alumno->madre_profesion) }}"></div>
                    <div class="col-md-5"><label class="form-label">Dirección domicilio</label><input type="text" name="madre_direccion" class="form-control" value="{{ old('madre_direccion', $matricula->alumno->madre_direccion) }}"></div>
                    <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="madre_email" class="form-control" value="{{ old('madre_email', $matricula->alumno->madre_email) }}"></div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: Facturación --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-receipt me-2"></i>A nombre de quién se emitirá la factura <span class="small">(DATO OBLIGATORIO QUE DEBE SER LLENADO)</span></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-info btn-sm" id="copiarPadreFacturacion"><i class="bi bi-person-up me-1"></i>Copiar datos del Padre</button>
                        <button type="button" class="btn btn-outline-info btn-sm" id="copiarMadreFacturacion"><i class="bi bi-person-up me-1"></i>Copiar datos de la Madre</button>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label">Nombre y Apellidos del Facturador *</label><input type="text" name="facturador_nombre" class="form-control facturable" value="{{ old('facturador_nombre', $matricula->alumno->facturador_nombre) }}" required></div>
                    <div class="col-md-2"><label class="form-label">Cédula *</label><input type="text" name="facturador_cedula" class="form-control facturable" value="{{ old('facturador_cedula', $matricula->alumno->facturador_cedula) }}" required></div>
                    <div class="col-md-2"><label class="form-label">Teléfono *</label><input type="text" name="facturador_telefono" class="form-control facturable" value="{{ old('facturador_telefono', $matricula->alumno->facturador_telefono) }}" required></div>
                    <div class="col-md-3"><label class="form-label">Email *</label><input type="email" name="facturador_email" class="form-control facturable" value="{{ old('facturador_email', $matricula->alumno->facturador_email) }}" required></div>
                    <div class="col-md-8"><label class="form-label">Dirección *</label><input type="text" name="facturador_direccion" class="form-control facturable" value="{{ old('facturador_direccion', $matricula->alumno->facturador_direccion) }}" required></div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: Representante Alterno --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-person-check me-2"></i>Representante en caso de no ser la Madre o el Padre</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre del Representante</label><input type="text" name="representante_alterno_nombre" class="form-control" value="{{ old('representante_alterno_nombre', $matricula->alumno->representante_alterno_nombre) }}"></div>
                    <div class="col-md-6">
                        <label class="form-label">Parentesco</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check"><input class="form-check-input" type="radio" name="representante_alterno_parentesco" value="Papá" {{ old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco) == 'Papá' ? 'checked' : '' }}><label class="form-check-label">Papá</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="representante_alterno_parentesco" value="Mamá" {{ old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco) == 'Mamá' ? 'checked' : '' }}><label class="form-check-label">Mamá</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="representante_alterno_parentesco" value="Facturador" {{ old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco) == 'Facturador' ? 'checked' : '' }}><label class="form-check-label">Facturador</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="representante_alterno_parentesco" value="" id="radio_alterno_otro" {{ old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco) == '' || (!in_array(old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco), ['Papá','Mamá','Facturador']) && old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco) !== null) ? 'checked' : '' }}><label class="form-check-label">Otro</label></div>
                            <input type="text" name="representante_alterno_parentesco_otro" class="form-control form-control-sm w-auto" id="alterno_otro_texto" placeholder="Especifique" value="{{ old('representante_alterno_parentesco', $matricula->alumno->representante_alterno_parentesco) }}">
                        </div>
                    </div>
                    <div class="col-md-3"><label class="form-label">Cédula</label><input type="text" name="representante_alterno_cedula" class="form-control" value="{{ old('representante_alterno_cedula', $matricula->alumno->representante_alterno_cedula) }}"></div>
                    <div class="col-md-3"><label class="form-label">Teléfono</label><input type="text" name="representante_alterno_telefono" class="form-control" value="{{ old('representante_alterno_telefono', $matricula->alumno->representante_alterno_telefono) }}"></div>
                    <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="representante_alterno_email" class="form-control" value="{{ old('representante_alterno_email', $matricula->alumno->representante_alterno_email) }}"></div>
                    <div class="col-md-3"><label class="form-label">Dirección</label><input type="text" name="representante_alterno_direccion" class="form-control" value="{{ old('representante_alterno_direccion', $matricula->alumno->representante_alterno_direccion) }}"></div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: MATRICULANDO --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-card-checklist me-2"></i>Datos Obligatorios - Matriculando</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Fecha de Matrícula</label>
                        <input type="date" name="fecha_matricula" class="form-control" value="{{ old('fecha_matricula', $matricula->created_at?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Curso *</label>
                        <select name="curso_id" class="form-select" required>
                            @foreach($cursos as $curso)
                                <option value="{{ $curso->id }}" {{ old('curso_id', $matricula->curso_id) == $curso->id ? 'selected' : '' }}>{{ $curso->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Paralelo *</label>
                        <select name="paralelo_id" class="form-select" required>
                            @foreach($paralelos as $p)
                                <option value="{{ $p->id }}" {{ old('paralelo_id', $matricula->paralelo_id) == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Periodo *</label>
                        <select name="periodo_id" class="form-select" required>
                            @foreach($periodos as $per)
                                <option value="{{ $per->id }}" {{ old('periodo_id', $matricula->periodo_id) == $per->id ? 'selected' : '' }}>{{ $per->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Especialización</label>
                        <input type="text" name="especializacion" class="form-control" value="{{ old('especializacion', $matricula->especializacion) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN: Firmas --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><i class="bi bi-pen me-2"></i>Legalización de Firmas</div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4 border-end">
                        <div class="py-4 px-2">
                            <div class="form-check form-switch d-flex justify-content-center mb-2">
                                <input class="form-check-input" type="checkbox" name="firma_rectora_completada" value="1" {{ old('firma_rectora_completada', $matricula->firma_rectora_completada) ? 'checked' : '' }}>
                            </div>
                            <p class="fw-semibold mb-0">F. RECTORA</p>
                            <small class="text-muted">f: {{ $matricula->firma_rectora_completada ? 'Completada' : 'Pendiente' }}</small>
                        </div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="py-4 px-2">
                            <div class="form-check form-switch d-flex justify-content-center mb-2">
                                <input class="form-check-input" type="checkbox" name="firma_representante_completada" value="1" {{ old('firma_representante_completada', $matricula->firma_representante_completada) ? 'checked' : '' }}>
                            </div>
                            <p class="fw-semibold mb-0">F. REPRESENTANTE</p>
                            <small class="text-muted">f: {{ $matricula->firma_representante_completada ? 'Completada' : 'Pendiente' }}</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="py-4 px-2">
                            <div class="form-check form-switch d-flex justify-content-center mb-2">
                                <input class="form-check-input" type="checkbox" name="firma_secretaria_completada" value="1" {{ old('firma_secretaria_completada', $matricula->firma_secretaria_completada) ? 'checked' : '' }}>
                            </div>
                            <p class="fw-semibold mb-0">F. SECRETARÍA</p>
                            <small class="text-muted">f: {{ $matricula->firma_secretaria_completada ? 'Completada' : 'Pendiente' }}</small>
                        </div>
                    </div>
                </div>
                <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Para las firmas físicas solo con bolígrafo azul.</p>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('admin.pre-registros.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy me-1"></i>Guardar Ficha de Matrícula</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleField(radioName, groupId) {
        const radios = document.querySelectorAll(`input[name="${radioName}"]`);
        const group = document.getElementById(groupId);
        function update() {
            const checked = document.querySelector(`input[name="${radioName}"]:checked`);
            if (group) group.style.display = (checked && checked.value === '1') ? 'block' : 'none';
        }
        radios.forEach(r => r.addEventListener('change', update));
        update();
    }

    toggleField('padece_enfermedad', 'grupo_enfermedad');
    toggleField('toma_medicina', 'grupo_medicina');
    toggleField('necesidades_educativas_especificas_nee', 'grupo_nee');
    toggleField('tiene_carnet', 'grupo_carnet');

    document.getElementById('copiarPadreFacturacion').addEventListener('click', function () {
        document.querySelector('input[name="facturador_nombre"]').value = document.querySelector('input[name="padre_nombres_apellidos"]').value || '';
        document.querySelector('input[name="facturador_cedula"]').value = document.querySelector('input[name="padre_cedula"]').value || '';
        document.querySelector('input[name="facturador_telefono"]').value = document.querySelector('input[name="padre_telefono"]').value || '';
        document.querySelector('input[name="facturador_direccion"]').value = document.querySelector('input[name="padre_direccion"]').value || '';
        document.querySelector('input[name="facturador_email"]').value = document.querySelector('input[name="padre_email"]').value || '';
    });

    document.getElementById('copiarMadreFacturacion').addEventListener('click', function () {
        document.querySelector('input[name="facturador_nombre"]').value = document.querySelector('input[name="madre_nombres_apellidos"]').value || '';
        document.querySelector('input[name="facturador_cedula"]').value = document.querySelector('input[name="madre_cedula"]').value || '';
        document.querySelector('input[name="facturador_telefono"]').value = document.querySelector('input[name="madre_telefono"]').value || '';
        document.querySelector('input[name="facturador_direccion"]').value = document.querySelector('input[name="madre_direccion"]').value || '';
        document.querySelector('input[name="facturador_email"]').value = document.querySelector('input[name="madre_email"]').value || '';
    });

    const radiosAlterno = document.querySelectorAll('input[name="representante_alterno_parentesco"]');
    const otroTexto = document.getElementById('alterno_otro_texto');
    function updateAlterno() {
        const checked = document.querySelector('input[name="representante_alterno_parentesco"]:checked');
        if (checked && !checked.value) {
            otroTexto.style.display = 'inline-block';
        } else {
            otroTexto.style.display = 'none';
        }
    }
    radiosAlterno.forEach(r => r.addEventListener('change', updateAlterno));
    updateAlterno();

    otroTexto.addEventListener('input', function () {
        document.getElementById('radio_alterno_otro').checked = true;
    });

    const form = document.querySelector('form');
    const apellidosInput = document.getElementById('apellidos_nombres_input');
    const nombresHidden = document.getElementById('nombres_hidden');
    const apellidosHidden = document.getElementById('apellidos_hidden');

    form.addEventListener('submit', function () {
        const val = apellidosInput.value.trim();
        if (val) {
            const words = val.split(/\s+/);
            if (words.length <= 2) {
                apellidosHidden.value = words[0] || '';
                nombresHidden.value = words[1] || '';
            } else {
                apellidosHidden.value = words.slice(0, Math.ceil(words.length / 2)).join(' ');
                nombresHidden.value = words.slice(Math.ceil(words.length / 2)).join(' ');
            }
        }
    });
});
</script>
@endpush
