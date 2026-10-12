<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Observaciones</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 14px; color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; background: #17a2b8; color: #fff; }
    </style>
</head>
<body>
    <h1>Historial de Observaciones</h1>
    <h2>{{ $alumno->apellidos }} {{ $alumno->nombres }}</h2>

    @if($observaciones->isEmpty())
        <p>No hay observaciones registradas.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($observaciones as $obs)
                    <tr>
                        <td>{{ $obs->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge">{{ $obs->tipoObservacion?->nombre }}</span></td>
                        <td>{{ $obs->descripcion_texto }}</td>
                        <td>{{ $obs->profesor?->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>