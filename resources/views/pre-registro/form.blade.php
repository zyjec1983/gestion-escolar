@extends('layouts.public')

@section('titulo', 'Solicitud de Matrícula')

@section('contenido')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h5 class="fw-bold mb-1">UNIDAD EDUCATIVA "FRANCISCANO SALITRE"</h5>
                        <h6 class="text-muted mb-1">HERMANAS FRANCISCANAS DE MARÍA INMACULADA</h6>
                        <small class="d-block mb-1">DIR. AVENIDA 27 DE NOVIEMBRE Y AV. REPÚBLICA DE AUSTRIA</small>
                        <small class="d-block mb-1">TELF. 2792-252 &nbsp;|&nbsp; AMIE: 09H04858 &nbsp;|&nbsp; EMAIL: franciscanosalitre@hotmail.com</small>
                        <small class="d-block">Salitre - Ecuador</small>
                        <hr class="my-2">
                        <h5 class="fw-bold mb-1">SOLICITUD DE MATRÍCULA {{ $periodo->nombre ?? '' }}</h5>
                        <small class="text-muted">DATOS GENERALES, BÁSICOS Y ACTUALIZADOS DEL ESTUDIANTE</small><br>
                        <small class="text-muted">(Debe ser llenada en computadora con letra legible, sin tachones ni enmendaduras)</small>

                        @if(isset($adminMode) && $adminMode)
                            <div class="alert alert-info mt-3 mb-0 py-2">
                                <i class="bi bi-shield-lock me-1"></i> Registro manual por: <strong>{{ auth()->user()->name }}</strong>
                            </div>
                        @endif
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ isset($adminMode) && $adminMode ? route('admin.pre-registros.store-manual') : route('pre-registro.store') }}">
                        @csrf
                        @if(isset($adminMode) && $adminMode)
                            <input type="hidden" name="tipo_solicitud" value="cambio_colegio">
                        @endif

                        @if(!isset($adminMode) || !$adminMode)
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-tag me-2"></i>Tipo de Solicitud</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="d-flex gap-4 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_solicitud" value="nuevo_ingreso" id="ts_nuevo" {{ old('tipo_solicitud', 'nuevo_ingreso') == 'nuevo_ingreso' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ts_nuevo"><strong>Nuevo Ingreso</strong> <small class="text-muted">(alumno nuevo)</small></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_solicitud" value="separacion" id="ts_separacion" {{ old('tipo_solicitud') == 'separacion' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ts_separacion"><strong>Separación de Matrícula</strong> <small class="text-muted">(alumno que ya pertenece a la institución)</small></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- REPRESENTANTE --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-person-fill me-2"></i>Datos del Representante</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small">Tipo de Documento</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="rep_tipo_documento" value="cedula" id="rep_cedula" checked><label class="form-check-label" for="rep_cedula">Cédula</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="rep_tipo_documento" value="pasaporte" id="rep_pasaporte"><label class="form-check-label" for="rep_pasaporte">Pasaporte</label></div>
                                </div>
                                @error('rep_tipo_documento') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Número de Documento <span class="text-danger">*</span></label>
                                <input type="text" name="rep_numero_documento" class="form-control @error('rep_numero_documento') is-invalid @enderror" value="{{ old('rep_numero_documento') }}" required>
                                @error('rep_numero_documento') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Teléfono</label>
                                <input type="text" name="rep_telefono" class="form-control" value="{{ old('rep_telefono') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="rep_nombres" class="form-control @error('rep_nombres') is-invalid @enderror" value="{{ old('rep_nombres') }}" required>
                                @error('rep_nombres') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" name="rep_apellidos" class="form-control @error('rep_apellidos') is-invalid @enderror" value="{{ old('rep_apellidos') }}" required>
                                @error('rep_apellidos') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" name="rep_email" class="form-control @error('rep_email') is-invalid @enderror" value="{{ old('rep_email') }}" required>
                                @error('rep_email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        {{-- ESTUDIANTE --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-person-vcard me-2"></i>Datos del Estudiante</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small">Tipo de Documento</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tipo_documento" value="cedula" id="al_cedula" checked><label class="form-check-label" for="al_cedula">Cédula</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tipo_documento" value="pasaporte" id="al_pasaporte"><label class="form-check-label" for="al_pasaporte">Pasaporte</label></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Número de Documento <span class="text-danger">*</span></label>
                                <input type="text" name="alumno_numero_documento" class="form-control @error('alumno_numero_documento') is-invalid @enderror" value="{{ old('alumno_numero_documento') }}" required>
                                @error('alumno_numero_documento') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small"># Cédula</label>
                                <input type="text" name="alumno_numero_cedula" class="form-control" value="{{ old('alumno_numero_cedula') }}">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small">Apellidos y Nombres <span class="text-danger">*</span></label>
                                <input type="text" id="alumno_apellidos_nombres" class="form-control" value="{{ old('alumno_apellidos_nombres') }}" required>
                                <input type="hidden" name="alumno_nombres" id="alumno_nombres_hidden" value="{{ old('alumno_nombres') }}">
                                <input type="hidden" name="alumno_apellidos" id="alumno_apellidos_hidden" value="{{ old('alumno_apellidos') }}">
                                @error('alumno_nombres') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Tipo Sangre</label>
                                <input type="text" name="alumno_tipo_sangre" class="form-control" value="{{ old('alumno_tipo_sangre') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Fecha Nac. (año, mes, día)</label>
                                <input type="date" name="alumno_fecha_nacimiento" class="form-control" value="{{ old('alumno_fecha_nacimiento') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Género</label>
                                <select name="alumno_genero" class="form-select">
                                    <option value="">--</option>
                                    <option value="masculino" @selected(old('alumno_genero')==='masculino')>Masculino</option>
                                    <option value="femenino" @selected(old('alumno_genero')==='femenino')>Femenino</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Etnia</label>
                                <input type="text" name="alumno_etnia" class="form-control" value="{{ old('alumno_etnia') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Nacionalidad</label>
                                <input type="text" name="alumno_nacionalidad" class="form-control" value="{{ old('alumno_nacionalidad') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Telf. Convencional</label>
                                <input type="text" name="alumno_telefono_convencional" class="form-control" value="{{ old('alumno_telefono_convencional') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Celular</label>
                                <input type="text" name="alumno_celular" class="form-control" value="{{ old('alumno_celular') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Email activo del Alumno</label>
                                <input type="email" name="alumno_email_activo" class="form-control" value="{{ old('alumno_email_activo') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">N° Hermanos</label>
                                <input type="number" name="alumno_numero_hermanos" class="form-control" min="0" value="{{ old('alumno_numero_hermanos') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Lugar entre Hermanos</label>
                                <input type="number" name="alumno_lugar_entre_hermanos" class="form-control" min="0" value="{{ old('alumno_lugar_entre_hermanos') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Institución Educativa de Procedencia</label>
                                <input type="text" name="alumno_institucion_procedencia" class="form-control" value="{{ old('alumno_institucion_procedencia') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">¿Con quién vive el estudiante?</label>
                                <input type="text" name="alumno_con_quien_vive" class="form-control" value="{{ old('alumno_con_quien_vive') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Parentesco</label>
                                <input type="text" name="alumno_parentesco_convivencia" class="form-control" value="{{ old('alumno_parentesco_convivencia') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Dirección domiciliaria del estudiante</label>
                                <input type="text" name="direccion_domiciliaria" class="form-control" value="{{ old('direccion_domiciliaria') }}">
                            </div>
                        </div>

                        {{-- FICHA MÉDICA --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-heart-pulse me-2"></i>Ficha Médica y Necesidades Específicas</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small">¿Padece alguna enfermedad?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_padece_enfermedad" value="1" {{ old('alumno_padece_enfermedad') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_padece_enfermedad" value="0" {{ !old('alumno_padece_enfermedad') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-md-6" id="gpo_enfermedad">
                                <label class="form-label small">Indique el nombre de la enfermedad</label>
                                <input type="text" name="alumno_nombre_enfermedad" class="form-control" value="{{ old('alumno_nombre_enfermedad') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">¿Tiene un tratamiento a seguir por esa condición?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_tratamiento" value="1" {{ old('alumno_tiene_tratamiento') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_tratamiento" value="0" {{ !old('alumno_tiene_tratamiento') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                    <small class="text-muted align-self-center">adjunte documento físico</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">¿Toma alguna medicina?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_toma_medicina" value="1" {{ old('alumno_toma_medicina') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_toma_medicina" value="0" {{ !old('alumno_toma_medicina') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-md-6" id="gpo_medicina">
                                <label class="form-label small">Indique el nombre de la medicina</label>
                                <input type="text" name="alumno_nombre_medicina" class="form-control" value="{{ old('alumno_nombre_medicina') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">¿Tiene Necesidades Educativas Específicas?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_nee" value="1" {{ old('alumno_tiene_nee') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_nee" value="0" {{ !old('alumno_tiene_nee') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-md-6" id="gpo_nee">
                                <label class="form-label small">Indique el nombre de la NEE</label>
                                <input type="text" name="alumno_nombre_nee" class="form-control" value="{{ old('alumno_nombre_nee') }}">
                            </div>
                            <div class="col-12"><hr></div>
                            <div class="col-md-4">
                                <label class="form-label small">¿Qué tipo de discapacidad tiene?</label>
                                <input type="text" name="alumno_tipo_discapacidad" class="form-control" value="{{ old('alumno_tipo_discapacidad') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">¿Tiene carnet de discapacidad?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_carnet_discapacidad" value="1" {{ old('alumno_tiene_carnet_discapacidad') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_carnet_discapacidad" value="0" {{ !old('alumno_tiene_carnet_discapacidad') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-md-4" id="gpo_carnet">
                                <label class="form-label small"># Carnet de discapacidad</label>
                                <input type="text" name="alumno_numero_carnet" class="form-control" value="{{ old('alumno_numero_carnet') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">¿Tiene informe psicopedagógico actualizado?</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_informe_psicopedagogico" value="1" {{ old('alumno_tiene_informe_psicopedagogico') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alumno_tiene_informe_psicopedagogico" value="0" {{ !old('alumno_tiene_informe_psicopedagogico') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                    <small class="text-muted align-self-center">adjunte documento físico</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Observaciones</label>
                                <textarea name="alumno_observaciones" class="form-control" rows="2">{{ old('alumno_observaciones') }}</textarea>
                            </div>
                        </div>

                        {{-- VIVIENDA --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-house-door me-2"></i>Datos de Vivienda</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small">Dirección</label>
                                <input type="text" name="direccion_domiciliaria" class="form-control" value="{{ old('direccion_domiciliaria') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Sector</label>
                                <input type="text" name="vivienda_sector" class="form-control" value="{{ old('vivienda_sector') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small"># Casa</label>
                                <input type="text" name="vivienda_numero_casa" class="form-control" value="{{ old('vivienda_numero_casa') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Referencia del domicilio</label>
                                <input type="text" name="vivienda_referencia" class="form-control" value="{{ old('vivienda_referencia') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Provincia</label>
                                <input type="text" name="vivienda_provincia" class="form-control" value="{{ old('vivienda_provincia') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Cantón</label>
                                <input type="text" name="vivienda_canton" class="form-control" value="{{ old('vivienda_canton') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Parroquia</label>
                                <input type="text" name="vivienda_parroquia" class="form-control" value="{{ old('vivienda_parroquia') }}">
                            </div>
                            <div class="col-12"><hr></div>
                            <div class="col-3">
                                <label class="form-label small d-block">Casa Propia</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_casa_propia" value="1" {{ old('vivienda_casa_propia') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_casa_propia" value="0" {{ !old('vivienda_casa_propia') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-3">
                                <label class="form-label small d-block">Agua</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_tiene_agua" value="1" {{ old('vivienda_tiene_agua') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_tiene_agua" value="0" {{ !old('vivienda_tiene_agua') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-3">
                                <label class="form-label small d-block">SS.HH.</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_tiene_sshh" value="1" {{ old('vivienda_tiene_sshh') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_tiene_sshh" value="0" {{ !old('vivienda_tiene_sshh') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-3">
                                <label class="form-label small d-block">Luz</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_tiene_luz" value="1" {{ old('vivienda_tiene_luz') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="vivienda_tiene_luz" value="0" {{ !old('vivienda_tiene_luz') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                        </div>

                        {{-- DATOS FAMILIARES --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-people me-2"></i>Datos Familiares</h6>
                        <div class="alert alert-warning small py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>EL TELÉFONO DE CONTACTO DEL REPRESENTANTE LEGAL DEBE SER VÁLIDO. ASÍ MISMO EL CORREO ELECTRÓNICO DEBE ESTAR VIGENTE, YA QUE LAS FACTURAS SE ENVIARÁN AL CORREO ELECTRÓNICO.</strong>
                        </div>
                        <div class="row g-3 mb-3">
                            <h6 class="text-secondary">Padre</h6>
                            <div class="col-md-5"><label class="form-label small">Nombres y apellidos del Padre</label><input type="text" name="padre_nombres_apellidos" class="form-control" value="{{ old('padre_nombres_apellidos') }}"></div>
                            <div class="col-md-2"><label class="form-label small">Cédula</label><input type="text" name="padre_cedula" class="form-control" value="{{ old('padre_cedula') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Teléf. Vigente</label><input type="text" name="padre_telefono" class="form-control" value="{{ old('padre_telefono') }}"></div>
                            <div class="col-md-2">
                                <label class="form-label small">Facturador</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="padre_es_facturador" value="1" {{ old('padre_es_facturador') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="padre_es_facturador" value="0" {{ !old('padre_es_facturador') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Profesión del Padre o actividad que realiza</label><input type="text" name="padre_profesion" class="form-control" value="{{ old('padre_profesion') }}"></div>
                            <div class="col-md-5"><label class="form-label small">Dirección domicilio</label><input type="text" name="padre_direccion" class="form-control" value="{{ old('padre_direccion') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Email</label><input type="email" name="padre_email" class="form-control" value="{{ old('padre_email') }}"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <h6 class="text-secondary">Madre</h6>
                            <div class="col-md-5"><label class="form-label small">Nombres y apellidos de la Madre</label><input type="text" name="madre_nombres_apellidos" class="form-control" value="{{ old('madre_nombres_apellidos') }}"></div>
                            <div class="col-md-2"><label class="form-label small">Cédula</label><input type="text" name="madre_cedula" class="form-control" value="{{ old('madre_cedula') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Teléf. Vigente</label><input type="text" name="madre_telefono" class="form-control" value="{{ old('madre_telefono') }}"></div>
                            <div class="col-md-2">
                                <label class="form-label small">Facturador</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="madre_es_facturador" value="1" {{ old('madre_es_facturador') ? 'checked' : '' }}><label class="form-check-label">Sí</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="madre_es_facturador" value="0" {{ !old('madre_es_facturador') ? 'checked' : '' }}><label class="form-check-label">No</label></div>
                                </div>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Profesión de la Madre o actividad que realiza</label><input type="text" name="madre_profesion" class="form-control" value="{{ old('madre_profesion') }}"></div>
                            <div class="col-md-5"><label class="form-label small">Dirección domicilio</label><input type="text" name="madre_direccion" class="form-control" value="{{ old('madre_direccion') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Email</label><input type="email" name="madre_email" class="form-control" value="{{ old('madre_email') }}"></div>
                        </div>

                        {{-- FACTURACIÓN --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-receipt me-2"></i>A nombre de quién se emitirá la factura <span class="small">(DATO OBLIGATORIO)</span></h6>
                        <div class="row mb-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-info btn-sm" id="copiarPadreFact"><i class="bi bi-person-up me-1"></i>Copiar datos del Padre</button>
                                <button type="button" class="btn btn-outline-info btn-sm" id="copiarMadreFact"><i class="bi bi-person-up me-1"></i>Copiar datos de la Madre</button>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-5"><label class="form-label small">Nombre y Apellidos del Facturador *</label><input type="text" name="facturador_nombre" class="form-control facturable" value="{{ old('facturador_nombre') }}" required></div>
                            <div class="col-md-2"><label class="form-label small">Cédula *</label><input type="text" name="facturador_cedula" class="form-control facturable" value="{{ old('facturador_cedula') }}" required></div>
                            <div class="col-md-2"><label class="form-label small">Teléfono *</label><input type="text" name="facturador_telefono" class="form-control facturable" value="{{ old('facturador_telefono') }}" required></div>
                            <div class="col-md-3"><label class="form-label small">Email *</label><input type="email" name="facturador_email" class="form-control facturable" value="{{ old('facturador_email') }}" required></div>
                            <div class="col-md-8"><label class="form-label small">Dirección *</label><input type="text" name="facturador_direccion" class="form-control facturable" value="{{ old('facturador_direccion') }}" required></div>
                        </div>

                        {{-- REPRESENTANTE ALTERNO --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-person-check me-2"></i>Representante en caso de no ser la Madre o el Padre</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label small">Nombre del Representante</label><input type="text" name="alterno_nombre" class="form-control" value="{{ old('alterno_nombre') }}"></div>
                            <div class="col-md-6">
                                <label class="form-label small">Parentesco</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alterno_parentesco" value="Papá" {{ old('alterno_parentesco') == 'Papá' ? 'checked' : '' }}><label class="form-check-label">Papá</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alterno_parentesco" value="Mamá" {{ old('alterno_parentesco') == 'Mamá' ? 'checked' : '' }}><label class="form-check-label">Mamá</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alterno_parentesco" value="Facturador" {{ old('alterno_parentesco') == 'Facturador' ? 'checked' : '' }}><label class="form-check-label">Facturador</label></div>
                                    <div class="form-check"><input class="form-check-input" type="radio" name="alterno_parentesco" value="" id="radio_alt_otro" {{ old('alterno_parentesco') && !in_array(old('alterno_parentesco'), ['Papá','Mamá','Facturador']) ? 'checked' : '' }}><label class="form-check-label">Otro</label></div>
                                    <input type="text" name="alterno_parentesco_otro" class="form-control form-control-sm w-auto" id="alto_otro_texto" placeholder="Especifique" value="{{ old('alterno_parentesco') }}">
                                </div>
                            </div>
                            <div class="col-md-3"><label class="form-label small">Cédula</label><input type="text" name="alterno_cedula" class="form-control" value="{{ old('alterno_cedula') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Teléfono</label><input type="text" name="alterno_telefono" class="form-control" value="{{ old('alterno_telefono') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Email</label><input type="email" name="alterno_email" class="form-control" value="{{ old('alterno_email') }}"></div>
                            <div class="col-md-3"><label class="form-label small">Dirección</label><input type="text" name="alterno_direccion" class="form-control" value="{{ old('alterno_direccion') }}"></div>
                        </div>

                        {{-- MATRICULANDO --}}
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-card-checklist me-2"></i>Datos Obligatorios - Matriculando</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small">Curso <span class="text-danger">*</span></label>
                                <select name="curso_id" class="form-select @error('curso_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar curso</option>
                                    @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}" @selected(old('curso_id')==$curso->id)>{{ $curso->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('curso_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Paralelo <span class="text-danger">*</span></label>
                                <select name="paralelo_id" class="form-select @error('paralelo_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar</option>
                                    @foreach($paralelos as $p)
                                    <option value="{{ $p->id }}" @selected(old('paralelo_id')==$p->id)>{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('paralelo_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Especialización</label>
                                <input type="text" name="especializacion" class="form-control" value="{{ old('especializacion') }}">
                            </div>
                            @if($periodo)
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Período Lectivo: <strong>{{ $periodo->nombre }}</strong>
                                </small>
                                <input type="hidden" name="periodo_id" value="{{ $periodo->id }}">
                            </div>
                            @endif
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Generar Solicitud y Descargar PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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

    toggleField('alumno_padece_enfermedad', 'gpo_enfermedad');
    toggleField('alumno_toma_medicina', 'gpo_medicina');
    toggleField('alumno_tiene_nee', 'gpo_nee');
    toggleField('alumno_tiene_carnet_discapacidad', 'gpo_carnet');

    document.getElementById('copiarPadreFact').addEventListener('click', function () {
        document.querySelector('input[name="facturador_nombre"]').value = document.querySelector('input[name="padre_nombres_apellidos"]').value || '';
        document.querySelector('input[name="facturador_cedula"]').value = document.querySelector('input[name="padre_cedula"]').value || '';
        document.querySelector('input[name="facturador_telefono"]').value = document.querySelector('input[name="padre_telefono"]').value || '';
        document.querySelector('input[name="facturador_direccion"]').value = document.querySelector('input[name="padre_direccion"]').value || '';
        document.querySelector('input[name="facturador_email"]').value = document.querySelector('input[name="padre_email"]').value || '';
    });

    document.getElementById('copiarMadreFact').addEventListener('click', function () {
        document.querySelector('input[name="facturador_nombre"]').value = document.querySelector('input[name="madre_nombres_apellidos"]').value || '';
        document.querySelector('input[name="facturador_cedula"]').value = document.querySelector('input[name="madre_cedula"]').value || '';
        document.querySelector('input[name="facturador_telefono"]').value = document.querySelector('input[name="madre_telefono"]').value || '';
        document.querySelector('input[name="facturador_direccion"]').value = document.querySelector('input[name="madre_direccion"]').value || '';
        document.querySelector('input[name="facturador_email"]').value = document.querySelector('input[name="madre_email"]').value || '';
    });

    const radiosAlt = document.querySelectorAll('input[name="alterno_parentesco"]');
    const otroTexto = document.getElementById('alto_otro_texto');
    function updateAlterno() {
        const checked = document.querySelector('input[name="alterno_parentesco"]:checked');
        if (checked && !checked.value) {
            otroTexto.style.display = 'inline-block';
        } else {
            otroTexto.style.display = 'none';
        }
    }
    radiosAlt.forEach(r => r.addEventListener('change', updateAlterno));
    updateAlterno();
    otroTexto.addEventListener('input', function () { document.getElementById('radio_alt_otro').checked = true; });

    const form = document.querySelector('form');
    const apellidosInput = document.getElementById('alumno_apellidos_nombres');
    const nombresHidden = document.getElementById('alumno_nombres_hidden');
    const apellidosHidden = document.getElementById('alumno_apellidos_hidden');
    form.addEventListener('submit', function () {
        const val = apellidosInput.value.trim();
        if (val) {
            const w = val.split(/\s+/);
            if (w.length <= 2) {
                apellidosHidden.value = w[0] || '';
                nombresHidden.value = w[1] || '';
            } else {
                apellidosHidden.value = w.slice(0, Math.ceil(w.length / 2)).join(' ');
                nombresHidden.value = w.slice(Math.ceil(w.length / 2)).join(' ');
            }
        }
    });
});
</script>
@endpush
