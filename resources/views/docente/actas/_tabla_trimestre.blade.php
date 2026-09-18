<table style="width:100%; border-collapse:collapse; font-size:11px;">
    <thead>
        <tr style="background:#e9ecef; text-align:center;">
            <th style="border:1px solid #000; padding:4px;">N.º</th>
            <th style="border:1px solid #000; padding:4px;">Cédula</th>
            <th style="border:1px solid #000; padding:4px; text-align:left;">Apellidos y Nombres</th>
            <th style="border:1px solid #000; padding:4px;">Trimestre 1</th>
            <th style="border:1px solid #000; padding:4px;">Trimestre 2</th>
            <th style="border:1px solid #000; padding:4px;">Trimestre 3</th>
            <th style="border:1px solid #000; padding:4px;">AÑO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filas as $i => $r)
            <tr>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $i + 1 }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $r['alumno']->numero_cedula }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:left;">{{ $r['alumno']->apellidos }} {{ $r['alumno']->nombres }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['t1']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['t2']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['t3']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ formatearNota($r['anio']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>