@extends('layouts.aula')

@section('titulo', ($clase_actual['materia'] ?? '') . ' - Materiales')

@section('contenido-aula')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-folder2-open me-2"></i>{{ $clase_actual['materia'] ?? '' }}</h4>
        <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
    </div>
    <span class="badge bg-light text-dark border">{{ $materiales->count() }} material(es)</span>
</div>

@if(session('success'))
    <div id="swal-success" data-message="{{ session('success') }}" class="d-none"></div>
@endif

@if($es_docente)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="small"><i class="bi bi-cloud-arrow-up me-1"></i>Subir material</strong>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#formMaterial">Mostrar / ocultar</button>
    </div>
    <div class="collapse" id="formMaterial">
        <div class="card-body">
            <form method="POST" action="{{ route('clases.materiales.store', $clase->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Título</label>
                        <input type="text" name="titulo" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small mb-1">Descripción (opcional)</label>
                        <input type="text" name="descripcion" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small mb-1">Archivo</label>
                        <input type="file" name="archivo" class="form-control form-control-sm" required>
                        <small class="text-muted">Máximo 20 MB por archivo.</small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-md-end">
                        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-cloud-arrow-up me-1"></i>Subir</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="row g-3">
    @forelse($materiales as $material)
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start gap-2 mb-1">
                    <i class="bi bi-file-earmark-arrow-down fs-3 text-primary"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block text-truncate" title="{{ $material->titulo }}">{{ $material->titulo }}</strong>
                        @if($material->descripcion)
                            <small class="text-muted">{{ $material->descripcion }}</small>
                        @endif
                        <small class="d-block text-muted text-truncate mt-1"><i class="bi bi-paperclip me-1"></i>{{ $material->nombre_archivo }}</small>
                    </div>
                    @if($es_docente)
                    <form method="POST" action="{{ route('clases.materiales.destroy', [$clase->id, $material->id]) }}" class="d-inline confirm-swal" data-titulo="Eliminar material" data-mensaje="¿Eliminar '{{ $material->titulo }}'?">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    @endif
                </div>
                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                    <small class="text-muted text-truncate me-2">
                        {{ $material->subidor ? trim($material->subidor->name . ' ' . $material->subidor->apellidos) : 'Docente' }}
                        · {{ $material->created_at?->format('d/m/Y') }}
                    </small>
                    <a href="{{ $material->url }}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap">
                        <i class="bi bi-download me-1"></i>Descargar
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
                No hay materiales subidos para esta clase.
            </div>
        </div>
    </div>
    @endforelse
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.confirm-swal').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: f.dataset.titulo || '¿Eliminar?',
                text: f.dataset.mensaje || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (res) { if (res.isConfirmed) f.submit(); });
        });
    });
});
</script>
@endsection