@extends('layouts.principal')

@section('titulo', 'Mis citas')

@push('styles')
<style>
    .cita-dia { background: rgba(220, 53, 69, 0.08) !important; }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Mis citas</h4>
            <small class="text-muted">
                @if($alumno) Representado: <strong>{{ $alumno->apellidos }} {{ $alumno->nombres }}</strong> @endif
            </small>
        </div>
        @if(count($representados) > 1)
        <div class="col-auto">
            <form method="GET" action="{{ route('representante.citas') }}">
                <select name="alumno_id" class="form-select" onchange="this.form.submit()">
                    @foreach($representados as $r)
                        <option value="{{ $r->id }}" {{ $alumno && $alumno->id == $r->id ? 'selected' : '' }}>
                            {{ $r->apellidos }} {{ $r->nombres }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Calendario de mis citas</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('representante.citas', ['alumno_id' => $alumno?->id, 'anio' => $prevYear, 'mes' => $prevMonth]) }}"
                           class="btn btn-outline-secondary btn-sm">&larr; Anterior</a>
                        <h6 class="mb-0">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</h6>
                        <a href="{{ route('representante.citas', ['alumno_id' => $alumno?->id, 'anio' => $nextYear, 'mes' => $nextMonth]) }}"
                           class="btn btn-outline-secondary btn-sm">Siguiente &rarr;</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0">
                            <thead class="table-light">
                                <tr><th>Dom</th><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th></tr>
                            </thead>
                            <tbody>
                                @foreach($calendar as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            @php
                                                $tieneCita = $day && in_array($day, $diasConCitas);
                                                $isToday = $day && \Carbon\Carbon::createFromDate($year, $month, $day)->isToday();
                                            @endphp
                                            <td class="{{ $day ? ($tieneCita ? 'cita-dia' : '') : 'bg-light' }}">
                                                @if($day)
                                                    <a href="{{ route('representante.citas', ['alumno_id' => $alumno?->id, 'anio' => $year, 'mes' => $month, 'dia' => $day]) }}"
                                                       class="d-block p-2 text-decoration-none {{ $isToday ? 'btn btn-primary btn-sm' : 'text-dark' }}">
                                                        {{ $day }}
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($diaSeleccionado)
                        <div class="mt-3">
                            <h6 class="fw-bold">Citas del {{ \Carbon\Carbon::createFromDate($year, $month, $diaSeleccionado)->format('d/m/Y') }}</h6>
                            @if($citasDelDia->isEmpty())
                                <div class="alert alert-light border">No hay citas este día.</div>
                            @else
                                <ul class="list-group">
                                    @foreach($citasDelDia as $cita)
                                        <li class="list-group-item">
                                            <strong>{{ $cita->fecha_inicio->format('H:i') }}</strong>
                                            @if($cita->docente)
                                                <span class="text-muted">Citado por <strong>{{ $cita->docente->name }} {{ $cita->docente->apellidos }}</strong></span>
                                            @endif
                                            <span class="badge bg-{{ $cita->estado == 'confirmada' ? 'success' : ($cita->estado == 'cancelada' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($cita->estado) }}
                                            </span>
                                            <p class="mb-0 mt-1 small">{{ $cita->motivo }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Solicitar Cita</h6>
                </div>
                <div class="card-body">
                    @if(!$alumno)
                        <div class="alert alert-warning mb-0">No tiene estudiantes a su cargo.</div>
                    @else
                        <form method="POST" action="{{ route('representante.citas.store') }}">
                            @csrf
                            <input type="hidden" name="alumno_id" value="{{ $alumno->id }}">

                            <div class="mb-2">
                                <label class="form-label">Representado: <strong>{{ $alumno->apellidos }} {{ $alumno->nombres }}</strong></label>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Materia</label>
                                    <select name="materia_id" id="materiaSelect" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        @foreach($materias as $mat)
                                            <option value="{{ $mat['id'] }}">{{ $mat['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Docente</label>
                                    <select name="docente_id" id="docenteSelect" class="form-select" required disabled>
                                        <option value="">Seleccione materia primero...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Motivo de la cita</label>
                                <select name="motivo" id="motivoSelect" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach($motivos as $motivo)
                                        <option value="{{ $motivo }}">{{ $motivo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3" id="otroMotivoWrap" style="display:none;">
                                <label class="form-label">Especifique el motivo</label>
                                <input type="text" name="motivo_otro" id="motivoOtro" class="form-control"
                                       placeholder="Describa brevemente el motivo de su consulta" maxlength="500">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-control"
                                       min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hora preferida</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <select name="hora" class="form-select" required>
                                            <option value="">HH</option>
                                            @for($h = 8; $h <= 15; $h++)
                                                <option value="{{ $h }}" {{ $h == 9 ? 'selected' : '' }}>{{ sprintf('%02d', $h) }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select name="minuto" class="form-select" required>
                                            <option value="">MM</option>
                                            @foreach([0, 15, 30, 45] as $mm)
                                                <option value="{{ $mm }}">{{ sprintf('%02d', $mm) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-1"></i>Enviar solicitud al docente
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Próximas citas</h6>
                </div>
                <div class="card-body">
                    @if($proximas->isEmpty())
                        <p class="text-muted mb-0 text-center">Sin citas próximas.</p>
                    @else
                        <ul class="list-group">
                            @foreach($proximas as $cita)
                                <li class="list-group-item">
                                    <strong>{{ $cita->fecha_inicio->format('d/m/Y H:i') }}</strong>
                                    @if($cita->docente)
                                        <span class="text-muted"> · Citado por <strong>{{ $cita->docente->name }} {{ $cita->docente->apellidos }}</strong></span>
                                    @endif
                                    <br>
                                    <span class="badge bg-{{ $cita->estado == 'confirmada' ? 'success' : ($cita->estado == 'cancelada' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($cita->estado) }}
                                    </span>
                                    <small class="text-muted d-block">{{ $cita->motivo }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var materiasData = @json($materias);
    var materiaSelect = document.getElementById('materiaSelect');
    var docenteSelect = document.getElementById('docenteSelect');
    var motivoSelect = document.getElementById('motivoSelect');
    var motivoOtroWrap = document.getElementById('otroMotivoWrap');
    var motivoOtro = document.getElementById('motivoOtro');

    function llenarDocentes() {
        var materiaId = materiaSelect.value;
        docenteSelect.innerHTML = '<option value="">Seleccionar...</option>';
        docenteSelect.disabled = true;
        if (!materiaId) return;
        var materia = materiasData.find(function (m) { return String(m.id) === materiaId; });
        if (!materia) return;
        (materia.docentes || []).forEach(function (d) {
            var opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            docenteSelect.appendChild(opt);
        });
        if (materia.docentes && materia.docentes.length === 1) {
            docenteSelect.value = materia.docentes[0].id;
        }
        if (materia.docentes && materia.docentes.length > 0) {
            docenteSelect.disabled = false;
        }
    }

    materiaSelect.addEventListener('change', llenarDocentes);

    function sincronizarOtro() {
        var mostrar = motivoSelect.value === 'Otro';
        motivoOtroWrap.style.display = mostrar ? 'block' : 'none';
        motivoOtro.required = mostrar;
    }

    motivoSelect.addEventListener('change', sincronizarOtro);
});
</script>
@endpush