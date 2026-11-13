<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de acuerdo DECE</title>
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
        .firmas { width: 100%; margin-top: 44px; }
        .firmas td { text-align: center; padding: 0 10px; vertical-align: bottom; }
        .firmas .linea { border-top: 1px solid #374151; padding-top: 4px; }
        .pie { margin-top: 22px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ $institucion?->nombre ?? 'Unidad Educativa' }}</h1>
        <p>{{ $institucion?->subtitulo ?? 'Departamento de Consejería Estudiantil' }}</p>
        <p><strong>ACTA DE ACUERDO Y COMPROMISO — DECE</strong></p>
    </div>

    <h2>Identificación</h2>
    <table class="datos">
        <tr>
            <td class="etiqueta">Estudiante</td>
            <td>{{ $acuerdo->alumno?->nombreCompleto() }}</td>
            <td class="etiqueta">Código</td>
            <td>{{ $acuerdo->alumno?->codigo }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Representante</td>
            <td>{{ $acuerdo->alumno?->representante?->nombreCompleto() ?? '—' }}</td>
            <td class="etiqueta">Tipo de acuerdo</td>
            <td>{{ $acuerdo->tipoLabel() }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Fecha de inicio</td>
            <td>{{ $acuerdo->fecha_inicio->format('d/m/Y') }}</td>
            <td class="etiqueta">Fecha límite</td>
            <td>{{ $acuerdo->fecha_compromiso->format('d/m/Y') }}</td>
        </tr>
    </table>

    <h2>Descripción del acuerdo</h2>
    <div class="bloque">{{ $acuerdo->descripcion }}</div>

    <h2>Compromiso del estudiante</h2>
    <div class="bloque">{{ $acuerdo->compromiso_estudiante ?: 'No definido.' }}</div>

    <h2>Compromiso del representante</h2>
    <div class="bloque">{{ $acuerdo->compromiso_representante ?: 'No definido.' }}</div>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">
                    {{ $acuerdo->alumno?->nombreCompleto() }}<br>
                    <small>Estudiante</small><br>
                    <small>{{ $acuerdo->firmado_estudiante_en?->format('d/m/Y H:i') }}</small>
                </div>
            </td>
            <td>
                <div class="linea">
                    {{ $acuerdo->alumno?->representante?->nombreCompleto() ?? 'Representante legal' }}<br>
                    <small>Representante legal</small><br>
                    <small>{{ $acuerdo->firmado_representante_en?->format('d/m/Y H:i') }}</small>
                </div>
            </td>
            <td>
                <div class="linea">
                    {{ $acuerdo->profesional?->nombreCompleto() ?? 'Profesional del DECE' }}<br>
                    <small>Profesional del DECE</small><br>
                    <small>{{ $acuerdo->firmado_profesional_en?->format('d/m/Y H:i') }}</small>
                </div>
            </td>
        </tr>
    </table>

    <div class="pie">
        Acta emitida el {{ $fechaEmision->format('d/m/Y H:i') }} con firma digital y sello de tiempo. Documento confidencial.
    </div>
</body>
</html>
