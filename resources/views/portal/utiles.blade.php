{{--
    Página "Lista de útiles" del portal.

    Qué hace: muestra las listas nuevas creadas en el editor tipo Excel y,
    debajo, los artículos antiguos que aún no pertenecen a una lista,
    agrupados por nivel. Cada pestaña corresponde a un curso o nivel.
    Conecta con: PortalController@utiles, ListaUtil, UtilItem,
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
        @if($grupos->count() > 0)
        <ul class="nav nav-pills gap-2 mb-4 flex-wrap" id="utiles-tabs" role="tablist">
            @foreach($grupos as $grupo)
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4 {{ $loop->first ? 'active' : '' }}"
                        data-bs-toggle="pill" data-bs-target="#utiles-grupo-{{ $loop->index }}"
                        type="button" role="tab">
                    {{ $grupo['nombre'] }}
                </button>
            </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach($grupos as $grupo)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                 id="utiles-grupo-{{ $loop->index }}" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-cream py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-navy mb-0">{{ $grupo['nombre'] }}</h5>
                        <span class="badge bg-navy">{{ $grupo['items']->count() }} artículos</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width:52px;">#</th>
                                    <th class="text-center" style="width:110px;">Cantidad</th>
                                    <th>Artículo / Descripción</th>
                                    <th class="pe-4" style="width:30%;">Materia / Asignatura</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo['items'] as $item)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $loop->iteration }}</td>
                                    <td class="text-center fw-semibold">{{ $item->cantidad ?: '—' }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $item->nombre }}</span>
                                        @if($item->especificacion)
                                        <div class="small text-muted">{{ $item->especificacion }}</div>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-muted">{{ $item->materiaVisible() ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
