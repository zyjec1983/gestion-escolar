@extends('layouts.aula')

@section('titulo', ($clase_actual['materia'] ?? '') . ' - Foros')

@section('contenido-aula')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Foros de Discusión</h4>
        <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
    </div>
    @if($es_docente)
    <a href="{{ route('clases.foros.create', $clase->id) }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo tema de foro</a>
    @endif
</div>

@if(session('success'))
    <div id="swal-success" data-message="{{ session('success') }}" class="d-none"></div>
@endif

@forelse($foros as $foro)
    @php
        $estadoInfo = match($foro->estado) {
            'activo' => ['Activo', 'bg-success'],
            'no_iniciado' => ['Por iniciar', 'bg-warning text-dark'],
            default => ['Cerrado', 'bg-secondary'],
        };
    @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge {{ $estadoInfo[1] }}">{{ $estadoInfo[0] }}</span>
                    <h5 class="mb-0 fw-bold">{{ $foro->titulo }}</h5>
                </div>
                <small class="text-muted text-md-end">
                    <i class="bi bi-calendar3 me-1"></i>{{ $foro->fecha_inicio?->format('d/m/Y H:i') }}
                    <i class="bi bi-arrow-right mx-1"></i>{{ $foro->fecha_cierre?->format('d/m/Y H:i') }}
                </small>
            </div>

            <p class="text-muted small mb-3">{{ \Illuminate\Support\Str::limit($foro->pregunta, 180) }}</p>

            <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                <span class="badge bg-light text-dark border" id="count-foro-{{ $foro->id }}">
                    <i class="bi bi-chat-left-text me-1"></i><span class="foro-count-valor">{{ $foro->intervenciones_count }}</span> participación(es)
                </span>
                @if(count($foro->links ?? []))
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-link-45deg me-1"></i>{{ count($foro->links) }} enlace(s) de consulta
                    </span>
                @endif
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('foros.show', $foro->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Ver foro</a>
                @if($es_docente)
                <form method="POST" action="{{ route('foros.destroy', $foro->id) }}"
                      class="confirm-swal" data-titulo="Eliminar foro" data-mensaje="¿Eliminar este tema de foro con todas sus intervenciones?">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
                </form>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
            Aún no hay temas de foro para esta clase.
            @if($es_docente) Use el botón "Nuevo tema de foro" para crear el primero. @endif
        </div>
    </div>
@endforelse

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var urlContadores = @json(route('clases.foros.contadores', $clase->id));
        var intervalTiempo = 10000;

        function actualizarContadores() {
            fetch(urlContadores, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    Object.keys(data).forEach(function (foroId) {
                        var badge = document.getElementById('count-foro-' + foroId);
                        if (badge) {
                            var valor = badge.querySelector('.foro-count-valor');
                            if (valor) valor.textContent = data[foroId];
                        }
                    });
                }).catch(function () {});
        }

        actualizarContadores();
        setInterval(actualizarContadores, intervalTiempo);
    });

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