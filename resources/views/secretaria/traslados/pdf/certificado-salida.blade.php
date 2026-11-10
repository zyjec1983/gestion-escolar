<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado de Salida</title>
    <style>
        @page { margin: 42px 46px 52px 46px; }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            margin: 0;
            line-height: 1.55;
        }
        .membrete {
            border-bottom: 3px solid #0f766e;
            padding-bottom: 12px;
            margin-bottom: 22px;
            text-align: center;
        }
        .membrete .institucion {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .membrete .subtitulo { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .membrete .lema { font-size: 9px; color: #6b7280; margin-top: 3px; font-style: italic; }

        .titulo {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #0f766e;
            margin: 6px 0 20px;
        }
        .codigo {
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            margin-top: -14px;
            margin-bottom: 20px;
            letter-spacing: .5px;
        }

        .cuerpo p { text-align: justify; margin: 0 0 10px; }
        .cuerpo .destacado { font-weight: bold; }
        .cuerpo .linea-dato { border-bottom: 1px solid #9ca3af; padding: 0 4px; font-weight: bold; }

        .ficha {
            width: 100%;
            border-collapse: collapse;
            margin: 9px 0 14px;
            background: #f8fafc;
            border: 1px solid #d1d5db;
        }
        .ficha td { padding: 5px 8px; font-size: 9.5px; vertical-align: top; border-bottom: 1px solid #e5e7eb; }
        .ficha tr:last-child td { border-bottom: 0; }
        .ficha .label { color: #6b7280; width: 24%; }

        .tabla-resumen { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        .tabla-resumen th {
            background: #0f172a;
            color: #fff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 6px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .tabla-resumen td { border: 1px solid #d1d5db; padding: 5px 6px; font-size: 9px; }
        .tabla-resumen tbody tr:nth-child(even) td { background: #f8fafc; }
        .centro { text-align: center; }
        .vacio { color: #9ca3af; font-style: italic; }

        .declaracion {
            margin-top: 14px;
            padding: 9px 12px;
            border-left: 4px solid #0f766e;
            background: #f0fdfa;
            font-size: 9px;
            text-align: justify;
        }

        .firmas {
            width: 100%;
            margin-top: 40px;
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
            bottom: -34px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
        .sello {
            margin-top: 16px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="pie">
        Certificado de salida emitido por el Sistema de Gestión Escolar · {{ $fechaEmision->format('d/m/Y H:i') }}
        · Expediente TR-{{ str_pad((string) $expediente->id, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="membrete">
        <div class="institucion">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</div>
        @if($institucion?->subtitulo)
            <div class="subtitulo">{{ $institucion->subtitulo }}</div>
        @endif
        <div class="lema">Comprometidos con la formación integral de la niñez y la juventud</div>
    </div>

    <div class="titulo">Certificado de Salida</div>
    <div class="codigo">
        Emitido el {{ $fechaEmision->format('d/m/Y') }} · Año lectivo {{ $expediente->anio_lectivo }}
    </div>

    <div class="cuerpo">
        <p>
            El/La infrascrito/a <span class="destacado">Rector/a</span> y el/la
            <span class="destacado">Secretario/a General</span> de
            <span class="destacado">{{ $institucion?->nombre ?? 'la Unidad Educativa' }}</span>,
            en uso de sus atribuciones legales,
        </p>

        <p class="destacado" style="text-align:center; letter-spacing:.6px; margin:14px 0;">
            CERTIFICAN
        </p>

        <p>
            Que el/la estudiante <span class="destacado">{{ $alumno?->nombreCompleto() ?? 'Sin nombre' }}</span>,
            portador/a de la identificación N.° <span class="destacado">{{ $alumno?->numero_cedula ?: ($alumno?->codigo ?? '—') }}</span>,
            cursó y aprobó sus estudios en esta institución bajo las condiciones que se detallan a continuación,
            y se encuentra <span class="destacado">habilitado/a para solicitar su traslado</span> a otra institución educativa.
        </p>

        <table class="ficha">
            <tr>
                <td class="label">Curso / paralelo</td>
                <td>{{ $expediente->curso?->nombre ?? '—' }} {{ $expediente->paralelo?->nombre ?? '' }}</td>
                <td class="label">Período lectivo</td>
                <td>{{ $expediente->matricula?->periodo?->nombre ?? $expediente->anio_lectivo }}</td>
            </tr>
            <tr>
                <td class="label">Institución de destino</td>
                <td>{{ $expediente->institucion }}</td>
                <td class="label">Fecha de retiro</td>
                <td>{{ $expediente->fecha_traslado?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Sistema de evaluación</td>
                <td>{{ $sistema === 'trimestre' ? 'Trimestral' : 'Quimestral' }}</td>
                <td class="label">Estado del expediente</td>
                <td>{{ $expediente->estadoLabel() }}</td>
            </tr>
        </table>

        <table class="tabla-resumen">
            <thead>
                <tr>
                    <th style="width:38%;">Asignatura</th>
                    @if($sistema === 'trimestre')
                        <th class="centro">Trimestre 1</th>
                        <th class="centro">Trimestre 2</th>
                        <th class="centro">Trimestre 3</th>
                    @else
                        <th class="centro">1er Quimestre</th>
                        <th class="centro">2do Quimestre</th>
                    @endif
                    <th class="centro" style="width:16%;">Promedio anual</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resumenActual as $fila)
                    @php($datos = $fila['datos'])
                    <tr>
                        <td>{{ $fila['materia'] }}</td>
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
                        <td colspan="{{ $sistema === 'trimestre' ? 5 : 4 }}" class="centro vacio">
                            Sin calificaciones registradas en el período.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="ficha">
            <tr>
                <td class="label">Asistencia acumulada</td>
                <td>
                    {{ $asistencia['total'] }} día(s) registrado(s) ·
                    {{ $asistencia['asistidas'] }} asistencia(s) válida(s) ·
                    <span class="destacado">{{ $asistencia['porcentaje'] !== null ? number_format($asistencia['porcentaje'], 2).'%' : 'sin registro' }}</span>
                </td>
            </tr>
            <tr>
                <td class="label">Paz y salvo financiero</td>
                <td>{{ $expediente->paz_y_salvo_financiero ? 'Conforme — sin deudas pendientes' : 'Pendiente' }}</td>
            </tr>
            <tr>
                <td class="label">Paz y salvo biblioteca</td>
                <td>{{ $expediente->paz_y_salvo_biblioteca ? 'Conforme — sin material pendiente' : 'Pendiente' }}</td>
            </tr>
        </table>

        <div class="declaracion">
            <strong>Constancia de habilitación (RN-02):</strong>
            se deja constancia de que el estudiante no mantiene deudas financieras ni material bibliográfico
            pendiente con esta institución, y que el expediente cuenta con la documentación obligatoria completa.
            El historial académico externo asociado a este expediente es inmutable desde su cierre (RN-03).
            @if($expediente->paz_y_salvo_observacion)
                <br><br><strong>Observación:</strong> {{ $expediente->paz_y_salvo_observacion }}
            @endif
        </div>

        <p style="margin-top:16px; text-align:justify;">
            Se extiende el presente certificado a solicitud del/de la interesado/a para los fines legales y
            académicos que estime convenientes.
        </p>
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
