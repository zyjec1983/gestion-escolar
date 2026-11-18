{{--
    Paginación Bootstrap 5 en español.

    Qué hace: reemplaza la vista Tailwind que Laravel usa por defecto (sus SVG
    se veían gigantes al no existir Tailwind en el proyecto) y traduce los
    textos al español. Se aplica a todos los `$paginador->links()`.
    Conecta con: AppServiceProvider (Paginator::useBootstrapFive) y las vistas
    con listados paginados.
--}}
@if ($paginator->hasPages())
    <nav aria-label="Navegación de páginas"
         class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
        <p class="small text-muted mb-0">
            Mostrando
            <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            a
            <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            de
            <span class="fw-semibold">{{ $paginator->total() }}</span>
            {{ $paginator->total() === 1 ? 'resultado' : 'resultados' }}
        </p>

        <ul class="pagination pagination-sm mb-0 flex-wrap">
            {{-- Página anterior --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Anterior">
                    <span class="page-link" aria-hidden="true">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">
                        &laquo; <span class="d-none d-sm-inline">Anterior</span>
                    </a>
                </li>
            @endif

            {{-- Números de página --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Página siguiente --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">
                        <span class="d-none d-sm-inline">Siguiente</span> &raquo;
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Siguiente">
                    <span class="page-link" aria-hidden="true">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
