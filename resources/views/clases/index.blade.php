{{-- 
  Vista Mis Clases.
  Renderiza tarjetas de clases asignadas con filtro por categorías
  y campo de búsqueda.
--}}
@extends('layouts.principal')

@section('titulo', 'Mis Clases')

@push('styles')
<style>
    .clase-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: fadeInUp 0.5s ease backwards;
        will-change: transform;
    }
    .clase-card:hover {
        transform: scale(1.04);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-book me-2"></i>Mis Clases</h4>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center mb-4 gap-2">
        <div class="btn-group flex-wrap" role="group">
            @foreach($filtros['categorias'] ?? [] as $cat)
            <button class="btn btn-sm {{ $cat['seleccionado'] ? 'btn-dark' : 'btn-outline-secondary' }}">
                {{ $cat['label'] }}
            </button>
            @endforeach
        </div>
        <div class="w-100 w-sm-auto">
            <input type="text" class="form-control form-control-sm" 
                   placeholder="{{ $filtros['busqueda']['placeholder'] ?? 'Buscar...' }}">
        </div>
    </div>

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4">
        @forelse($clases_asignadas ?? [] as $clase)
        <div class="col">
            <a href="/clases/{{ $clase['id'] }}" class="text-decoration-none">
                <div class="card clase-card text-white shadow-sm h-100"
                     style="background-color: {{ $clase['configuracion_visual']['color_tarjeta'] ?? '#E63946' }}; animation-delay: {{ $loop->index * 0.06 }}s;">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fw-bold">{{ $clase['materia'] }}</h6>
                        <p class="card-text small mb-1">{{ $clase['curso'] }}</p>
                        <p class="card-text small mb-0">{{ $clase['paralelo'] }}</p>
                        <div class="mt-auto pt-3">
                            <i class="bi bi-arrow-right-circle ingresar-icon"></i> Ingresar
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center text-muted py-5">
                <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                No tienes clases asignadas en este período
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
