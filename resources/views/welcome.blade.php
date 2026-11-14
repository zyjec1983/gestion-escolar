@extends('layouts.public')

@section('titulo', 'Inicio')

@section('contenido')
@if($slides->count() > 0)
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel"
    data-interval="{{ $institucion?->carousel_interval ?? 2000 }}">
    <div class="carousel-indicators">
        @foreach($slides as $i => $slide)
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $i }}"
            class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
        @endforeach
    </div>
    <div class="carousel-inner">
        @foreach($slides as $i => $slide)
        <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
            <img src="{{ Storage::url($slide->imagen_path) }}" class="d-block w-100"
                alt="{{ $slide->titulo ?? 'Slide' }}"
                style="max-height:70vh;object-fit:cover;">
            @if($slide->titulo || $slide->subtitulo)
            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center"
                style="background: rgba(0,0,0,0.4); left:0; right:0; bottom:0; top:0; padding:2rem;">
                @if($slide->titulo)
                <h1 class="display-4 fw-bold text-white">{{ $slide->titulo }}</h1>
                @endif
                @if($slide->subtitulo)
                <p class="lead text-white mb-0">{{ $slide->subtitulo }}</p>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Anterior</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Siguiente</span>
    </button>
</div>
@else
<section class="bg-dark text-white py-5" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
    <div class="container text-center py-5">
        <i class="bi bi-mortarboard-fill display-1 text-primary mb-3"></i>
        <h1 class="display-4 fw-bold">{{ $institucion?->nombre ?? 'Unidad Educativa' }}</h1>
        <p class="lead text-secondary mb-4">{{ $institucion?->subtitulo ?? 'Formando el futuro de nuestra sociedad' }}</p>
    </div>
</section>
@endif

<section class="py-4 text-center" style="background: #1a1a2e;">
    <div class="container">
        @if($institucion?->pre_registro_activo)
        <a href="/pre-registro" class="btn btn-primary btn-lg rounded-pill px-5">
            <i class="bi bi-pencil-square me-2"></i>Solicitar Matrícula
        </a>
        @else
        <button class="btn btn-secondary btn-lg rounded-pill px-5" disabled>
            <i class="bi bi-clock me-2"></i>Inscripciones Cerradas
        </button>
        @endif
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <i class="bi bi-eye display-5 text-primary mb-3"></i>
                    <h5 class="fw-bold">Misión</h5>
                    <p class="text-muted small">Formar estudiantes íntegros con valores éticos, académicos y sociales, preparados para los desafíos del futuro.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <i class="bi bi-stars display-5 text-primary mb-3"></i>
                    <h5 class="fw-bold">Visión</h5>
                    <p class="text-muted small">Ser una institución educativa líder en innovación pedagógica y formación integral a nivel nacional.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <i class="bi bi-heart display-5 text-primary mb-3"></i>
                    <h5 class="fw-bold">Valores</h5>
                    <p class="text-muted small">Respeto, responsabilidad, honestidad, solidaridad y compromiso con la excelencia educativa.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">¿Cómo funciona?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">1</span>
                    </div>
                    <h5 class="fw-bold">Complete el formulario</h5>
                    <p class="text-muted small">Llene sus datos personales y los del aspirante en línea.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">2</span>
                    </div>
                    <h5 class="fw-bold">Reciba su PDF</h5>
                    <p class="text-muted small">Descargue e imprima su solicitud de matrícula inmediatamente.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fs-4 fw-bold">3</span>
                    </div>
                    <h5 class="fw-bold">Espere la confirmación</h5>
                    <p class="text-muted small">La institución revisará y aprobará su solicitud. Recibirá un email con sus credenciales.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="/pre-registro" class="btn btn-primary btn-lg rounded-pill px-5">
                <i class="bi bi-arrow-right me-2"></i>Comenzar Solicitud
            </a>
        </div>
    </div>
</section>
@endsection
