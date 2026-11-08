@extends('layouts.principal')

@section('titulo', 'Gestión de Pre-Registros')

@section('contenido')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Solicitudes de Matrícula</h4>
    </div>

    <div class="btn-group mb-4 flex-wrap" role="group">
        <a href="?filtro=pendiente" class="btn btn-sm {{ $filtro === 'pendiente' ? 'btn-warning' : 'btn-outline-warning' }}">
            <i class="bi bi-clock me-1"></i>Pendientes
        </a>
        <a href="?filtro=aprobado" class="btn btn-sm {{ $filtro === 'aprobado' ? 'btn-success' : 'btn-outline-success' }}">
            <i class="bi bi-check-circle me-1"></i>Aprobados
        </a>
        <a href="?filtro=rechazado" class="btn btn-sm {{ $filtro === 'rechazado' ? 'btn-danger' : 'btn-outline-danger' }}">
            <i class="bi bi-x-circle me-1"></i>Rechazados
        </a>
        <a href="?filtro=todos" class="btn btn-sm {{ $filtro === 'todos' ? 'btn-secondary' : 'btn-outline-secondary' }}">
            <i class="bi bi-list me-1"></i>Todos
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Solicitud</th>
                        <th>Representante</th>
                        <th>Alumno</th>
                        <th>Curso</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preRegistros as $pr)
                    <tr>
                        <td>{{ $pr->id }}</td>
                        <td><small>{{ $pr->codigo_solicitud }}</small></td>
                        <td>
                            <strong>{{ $pr->nombre_representante }}</strong>
                            <br><small class="text-muted">{{ $pr->rep_email }}</small>
                        </td>
                        <td>
                            <strong>{{ $pr->nombre_alumno }}</strong>
                            <br><small class="text-muted">{{ strtoupper($pr->alumno_tipo_documento) }}: {{ $pr->alumno_numero_documento }}</small>
                        </td>
                        <td><small>{{ $pr->curso->nombre ?? '-' }} / {{ $pr->paralelo ? "Par. {$pr->paralelo->nombre}" : '-' }}</small></td>
                        <td>
                            @switch($pr->estado)
                                @case('pendiente') <span class="badge bg-warning text-dark">Pendiente</span> @break
                                @case('aprobado') <span class="badge bg-success">Aprobado</span> @break
                                @case('rechazado') <span class="badge bg-danger">Rechazado</span> @break
                            @endswitch
                        </td>
                        <td><small>{{ $pr->created_at?->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <a href="/admin/pre-registros/{{ $pr->id }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No hay solicitudes {{ $filtro !== 'todos' ? $filtro : '' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($preRegistros->hasPages())
        <div class="card-footer">
            {{ $preRegistros->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
