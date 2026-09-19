<div class="p-3 d-flex flex-column h-100">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h5 class="mb-0">
                @if(isset($interlocutor_id) && $hilo->isNotEmpty())
                    @php $ultimo = $hilo->last(); @endphp
                    @if($ultimo->destinatario_id === auth()->id())
                        {{ trim($ultimo->emisor->name . ' ' . $ultimo->emisor->apellidos) }}
                    @else
                        {{ trim($ultimo->destinatario->name . ' ' . $ultimo->destinatario->apellidos) }}
                    @endif
                @else
                    Conversación
                @endif
            </h5>
            @if($alumno_contexto)
                <small class="text-muted">Sobre: <strong>{{ $alumno_contexto->nombres }} {{ $alumno_contexto->apellidos }}</strong></small>
            @endif
        </div>
        @if(isset($retorno) && $hilo->isNotEmpty())
            <form method="POST" action="{{ route('mensajes.destroy') }}" class="d-inline p-eliminar-hilo" data-confirm="¿Eliminar esta conversación de tu bandeja?">
                @csrf
                @method('DELETE')
                <input type="hidden" name="ids[]" value="{{ isset($anchor_id) ? $anchor_id : $hilo->last()->id }}">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
            </form>
        @endif
    </div>

    <div class="flex-grow-1" style="overflow-y: auto;">
        @forelse($hilo as $msg)
        <div class="d-flex mb-3 {{ $msg->emisor_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
            <div class="{{ $msg->emisor_id === auth()->id() ? 'bg-primary text-white' : 'bg-light border' }} rounded-3 px-3 py-2" style="max-width: 80%;">
                <div class="small {{ $msg->emisor_id === auth()->id() ? 'text-white-50' : 'text-muted' }}">
                    {{ $msg->emisor_id === auth()->id() ? 'Tú' : trim($msg->emisor->name . ' ' . $msg->emisor->apellidos) }}
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
        <p class="text-muted text-center">La conversación fue eliminada de tu bandeja.</p>
        @endforelse
    </div>

    @if(isset($interlocutor_id) && $hilo->isNotEmpty())
    <div class="border-top pt-3 mt-3">
        <form method="POST" action="{{ route('mensajes.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="destinatario_id" value="{{ $interlocutor_id }}">
            <input type="hidden" name="contexto_estudiante_id" value="{{ $contexto_estudiante_id }}">
            <input type="hidden" name="hilo_id" value="{{ $hilo_id ?? $hilo->last()->id }}">
            <input type="hidden" name="retorno" value="{{ $retorno ?? route('mensajes.index') }}">
            <div class="row g-2">
                <div class="col-md-4 mb-2">
                    <input type="text" name="asunto" class="form-control form-control-sm" value="{{ $hilo->first()->asunto }}" readonly>
                </div>
                <div class="col-md-8 mb-2">
                    <input type="text" name="cuerpo_contenido" class="form-control form-control-sm" placeholder="Escriba su respuesta..." required>
                </div>
                <div class="col-md-6">
                    <input type="file" name="adjunto" class="form-control form-control-sm">
                </div>
                <div class="col-md-6 text-md-end">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Responder</button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>