<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estudiantes en riesgo</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        .cabecera { text-align: center; margin-bottom: 12px; }
        .cabecera .institucion { text-transform: uppercase; font-weight: bold; font-size: 14px; }
        .cabecera .titulo { text-transform: uppercase; font-weight: bold; font-size: 13px; margin-top: 6px; }
        .cabecera .detalle { font-size: 9px; color: #444; margin-top: 4px; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .kpis td { border: 1px solid #bbb; padding: 6px; text-align: center; width: 33%; }
        .kpis .valor { font-size: 14px; font-weight: bold; display: block; }
        .kpis .etiqueta { font-size: 8px; color: #555; text-transform: uppercase; }
        table.datos { width: 100%; border-collapse: collapse; }
        table.datos th, table.datos td { border: 1px solid #999; padding: 3px 4px; }
        table.datos th { background: #e9ecef; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.datos td.numero { text-align: right; }
        .pie { margin-top: 16px; font-size: 8px; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="cabecera">
        <div class="institucion">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</div>
        <div>{{ $institucion?->subtitulo ?? '' }}</div>
        <div class="titulo">Estudiantes en riesgo · {{ $corte_etiqueta }}</div>
        <div class="detalle">
            Periodo: {{ $periodo?->nombre ?? 'Sin periodo' }}
            · Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <span class="valor">{{ number_format($total_rojo, 0, ',', '.') }}</span>
                <span class="etiqueta">Nivel rojo</span>
            </td>
            <td>
                <span class="valor">{{ number_format($total_amarillo, 0, ',', '.') }}</span>
                <span class="etiqueta">Nivel amarillo</span>
            </td>
            <td>
                <span class="valor">{{ number_format($estudiantes->count(), 0, ',', '.') }}</span>
                <span class="etiqueta">Total en seguimiento</span>
            </td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Cédula</th>
                <th>Curso</th>
                <th>Paralelo</th>
                <th class="numero">Promedio</th>
                <th class="numero">Reprobadas</th>
                <th class="numero">Asistencia</th>
                <th>Semáforo</th>
                <th>Motivos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($estudiantes as $estudiante)
            <tr>
                <td>{{ $estudiante['alumno']->nombreCompleto() }}</td>
                <td>{{ $estudiante['alumno']->numero_cedula ?? '—' }}</td>
                <td>{{ $estudiante['curso'] ?? '—' }}</td>
                <td>{{ $estudiante['paralelo'] ?? '—' }}</td>
                <td class="numero">{{ $estudiante['promedio'] === null ? '—' : number_format($estudiante['promedio'], 2, ',', '.') }}</td>
                <td class="numero">{{ $estudiante['reprobadas'] }}</td>
                <td class="numero">{{ $estudiante['asistencia'] === null ? '—' : number_format($estudiante['asistencia'], 1, ',', '.') . ' %' }}</td>
                <td>{{ $estudiante['nivel'] === 'rojo' ? 'Rojo' : 'Amarillo' }}</td>
                <td>{{ implode(' · ', $estudiante['motivos']) }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;">No hay estudiantes en riesgo para los filtros elegidos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Documento generado por el sistema de gestión escolar · Escala de aprobación: 7/10
    </div>
</body>
</html>
