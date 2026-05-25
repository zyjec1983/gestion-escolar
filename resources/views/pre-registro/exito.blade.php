@extends('layouts.public')

@section('titulo', 'Solicitud Enviada')

@section('contenido')
<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <i class="bi bi-check-circle-fill display-1 text-success mb-3"></i>
                    <h4 class="fw-bold">Solicitud Enviada Exitosamente</h4>
                    <p class="text-muted">Su solicitud de matrícula ha sido registrada. El PDF se ha descargado automáticamente.</p>
                    <p class="text-muted small">Imprímalo y entréguelo en la institución para continuar con el proceso.</p>
                    <hr>
                    <p class="small text-muted mb-0"><strong>Número de solicitud:</strong> {{ session('codigo_solicitud') ?? '—' }}</p>
                    <p class="small text-muted">Estado: <span class="badge bg-warning">Pendiente de revisión</span></p>
                    <a href="/" class="btn btn-primary mt-3 rounded-pill px-4">
                        <i class="bi bi-house-door me-1"></i>Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
