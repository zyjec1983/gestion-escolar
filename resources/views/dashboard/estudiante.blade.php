@extends('layouts.principal')

@section('titulo', 'Mi Espacio')

@section('contenido')
<div class="row mb-4">
    <div class="col">
        <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>Hola, {{ auth()->user()->name }}</h4>
        <small class="text-muted">
            @forelse($alumnos as $a)
                {{ $a->apellidos }} {{ $a->nombres }}
            @empty
                Cuenta de estudiante
            @endforelse
        </small>
    </div>
</div>

<div class="row g-3 row-cols-1 row-cols-md-3">
    <div class="col">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Mensajes</h6>
                        <h2 class="mb-0">{{ $noLeidos }}</h2>
                    </div>
                    <i class="bi bi-envelope fs-1 opacity-50"></i>
                </div>
                <a href="{{ route('mensajes.index') }}" class="text-white small">Ver mensajes →</a>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Mis Clases</h6>
                    </div>
                    <i class="bi bi-book fs-1 opacity-50"></i>
                </div>
                <a href="{{ route('clases.index') }}" class="text-white small">Ver clases →</a>
            </div>
        </div>
    </div>
</div>
@endsection