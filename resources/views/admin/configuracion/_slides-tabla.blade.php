{{--
    Tabla reutilizable de slides del carrusel.

    Qué hace: muestra los slides de una ubicación (inicio o login) con su orden,
    vista previa, título, estado y acciones. La usan las pestañas "Carrusel del
    portal" y "Carrusel del login" de Configuración.
    Conecta con: Admin\HeroSlideController y App\Models\HeroSlide.
    Rutas: GET /admin/hero-slides/crear, GET /admin/hero-slides/{id}/editar,
            DELETE /admin/hero-slides/{id}.
--}}
@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\HeroSlide> $lista */
    $lista = $lista ?? collect();
    // Ubicación con la que se preselecciona el formulario al crear un slide.
    $ubicacion = $ubicacion ?? null;
    $urlNuevo = route('admin.hero-slides.create', $ubicacion ? ['ubicacion' => $ubicacion] : []);
@endphp

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-images me-1"></i>{{ $titulo ?? 'Slides' }} ({{ $lista->count() }})</h6>
        <a href="{{ $urlNuevo }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Slide
        </a>
    </div>
    <div class="card-body p-0">
        @if($lista->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">Orden</th>
                        <th style="width:120px;">Vista previa</th>
                        <th>Título</th>
                        <th>Mostrar en</th>
                        <th>Estado</th>
                        <th style="width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lista as $slide)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">{{ $slide->orden }}</span>
                        </td>
                        <td>
                            @if($slide->imagen_path)
                            <img src="{{ Storage::url($slide->imagen_path) }}" alt="{{ $slide->titulo }}"
                                class="slide-thumb rounded">
                            @else
                            <span class="text-muted small">Sin imagen</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $slide->titulo ?? 'Sin título' }}</strong>
                            @if($slide->subtitulo)
                            <br><small class="text-muted">{{ $slide->subtitulo }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">
                                {{ \App\Models\HeroSlide::UBICACIONES[$slide->ubicacion] ?? 'Página de inicio' }}
                            </span>
                        </td>
                        <td>
                            @if($slide->activo)
                            <span class="badge bg-success">Activo</span>
                            @else
                            <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.hero-slides.edit', $slide->id) }}"
                                class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide->id) }}"
                                class="d-inline" onsubmit="return confirm('¿Eliminar este slide?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-images display-1 text-muted mb-3 d-block"></i>
            <p class="text-muted">No hay slides en esta sección. Crea el primero.</p>
            <a href="{{ $urlNuevo }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nuevo Slide
            </a>
        </div>
        @endif
    </div>
</div>
