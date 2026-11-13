@extends('layouts.principal')

@section('titulo', 'Editar registro NEE')

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar registro NEE</h4>
        <small class="text-muted">{{ $nee->nombreEstudiante() }}</small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.nee.update', $nee) }}" class="card shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('dece.nee._form')
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('dece.nee.show', $nee) }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Actualizar registro</button>
        </div>
    </form>
</div>
@endsection
