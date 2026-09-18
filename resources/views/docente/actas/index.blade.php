@extends('layouts.educalinks')

@section('titulo', 'Actas')

@push('styles')
<style>
    .acta-documento { font-size: 12px; }
    .acta-documento table { font-size: 11px; }
    .acta-documento h5 { font-size: 15px; }
    .acta-documento h4 { font-size: 17px; }
    .acta-documento .small, .acta-documento small { font-size: 0.9em; }
    @media print {
        body * { visibility: hidden; }
        .acta-documento, .acta-documento * { visibility: visible; }
        .acta-documento { position: absolute; left: 0; top: 0; width: 100%; font-size: 12px; }
        .acta-documento table { font-size: 11px !important; }
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid">
    <h4 class="mb-4 no-print"><i class="bi bi-file-earmark-text me-2"></i>Actas</h4>

    <div class="no-print">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Clase</label>
                <select name="clase_docente_id" class="form-select" onchange="this.form.submit()">
                    @foreach($clases as $c)
                        <option value="{{ $c->id }}" {{ $clase && $clase->id == $c->id ? 'selected' : '' }}>
                            {{ $c->curso->nombre ?? 'N/A' }} - {{ $c->paralelo->nombre ?? 'N/A' }} - {{ $c->materia->nombre ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de Acta</label>
                <div class="d-flex gap-2">
                    <a href="{{ route('docente.actas.index', ['clase_docente_id' => $clase?->id, 'tipo' => 'parcial']) }}" class="btn {{ $tipo === 'parcial' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="bi bi-journal-text me-1"></i>Acta Parcial
                    </a>
                    <a href="{{ route('docente.actas.index', ['clase_docente_id' => $clase?->id, 'tipo' => 'quimestral']) }}" class="btn {{ $tipo === 'quimestral' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="bi bi-journal-check me-1"></i>Acta Quimestral
                    </a>
                </div>
            </div>
            @if($tipo === 'parcial' && !empty($opciones))
            <div class="col-md-2">
                <label class="form-label">Periodo</label>
                <select name="parcial" class="form-select" onchange="this.form.submit()">
                    @foreach($opciones as $op)
                        <option value="{{ $op['parcial'] }}" {{ $parcial == $op['parcial'] ? 'selected' : '' }}>
                            {{ $op['grupo'] }} - {{ $op['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <a href="{{ route('docente.actas.pdf', request()->query()) }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
                <a href="{{ route('docente.actas.excel', request()->query()) }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
            </div>
        </form>
    </div>

    @if(!$clase)
        <div class="alert alert-warning no-print">No tienes clases asignadas.</div>
    @elseif(empty($filas))
        <div class="alert alert-info no-print">Aún no hay notas guardadas para mostrar en esta acta.</div>
    @else
        <div class="acta-documento bg-white p-4 border shadow-sm">
            @include('docente.actas._cabecera')

            @if($tipo === 'parcial')
                @include('docente.actas._tabla_parcial')
            @elseif($sistema === 'quimestre')
                @include('docente.actas._tabla_quimestral')
            @else
                @include('docente.actas._tabla_trimestre')
            @endif

            @include('docente.actas._firmas')
        </div>
    @endif
</div>
@endsection