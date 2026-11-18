<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Rendimiento académico</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1, h2, h3 { margin: 0; }
        .cabecera { text-align: center; margin-bottom: 12px; }
        .cabecera .institucion { text-transform: uppercase; font-weight: bold; font-size: 14px; }
        .cabecera .titulo { text-transform: uppercase; font-weight: bold; font-size: 13px; margin-top: 6px; }
        .cabecera .detalle { font-size: 10px; color: #444; margin-top: 4px; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .kpis td { border: 1px solid #bbb; padding: 6px; text-align: center; width: 25%; }
        .kpis .valor { font-size: 14px; font-weight: bold; display: block; }
        .kpis .etiqueta { font-size: 9px; color: #555; text-transform: uppercase; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.datos th, table.datos td { border: 1px solid #999; padding: 4px 5px; }
        table.datos th { background: #e9ecef; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.datos td.numero { text-align: right; }
        .seccion { font-size: 11px; font-weight: bold; margin: 12px 0 6px; text-transform: uppercase; }
        .pie { margin-top: 18px; font-size: 9px; color: #555; text-align: center; }
    </style>
</head>
<body>
    <div class="cabecera">
        <div class="institucion">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</div>
        <div>{{ $institucion?->subtitulo ?? '' }}</div>
        <div class="titulo">Rendimiento académico · {{ $corte_etiqueta }}</div>
        <div class="detalle">
            Periodo: {{ $periodo?->nombre ?? 'Sin periodo' }}
            · Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <span class="valor">{{ $promedio === null ? '—' : number_format($promedio, 2, ',', '.') . ' / 10' }}</span>
                <span class="etiqueta">Promedio general</span>
            </td>
            <td>
                <span class="valor">{{ number_format($evaluados, 0, ',', '.') }}</span>
                <span class="etiqueta">Notas evaluadas</span>
            </td>
            <td>
                <span class="valor">{{ number_format($aprobados, 0, ',', '.') }}</span>
                <span class="etiqueta">Aprobados</span>
            </td>
            <td>
                <span class="valor">{{ number_format($reprobados, 0, ',', '.') }}</span>
                <span class="etiqueta">Reprobados</span>
            </td>
        </tr>
    </table>

    <div class="seccion">Distribución de notas</div>
    <table class="datos">
        <thead>
            <tr>
                <th>Rango</th>
                <th>Escala</th>
                <th class="numero">Total</th>
                <th class="numero">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribucion as $rango)
            <tr>
                <td>{{ $rango['etiqueta'] }}</td>
                <td>{{ $rango['rango'] }}</td>
                <td class="numero">{{ $rango['total'] }}</td>
                <td class="numero">{{ number_format($rango['porcentaje'], 1, ',', '.') }} %</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="seccion">Resumen por nivel</div>
    <table class="datos">
        <thead>
            <tr>
                <th>Nivel</th>
                <th class="numero">Evaluados</th>
                <th class="numero">Promedio</th>
                <th class="numero">Aprobados</th>
                <th class="numero">Reprobados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumen_niveles as $fila)
            <tr>
                <td>{{ $fila['etiqueta'] }}</td>
                <td class="numero">{{ $fila['evaluados'] }}</td>
                <td class="numero">{{ $fila['promedio'] === null ? '—' : number_format($fila['promedio'], 2, ',', '.') }}</td>
                <td class="numero">{{ $fila['aprobados'] }}</td>
                <td class="numero">{{ $fila['reprobados'] }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;">Sin datos para los filtros elegidos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="seccion">Resumen por curso y paralelo</div>
    <table class="datos">
        <thead>
            <tr>
                <th>Curso y paralelo</th>
                <th class="numero">Evaluados</th>
                <th class="numero">Promedio</th>
                <th class="numero">Aprobados</th>
                <th class="numero">Reprobados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumen_cursos as $fila)
            <tr>
                <td>{{ $fila['etiqueta'] }}</td>
                <td class="numero">{{ $fila['evaluados'] }}</td>
                <td class="numero">{{ $fila['promedio'] === null ? '—' : number_format($fila['promedio'], 2, ',', '.') }}</td>
                <td class="numero">{{ $fila['aprobados'] }}</td>
                <td class="numero">{{ $fila['reprobados'] }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;">Sin datos para los filtros elegidos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="seccion">Resumen por asignatura</div>
    <table class="datos">
        <thead>
            <tr>
                <th>Asignatura</th>
                <th class="numero">Evaluados</th>
                <th class="numero">Promedio</th>
                <th class="numero">Aprobados</th>
                <th class="numero">Reprobados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumen_materias as $fila)
            <tr>
                <td>{{ $fila['etiqueta'] }}</td>
                <td class="numero">{{ $fila['evaluados'] }}</td>
                <td class="numero">{{ $fila['promedio'] === null ? '—' : number_format($fila['promedio'], 2, ',', '.') }}</td>
                <td class="numero">{{ $fila['aprobados'] }}</td>
                <td class="numero">{{ $fila['reprobados'] }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;">Sin datos para los filtros elegidos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="seccion">Resumen por estudiante</div>
    <table class="datos">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Curso</th>
                <th>Paralelo</th>
                <th class="numero">Promedio</th>
                <th class="numero">Materias</th>
                <th class="numero">Reprobadas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumnos as $fila)
            <tr>
                <td>{{ trim(($fila['alumno']->apellidos ?? '') . ' ' . ($fila['alumno']->nombres ?? '')) }}</td>
                <td>{{ $fila['curso'] ?? '—' }}</td>
                <td>{{ $fila['paralelo'] ?? '—' }}</td>
                <td class="numero">{{ $fila['promedio'] === null ? '—' : number_format($fila['promedio'], 2, ',', '.') }}</td>
                <td class="numero">{{ $fila['materias'] }}</td>
                <td class="numero">{{ $fila['reprobadas'] }}</td>
                <td>{{ $fila['estado'] }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;">Sin datos para los filtros elegidos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Documento generado por el sistema de gestión escolar · Escala de aprobación: 7/10
    </div>
</body>
</html>
