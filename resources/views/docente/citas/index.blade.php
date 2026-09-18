@extends('layouts.educalinks')

@section('titulo', 'Citas')

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4"><i class="bi bi-calendar-event me-2"></i>Agenda de Citas</h4>

    @if(session('success'))
        <div id="swal-success" data-message="{{ session('success') }}" hidden></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('docente.citas.index', ['anio' => $prevYear, 'mes' => $prevMonth]) }}"
                           class="btn btn-outline-secondary btn-sm">&larr; Anterior</a>
                        <h5 class="mb-0">{{ strftime('%B %Y', $date->timestamp) }}</h5>
                        <a href="{{ route('docente.citas.index', ['anio' => $nextYear, 'mes' => $nextMonth]) }}"
                           class="btn btn-outline-secondary btn-sm">Siguiente &rarr;</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dom</th><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calendar as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            @php
                                                $tieneCita = $day && in_array($day, $diasConCitas);
                                                $isToday = $day && \Carbon\Carbon::createFromDate($year, $month, $day)->isToday();
                                            @endphp
                                            <td class="{{ $day ? ($tieneCita ? 'bg-danger bg-opacity-10' : '') : 'bg-light' }}" style="min-height:60px;">
                                                @if($day)
                                                    <a href="{{ route('docente.citas.index', ['anio' => $year, 'mes' => $month, 'dia' => $day]) }}"
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
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            @if($diaSeleccionado)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            Citas del {{ \Carbon\Carbon::createFromDate($year, $month, $diaSeleccionado)->format('d/m/Y') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($citasDelDia->isEmpty())
                            <div class="alert alert-info mb-0">No hay citas para este día.</div>
                        @else
                            <ul class="list-group">
                                @foreach($citasDelDia as $cita)
                                    <li class="list-group-item">
                                        <strong>{{ $cita->fecha_inicio->format('H:i') }} - {{ $cita->fecha_fin->format('H:i') }}</strong>
                                        <br>
                                        <span class="badge bg-{{ $cita->estado == 'confirmada' ? 'success' : ($cita->estado == 'cancelada' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($cita->estado) }}
                                        </span>
                                        @if($cita->alumno)
                                            <br><small class="text-muted">Estudiante: <strong>{{ $cita->alumno->apellidos }} {{ $cita->alumno->nombres }}</strong></small>
                                            @if($cita->alumno->representante)
                                                <br><small class="text-muted">Representante: <strong>{{ $cita->alumno->representante->name }}</strong></small>
                                            @endif
                                        @endif
                                        <p class="mb-0 mt-1 small">{{ $cita->motivo }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Agendar Nueva Cita</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <label class="form-label">Clase</label>
                        <select name="clase_docente_id" class="form-select" onchange="this.form.submit()">
                            @foreach($clases as $c)
                                <option value="{{ $c->id }}" {{ $clase && $clase->id == $c->id ? 'selected' : '' }}>
                                    {{ $c->curso->nombre ?? 'N/A' }} - {{ $c->paralelo->nombre ?? 'N/A' }} - {{ $c->materia->nombre ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="anio" value="{{ $year }}">
                        <input type="hidden" name="mes" value="{{ $month }}">
                        @if($diaSeleccionado)
                            <input type="hidden" name="dia" value="{{ $diaSeleccionado }}">
                        @endif
                    </form>

                    <form method="POST" action="{{ route('docente.citas.store') }}">
                        @csrf
                        <input type="hidden" name="clase_docente_id" value="{{ $clase?->id }}">

                        <div class="mb-3">
                            <label class="form-label">Estudiante</label>
                            <select name="alumno_id" class="form-select" required>
                                <option value="">Seleccionar estudiante...</option>
                                @foreach($alumnos as $al)
                                    <option value="{{ $al->id }}">
                                        {{ $al->apellidos }} {{ $al->nombres }}
                                        @if($al->representante)
                                            (Rep: {{ $al->representante->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control"
                                   min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <select name="hora" class="form-select" required>
                                        <option value="">HH</option>
                                        @for($h = 8; $h <= 15; $h++)
                                            <option value="{{ $h }}">{{ sprintf('%02d', $h) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="minuto" class="form-select" required>
                                        <option value="">MM</option>
                                        <option value="0">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <select name="motivo" class="form-select" required>
                                <option value="">Seleccionar...</option>
                                @foreach($motivos as $motivo)
                                    <option value="{{ $motivo }}">{{ $motivo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje (opcional)</label>
                            <textarea name="mensaje" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-calendar-plus me-1"></i>Agendar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
