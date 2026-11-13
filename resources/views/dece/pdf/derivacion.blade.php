<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Derivación DECE</title>
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
        .firma { margin-top: 46px; text-align: center; }
        .firma .linea { border-top: 1px solid #374151; margin: 0 auto; width: 60%; padding-top: 4px; }
        .pie { margin-top: 22px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ $institucion?->nombre ?? 'Unidad Educativa' }}</h1>
        <p>{{ $institucion?->subtitulo ?? 'Departamento de Consejería Estudiantil' }}</p>
        <p><strong>DOCUMENTO DE DERIVACIÓN — DECE</strong></p>
    </div>

    <h2>Identificación del caso</h2>
    <table class="datos">
        <tr>
            <td class="etiqueta">Estudiante</td>
            <td>{{ $derivacion->alumno?->nombreCompleto() ?? '—' }}</td>
            <td class="etiqueta">Código</td>
            <td>{{ $derivacion->alumno?->codigo ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Representante</td>
            <td>{{ $derivacion->alumno?->representante?->nombreCompleto() ?? '—' }}</td>
            <td class="etiqueta">Tipo</td>
            <td>{{ $derivacion->tipoLabel() }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Fecha de derivación</td>
            <td>{{ $derivacion->fecha_derivacion?->format('d/m/Y') ?? '—' }}</td>
            <td class="etiqueta">Fecha límite</td>
            <td>{{ $derivacion->fecha_limite?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Oficio</td>
            <td>{{ $derivacion->oficio ?? '—' }}</td>
            <td class="etiqueta">Estado</td>
            <td>{{ $derivacion->estadoLabel() }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Entidad de destino</td>
            <td colspan="3">{{ $derivacion->entidad_externa ?? 'Departamento de Consejería Estudiantil' }}</td>
        </tr>
    </table>

    <h2>Motivo</h2>
    <div class="bloque">{{ $derivacion->motivo }}</div>

    <h2>Descripción del caso</h2>
    <div class="bloque">{{ $derivacion->descripcion }}</div>

    @if($derivacion->respuesta)
    <h2>Respuesta / seguimiento</h2>
    <div class="bloque">{{ $derivacion->respuesta }}</div>
    @endif

    <div class="firma">
        <div class="linea">
            {{ $derivacion->derivador?->nombreCompleto() ?? 'Profesional del DECE' }}
        </div>
        <div class="small">Profesional que remite el caso</div>
    </div>

    <div class="pie">
        Documento generado el {{ $fechaEmision->format('d/m/Y H:i') }} · Derivación #{{ $derivacion->id }}
    </div>
</body>
</html>
