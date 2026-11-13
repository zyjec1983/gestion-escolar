<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de atención DECE</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .encabezado { text-align: center; border-bottom: 2px solid #6f42c1; padding-bottom: 8px; margin-bottom: 14px; }
        .encabezado h1 { font-size: 15px; margin: 0; }
        .encabezado p { margin: 2px 0; color: #6b7280; font-size: 10px; }
        h2 { font-size: 12px; color: #6f42c1; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        .datos td { padding: 3px 4px; vertical-align: top; }
        .datos td.etiqueta { width: 26%; color: #6b7280; }
        .bloque { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; white-space: pre-line; }
        .firma { margin-top: 40px; text-align: center; }
        .firma .linea { border-top: 1px solid #374151; margin: 0 auto; width: 60%; padding-top: 4px; }
        .pie { margin-top: 22px; font-size: 9px; color: #9ca3af; text-align: center; }
        ul { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ $institucion?->nombre ?? 'Unidad Educativa' }}</h1>
        <p>{{ $institucion?->subtitulo ?? 'Departamento de Consejería Estudiantil' }}</p>
        <p><strong>FICHA DE ATENCIÓN INDIVIDUAL — DECE</strong></p>
    </div>

    <h2>Datos de la atención</h2>
    <table class="datos">
        <tr>
            <td class="etiqueta">Estudiante</td>
            <td>{{ $atencion->alumno?->nombreCompleto() }}</td>
            <td class="etiqueta">Código</td>
            <td>{{ $atencion->alumno?->codigo }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Cédula</td>
            <td>{{ $atencion->alumno?->numero_cedula ?? '—' }}</td>
            <td class="etiqueta">Representante</td>
            <td>{{ $atencion->alumno?->representante?->nombreCompleto() ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Fecha y hora</td>
            <td>{{ $atencion->fecha_atencion->format('d/m/Y H:i') }}</td>
            <td class="etiqueta">Estado</td>
            <td>{{ $atencion->estadoLabel() }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Motivo</td>
            <td>{{ $atencion->motivo }}</td>
            <td class="etiqueta">Profesional</td>
            <td>{{ $atencion->profesional?->nombreCompleto() ?? '—' }}</td>
        </tr>
    </table>

    <h2>Relatoría de la entrevista</h2>
    <div class="bloque">{{ $atencion->historia_narrada }}</div>

    <h2>Acuerdos y recomendaciones</h2>
    <div class="bloque">{{ $atencion->acuerdos ?: 'Sin acuerdos registrados.' }}</div>

    @if($atencion->archivos->isNotEmpty())
    <h2>Evidencias adjuntas</h2>
    <ul>
        @foreach($atencion->archivos as $archivo)
            <li>{{ $archivo->nombre_original }} ({{ $archivo->tamanoLegible() }})</li>
        @endforeach
    </ul>
    @endif

    <div class="firma">
        <div class="linea">
            {{ $atencion->profesional?->nombreCompleto() ?? 'Profesional del DECE' }}<br>
            <small>Profesional de Consejería Estudiantil</small>
        </div>
    </div>

    <div class="pie">
        Documento generado el {{ $fechaEmision->format('d/m/Y H:i') }} · Información confidencial protegida por el DECE.
    </div>
</body>
</html>
