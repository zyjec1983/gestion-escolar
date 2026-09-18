@php($esExamen = $filas[0]['esExamen'] ?? false)
<table style="width:100%; border-collapse:collapse; font-size:11px;">
    <thead>
        <tr style="background:#e9ecef;">
            <th style="border:1px solid #000; padding:4px; text-align:center;">N.º</th>
            <th style="border:1px solid #000; padding:4px; text-align:center;">Cédula</th>
            <th style="border:1px solid #000; padding:4px; text-align:left;">Apellidos y Nombres</th>
            @if($esExamen)
                <th style="border:1px solid #000; padding:4px; text-align:center;">Examen Quimestral</th>
            @else
                <th style="border:1px solid #000; padding:4px; text-align:center;">FII</th>
                <th style="border:1px solid #000; padding:4px; text-align:center;">FFI</th>
                <th style="border:1px solid #000; padding:4px; text-align:center;">FIG</th>
                <th style="border:1px solid #000; padding:4px; text-align:center;">FFG</th>
                <th style="border:1px solid #000; padding:4px; text-align:center;">Pr. Integ.</th>
                <th style="border:1px solid #000; padding:4px; text-align:center;">Eval.</th>
            @endif
            <th style="border:1px solid #000; padding:4px; text-align:center;">Nota</th>
            <th style="border:1px solid #000; padding:4px; text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filas as $i => $f)
            <tr>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $i + 1 }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $f['alumno']->numero_cedula }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:left;">{{ $f['alumno']->apellidos }} {{ $f['alumno']->nombres }}</td>
                @if($esExamen)
                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($f['nota']) }}</td>
                @else
                    @foreach (['fii', 'ffi', 'fig', 'ffg', 'proyecto_integrador', 'evaluacion_parcial'] as $campo)
                        @php($val = (float) ($f['propios']->{$campo} ?? 0))
                        <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $val > 0 ? number_format($val, 2, '.', '') : '—' }}</td>
                    @endforeach
                @endif
                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ formatearNota($f['nota']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $f['estado'] === 'final' ? 'Final' : ($f['estado'] === 'parcial' ? 'Parcial' : 'Pendiente') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>