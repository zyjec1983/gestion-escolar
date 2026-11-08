@extends('layouts.principal')

@section('titulo', 'Actualizar datos del alumno')

@php
    // Los textos se muestran en mayusculas; los correos conservan su formato.
    $mayus = fn ($valor) => mb_strtoupper((string) $valor, 'UTF-8');
    $esCorreo = fn (string $campo) => $campo === 'email_activo' || str_ends_with($campo, '_email');

    // Campos de texto simples agrupados por seccion, para no repetir el mismo
    // input 40 veces. El orden es el mismo en que se pintan.
    $grupos = [
        'Contacto' => [
            'telefono_convencional' => 'Teléfono convencional',
            'celular' => 'Celular',
            'email_activo' => 'Correo del alumno',
        ],
        'Familia' => [
            'numero_hermanos' => 'Número de hermanos',
            'lugar_que_ocupa_entre_hermanos' => 'Lugar que ocupa entre los hermanos',
            'con_quien_vive' => 'Con quién vive',
            'parentesco_convivencia' => 'Parentesco de convivencia',
        ],
        'Origen' => [
            'institucion_procedencia' => 'Institución de procedencia',
            'observaciones_generales' => 'Observaciones generales',
        ],
        'Salud' => [
            'nombre_enfermedad' => 'Nombre de la enfermedad',
            'nombre_medicina' => 'Nombre del medicamento',
            'nombre_nee' => 'Nombre de la necesidad educativa',
            'tipo_discapacidad' => 'Tipo de discapacidad',
            'numero_carnet' => 'Número del carné',
        ],
        'Domicilio' => [
            'direccion_domiciliaria' => 'Dirección',
            'sector' => 'Sector',
            'referencia_domicilio' => 'Referencia del domicilio',
            'provincia' => 'Provincia',
            'canton' => 'Cantón',
            'parroquia' => 'Parroquia',
            'numero_casa' => 'Número de casa',
        ],
        'Padre' => [
            'padre_nombres_apellidos' => 'Nombres y apellidos',
            'padre_cedula' => 'Cédula',
            'padre_telefono' => 'Teléfono',
            'padre_profesion' => 'Profesión',
            'padre_direccion' => 'Dirección',
            'padre_email' => 'Correo',
        ],
        'Madre' => [
            'madre_nombres_apellidos' => 'Nombres y apellidos',
            'madre_cedula' => 'Cédula',
            'madre_telefono' => 'Teléfono',
            'madre_profesion' => 'Profesión',
            'madre_direccion' => 'Dirección',
            'madre_email' => 'Correo',
        ],
        'Representante alterno' => [
            'representante_alterno_nombre' => 'Nombre',
            'representante_alterno_cedula' => 'Cédula',
            'representante_alterno_telefono' => 'Teléfono',
            'representante_alterno_email' => 'Correo',
            'representante_alterno_direccion' => 'Dirección',
        ],
        'Facturador' => [
            'facturador_nombre' => 'Nombre',
            'facturador_cedula' => 'Cédula',
            'facturador_telefono' => 'Teléfono',
            'facturador_direccion' => 'Dirección',
            'facturador_email' => 'Correo',
        ],
    ];

    $interruptores = [
        'Salud' => [
            'padece_enfermedad' => 'Padece alguna enfermedad',
            'tiene_tratamiento_medico' => 'Tiene tratamiento médico',
            'toma_medicina' => 'Toma medicina',
            'necesidades_educativas_especificas_nee' => 'Tiene necesidades educativas específicas (NEE)',
            'tiene_discapacidad' => 'Tiene discapacidad',
            'tiene_carnet' => 'Tiene carné',
            'tiene_informe_psicopedagogico_actualizado' => 'Informe psicopedagógico actualizado',
        ],
        'Domicilio' => [
            'casa_propia' => 'La casa es propia',
            'tiene_agua' => 'Tiene agua',
            'tiene_sshh_letrina' => 'Tiene SSHH o letrina',
            'tiene_luz_electrica' => 'Tiene luz eléctrica',
        ],
        'Padre' => ['padre_es_facturador' => 'Es facturador'],
        'Madre' => ['madre_es_facturador' => 'Es facturador'],
    ];
@endphp

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4">
        <i class="bi bi-pencil-square me-2"></i>
        Actualizar datos: {{ $mayus($alumno->apellidos.' '.$alumno->nombres) }}
    </h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa lo siguiente:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('actualizacion.update', $alumno->id) }}">
        @csrf
        @method('PUT')

        {{-- ---------------------------------------------------------------- --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-vcard me-2"></i>Representante legal
            </div>
            <div class="card-body">
                <p class="text-muted">
                    El representante legal debe ser una persona mayor de edad. Si el caso lo pide
                    (divorcio, salida del pa&iacute;s, prisi&oacute;n, etc.) se puede cambiar por otra cuenta.
                </p>

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label for="representante_busqueda" class="form-label">Representante legal actual</label>
                        <input type="text" name="representante_busqueda" id="representante_busqueda"
                               class="form-control @error('representante_busqueda') is-invalid @enderror"
                               list="representantesLista" autocomplete="off"
                               placeholder="Escriba el nombre o la c&eacute;dula"
                               value="{{ $representanteBusqueda }}">
                        <datalist id="representantesLista">
                            @foreach($representantesLista as $opcion)
                                <option value="{{ $opcion['etiqueta'] }}" data-id="{{ $opcion['id'] }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="representante_user_id" id="representante_user_id"
                               value="{{ old('representante_user_id', $representanteActualId) }}">
                        <div class="form-text">
                            Escriba y elija de la lista. Al cambiar de persona se recarga la ficha con sus datos.
                        </div>
                        @error('representante_busqueda')
                            <div class="invalid-feedback d-block js-busqueda">{{ $message }}</div>
                        @enderror
                        @error('representante_user_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-check mt-lg-4">
                            <input class="form-check-input" type="checkbox" name="crear_representante" value="1"
                                   id="crear_representante" @checked(old('crear_representante'))>
                            <label class="form-check-label" for="crear_representante">
                                La persona nueva a&uacute;n no tiene cuenta: crearla y asignarla
                            </label>
                        </div>
                    </div>
                </div>

                <hr>

                @if($representante)
                    <div class="alert alert-light border">
                        <strong>Datos de {{ $mayus($representante->nombreCompleto()) }}</strong>
                        @if($representante->fecha_nacimiento)
                            <span class="badge bg-{{ $representante->esAdulto() ? 'success' : 'danger' }} ms-2">
                                {{ $representante->edad() }} años
                                {{ $representante->esAdulto() ? '(mayor de edad)' : '(menor de edad)' }}
                            </span>
                        @else
                            <span class="badge bg-secondary ms-2">sin fecha de nacimiento</span>
                        @endif
                        @if($alumno->cambiosRepresentante()->exists())
                            <span class="badge bg-info ms-2">tiene historial de cambios</span>
                        @endif
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="representante_nombres" class="form-control"
                               value="{{ $mayus(old('representante_nombres', $representante->name ?? '')) }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Apellidos</label>
                        <input type="text" name="representante_apellidos" class="form-control"
                               value="{{ $mayus(old('representante_apellidos', $representante->apellidos ?? '')) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">C&eacute;dula</label>
                        <input type="text" name="representante_cedula" class="form-control"
                               value="{{ $mayus(old('representante_cedula', $representante->numero_cedula ?? '')) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tel&eacute;fono</label>
                        <input type="text" name="representante_telefono" class="form-control"
                               value="{{ $mayus(old('representante_telefono', $representante->telefono ?? '')) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Correo</label>
                        <input type="email" name="representante_email" class="form-control"
                               data-no-mayusculas
                               value="{{ old('representante_email', $representante->email ?? '') }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="representante_fecha_nacimiento" class="form-control"
                               value="{{ old('representante_fecha_nacimiento', $representante?->fecha_nacimiento?->format('Y-m-d')) }}">
                        <div class="form-text">Debe ser mayor de edad.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------------------------------------------------------------- --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-person me-2"></i>Datos del alumno
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="nombres" class="form-control" required
                               value="{{ $mayus(old('nombres', $alumno->nombres)) }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Apellidos *</label>
                        <input type="text" name="apellidos" class="form-control" required
                               value="{{ $mayus(old('apellidos', $alumno->apellidos)) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">C&eacute;dula</label>
                        <input type="text" name="numero_cedula" class="form-control"
                               value="{{ $mayus(old('numero_cedula', $alumno->numero_cedula)) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">C&oacute;digo interno</label>
                        <input type="text" class="form-control" value="{{ $alumno->codigo }}" disabled>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control"
                               value="{{ old('fecha_nacimiento', $alumno->fecha_nacimiento?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">G&eacute;nero</label>
                        <select name="genero" class="form-select">
                            <option value="">— sin definir —</option>
                            <option value="masculino" @selected(old('genero', $alumno->genero) === 'masculino')>Masculino</option>
                            <option value="femenino" @selected(old('genero', $alumno->genero) === 'femenino')>Femenino</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tipo de sangre</label>
                        @include('partials.tipo-sangre', ['name' => 'tipo_sangre', 'value' => $alumno->tipo_sangre])
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Etnia</label>
                        <input type="text" name="etnia" class="form-control"
                               value="{{ $mayus(old('etnia', $alumno->etnia)) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Nacionalidad</label>
                        <input type="text" name="nacionalidad" class="form-control"
                               value="{{ $mayus(old('nacionalidad', $alumno->nacionalidad)) }}">
                    </div>
                </div>
            </div>
        </div>

        @foreach($grupos as $titulo => $campos)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light text-dark">
                    <i class="bi bi-card-list me-2"></i>{{ $titulo }}
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($campos as $campo => $etiqueta)
                            @php $correo = $esCorreo($campo); @endphp
                            <div class="col-12 col-md-4">
                                <label class="form-label">{{ $etiqueta }}</label>
                                <input type="text" name="{{ $campo }}" class="form-control"
                                       @if($correo) data-no-mayusculas @endif
                                       value="{{ $correo ? old($campo, $alumno->$campo) : $mayus(old($campo, $alumno->$campo)) }}">
                            </div>
                        @endforeach
                    </div>

                    @if(isset($interruptores[$titulo]))
                        <div class="row g-2 mt-1">
                            @foreach($interruptores[$titulo] as $campo => $etiqueta)
                                <div class="col-12 col-md-6">
                                    <input type="hidden" name="{{ $campo }}" value="0">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="{{ $campo }}" value="1"
                                               id="{{ $campo }}" @checked(old($campo, (bool) $alumno->$campo))>
                                        <label class="form-check-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light text-dark">
                <i class="bi bi-arrow-left-right me-2"></i>Representante alterno y parentesco
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Parentesco</label>
                        <input type="text" name="representante_alterno_parentesco" class="form-control"
                               value="{{ $mayus(old('representante_alterno_parentesco', $alumno->representante_alterno_parentesco)) }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Parentesco (si es otro)</label>
                        <input type="text" name="representante_alterno_parentesco_otro" class="form-control"
                               value="{{ $mayus(old('representante_alterno_parentesco_otro')) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Guardar cambios
            </button>
            <a href="{{ route('actualizacion.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </form>

    @if($cambios->isNotEmpty())
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Cambios de representante legal</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Antes</th>
                                <th>Despu&eacute;s</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cambios as $cambio)
                                <tr>
                                    <td>{{ $cambio->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $cambio->anterior ? $mayus($cambio->anterior->nombreCompleto()) : '—' }}</td>
                                    <td>{{ $cambio->nuevo ? $mayus($cambio->nuevo->nombreCompleto()) : '—' }}</td>
                                    <td>{{ $cambio->registradoPor ? $mayus($cambio->registradoPor->nombreCompleto()) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const busqueda = document.getElementById('representante_busqueda');
    const oculto = document.getElementById('representante_user_id');
    const opciones = () => Array.from(document.querySelectorAll('#representantesLista option'));

    const normalizar = (texto) => (texto || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLocaleUpperCase('es-EC');

    const resolver = (texto) => {
        const buscado = normalizar(texto);
        if (!buscado) return null;

        const lista = opciones();
        const exactas = lista.filter((opcion) => normalizar(opcion.value) === buscado);
        if (exactas.length === 1) return exactas[0];
        if (exactas.length > 1) return null;

        const parciales = lista.filter((opcion) => normalizar(opcion.value).includes(buscado));
        return parciales.length === 1 ? parciales[0] : null;
    };

    const marcarError = (mensaje) => {
        busqueda.classList.toggle('is-invalid', Boolean(mensaje));

        let feedback = busqueda.parentElement.querySelector('.invalid-feedback.js-busqueda');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback js-busqueda';
            busqueda.parentElement.appendChild(feedback);
        }

        feedback.textContent = mensaje || '';
        feedback.style.display = mensaje ? 'block' : 'none';
    };

    if (busqueda && oculto) {
        busqueda.addEventListener('input', () => marcarError(''));

        busqueda.addEventListener('change', () => {
            const texto = busqueda.value.trim();
            if (!texto) {
                marcarError('');
                return;
            }

            const opcion = resolver(texto);
            if (!opcion) {
                marcarError('Elija un representante de la lista.');
                return;
            }

            const id = opcion.dataset.id || '';
            if (id === oculto.value) {
                marcarError('');
                return;
            }

            if (!id) {
                marcarError('Elija un representante de la lista.');
                return;
            }

            // Otra persona: se recarga la ficha para que los campos coincidan
            // con sus datos y no se sobrescriban al guardar.
            const url = new URL(window.location.href);
            url.searchParams.set('representante', id);
            window.location.href = url.toString();
        });
    }

    const pasarAMayusculas = (input) => {
        const inicio = input.selectionStart;
        const fin = input.selectionEnd;
        const valor = input.value.toLocaleUpperCase('es-EC');

        if (valor !== input.value) {
            input.value = valor;

            if (inicio !== null && fin !== null) {
                try {
                    input.setSelectionRange(inicio, fin);
                } catch (error) {
                    // Algunos navegadores no permiten seleccionar en ciertos tipos.
                }
            }
        }
    };

    document.querySelectorAll('form input[type="text"]').forEach((input) => {
        if (input === busqueda || input.dataset.noMayusculas !== undefined) {
            return;
        }

        pasarAMayusculas(input);
        input.addEventListener('input', () => pasarAMayusculas(input));
    });
})();
</script>
@endpush