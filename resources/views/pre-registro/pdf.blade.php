<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Solicitud de Matrícula</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 18px; margin: 5px 0; }
        .header h2 { font-size: 14px; color: #555; margin: 0; }
        .section { margin-bottom: 20px; }
        .section h3 { font-size: 14px; border-bottom: 2px solid #0d6efd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; text-align: left; border: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; width: 35%; }
        .badge { display: inline-block; padding: 3px 10px; font-size: 11px; border-radius: 4px; }
        .badge-pendiente { background: #ffc107; color: #000; }
        .footer { text-align: center; margin-top: 40px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .solicitud-id { text-align: right; font-size: 16px; font-weight: bold; color: #0d6efd; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="solicitud-id">N° {{ $preRegistro->codigo_solicitud }}</div>
    <div class="header">
        <h1>Unidad Educativa</h1>
        <h2>SOLICITUD DE MATRÍCULA</h2>
        <span class="badge badge-pendiente">{{ strtoupper($preRegistro->estado) }}</span>
    </div>

    <div class="section">
        <h3>Datos del Representante</h3>
        <table>
            <tr><th>Tipo Documento</th><td>{{ strtoupper($preRegistro->rep_tipo_documento) }}</td></tr>
            <tr><th>Número Documento</th><td>{{ $preRegistro->rep_numero_documento }}</td></tr>
            <tr><th>Nombres</th><td>{{ $preRegistro->rep_nombres }}</td></tr>
            <tr><th>Apellidos</th><td>{{ $preRegistro->rep_apellidos }}</td></tr>
            <tr><th>Fecha de nacimiento</th><td>{{ $preRegistro->rep_fecha_nacimiento?->format('d/m/Y') ?? '-' }}</td></tr>
            <tr><th>Email</th><td>{{ $preRegistro->rep_email }}</td></tr>
            <tr><th>Teléfono</th><td>{{ $preRegistro->rep_telefono ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Datos del Alumno (Aspirante)</h3>
        <table>
            <tr><th>Tipo Documento</th><td>{{ strtoupper($preRegistro->alumno_tipo_documento) }}</td></tr>
            <tr><th>Número Documento</th><td>{{ $preRegistro->alumno_numero_documento }}</td></tr>
            <tr><th>Nombres</th><td>{{ $preRegistro->alumno_nombres }}</td></tr>
            <tr><th>Apellidos</th><td>{{ $preRegistro->alumno_apellidos }}</td></tr>
            <tr><th>Género</th><td>{{ $preRegistro->alumno_genero ? ucfirst($preRegistro->alumno_genero) : '-' }}</td></tr>
            <tr><th>Fecha Nacimiento</th><td>{{ $preRegistro->alumno_fecha_nacimiento?->format('d/m/Y') ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Selección Académica</h3>
        <table>
            <tr><th>Curso</th><td>{{ $preRegistro->curso->nombre ?? '-' }}</td></tr>
            <tr><th>Paralelo</th><td>{{ $preRegistro->paralelo ? "Paralelo {$preRegistro->paralelo->nombre}" : '-' }}</td></tr>
            <tr><th>Período Lectivo</th><td>{{ $preRegistro->periodo->nombre ?? '-' }}</td></tr>
            <tr><th>Fecha de Solicitud</th><td>{{ $preRegistro->created_at?->format('d/m/Y H:i') }}</td></tr>
        </table>
    </div>

    <div class="footer">
        <p>Esta solicitud debe ser impresa y entregada en la institución para continuar con el proceso de matrícula.</p>
        <p>Unidad Educativa - {{ date('Y') }}</p>
    </div>
</body>
</html>
