@extends('layouts.public')

@section('titulo', 'Inscripciones Cerradas')

@section('contenido')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-5">
                    <i class="bi bi-calendar-x display-1 text-muted mb-3 d-block"></i>
                    <h4 class="fw-bold">Inscripciones Cerradas</h4>
                    <p class="text-muted">Actualmente no estamos recibiendo solicitudes de matrícula.</p>
                    <p class="text-muted small">Las inscripciones se habilitarán durante el período de admisiones. Por favor, vuelva a consultar más tarde o comuníquese con la institución.</p>
                    <a href="/" class="btn btn-primary mt-3"><i class="bi bi-house me-1"></i>Volver al inicio</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
