{{--
    Página "Comunidad educativa" del portal.

    Qué hace: muestra a las autoridades, docentes y personal agrupados por
    área, con su cargo, fotografía y responsabilidad. Las tarjetas y los grupos
    se administran desde Administración -> Comunidad educativa.
    Conecta con: PortalController@comunidad, ComunidadMiembro,
    App\Support\PortalContenido y portal/layout.blade.php.
    Ruta: GET /comunidad (portal.comunidad).
--}}
@extends('portal.layout')

@section('titulo', 'Comunidad Educativa')

@section('contenido')
    @include('portal._page-header', [
        'categoria' => 'Nosotros',
        'pagina' => 'Comunidad Educativa',
        'titulo' => $texto('comunidad.titulo'),
        'subtitulo' => $texto('comunidad.subtitulo'),
    ])

    <div class="container py-5">
        @forelse($miembros as $area => $integrantes)
        <div class="mb-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <h4 class="fw-bold text-navy mb-0">{{ $area ?: 'Comunidad educativa' }}</h4>
                <span class="badge bg-light text-navy border">{{ $integrantes->count() }} integrante(s)</span>
            </div>
            <div class="row g-4">
                @foreach($integrantes as $miembro)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 p-4 text-center card-hover">
                        @if($miembro->fotoUrl())
                        <img src="{{ $miembro->fotoUrl() }}" alt="{{ $miembro->nombre }}" class="miembro-foto mx-auto mb-3">
                        @else
                        <div class="rounded-circle bg-navy text-warning d-inline-flex align-items-center justify-content-center mx-auto mb-3"
                             style="width:76px;height:76px;">
                            <i class="bi bi-person-hearts fs-2"></i>
                        </div>
                        @endif
                        <span class="badge bg-warning text-dark mx-auto mb-2">{{ $miembro->cargo }}</span>
                        <h5 class="fw-bold text-navy mb-1">{{ $miembro->nombre }}</h5>
                        @if($miembro->responsabilidad)
                        <p class="text-muted small mb-0">{{ $miembro->responsabilidad }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-people display-1 d-block mb-3 opacity-25"></i>
            <p class="mb-0">La comunidad educativa se está actualizando.</p>
        </div>
        @endforelse
    </div>
@endsection
