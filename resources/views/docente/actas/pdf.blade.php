<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }
    </style>
</head>
<body>
    @include('docente.actas._cabecera')

    @if(empty($filas))
        <p style="text-align:center; margin-top:24px; font-size:12px;">No hay notas guardadas para mostrar en esta acta.</p>
    @elseif($tipo === 'parcial')
        @include('docente.actas._tabla_parcial')
    @elseif($sistema === 'quimestre')
        @include('docente.actas._tabla_quimestral')
    @else
        @include('docente.actas._tabla_trimestre')
    @endif

    @include('docente.actas._firmas')
</body>
</html>