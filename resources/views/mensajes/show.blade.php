@extends('layouts.principal')

@section('titulo', 'Mensaje')

@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-envelope me-2"></i>Conversación</h4>
    </div>
    <a href="{{ route('mensajes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a la bandeja
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @include('mensajes._conversacion', [
            'hilo' => $hilo,
            'interlocutor_id' => $interlocutor_id,
            'contexto_estudiante_id' => $contexto_estudiante_id,
            'alumno_contexto' => $alumno_contexto,
            'retorno' => $retorno,
            'anchor_id' => $hilo->isNotEmpty() ? $hilo->last()->id : $interlocutor_id,
            'hilo_id' => $hilo_id ?? ($hilo->isNotEmpty() ? $hilo->first()->id : null),
        ])
    </div>
</div>
@endsection