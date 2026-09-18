<div style="text-align:center; margin-bottom:16px;">
    <div style="text-transform:uppercase; font-weight:bold; font-size:15px;">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</div>
    <div style="font-size:12px; color:#555;">{{ $institucion?->subtitulo ?? '' }}</div>
    <div style="text-transform:uppercase; font-weight:bold; font-size:17px; margin-top:8px;">
        Acta {{ $tipo === 'parcial' ? 'Parcial' : ($sistema === 'quimestre' ? 'Quimestral' : 'Trimestral') }}
        @if($tipo === 'parcial' && !empty($opActual)) N. {{ $parcial }} — {{ $opActual['label'] }} @endif
    </div>
    <div style="font-size:12px;">Año Lectivo {{ now()->year }}</div>
</div>
<table style="width:100%; font-size:12px; margin-bottom:12px;">
    <tr>
        <td>Docente: <strong>{{ $clase->docente->apellidos ?? '' }} {{ $clase->docente->name ?? '' }}</strong></td>
        <td style="text-align:right;">Fecha: {{ now()->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td>Asignatura: <strong>{{ $clase->materia->nombre ?? '' }}</strong></td>
        <td style="text-align:right;">Curso/Paralelo: <strong>{{ $clase->curso->nombre ?? '' }} - {{ $clase->paralelo->nombre ?? '' }}</strong></td>
    </tr>
</table>