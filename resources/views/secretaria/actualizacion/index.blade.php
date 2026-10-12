@extends('layouts.principal')

@section('titulo', 'Actualizacion de datos')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Actualizaci&oacute;n de datos</h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p class="text-muted">
                Busca al alumno por <strong>n&uacute;mero de c&eacute;dula</strong> o por <strong>apellidos</strong>
                para actualizar sus datos y los de su representante legal.
            </p>

            <form method="GET" action="{{ route('actualizacion.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="cedula" class="form-label">C&eacute;dula del alumno</label>
                        <input type="text" name="cedula" id="cedula" class="form-control"
                               value="{{ $cedula }}" placeholder="1712345678">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="apellidos" class="form-label">Apellidos</label>
                        <input type="text" name="apellidos" id="apellidos" class="form-control"
                               value="{{ $apellidos }}" placeholder="AGUIRRE">
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Buscar
                        </button>
                    </div>
                </div>
            </form>

            <hr class="my-3">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a href="{{ route('actualizacion.index', ['pendientes' => 1]) }}"
                   class="btn btn-sm {{ $pendientes ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="bi bi-person-exclamation me-1"></i>
                    Fichas por regularizar
                    <span class="badge bg-dark">{{ $totalPendientes }}</span>
                </a>
                <small class="text-muted">
                    Alumnos cuyo representante no tiene fecha de nacimiento o todavia no cumple 18 anos.
                </small>
            </div>
        </div>
    </div>

    @if(!$busco)
        <div class="alert alert-info">Escribe una c&eacute;dula o un apellido para empezar, o revisa las fichas por regularizar.</div>
    @elseif($alumnos->isEmpty())
        <div class="alert alert-warning mb-0">
            @if($pendientes)
                No hay fichas por regularizar: todos los representantes tienen fecha de nacimiento y son mayores de edad.
            @else
                No se encontraron alumnos con esos datos.
            @endif
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    {{ $alumnos->count() }} alumno(s) encontrado(s)
                    @if($pendientes)
                        <span class="text-muted fw-normal">por regularizar</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>C&eacute;dula</th>
                                <th>Alumno</th>
                                <th>Curso / Paralelo</th>
                                <th>Representante legal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alumnos as $al)
                                <tr>
                                    <td>{{ $al->numero_cedula ?: '—' }}</td>
                                    <td>
                                        {{ $al->apellidos }} {{ $al->nombres }}
                                        <br><small class="text-muted">{{ $al->codigo }}</small>
                                    </td>
                                    <td>
                                        @forelse($al->matriculas as $m)
                                            <span class="badge bg-secondary">
                                                {{ $m->curso?->nombre }} {{ $m->paralelo?->nombre }}
                                            </span>
                                        @empty
                                            <span class="text-muted">Sin matr&iacute;cula</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if($al->representante)
                                            {{ $al->representante->nombreCompleto() }}
                                            <br><small class="text-muted">{{ $al->representante->email }}</small>
                                            @if(!$al->representante->fecha_nacimiento)
                                                <span class="badge bg-warning text-dark">falta fecha de nacimiento</span>
                                            @elseif(!$al->representante->esAdulto())
                                                <span class="badge bg-danger">no es mayor de edad</span>
                                            @endif
                                        @else
                                            <span class="text-danger">Sin representante</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('actualizacion.edit', $al->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil me-1"></i>Actualizar
                                        </a>
                                    </td>
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