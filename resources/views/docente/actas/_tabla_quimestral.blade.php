<table style="width:100%; border-collapse:collapse; font-size:11px;">
    <thead>
        <tr style="background:#e9ecef; text-align:center;">
            <th rowspan="2" style="border:1px solid #000; padding:4px;">N.º</th>
            <th rowspan="2" style="border:1px solid #000; padding:4px;">Cédula</th>
            <th rowspan="2" style="border:1px solid #000; padding:4px; text-align:left;">Apellidos y Nombres</th>
            <th colspan="7" style="border:1px solid #000; padding:4px;">Quimestre 1</th>
            <th colspan="7" style="border:1px solid #000; padding:4px;">Quimestre 2</th>
            <th rowspan="2" style="border:1px solid #000; padding:4px;">AÑO</th>
        </tr>
        <tr style="background:#e9ecef; text-align:center;">
            <th style="border:1px solid #000; padding:4px;">P1</th>
            <th style="border:1px solid #000; padding:4px;">P2</th>
            <th style="border:1px solid #000; padding:4px;">Prom.</th>
            <th style="border:1px solid #000; padding:4px;">70%</th>
            <th style="border:1px solid #000; padding:4px;">T.Exam</th>
            <th style="border:1px solid #000; padding:4px;">30%</th>
            <th style="border:1px solid #000; padding:4px;">Total</th>
            <th style="border:1px solid #000; padding:4px;">P3</th>
            <th style="border:1px solid #000; padding:4px;">P4</th>
            <th style="border:1px solid #000; padding:4px;">Prom.</th>
            <th style="border:1px solid #000; padding:4px;">70%</th>
            <th style="border:1px solid #000; padding:4px;">T.Exam</th>
            <th style="border:1px solid #000; padding:4px;">30%</th>
            <th style="border:1px solid #000; padding:4px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filas as $i => $r)
            @php
                $prom1 = ($r['p1'] !== null && $r['p2'] !== null) ? ($r['p1'] + $r['p2']) / 2 : null;
                $v701 = $prom1 !== null ? $prom1 * 0.7 : null;
                $v301 = $r['te1'] !== null ? $r['te1'] * 0.3 : null;
                $prom2 = ($r['p3'] !== null && $r['p4'] !== null) ? ($r['p3'] + $r['p4']) / 2 : null;
                $v702 = $prom2 !== null ? $prom2 * 0.7 : null;
                $v302 = $r['te2'] !== null ? $r['te2'] * 0.3 : null;
            @endphp
            <tr>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $i + 1 }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $r['alumno']->numero_cedula }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:left;">{{ $r['alumno']->apellidos }} {{ $r['alumno']->nombres }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['p1']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['p2']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($prom1) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($v701) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['te1']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($v301) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ formatearNota($r['q1']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['p3']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['p4']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($prom2) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($v702) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($r['te2']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ formatearNota($v302) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ formatearNota($r['q2']) }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ formatearNota($r['anio']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>