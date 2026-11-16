@extends('layouts.principal')

@section('titulo', 'Malla curricular')

{{--
    Malla curricular: catálogo de materias y materias por año de educación.

    Qué hace:
    - Muestra el catálogo de materias con su área y estado.
    - Permite agregar materias al catálogo y editarlas.
    - Selecciona un año de educación y administra su malla con períodos
      semanales de 40 minutos.
    - Bloquea años o materias sin borrar notas ni clases históricas.

    Conecta con: Admin\MateriaController, App\Models\Curso,
    App\Models\Materia y App\Models\CursoMateria.
    Rutas: GET /admin/materias (admin.materias.index) y sus acciones.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-diagram-3 me-2"></i>Malla curricular</h4>
            <p class="text-muted small mb-0">
                Materias por año de educación. Los períodos son de 40 minutos y alimentan el distributivo docente.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCurso">
                <i class="bi bi-plus-lg me-1"></i>Nuevo año
            </button>
            <a href="{{ route('admin.asignaciones.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-person-workspace me-1"></i>Distributivo docente
            </a>
        </div>
    </div>

    @if($cursos->isEmpty())
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>
            <i class="bi bi-exclamation-triangle me-1"></i>
            No hay años de educación registrados. Agregue el primero para configurar su malla.
        </span>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCurso">
            <i class="bi bi-plus-lg me-1"></i>Agregar año
        </button>
    </div>
    @else
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-end gap-2">
            <form method="GET" action="{{ route('admin.materias.index') }}" class="flex-grow-1" style="min-width:240px;">
                <label class="form-label small fw-semibold" for="curso">Año de educación</label>
                <select id="curso" name="curso" class="form-select" onchange="this.form.submit()">
                    @foreach($cursos as $curso)
                    <option value="{{ $curso->id }}" @selected($cursoSeleccionado?->id === $curso->id)>
                        {{ $curso->nombre }} @if(! $curso->activo)(bloqueado)@endif
                    </option>
                    @endforeach
                </select>
            </form>
            @if($cursoSeleccionado)
            <div class="d-flex flex-wrap align-items-center gap-2 pb-1">
                @if($cursoSeleccionado->activo)
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-unlock me-1"></i>Año activo
                </span>
                @else
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    <i class="bi bi-lock me-1"></i>Año bloqueado
                </span>
                @endif

                <form method="POST" action="{{ route('admin.cursos.estado', $cursoSeleccionado) }}"
                      data-confirm="{{ $cursoSeleccionado->activo
                          ? '¿Bloquear este año? Dejará de ofertarse y no se podrán crear asignaciones, pero las notas se conservan.'
                          : '¿Reactivar este año? Volverá a estar disponible para la malla y el distributivo.' }}">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $cursoSeleccionado->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        <i class="bi {{ $cursoSeleccionado->activo ? 'bi-lock' : 'bi-unlock' }} me-1"></i>
                        {{ $cursoSeleccionado->activo ? 'Bloquear año' : 'Reactivar año' }}
                    </button>
                </form>

                <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#modalCurso" data-modo="editar"
                    data-action="{{ route('admin.cursos.update', $cursoSeleccionado) }}"
                    data-nombre="{{ $cursoSeleccionado->nombre }}"
                    data-codigo="{{ $cursoSeleccionado->codigo }}">
                    <i class="bi bi-pencil-square me-1"></i>Editar año
                </button>

                <form method="POST" action="{{ route('admin.cursos.destroy', $cursoSeleccionado) }}"
                      data-confirm="¿Eliminar el año {{ $cursoSeleccionado->nombre }}? Solo se permite si no tiene matrículas, clases, asistencias ni pre-registros. Si conserva historial, use «Bloquear año».">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>Eliminar año
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    @if($cursoSeleccionado && ! $cursoSeleccionado->activo)
    <div class="alert alert-warning py-2">
        <i class="bi bi-lock me-1"></i>
        Este año está bloqueado: no se pueden agregar materias ni asignar docentes. Las notas y clases existentes se conservan.
    </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-journal-bookmark me-1"></i>Catálogo de materias</h6>
                    <span class="badge bg-primary-subtle text-primary border">{{ $materias->count() }}</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.materias.catalogo.store') }}" class="row g-2 mb-3">
                        @csrf
                        @if($cursoSeleccionado)
                        <input type="hidden" name="curso" value="{{ $cursoSeleccionado->id }}">
                        @endif
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold" for="nombre_materia">Materia</label>
                            <input type="text" id="nombre_materia" name="nombre" class="form-control form-control-sm"
                                value="{{ old('nombre') }}" placeholder="Ej. Biología" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold" for="area_materia">Área</label>
                            <input type="text" id="area_materia" name="area" class="form-control form-control-sm"
                                value="{{ old('area') }}" placeholder="Ej. Ciencias Naturales">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold" for="codigo_materia">Código (opcional)</label>
                            <input type="text" id="codigo_materia" name="codigo" class="form-control form-control-sm"
                                value="{{ old('codigo') }}" placeholder="Ej. BIO-01">
                        </div>
                        <div class="col-md-5 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                                <i class="bi bi-plus-lg me-1"></i>Agregar al catálogo
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0" style="min-width: 640px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Materia</th>
                                    <th>Área</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materias as $materia)
                                <tr class="{{ $materia->activo ? '' : 'table-light text-muted' }}">
                                    <td>
                                        <strong>{{ $materia->nombre }}</strong>
                                        @if($materia->codigo)
                                        <br><small class="text-muted font-monospace">{{ $materia->codigo }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $materia->area ?: 'Sin área' }}</small></td>
                                    <td>
                                        @if($materia->activo)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>
                                        @else
                                        <span class="badge bg-secondary-subtle text-secondary border">Bloqueada</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#modalMateria"
                                            data-action="{{ route('admin.materias.catalogo.update', $materia) }}"
                                            data-nombre="{{ $materia->nombre }}"
                                            data-area="{{ $materia->area }}"
                                            data-codigo="{{ $materia->codigo }}"
                                            title="Editar materia">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.materias.catalogo.estado', $materia) }}" class="d-inline"
                                              data-confirm="{{ $materia->activo
                                                  ? '¿Bloquear esta materia? No se podrá asignar a nuevos años, pero sus notas se conservan.'
                                                  : '¿Reactivar esta materia? Volverá a estar disponible para la malla.' }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $materia->activo ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                title="{{ $materia->activo ? 'Bloquear materia' : 'Reactivar materia' }}">
                                                <i class="bi {{ $materia->activo ? 'bi-lock' : 'bi-unlock' }}"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        El catálogo está vacío. Agregue la primera materia.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="mb-0"><i class="bi bi-list-check me-1"></i>Malla del año</h6>
                        @if($cursoSeleccionado)
                        <small class="text-muted">{{ $cursoSeleccionado->nombre }}</small>
                        @endif
                    </div>
                    @if($cursoSeleccionado)
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary-subtle text-primary border">
                            {{ $totalPeriodos }} períodos
                        </span>
                        <span class="badge bg-light text-dark border">
                            {{ number_format(($totalPeriodos * 40) / 60, 2, ',', '.') }} h reloj
                        </span>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    @if(! $cursoSeleccionado)
                    <div class="text-center text-muted py-4">Seleccione un año de educación.</div>
                    @else
                    <form method="POST" action="{{ route('admin.materias.malla.store', $cursoSeleccionado) }}"
                          class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="materia_id">Materia disponible</label>
                            <select id="materia_id" name="materia_id" class="form-select form-select-sm" required
                                @disabled(! $cursoSeleccionado->activo)>
                                <option value="">Seleccione una materia</option>
                                @foreach($materiasDisponibles as $disponible)
                                <option value="{{ $disponible->id }}">
                                    {{ $disponible->nombre }}{{ $disponible->area ? ' · '.$disponible->area : '' }}
                                </option>
                                @endforeach
                            </select>
                            @if($materiasDisponibles->isEmpty())
                            <div class="form-text">Todas las materias activas ya están en la malla o el catálogo está vacío.</div>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" for="periodos_semanales">Períodos/semana</label>
                            <input type="number" id="periodos_semanales" name="periodos_semanales"
                                class="form-control form-control-sm text-center" min="1" max="20" value="4" required
                                @disabled(! $cursoSeleccionado->activo)>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"
                                @disabled(! $cursoSeleccionado->activo || $materiasDisponibles->isEmpty())>
                                <i class="bi bi-plus-lg me-1"></i>Asociar
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0" style="min-width: 640px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px;">Orden</th>
                                    <th>Materia</th>
                                    <th>Área</th>
                                    <th class="text-center">Períodos/sem</th>
                                    <th class="text-center">Horas reloj</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cursoSeleccionado->malla as $fila)
                                <tr class="{{ $fila->materia?->activo ? '' : 'table-light text-muted' }}">
                                    <td class="text-center font-monospace">{{ $fila->orden }}</td>
                                    <td>
                                        <strong>{{ $fila->materia?->nombre ?? 'Materia eliminada' }}</strong>
                                        @if($fila->materia && ! $fila->materia->activo)
                                        <br><span class="badge bg-secondary-subtle text-secondary border">Bloqueada</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $fila->materia?->area ?: 'Sin área' }}</small></td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('admin.materias.malla.update', [$cursoSeleccionado, $fila]) }}"
                                              class="d-flex align-items-center justify-content-center gap-1">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="periodos_semanales" value="{{ $fila->periodos_semanales }}"
                                                class="form-control form-control-sm text-center" style="width:70px;"
                                                min="1" max="20" required @disabled(! $cursoSeleccionado->activo)>
                                            <input type="hidden" name="orden" value="{{ $fila->orden }}">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar períodos"
                                                @disabled(! $cursoSeleccionado->activo)>
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center font-monospace">
                                        {{ number_format($fila->horasReloj(), 2, ',', '.') }} h
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.materias.malla.destroy', [$cursoSeleccionado, $fila]) }}" class="d-inline"
                                              data-confirm="¿Quitar {{ $fila->materia?->nombre }} de la malla de {{ $cursoSeleccionado->nombre }}? Solo se permite si no tiene docentes asignados.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar de la malla"
                                                @disabled(! $cursoSeleccionado->activo)>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-journal-plus fs-4 d-block mb-2"></i>
                                        Este año aún no tiene materias en su malla.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($cursoSeleccionado->malla->isNotEmpty())
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end text-uppercase small text-secondary">Total semanal</td>
                                    <td class="text-center font-monospace">{{ $totalPeriodos }} per.</td>
                                    <td class="text-center font-monospace">{{ number_format(($totalPeriodos * 40) / 60, 2, ',', '.') }} h</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Edición del catálogo en un solo modal reutilizable. --}}
<div class="modal fade" id="modalMateria" tabindex="-1" aria-labelledby="modalMateriaTitulo" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formMateria">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h6 class="modal-title" id="modalMateriaTitulo">Editar materia</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editar_nombre">Nombre</label>
                        <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editar_area">Área</label>
                        <input type="text" id="editar_area" name="area" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="editar_codigo">Código</label>
                        <input type="text" id="editar_codigo" name="codigo" class="form-control font-monospace">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- Creación y edición de años de educación en un solo modal reutilizable. --}}
<div class="modal fade" id="modalCurso" tabindex="-1" aria-labelledby="modalCursoTitulo" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formCurso" action="{{ route('admin.cursos.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="metodoCurso">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalCursoTitulo">Nuevo año de educación</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="curso_nombre">Nombre del año <span class="text-danger">*</span></label>
                        <input type="text" id="curso_nombre" name="nombre" class="form-control" required
                            placeholder="Ej. TERCERO DE BACHILLERATO - CIENCIAS">
                        <div class="form-text">
                            Incluya el grado (OCTAVO, NOVENO, DÉCIMO, PRIMERO, SEGUNDO o TERCERO) y, si aplica,
                            la especialización después de un guion.
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="curso_codigo">Código (opcional)</label>
                        <input type="text" id="curso_codigo" name="codigo" class="form-control font-monospace"
                            placeholder="Ej. 3B-CIE">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="cursoGuardar">
                        <i class="bi bi-plus-lg me-1"></i>Agregar año
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('modalMateria');

        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (evento) {
            var boton = evento.relatedTarget;
            var formulario = document.getElementById('formMateria');

            if (!boton || !formulario) return;

            formulario.action = boton.dataset.action;
            formulario.querySelector('#editar_nombre').value = boton.dataset.nombre || '';
            formulario.querySelector('#editar_area').value = boton.dataset.area || '';
            formulario.querySelector('#editar_codigo').value = boton.dataset.codigo || '';
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        var modalCurso = document.getElementById('modalCurso');

        if (!modalCurso) return;

        modalCurso.addEventListener('show.bs.modal', function (evento) {
            var boton = evento.relatedTarget;
            var formulario = document.getElementById('formCurso');
            var metodo = document.getElementById('metodoCurso');
            var titulo = document.getElementById('modalCursoTitulo');
            var guardar = document.getElementById('cursoGuardar');
            var nombre = document.getElementById('curso_nombre');
            var codigo = document.getElementById('curso_codigo');

            if (!formulario) return;

            var editar = boton && boton.dataset.modo === 'editar';

            formulario.reset();

            if (editar) {
                formulario.action = boton.dataset.action;
                metodo.value = 'PUT';
                titulo.textContent = 'Editar año de educación';
                guardar.innerHTML = '<i class="bi bi-save me-1"></i>Guardar cambios';
                nombre.value = boton.dataset.nombre || '';
                codigo.value = boton.dataset.codigo || '';
            } else {
                formulario.action = '{{ route('admin.cursos.store') }}';
                metodo.value = 'POST';
                titulo.textContent = 'Nuevo año de educación';
                guardar.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Agregar año';
            }
        });
    });
</script>
@endpush
