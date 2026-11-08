<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de observaciones - {{ optional($alumno)->apellidos ?? 'Por curso/paralelo' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        h1 { text-align: center; font-size: 12pt; margin-bottom: 6px; }
        h2 { text-align: center; font-size: 10pt; margin-top: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #f2f2f2; text-align: left; font-size: 9pt; }
        td { font-size: 9pt; vertical-align: top; }
        .meta { margin-top: 8px; font-size: 9pt; }
    </style>
</head>
<body>
    <h1>REPORTE DE OBSERVACIONES</h1>
    @if ($alumno)
        <h2>{{ strtoupper($alumno->apellidos.' '.$alumno->nombres) }}</h2>
    @else
        <h2>POR NIVEL / PARALELO</h2>
    @endif
    <div class="meta">
        @if ($fechaDesde || $fechaHasta)
            <div>Rango de fechas: {{ $fechaDesde ? $fechaDesde->format('d/m/Y') : '—' }} a {{ $fechaHasta ? $fechaHasta->format('d/m/Y') : '—' }}</div>
        @endif
        <div>Total registros: {{ $observaciones->count() }}</div>
    </div>
    @if ($alumno && $resumenAlumno)
        @php $pc = $resumenAlumno['por_clasificacion']; @endphp
        <div class="meta">
            <strong>Resumen:</strong>
            Positivas: {{ $pc['positiva'] }} |
            Leves: {{ $pc['leve'] }} |
            Graves: {{ $pc['grave'] }} |
            Muy Graves: {{ $pc['muy_grave'] }}
            @if ($hojaVida->isNotEmpty())
                <br><strong>Por tipo:</strong>
                @foreach ($hojaVida as $hv)
                    {{ $hv->tipo_nombre }}: {{ $hv->total }}{{ $loop->last ? '' : ' | ' }}
                @endforeach
            @endif
        </div>
    @endif
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                @if (!$alumno)
                    <th>Estudiante</th>
                @endif
                <th>Tipo</th>
                <th>Clasificación</th>
                <th>Descripción</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($observaciones as $obs)
                <tr>
                    <td>{{ $obs->created_at->format('d/m/Y H:i') }}</td>
                    @if (!$alumno)
                        <td>{{ $obs->alumno->apellidos ?? '' }} {{ $obs->alumno->nombres ?? '' }}</td>
                    @endif
                    <td>{{ $obs->tipoObservacion->nombre ?? '' }}</td>
                    <td>{{ $obs->tipoObservacion?->etiquetaClasificacion() }}</td>
                    <td>{{ $obs->descripcion_texto }}</td>
                    <td>{{ nombreCorto($obs->profesor->name ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $alumno ? 5 : 6 }}">Sin observaciones en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
