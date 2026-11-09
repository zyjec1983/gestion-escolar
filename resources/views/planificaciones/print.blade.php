<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Planificación - {{ $unidad->titulo }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4; margin: 15mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111827;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .toolbar {
            max-width: 800px;
            margin: 0 auto 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn {
            border: 1px solid #1f2937;
            background: #1f2937;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-outline { background: #fff; color: #1f2937; }
        .reporte {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 28px 32px;
            border: 1px solid #d1d5db;
        }
        .membrete {
            text-align: center;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .membrete h1 {
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .membrete p { margin: 4px 0 0; color: #4b5563; font-size: 11px; }
        .titulo-reporte {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 16px 0 4px;
        }
        .subtitulo-reporte {
            text-align: center;
            color: #4b5563;
            font-size: 10px;
            margin-bottom: 18px;
        }
        .seccion { margin-bottom: 16px; page-break-inside: avoid; }
        .seccion h2 {
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 4px;
            margin: 0 0 8px;
            color: #1f2937;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 24px;
        }
        .grid p { margin: 0; }
        .contenido {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 10px 12px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.5;
        }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td {
            border: 1px solid #9ca3af;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #e5e7eb; }
        .firmas {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            margin-top: 48px;
            text-align: center;
            page-break-inside: avoid;
        }
        .firma { flex: 1; }
        .firma-linea { border-top: 1px solid #111827; margin-bottom: 4px; height: 28px; }
        .firma small { font-size: 9px; color: #4b5563; }
        .pie {
            margin-top: 24px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .reporte { border: none; max-width: none; padding: 0; }
        }
        @media (max-width: 600px) {
            .grid { grid-template-columns: 1fr; }
            .firmas { flex-direction: column; gap: 32px; }
            .reporte { padding: 18px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn" onclick="window.print()">Imprimir</button>
        <button type="button" class="btn btn-outline" onclick="window.close()">Cerrar</button>
    </div>

    <div class="reporte">
        <div class="membrete">
            <h1>{{ $institucion?->nombre ?? config('app.name') }}</h1>
            <p>{{ $institucion?->subtitulo ?? 'Institución Educativa' }}</p>
            <p>Sistema de Gestión Escolar</p>
        </div>

        <div class="titulo-reporte">Reporte de Planificación Académica - Visita Áulica</div>
        <div class="subtitulo-reporte">
            Código: PLAN-{{ $unidad->id }}-{{ now()->format('YmdHis') }} ·
            Generado: {{ now()->format('d/m/Y H:i:s') }} ·
            Por: {{ auth()->user()->nombreCompleto() }}
        </div>

        <div class="seccion">
            <h2>Datos generales</h2>
            <div class="grid">
                <p><strong>Materia:</strong> {{ $unidad->materia?->nombre ?? '—' }}</p>
                <p><strong>Nivel / Paralelo:</strong> {{ $unidad->nivelParalelo() }}</p>
                <p><strong>Docente titular:</strong> {{ $unidad->docenteTitular()?->nombreCompleto() ?? '—' }}</p>
                <p><strong>Quimestre / Parcial:</strong> {{ $unidad->quimestre ?: '—' }} - {{ $unidad->parcial ?: '—' }}</p>
                <p><strong>Fecha de inicio:</strong> {{ $unidad->fecha_inicio?->format('d/m/Y') ?? '—' }}</p>
                <p><strong>Fecha de finalización:</strong> {{ $unidad->fecha_fin?->format('d/m/Y') ?? '—' }}</p>
                <p><strong>Duración:</strong> {{ $unidad->duracionDias() }} día(s)</p>
                <p><strong>Periodo lectivo:</strong> {{ $unidad->periodoLectivo?->nombre ?? '—' }}</p>
                <p><strong>Registrada por:</strong> {{ $unidad->creador?->nombreCompleto() ?? 'Registro institucional' }}</p>
                <p><strong>Estado:</strong> {{ $auditorias->isNotEmpty() ? 'Con intervención administrativa' : 'Vigente' }}</p>
            </div>
        </div>

        <div class="seccion">
            <h2>Contenido y objetivos</h2>
            <p><strong>Título de la unidad:</strong> {{ $unidad->titulo }}</p>
            <div class="contenido">{{ $unidad->descripcion ?: 'Sin contenido registrado.' }}</div>
        </div>

        <div class="seccion">
            <h2>Aplicación a paralelos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nivel / Paralelo</th>
                        <th>Docente titular</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo as $item)
                        <tr>
                            <td>{{ $item->nivelParalelo() }}</td>
                            <td>{{ $item->docenteTitular()?->nombreCompleto() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="seccion">
            <h2>Bitácora de auditoría</h2>
            @if($auditorias->isEmpty())
                <p class="text-muted">Sin intervenciones administrativas registradas.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Fecha exacta</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acción</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditorias as $auditoria)
                            <tr>
                                <td>{{ $auditoria->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $auditoria->usuario_nombre }}</td>
                                <td>{{ $auditoria->usuario_rol }}</td>
                                <td>{{ $auditoria->accionLabel() }}</td>
                                <td>{{ $auditoria->motivo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="firmas">
            <div class="firma">
                <div class="firma-linea"></div>
                <strong>Docente titular</strong>
                <br><small>{{ $unidad->docenteTitular()?->nombreCompleto() ?? '' }}</small>
            </div>
            <div class="firma">
                <div class="firma-linea"></div>
                <strong>Rectora / Vicerrectora</strong>
                <br><small>Supervisión pedagógica</small>
            </div>
            <div class="firma">
                <div class="firma-linea"></div>
                <strong>Secretaría</strong>
                <br><small>Registro y archivo</small>
            </div>
        </div>

        <div class="pie">
            Documento generado por el Sistema de Gestión Escolar. La información refleja el estado de la planificación al momento de su impresión.
        </div>
    </div>

    @if(request()->boolean('auto'))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
