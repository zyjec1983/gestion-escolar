@extends('layouts.aula')

@section('titulo', ($clase_actual['materia'] ?? '') . ' - Grupos de Clase')

@section('contenido-aula')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Grupos de Clase</h4>
        <small class="text-muted">{{ $clase_actual['curso_paralelo'] ?? '' }}</small>
    </div>
    @if($asignacion)
    <div class="d-flex gap-2">
        <button type="button" id="btnDescargarJpg" class="btn btn-sm btn-outline-success">
            <i class="bi bi-download me-1"></i>Descargar JPG</button>
        @if($es_docente)
        <form method="POST" action="{{ route('clases.grupos.destroy', $clase->id) }}"
              class="confirm-swal" data-titulo="Eliminar distribución" data-mensaje="¿Eliminar esta distribución de grupos?">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
        </form>
        @endif
    </div>
    @endif
</div>

@if(session('success'))
    <div id="swal-success" data-message="{{ session('success') }}" class="d-none"></div>
@endif

@if($es_docente)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <strong class="small"><i class="bi bi-shuffle me-1"></i>Generar nueva distribución</strong>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('clases.grupos.store', $clase->id) }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label small mb-1">Método de división</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="modo" id="modoGrupos" value="grupos" checked onchange="toggleModo()">
                    <label class="form-check-label small" for="modoGrupos">Por número de grupos</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="modo" id="modoTamano" value="tamano" onchange="toggleModo()">
                    <label class="form-check-label small" for="modoTamano">Por estudiantes por grupo</label>
                </div>
            </div>
            <div class="col-md-3" id="campoGrupos">
                <label class="form-label small mb-1">Número de grupos</label>
                <input type="number" name="numero_grupos" id="numeroGrupos" class="form-control form-control-sm" min="1" value="4">
            </div>
            <div class="col-md-3 d-none" id="campoTamano">
                <label class="form-label small mb-1">Estudiantes por grupo</label>
                <input type="number" name="integrantes_por_grupo" id="integrantesPorGrupo" class="form-control form-control-sm" min="1" value="3">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary btn-sm"><i class="bi bi-shuffle me-1"></i>Generar</button>
            </div>
        </form>
        <small class="text-muted d-block mt-2">
            {{ count($estudiantes_inscritos ?? []) }} estudiantes inscritos · la asignación es aleatoria y equilibrada.
            Al generar una nueva distribución, reemplaza la actual.
        </small>
    </div>
</div>
@endif

@if(!$asignacion)
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-people-fill fs-1 d-block mb-2"></i>
            Aún no hay una distribución de grupos para esta clase.
            @if($es_docente) Use el formulario para generarla. @endif
        </div>
    </div>
@else
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <span class="badge bg-light text-dark border">{{ count($grupos) }} grupos</span>
            <span class="badge bg-light text-dark border ms-1">{{ $total_estudiantes }} estudiantes</span>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" id="btnMostrarTodos">
            <i class="bi bi-eye-fill me-1"></i>Mostrar todos a la vez
        </button>
    </div>
    <small class="text-muted d-block mb-3">
        <i class="bi bi-hourglass-split me-1"></i>Los grupos aparecen uno a la vez, con 5 segundos entre cada uno.
    </small>

    <div class="row g-3" id="contenedor-grupos">
        @foreach($grupos as $numero => $miembros)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card grupo-card shadow-sm h-100 border-0"
                 style="--delay: {{ $loop->index * 5 }}s; background-color: {{ $paleta[$loop->index % count($paleta)] }};">
                <div class="card-body">
                    <h6 class="fw-bold mb-1"><i class="bi bi-people-fill me-1"></i>Grupo {{ $numero }}</h6>
                    <span class="badge bg-dark">{{ count($miembros) }} integrante(s)</span>
                    <ul class="list-unstyled mt-3 mb-0">
                        @foreach($miembros as $integrante)
                            <li class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-person-circle"></i>
                                <span>{{ $integrante->nombre_corto ?? trim($integrante->alumno->apellidos . ' ' . $integrante->alumno->nombres) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    function toggleModo() {
        var modo = document.querySelector('input[name="modo"]:checked').value;
        document.getElementById('campoGrupos').classList.toggle('d-none', modo !== 'grupos');
        document.getElementById('campoTamano').classList.toggle('d-none', modo !== 'tamano');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('btnMostrarTodos');
        if (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.grupo-card').forEach(function (c) {
                    c.classList.add('mostrar');
                });
            });
        }

        var btnJpg = document.getElementById('btnDescargarJpg');
        if (btnJpg) {
            btnJpg.addEventListener('click', function () {
                document.querySelectorAll('.grupo-card').forEach(function (c) {
                    c.classList.add('mostrar');
                });
                setTimeout(function () {
                    html2canvas(document.getElementById('contenedor-grupos'), {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: null,
                        logging: false
                    }).then(function (canvas) {
                        var titulo = @json(trim(($clase_actual['materia'] ?? 'clase') . ' ' . ($clase_actual['curso_paralelo'] ?? '')));
                        var enlace = document.createElement('a');
                        enlace.download = 'Grupos_' + String(titulo).replace(/\s+/g, '_') + '.jpg';
                        enlace.href = canvas.toDataURL('image/jpeg', 0.9);
                        enlace.click();
                    });
                }, 100);
            });
        }

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

<style>
    .grupo-card {
        opacity: 0;
        transform: translateY(20px);
        animation: aparecerGrupo 0.8s ease both;
        animation-delay: var(--delay, 0s);
    }
    .grupo-card.mostrar {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    @keyframes aparecerGrupo {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: none; }
    }
</style>
@endsection