<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial Académico Unificado</title>
    <style>
        @page { margin: 34px 34px 46px 34px; }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        .membrete {
            border-bottom: 3px solid #0f766e;
            padding-bottom: 10px;
            margin-bottom: 14px;
            text-align: center;
        }
        .membrete .institucion {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .membrete .subtitulo { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .membrete .titulo {
            margin-top: 10px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f766e;
            letter-spacing: .8px;
        }
        .membrete .periodo { font-size: 9px; color: #6b7280; margin-top: 2px; }

        .ficha {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .ficha td { padding: 3px 6px; vertical-align: top; }
        .ficha .label { color: #6b7280; width: 16%; }
        .ficha .valor { font-weight: bold; width: 34%; }

        h2 {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #0f766e;
            border-left: 4px solid #0f766e;
            padding-left: 7px;
            margin: 16px 0 6px;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.datos th {
            background: #0f172a;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 6px 6px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        table.datos td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 9px;
        }
        table.datos tbody tr:nth-child(even) td { background: #f8fafc; }
        .centro { text-align: center; }
        .derecha { text-align: right; }
        .vacio { color: #9ca3af; font-style: italic; }

        .resumen-asistencia {
            margin-top: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px 10px;
            background: #f8fafc;
        }
        .resumen-asistencia table { width: 100%; border-collapse: collapse; }
        .resumen-asistencia td { padding: 2px 4px; font-size: 9px; }
        .resumen-asistencia .big { font-size: 12px; font-weight: bold; color: #0f766e; }

        .firmas {
            width: 100%;
            margin-top: 46px;
            text-align: center;
            border-collapse: collapse;
        }
        .firmas td { width: 33.33%; padding: 0 8px; vertical-align: bottom; }
        .firmas .linea {
            border-top: 1px solid #111827;
            margin: 0 auto 4px;
            width: 82%;
        }
        .firmas .cargo { font-size: 9px; font-weight: bold; }
        .firmas .nombre { font-size: 8px; color: #6b7280; }

        .pie {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
        .nota-legal {
            margin-top: 14px;
            font-size: 8px;
            color: #6b7280;
            text-align: justify;
            line-height: 1.45;
        }
        .sello {
            margin-top: 18px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="pie">
        Documento generado automáticamente por el Sistema de Gestión Escolar · {{ $fechaEmision->format('d/m/Y H:i') }}
        · Código de expediente: TR-{{ str_pad((string) $expediente->id, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="membrete">
        <div class="institucion">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</div>
        @if($institucion?->subtitulo)
            <div class="subtitulo">{{ $institucion->subtitulo }}</div>
        @endif
        <div class="titulo">Historial Académico Unificado</div>
        <div class="periodo">
            Año lectivo {{ $expediente->anio_lectivo }} · Emitido el {{ $fechaEmision->format('d/m/Y') }}
        </div>
    </div>

    <table class="ficha">
        <tr>
            <td class="label">Estudiante</td>
            <td class="valor">{{ $alumno?->nombreCompleto() ?? 'Sin nombre' }}</td>
            <td class="label">Identificación</td>
            <td class="valor">{{ $alumno?->numero_cedula ?: ($alumno?->codigo ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">Curso / paralelo</td>
            <td class="valor">
                {{ $expediente->curso?->nombre ?? '—' }}
                {{ $expediente->paralelo?->nombre ?? '' }}
            </td>
            <td class="label">Fecha de nacimiento</td>
            <td class="valor">{{ $alumno?->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tipo de trámite</td>
            <td class="valor">{{ $expediente->tipoLabel() }} · {{ $expediente->momentoLabel() }}</td>
            <td class="label">Institución de origen</td>
            <td class="valor">{{ $expediente->institucion }}</td>
        </tr>
        <tr>
            <td class="label">Estado del expediente</td>
            <td class="valor">{{ $expediente->estadoLabel() }}</td>
            <td class="label">Sistema de evaluación</td>
            <td class="valor">{{ $sistema === 'trimestre' ? 'Trimestral' : 'Quimestral' }}</td>
        </tr>
    </table>

    <h2>1. Promociones de años anteriores (homologadas)</h2>
    <table class="datos">
        <thead>
            <tr>
                <th style="width:18%;">Año lectivo</th>
                <th style="width:30%;">Nivel / grado</th>
                <th style="width:14%;" class="centro">Promedio</th>
                <th style="width:14%;" class="centro">Escala</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expediente->promociones as $promocion)
                <tr>
                    <td>{{ $promocion->anio_lectivo }}</td>
                    <td>{{ $promocion->nivel }}</td>
                    <td class="centro">{{ number_format((float) $promocion->promedio, 2) }}</td>
                    <td class="centro">{{ $promocion->escala }}</td>
                    <td>{{ $promocion->observacion ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="centro vacio">No se registraron promociones externas homologadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Calificaciones parciales del año en curso (institución de origen)</h2>
    <table class="datos">
        <thead>
            <tr>
                <th style="width:26%;">Asignatura</th>
                <th style="width:18%;">Período</th>
                <th style="width:10%;" class="centro">Nota</th>
                <th style="width:12%;" class="centro">Ponderación</th>
                <th style="width:12%;" class="centro">Asistencia</th>
                <th style="width:12%;" class="centro">Escala</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expediente->notasParciales as $notaExterna)
                <tr>
                    <td>{{ $notaExterna->asignatura }}</td>
                    <td>{{ $notaExterna->periodo }}</td>
                    <td class="centro">{{ number_format((float) $notaExterna->nota, 2) }}</td>
                    <td class="centro">{{ $notaExterna->ponderacion !== null ? number_format((float) $notaExterna->ponderacion, 2).'%' : '—' }}</td>
                    <td class="centro">{{ $notaExterna->asistencia_porcentaje !== null ? number_format((float) $notaExterna->asistencia_porcentaje, 2).'%' : '—' }}</td>
                    <td class="centro">{{ $notaExterna->escala }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="centro vacio">No se registraron calificaciones parciales externas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Resumen del año lectivo en curso</h2>
    <table class="datos">
        <thead>
            <tr>
                <th style="width:34%;">Asignatura</th>
                <th style="width:22%;">Docente</th>
                @if($sistema === 'trimestre')
                    <th class="centro">Trimestre 1</th>
                    <th class="centro">Trimestre 2</th>
                    <th class="centro">Trimestre 3</th>
                    <th class="centro" style="width:12%;">Promedio anual</th>
                @else
                    <th class="centro">1er Quimestre</th>
                    <th class="centro">2do Quimestre</th>
                    <th class="centro" style="width:12%;">Promedio anual</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($resumenActual as $fila)
                @php($datos = $fila['datos'])
                <tr>
                    <td>{{ $fila['materia'] }}</td>
                    <td>{{ $fila['docente']?->nombreCompleto() ?? '—' }}</td>
                    @if($sistema === 'trimestre')
                        <td class="centro">{{ isset($datos['t1']) && $datos['t1'] !== null ? number_format($datos['t1'], 2) : '—' }}</td>
                        <td class="centro">{{ isset($datos['t2']) && $datos['t2'] !== null ? number_format($datos['t2'], 2) : '—' }}</td>
                        <td class="centro">{{ isset($datos['t3']) && $datos['t3'] !== null ? number_format($datos['t3'], 2) : '—' }}</td>
                    @else
                        <td class="centro">{{ isset($datos['q1']) && $datos['q1'] !== null ? number_format($datos['q1'], 2) : '—' }}</td>
                        <td class="centro">{{ isset($datos['q2']) && $datos['q2'] !== null ? number_format($datos['q2'], 2) : '—' }}</td>
                    @endif
                    <td class="centro" style="font-weight:bold;">
                        {{ isset($datos['anio']) && $datos['anio'] !== null ? number_format($datos['anio'], 2) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $sistema === 'trimestre' ? 6 : 5 }}" class="centro vacio">
                        El estudiante aún no registra calificaciones en esta institución.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="resumen-asistencia">
        <table>
            <tr>
                <td style="width:34%;">Registro de asistencia acumulada</td>
                <td>Días registrados: <strong>{{ $asistencia['total'] }}</strong></td>
                <td>Asistencias válidas: <strong>{{ $asistencia['asistidas'] }}</strong></td>
                <td class="derecha">
                    Porcentaje:
                    <span class="big">
                        {{ $asistencia['porcentaje'] !== null ? number_format($asistencia['porcentaje'], 2).'%' : 'Sin registro' }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="nota-legal">
        El presente historial unificado conserva la escala y la ponderación originales de las calificaciones
        provenientes de la institución de origen (RN-05). Las promociones y notas externas aquí consignadas
        fueron homologadas por Secretaría y, una vez cerrado el expediente, no admiten modificación (RN-03).
        Este documento no sustituye las actas oficiales de la institución de origen.
    </div>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea"></div>
                <div class="cargo">Rector/a</div>
                <div class="nombre">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</div>
            </td>
            <td>
                <div class="linea"></div>
                <div class="cargo">Secretario/a General</div>
                <div class="nombre">Responsable del expediente</div>
            </td>
            <td>
                <div class="linea"></div>
                <div class="cargo">Representante legal</div>
                <div class="nombre">{{ $alumno?->representante?->nombreCompleto() ?? 'Firma del representante' }}</div>
            </td>
        </tr>
    </table>

    <div class="sello">
        Documento válido sin enmendaduras · {{ $institucion?->nombre ?? 'Unidad Educativa' }}
    </div>
</body>
</html>
