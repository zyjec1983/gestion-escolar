@extends('layouts.principal')

@section('titulo', 'Mensaje')

@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-envelope me-2"></i>Conversación</h4>
        <small class="text-muted">
            @if($alumno_contexto)
                Sobre: <strong>{{ $alumno_contexto->nombres }} {{ $alumno_contexto->apellidos }}</strong>
            @endif
        </small>
    </div>
    <a href="{{ route('mensajes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-3" style="max-height: 55vh; overflow-y: auto;">
        @forelse($hilo as $msg)
        <div class="d-flex mb-3 {{ $msg->emisor_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
            <div class="{{ $msg->emisor_id === auth()->id() ? 'bg-primary text-white' : 'bg-light border' }} rounded-3 px-3 py-2" style="max-width: 75%;">
                <div class="small {{ $msg->emisor_id === auth()->id() ? 'text-white-50' : 'text-muted' }}">
                    {{ $msg->emisor_id === auth()->id() ? 'Tú' : $msg->emisor->name . ' ' . $msg->emisor->apellidos }}
                    · {{ $msg->fecha_envio?->format('d/m/Y H:i') }}
                </div>
                <div class="fw-semibold">{{ $msg->asunto }}</div>
                @if($msg->cuerpo_contenido)
                    <div class="text-wrap">{!! nl2br(e($msg->cuerpo_contenido)) !!}</div>
                @endif
                @if($msg->contextoEstudiante && $msg->emisor_id !== auth()->id())
                    <div><small class="badge bg-secondary mt-1">Sobre: {{ $msg->contextoEstudiante->nombres }} {{ $msg->contextoEstudiante->apellidos }}</small></div>
                @endif
                @if($msg->archivosAdjuntos->count())
                    <div class="mt-2">
                        @foreach($msg->archivosAdjuntos as $adjunto)
                            <a href="{{ $adjunto->url }}" target="_blank" class="{{ $msg->emisor_id === auth()->id() ? 'link-light' : 'link-primary' }} me-2">
                                <i class="bi bi-paperclip me-1"></i>{{ $adjunto->nombre }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @empty
        <p class="text-muted text-center mb-0">Sin mensajes.</p>
        @endforelse
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('mensajes.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="destinatario_id" value="{{ $interlocutor_id }}">
            <input type="hidden" name="contexto_estudiante_id" value="{{ $contexto_estudiante_id }}">
            <input type="hidden" name="retorno" value="{{ route('mensajes.show', $hilo->first()->id ?? 1) }}">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="asunto" class="form-control" placeholder="Asunto" required>
                </div>
                <div class="col-md-8">
                    <input type="text" name="cuerpo_contenido" class="form-control" placeholder="Escriba su respuesta..." required>
                </div>
            </div>
            <div class="row g-2 mt-1 align-items-center">
                <div class="col-md-6">
                    <input type="file" name="adjunto" class="form-control form-control-sm">
                </div>
                <div class="col-md-6 text-md-end">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Responder</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection