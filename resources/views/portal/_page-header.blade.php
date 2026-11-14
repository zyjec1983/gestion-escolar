{{--
    Encabezado reutilizable de las páginas internas del portal.

    Qué hace: dibuja la franja azul con la miga de pan (Home / categoría /
    página) y el título y subtítulo de la sección.
    Conecta con: portal/layout.blade.php y cada vista portal/*.blade.php.
    Rutas: /historia, /comunidad, /utiles, /descargas y /circulares.
--}}
<div class="bg-navy text-white py-5">
    <div class="container py-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item">
                    <a href="{{ route('welcome') }}" class="text-warning text-decoration-none">Home</a>
                </li>
                <li class="breadcrumb-item text-white-50">{{ $categoria }}</li>
                <li class="breadcrumb-item active text-white" aria-current="page">{{ $pagina }}</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">{{ $titulo }}</h1>
        <p class="lead text-white-50 mb-0">{{ $subtitulo }}</p>
    </div>
</div>
