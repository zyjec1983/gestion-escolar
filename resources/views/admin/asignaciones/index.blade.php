@extends('layouts.principal')

@section('titulo', 'Distributivo docente')

{{--
    Distributivo docente y carga horaria.

    Qué hace:
    - Busca docentes por nombre, apellido, correo o cédula.
    - Muestra la carga del docente en períodos de 40 minutos y horas reloj.
    - Asigna materias de la malla del año a un docente y paralelo.
    - Permite cambiar el paralelo y retirar asignaciones sin historial.
    - Impide retirar asignaciones con notas, asistencias o materiales.

    Conecta con: Admin\AsignacionDocenteController,
    App\Services\CargaHorariaDocente, App\Models\ClaseDocente y
    App\Models\CursoMateria.
    Rutas: GET /admin/asignaciones (admin.asignaciones.index) y sus acciones.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-workspace me-2"></i>Distributivo docente</h4>
            <p class="text-muted small mb-0">
                Asigne las materias de la malla a cada docente. La carga se calcula con períodos de 40 minutos.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.materias.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3 me-1"></i>Malla curricular
            </a>
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>Nuevo docente
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-people me-1"></i>Planta docente</h6>
                    <span class="badge bg-primary-subtle text-primary border">{{ $docentes->count() }}</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.asignaciones.index') }}" class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" name="buscar" class="form-control" value="{{ $buscar }}"
                                placeholder="Nombre, apellido, correo o cédula">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            @if($buscar !== '')
                            <a href="{{ route('admin.asignaciones.index') }}" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            @endif
                        </div>
                    </form>

                    <div class="d-flex flex-column gap-2">
                        @forelse($docentes as $docente)
                        <a href="{{ route('admin.asignaciones.index', array_filter(['docente' => $docente->id, 'buscar' => $buscar])) }}"
                           class="text-decoration-none">
                            <div class="border rounded-3 p-2 {{ $docenteSeleccionado?->id === $docente->id ? 'border-primary bg-primary-subtle' : 'bg-white' }}">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-dark text-truncate">{{ $docente->nombreCompleto() }}</div>
                                        <div class="text-muted small">
                                            @if($docente->numero_cedula)
                                            <i class="bi bi-person-vcard me-1"></i>{{ $docente->numero_cedula }}
                                            @endif
                                        </div>
                                        <div class="text-muted small text-truncate">{{ $docente->email }}</div>
                                    </div>
                                    <span class="badge bg-light text-dark border text-nowrap">
                                        {{ $docente->clases_docentes_count }} clase{{ $docente->clases_docentes_count === 1 ? '' : 's' }}
                                    </span>
                                </div>
                            </div>
                        </a>
                        @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-person-x fs-4 d-block mb-2"></i>
                            No hay docentes que coincidan con la búsqueda.
                            <div class="mt-2">
                                <a href="{{ route('admin.usuarios.create') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-person-plus me-1"></i>Crear cuenta
                                </a>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            @if(! $docenteSeleccionado)
            <div class="card shadow-sm h-100">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-person-workspace fs-1 d-block mb-2"></i>
                    Seleccione un docente de la planta para gestionar su carga horaria.
                </div>
            </div>
            @else
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 pb-3 mb-3 border-bottom">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h5 class="mb-0">{{ $docenteSeleccionado->nombreCompleto() }}</h5>
                                @if($docenteSeleccionado->numero_cedula)
                                <span class="badge bg-dark font-monospace">CI: {{ $docenteSeleccionado->numero_cedula }}</span>
                                @endif
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                    Jornada {{ $docenteSeleccionado->horas_contrato }} h reloj
                                </span>
                                <span class="badge bg-primary">{{ \App\Models\User::etiquetaRol($docenteSeleccionado->rol) }}</span>
                            </div>
                            @if($docenteSeleccionado->titulo)
                            <div class="text-muted small"><i class="bi bi-award me-1"></i>{{ $docenteSeleccionado->titulo }}</div>
                            @endif
                            <div class="text-muted small mt-1">
                                <i class="bi bi-envelope me-1"></i>{{ $docenteSeleccionado->email }}
                                @if($docenteSeleccionado->telefono)
                                <span class="mx-2">|</span><i class="bi bi-telephone me-1"></i>{{ $docenteSeleccionado->telefono }}
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.usuarios.edit', $docenteSeleccionado) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil-square me-1"></i>Editar cuenta
                        </a>
                    </div>

                    @if($carga['excede'])
                    <div class="alert alert-danger d-flex align-items-start gap-2 py-2">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                        <div class="small">
                            <strong>Sobrecarga horaria.</strong>
                            El docente tiene {{ $carga['total_periodos'] }} períodos
                            ({{ number_format($carga['horas_plantel'], 2, ',', '.') }} h reloj en plantel)
                            y el máximo para su contrato es {{ $carga['max_horas_plantel'] }} h reloj.
                            Reduzca paralelos o actividades complementarias.
                        </div>
                    </div>
                    @else
                    <div class="alert alert-light border d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <div class="small text-secondary">
                            Cada período dura <strong>40 minutos</strong>. Un contrato de
                            {{ $docenteSeleccionado->horas_contrato }} h reloj admite
                            <strong>{{ $carga['max_horas_plantel'] }} h de plantel</strong>
                            + {{ $carga['horas_autonomas'] }} h autónomas.
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-auto text-nowrap">Carga válida</span>
                    </div>
                    @endif

                    @php
                        $porcentaje = min(100, $carga['max_horas_plantel'] > 0
                            ? round(($carga['horas_plantel'] / $carga['max_horas_plantel']) * 100)
                            : 0);
                    @endphp

                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="border rounded-3 p-2 h-100">
                                <div class="text-muted small text-uppercase">Clases + gestión</div>
                                <div class="h5 mb-0 font-monospace">{{ $carga['total_periodos'] }} <span class="fs-6 fw-normal text-muted">per.</span></div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    {{ $carga['periodos_clase'] }} clase + {{ $carga['periodos_atencion'] + $carga['periodos_reunion'] + $carga['periodos_tutoria'] }} gestión
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border border-primary rounded-3 p-2 h-100 bg-primary-subtle">
                                <div class="text-primary small text-uppercase">Horas reloj plantel</div>
                                <div class="h5 mb-0 font-monospace text-primary">
                                    {{ number_format($carga['horas_plantel'], 2, ',', '.') }}
                                    <span class="fs-6 fw-normal">/ {{ $carga['max_horas_plantel'] }} h</span>
                                </div>
                                <div class="text-primary-emphasis" style="font-size:.72rem;">{{ $carga['total_periodos'] }} × 40 min ÷ 60</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded-3 p-2 h-100">
                                <div class="text-muted small text-uppercase">Disponible</div>
                                <div class="h5 mb-0 font-monospace text-success">
                                    {{ number_format($carga['horas_contingencia'], 2, ',', '.') }}
                                    <span class="fs-6 fw-normal text-muted">h</span>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">{{ $carga['periodos_disponibles'] }} períodos libres</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded-3 p-2 h-100">
                                <div class="text-muted small text-uppercase">Jornada semanal</div>
                                <div class="h5 mb-0 font-monospace">
                                    {{ number_format($carga['total_horas_jornada'], 2, ',', '.') }}
                                    <span class="fs-6 fw-normal text-muted">/ {{ $docenteSeleccionado->horas_contrato }} h</span>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">Incluye {{ $carga['horas_autonomas'] }} h autónomas</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold text-secondary">Ocupación de horas presenciales ({{ $porcentaje }}%)</span>
                            <span class="font-monospace text-muted">
                                {{ number_format($carga['horas_plantel'], 2, ',', '.') }} h de {{ $carga['max_horas_plantel'] }} h reloj
                            </span>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar {{ $carga['excede'] ? 'bg-danger' : ($porcentaje > 85 ? 'bg-warning' : 'bg-primary') }}"
                                 role="progressbar" style="width: {{ $porcentaje }}%"
                                 aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.asignaciones.carga', $docenteSeleccionado) }}"
                          class="row g-2 bg-light border rounded-3 p-3">
                        @csrf
                        @method('PUT')
                        <div class="col-12">
                            <h6 class="mb-0"><i class="bi bi-sliders me-1"></i>Carga complementaria y contrato</h6>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold" for="horas_contrato">Contrato</label>
                            <select id="horas_contrato" name="horas_contrato" class="form-select form-select-sm">
                                <option value="40" @selected((int) $docenteSeleccionado->horas_contrato === 40)>40 h reloj</option>
                                <option value="20" @selected((int) $docenteSeleccionado->horas_contrato === 20)>20 h reloj</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold" for="periodos_atencion_padres">Atención a padres</label>
                            <input type="number" id="periodos_atencion_padres" name="periodos_atencion_padres"
                                class="form-control form-control-sm text-center" min="0" max="20"
                                value="{{ $docenteSeleccionado->periodos_atencion_padres }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold" for="periodos_reunion_area">Reunión de área</label>
                            <input type="number" id="periodos_reunion_area" name="periodos_reunion_area"
                                class="form-control form-control-sm text-center" min="0" max="20"
                                value="{{ $docenteSeleccionado->periodos_reunion_area }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold" for="periodos_tutoria">Tutoría</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="periodos_tutoria" name="periodos_tutoria"
                                    class="form-control text-center" min="0" max="20"
                                    value="{{ $docenteSeleccionado->periodos_tutoria }}">
                                <button type="submit" class="btn btn-outline-primary" title="Guardar carga complementaria">
                                    <i class="bi bi-save"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-text mb-0">
                                Los períodos de atención, reunión y tutoría se suman a la carga de clases para calcular las horas de plantel.
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-journal-bookmark me-1"></i>Materias asignadas</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.asignaciones.store') }}" class="row g-2 mb-3">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $docenteSeleccionado->id }}">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold" for="asig_curso">Año de educación</label>
                            <select id="asig_curso" name="curso_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione un año</option>
                                @foreach($cursos as $curso)
                                <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="asig_materia">Materia de la malla</label>
                            <select id="asig_materia" name="materia_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione un año primero</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" for="asig_paralelo">Paralelo</label>
                            <select id="asig_paralelo" name="paralelo_id" class="form-select form-select-sm" required>
                                <option value="">--</option>
                                @foreach($paralelos as $paralelo)
                                <option value="{{ $paralelo->id }}">{{ $paralelo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100" title="Asignar materia"
                                @disabled($cursos->isEmpty() || $paralelos->isEmpty())>
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="col-12">
                            <div class="form-text mb-0" id="asig_periodos_pista">
                                Seleccione el año y la materia para ver los períodos semanales de la malla.
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0" style="min-width: 640px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Materia</th>
                                    <th>Año</th>
                                    <th>Paralelo</th>
                                    <th class="text-center">Períodos/sem</th>
                                    <th class="text-center">Horas reloj</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($docenteSeleccionado->clasesDocentes as $clase)
                                @php
                                    $periodos = (int) ($periodosPorClave[$clase->curso_id.'-'.$clase->materia_id] ?? 0);
                                @endphp
                                <tr>
                                    <td><strong>{{ $clase->materia?->nombre ?? 'Materia eliminada' }}</strong></td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $clase->curso?->nombre ?? 'Año eliminado' }}</span></td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.asignaciones.update', $clase) }}"
                                              class="d-flex align-items-center gap-1">
                                            @csrf
                                            @method('PUT')
                                            <select name="paralelo_id" class="form-select form-select-sm" style="min-width:90px;">
                                                @foreach($paralelos as $paralelo)
                                                <option value="{{ $paralelo->id }}" @selected((int) $clase->paralelo_id === (int) $paralelo->id)>
                                                    {{ $paralelo->nombre }}
                                                </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar paralelo">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center font-monospace">{{ $periodos }} per.</td>
                                    <td class="text-center font-monospace">{{ number_format(($periodos * 40) / 60, 2, ',', '.') }} h</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.asignaciones.destroy', $clase) }}" class="d-inline"
                                              data-confirm="¿Retirar {{ $clase->materia?->nombre }} de {{ $clase->curso?->nombre }} del distributivo de {{ $docenteSeleccionado->nombreCompleto() }}? Solo se permite si no tiene notas, asistencias ni materiales.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Retirar asignación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-journal-plus fs-4 d-block mb-2"></i>
                                        Este docente aún no tiene materias asignadas.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($docenteSeleccionado->clasesDocentes->isNotEmpty())
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end text-uppercase small text-secondary">Subtotal frente a estudiantes</td>
                                    <td class="text-center font-monospace">{{ $carga['periodos_clase'] }} per.</td>
                                    <td class="text-center font-monospace">{{ number_format(($carga['periodos_clase'] * 40) / 60, 2, ',', '.') }} h</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mallaPorCurso = @json($mallaPorCurso);
        var curso = document.getElementById('asig_curso');
        var materia = document.getElementById('asig_materia');
        var pista = document.getElementById('asig_periodos_pista');

        if (!curso || !materia) return;

        function actualizarPista() {
            var opcion = materia.options[materia.selectedIndex];

            if (!opcion || !opcion.dataset.periodos) {
                pista.textContent = 'Seleccione el año y la materia para ver los períodos semanales de la malla.';
                return;
            }

            pista.textContent = 'Esta materia tiene ' + opcion.dataset.periodos
                + ' períodos semanales de 40 minutos en la malla del año.';
        }

        function cargarMaterias() {
            var materias = mallaPorCurso[curso.value] || [];

            materia.innerHTML = '<option value="">Seleccione una materia</option>';

            materias.forEach(function (item) {
                var opcion = document.createElement('option');
                opcion.value = item.id;
                opcion.textContent = item.nombre + ' (' + item.periodos + ' per.)';
                opcion.dataset.periodos = item.periodos;
                materia.appendChild(opcion);
            });

            if (materias.length === 0) {
                materia.innerHTML = '<option value="">Sin materias activas en este año</option>';
            }

            actualizarPista();
        }

        curso.addEventListener('change', cargarMaterias);
        materia.addEventListener('change', actualizarPista);
        cargarMaterias();
    });
</script>
@endpush
