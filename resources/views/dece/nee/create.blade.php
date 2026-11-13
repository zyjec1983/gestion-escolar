@extends('layouts.principal')

@section('titulo', 'Nuevo registro NEE')

@section('contenido')
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="mb-0"><i class="bi bi-universal-access me-2"></i>Nuevo registro NEE</h4>
        <small class="text-muted">Estudiante del plantel o aspirante externo con necesidad educativa específica.</small>
    </div>

    @include('dece.partials.flash')

    <form method="POST" action="{{ route('dece.nee.store') }}" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body">
            @include('dece.nee._form')
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('dece.nee.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar registro</button>
        </div>
    </form>
</div>
@endsection
