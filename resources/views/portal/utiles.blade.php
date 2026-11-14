{{--
    Página "Lista de útiles" del portal.

    Qué hace: muestra los materiales por nivel (inicial, básica media y
    superior) en pestañas, con especificación, cantidad y área. La lista se
    administra desde Administración -> Sitio web -> Útiles.
    Conecta con: PortalController@utiles, UtilItem,
    App\Support\PortalContenido y portal/layout.blade.php.
    Ruta: GET /utiles (portal.utiles).
--}}
@extends('portal.layout')

@section('titulo', 'Lista de Útiles')

@section('contenido')
    @include('portal._page-header', [
        'categoria' => 'Servicios',
        'pagina' => 'Lista de Útiles',
        'titulo' => $texto('utiles.titulo'),
        'subtitulo' => $texto('utiles.subtitulo'),
    ])

    <div class="container py-5">
        @if($utiles->count() > 0)
        <ul class="nav nav-pills gap-2 mb-4 flex-wrap" id="utiles-tabs" role="tablist">
            @foreach(\App\Models\UtilItem::NIVELES as $clave => $etiqueta)
            @if($utiles->has($clave))
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4 {{ $loop->first ? 'active' : '' }}"
                        data-bs-toggle="pill" data-bs-target="#nivel-{{ $clave }}" type="button" role="tab">
                    {{ $etiqueta }}
                </button>
            </li>
            @endif
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach(\App\Models\UtilItem::NIVELES as $clave => $etiqueta)
            @if($utiles->has($clave))
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="nivel-{{ $clave }}" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-cream py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-navy mb-0">{{ $etiqueta }}</h5>
                        <span class="badge bg-navy">{{ $utiles[$clave]->count() }} artículos</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Material / Recurso</th>
                                    <th>Especificación</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="pe-4">Área / Uso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($utiles[$clave] as $item)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $item->nombre }}</td>
                                    <td class="text-muted">{{ $item->especificacion ?: '—' }}</td>
                                    <td class="text-center">{{ $item->cantidad ?: '—' }}</td>
                                    <td class="pe-4 text-muted">{{ $item->area ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
        @else
        <div class="text-center text-muted py-5">
            <i class="bi bi-backpack2 display-1 d-block mb-3 opacity-25"></i>
            <p class="mb-0">La lista de útiles se publicará próximamente.</p>
        </div>
        @endif
    </div>
@endsection
