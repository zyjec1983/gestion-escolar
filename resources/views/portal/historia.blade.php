{{--
    Página "Nuestra historia" del portal.

    Qué hace: muestra el relato fundacional, el lema de la Beata Madre Caridad
    Brader, la imagen principal y la línea de tiempo de hitos administrables.
    Conecta con: PortalController@historia, HistoriaHito,
    App\Support\PortalContenido y portal/layout.blade.php.
    Ruta: GET /historia (portal.historia).
--}}
@extends('portal.layout')

@section('titulo', 'Nuestra Historia')

@section('contenido')
    @include('portal._page-header', [
        'categoria' => 'Nosotros',
        'pagina' => 'Nuestra Historia',
        'titulo' => $texto('historia.titulo'),
        'subtitulo' => $texto('historia.subtitulo'),
    ])

    <div class="container py-5">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                    <img src="{{ $imagen('historia.imagen_principal') }}" class="img-fluid"
                         alt="Campus educativo de {{ $nombreInstitucion }}">
                    <div class="card-body bg-cream p-4">
                        <h5 class="fw-bold text-navy mb-2"><i class="bi bi-quote text-gold me-1"></i>Lema Fundacional</h5>
                        <p class="fst-italic text-muted mb-2">{{ $texto('historia.cita_fundadora') }}</p>
                        <small class="fw-bold text-navy">— Beata Madre Caridad Brader</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <h3 class="fw-bold text-navy mb-3">Raíces Franciscanas y Misión en el Ecuador</h3>
                <p class="text-muted lh-lg mb-4">{{ $texto('historia.contenido') }}</p>

                @if($hitos->count() > 0)
                <h4 class="fw-bold text-navy mb-4">Hitos Históricos</h4>
                <div class="timeline-wrapper">
                    @foreach($hitos as $hito)
                    <div class="timeline-item {{ $loop->last ? 'mb-0' : '' }}">
                        <div class="timeline-dot"></div>
                        <span class="badge {{ $loop->even ? 'bg-navy text-white' : 'bg-warning text-dark' }} mb-1">
                            {{ $hito->anio }}
                        </span>
                        <h6 class="fw-bold text-navy">{{ $hito->titulo }}</h6>
                        @if($hito->descripcion)
                        <p class="text-muted small mb-0">{{ $hito->descripcion }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection
