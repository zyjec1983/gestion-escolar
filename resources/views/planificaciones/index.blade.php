@extends('layouts.principal')

@section('titulo', 'Planificaciones de Unidad')

@section('contenido')
@php $puedeCrear = in_array(auth()->user()->rol, ['ADMIN', 'SECRETARIA', 'RECTORA', 'VICERRECTORA', 'DOCENTE']); @endphp
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-journal-bookmark-fill me-2"></i>Planificaciones de Unidad</h4>
            <small class="text-muted">
                @if($puedeCrear)
                    Administración de planificaciones académicas.
                @else
                    Listado de unidades planificadas por la institución.
                @endif
            </small>
        </div>
        @if($puedeCrear)
        <div class="col-auto">
            <a href="{{ route('planificaciones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nueva Planificación
            </a>
        </div>
        @endif
    </div>

    @if(session('success'))
        <div id="swal-perfil-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <form method="GET" class="row g-2 mb-4">
        <div class="col-auto">
            <select name="materia_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todas las materias</option>
                @foreach($materias as $materia)
                    <option value="{{ $materia->id }}" {{ (string) $materiaId === (string) $materia->id ? 'selected' : '' }}>
                        {{ $materia->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    @forelse($planificaciones as $materiaNombre => $items)
        <h6 class="mt-4 mb-3"><i class="bi bi-mortarboard me-2"></i>{{ $materiaNombre }}</h6>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Unidad</th>
                            <th>Nivel</th>
                            <th>Quimestre</th>
                            <th>Parcial</th>
                            <th>Fechas</th>
                            <th>Días</th>
                            <th>Creada por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $unidad)
                            <tr>
                                <td>
                                    <strong>{{ $unidad->titulo }}</strong>
                                    @if($unidad->descripcion)
                                        <small class="text-muted d-block text-truncate" style="max-width: 320px;">{{ $unidad->descripcion }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($unidad->claseDocente)
                                        {{ $unidad->claseDocente->curso?->nombre }} {{ $unidad->claseDocente->paralelo?->nombre }}
                                    @else
                                        <span class="text-muted">General</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $unidad->quimestre ?: '—' }}</span>
                                </td>
                                <td>{{ $unidad->parcial ?: '—' }}</td>
                                <td class="small text-nowrap">
                                    {{ $unidad->fecha_inicio?->format('d/m/Y') }}
                                    → <br>
                                    {{ $unidad->fecha_fin?->format('d/m/Y') }}
                                </td>
                                <td><span class="badge bg-info text-dark">{{ $unidad->duracionDias() }} día(s)</span></td>
                                <td class="small">
                                    @if($unidad->creador)
                                        {{ $unidad->creador->name }} {{ $unidad->creador->apellidos }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                No hay planificaciones registradas.
                @if($puedeCrear)
                    <br><a href="{{ route('planificaciones.create') }}" class="btn btn-primary btn-sm mt-3">Crear la primera</a>
                @endif
            </div>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('swal-perfil-success');
    if (el) {
        Swal.fire({
            icon: 'success',
            title: 'Guardado',
            text: el.dataset.message,
            confirmButtonText: 'Aceptar',
        });
    }
});
</script>
@endpush