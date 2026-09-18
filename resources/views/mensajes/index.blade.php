@extends('layouts.principal')

@section('titulo', 'Mensajes')

@section('contenido')
<div class="row mb-3">
    <div class="col">
        <h4 class="mb-0"><i class="bi bi-envelope me-2"></i>Mensajes</h4>
        <p class="text-muted mb-0">Bandeja de mensajes con docentes, estudiantes y representantes.</p>
    </div>
    <div class="col-auto align-self-center">
        <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#nuevoMensaje" aria-expanded="false">
            <i class="bi bi-pencil-square me-1"></i> Redactar
        </button>
    </div>
</div>

<div class="collapse mb-3" id="nuevoMensaje">
    <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Nuevo mensaje</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('mensajes.store') }}" enctype="multipart/form-data">
                @csrf
                @if(session('exito'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('exito') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Destinatario</label>
                    <select name="destinatario_id" id="destinatario" class="form-select" required>
                        <option value="">Seleccione un contacto...</option>
                        @foreach($contactos as $contacto)
                            <option value="{{ $contacto['destinatario_id'] }}" data-contexto="{{ $contacto['contexto_estudiante_id'] }}">
                                {{ $contacto['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="contexto_estudiante_id" id="contexto_estudiante_id" value="">
                    <input type="hidden" name="retorno" value="{{ route('mensajes.index') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Asunto</label>
                    <input type="text" name="asunto" class="form-control" maxlength="255" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje</label>
                    <textarea name="cuerpo_contenido" rows="4" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Adjunto (opcional, máx. 10 MB)</label>
                    <input type="file" name="adjunto" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Enviar</button>
            </form>
        </div>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $carpeta === 'recibidos' ? 'active' : '' }}" href="{{ route('mensajes.index', ['carpeta' => 'recibidos']) }}">
            Recibidos @if($carpeta === 'recibidos' && $no_leidos_total > 0)
                <span class="badge bg-danger ms-1">{{ $no_leidos_total }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $carpeta === 'enviados' ? 'active' : '' }}" href="{{ route('mensajes.index', ['carpeta' => 'enviados']) }}">
            Enviados
        </a>
    </li>
</ul>

@forelse($hilos as $hilo)
<div class="card shadow-sm mb-2">
    <a href="{{ route('mensajes.show', $hilo['id']) }}" class="text-decoration-none text-dark">
        <div class="card-body py-2 d-flex align-items-center">
            <i class="bi bi-person-circle fs-4 me-3 text-secondary"></i>
            <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex justify-content-between">
                    <strong>{{ $hilo['interlocutor'] }}</strong>
                    <small class="text-muted">{{ $hilo['fecha'] }}</small>
                </div>
                <div class="text-truncate small">
                    <span class="text-muted">{{ $hilo['ultimo_asunto'] }}</span>
                    @if($hilo['contexto_nombre']) <span class="badge bg-light text-dark border ms-1">Sobre {{ $hilo['contexto_nombre'] }}</span> @endif
                </div>
            </div>
            @if($hilo['no_leidos'] > 0)
                <span class="badge rounded-pill bg-danger ms-3">{{ $hilo['no_leidos'] }}</span>
            @endif
        </div>
    </a>
</div>
@empty
<div class="alert alert-light border text-center">
    <i class="bi bi-inbox fs-2 d-block text-muted"></i>
    No hay mensajes en esta carpeta.
</div>
@endforelse
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('destinatario');
    var contexto = document.getElementById('contexto_estudiante_id');
    if (!sel || !contexto) return;

    function syncContexto() {
        var opt = sel.options[sel.selectedIndex];
        contexto.value = opt && opt.dataset.contexto ? opt.dataset.contexto : '';
    }
    sel.addEventListener('change', syncContexto);
    if (sel.selectedIndex > 0) syncContexto();
});
</script>
@endpush