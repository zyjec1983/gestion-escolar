<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte DECE</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .encabezado { text-align: center; border-bottom: 2px solid #6f42c1; padding-bottom: 8px; margin-bottom: 14px; }
        .encabezado h1 { font-size: 15px; margin: 0; }
        .encabezado p { margin: 2px 0; color: #6b7280; font-size: 10px; }
        h2 { font-size: 12px; color: #6f42c1; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 5px; text-align: left; }
        th { background: #f3f4f6; }
        .indicadores td { text-align: center; }
        .indicadores .valor { font-size: 15px; font-weight: bold; color: #6f42c1; }
        .pie { margin-top: 22px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ $institucion?->nombre ?? 'Unidad Educativa' }}</h1>
        <p>{{ $institucion?->subtitulo ?? 'Departamento de Consejería Estudiantil' }}</p>
        <p><strong>REPORTE AGREGADO DEL DECE</strong></p>
        <p>Período: {{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}</p>
    </div>

    <h2>Indicadores generales</h2>
    <table class="indicadores">
        <tr>
            <td><div class="valor">{{ $stats['atencionesPeriodo'] }}</div>Atenciones del período</td>
            <td><div class="valor">{{ $citasPorEstado->sum() }}</div>Citas registradas</td>
            <td><div class="valor">{{ $stats['acuerdosActivos'] }}</div>Acuerdos activos</td>
            <td><div class="valor">{{ $stats['derivacionesAbiertas'] }}</div>Derivaciones abiertas</td>
            <td><div class="valor">{{ $stats['neeActivos'] }}</div>NEE activas</td>
        </tr>
    </table>

    <h2>Motivos de atención más frecuentes</h2>
    <table>
        <thead><tr><th>Motivo</th><th style="width: 90px;">Total</th></tr></thead>
        <tbody>
            @forelse($stats['porMotivo'] as $motivo => $total)
            <tr><td>{{ $motivo }}</td><td>{{ $total }}</td></tr>
            @empty
            <tr><td colspan="2">Sin atenciones en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Cursos con más atenciones</h2>
    <table>
        <thead><tr><th>Curso</th><th style="width: 90px;">Atenciones</th></tr></thead>
        <tbody>
            @forelse($atencionesPorCurso as $curso => $total)
            <tr><td>{{ $curso }}</td><td>{{ $total }}</td></tr>
            @empty
            <tr><td colspan="2">Sin datos en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Citas por estado</h2>
    <table>
        <thead><tr><th>Estado</th><th style="width: 90px;">Total</th></tr></thead>
        <tbody>
            @foreach($citasPorEstado as $estado => $total)
            <tr><td>{{ \App\Models\DeceCita::ESTADOS[$estado] ?? $estado }}</td><td>{{ $total }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Derivaciones por tipo</h2>
    <table>
        <thead><tr><th>Tipo</th><th style="width: 90px;">Total</th></tr></thead>
        <tbody>
            @foreach($derivacionesPorTipo as $tipo => $total)
            <tr><td>{{ \App\Models\DeceDerivacion::TIPOS[$tipo] ?? $tipo }}</td><td>{{ $total }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>NEE por categoría</h2>
    <table>
        <thead><tr><th>Categoría</th><th style="width: 90px;">Total</th></tr></thead>
        <tbody>
            @forelse($neePorCategoria as $categoria => $total)
            <tr><td>{{ $categoria }}</td><td>{{ $total }}</td></tr>
            @empty
            <tr><td colspan="2">Sin registros NEE activos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Talleres realizados</h2>
    <table>
        <thead>
            <tr><th>Fecha</th><th>Título</th><th>Tipo</th><th>Dirigido a</th><th style="width: 70px;">Asistentes</th></tr>
        </thead>
        <tbody>
            @forelse($talleres as $taller)
            <tr>
                <td>{{ $taller->fecha->format('d/m/Y') }}</td>
                <td>{{ $taller->titulo }}</td>
                <td>{{ $taller->tipoLabel() }}</td>
                <td>{{ $taller->dirigidoLabel() }}</td>
                <td>{{ $taller->asistentes_count }}</td>
            </tr>
            @empty
            <tr><td colspan="5">Sin talleres en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Reporte generado el {{ $fechaEmision->format('d/m/Y H:i') }} · Solo indicadores agregados, sin datos clínicos individuales.
    </div>
</body>
</html>
