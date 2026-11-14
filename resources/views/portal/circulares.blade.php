{{--
    Página "Circulares" del portal.

    Qué hace: lista todas las circulares publicadas por la institución con su
    número, fecha, descripción y archivo PDF. Las circulares se cargan desde
    Administración, Rectorado o Secretaría.
    Conecta con: PortalController@circulares, Circular y portal/layout.blade.php.
    Ruta: GET /circulares (portal.circulares).
--}}
@extends('portal.layout')

@section('titulo', 'Circulares')

@section('contenido')
    <div class="bg-navy text-white py-5">
        <div class="container py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item">
                        <a href="{{ route('welcome') }}" class="text-warning text-decoration-none">Home</a>
                    </li>
                    <li class="breadcrumb-item text-white-50">Servicios</li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Circulares</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold">Circulares institucionales</h1>
            <p class="lead text-white-50 mb-0">Comunicación oficial para estudiantes, familias y personal de la institución.</p>
        </div>
    </div>

    <div class="container py-5">
        @if($circulares->count() > 0)
        <div class="row g-4">
            @foreach($circulares as $circular)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-navy">{{ $circular->numero ?: 'Circular' }}</span>
                            <small class="text-muted">{{ $circular->fecha_publicacion?->format('d/m/Y') }}</small>
                        </div>
                        <h6 class="fw-bold text-navy">{{ $circular->titulo }}</h6>
                        @if($circular->descripcion ?? null)
                        <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($circular->descripcion, 160) }}</p>
                        @endif
                    </div>
                    @if($circular->archivo_url)
                    <div class="card-footer bg-white border-0 pt-0">
                        <a href="{{ $circular->archivo_url }}" target="_blank" rel="noopener"
                           class="btn btn-outline-danger btn-sm rounded-pill">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('login') }}" class="btn btn-navy rounded-pill px-4">
                <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar al portal
            </a>
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-megaphone display-1 d-block mb-3 opacity-25"></i>
            <p class="mb-0">No hay circulares publicadas por el momento.</p>
        </div>
        @endif
    </div>
@endsection
