@extends('layouts.principal')

@section('titulo', 'Mis datos')

{{--
    Formulario de datos para representantes ya matriculados.

    Qué hace: muestra los estudiantes vinculados a la cuenta y abre la ficha
    completa para que el representante complete datos del alumno, salud,
    domicilio, padre, madre, representante alterno y facturador.
    Conecta con: Representante\DatosController y la vista compartida
    secretaria.actualizacion.edit.
    Rutas: GET /mis-datos y GET /mis-datos/{alumno}.
--}}
@section('contenido')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-person-vcard me-2"></i>Mis datos</h4>
            <p class="text-muted small mb-0">
                Complete la ficha de su representado. Los campos marcados con * son obligatorios.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Estudiante</th>
                        <th>Cédula</th>
                        <th>Curso</th>
                        <th class="text-end">Ficha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alumnos as $alumno)
                    <tr>
                        <td>
                            <strong>{{ $alumno->nombreCompleto() }}</strong>
                            @if($alumno->tipo_sangre)
                                <br><small class="text-muted">Tipo de sangre: {{ $alumno->tipo_sangre }}</small>
                            @endif
                        </td>
                        <td><small>{{ $alumno->numero_cedula ?: '—' }}</small></td>
                        <td>
                            @php $matricula = $alumno->matriculas->first(); @endphp
                            @if($matricula)
                                <span class="badge bg-light text-dark border">
                                    {{ $matricula->curso?->nombre }} {{ $matricula->paralelo?->nombre }}
                                </span>
                            @else
                                <span class="text-muted small">Sin matrícula</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('representante.datos.edit', $alumno) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil-square me-1"></i>Completar datos
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            No hay estudiantes vinculados a su cuenta.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
