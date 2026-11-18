{{--
    Circulares recientes del panel directivo.

    Qué hace: reutiliza las circulares que entrega DashboardResource para que
    Rectorado y Vicerrectorado las consulten desde su panel.
    Espera: $circulares.
--}}
<div class="card shadow-sm h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-megaphone me-2"></i>Circulares recientes</h6>
        <a href="{{ route('admin.circulares.index') }}" class="btn btn-sm btn-outline-primary">
            Gestionar
        </a>
    </div>
    <div class="list-group list-group-flush">
        @forelse($circulares as $circular)
        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
            <div class="min-w-0">
                <div class="fw-semibold text-truncate">{{ $circular['titulo'] ?? 'Circular' }}</div>
                <small class="text-muted">{{ $circular['fecha_publicacion'] ?? '' }}</small>
            </div>
            @if(! empty($circular['archivo_url']))
            <a href="{{ $circular['archivo_url'] }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                <i class="bi bi-download"></i>
            </a>
            @endif
        </div>
        @empty
        <div class="list-group-item text-center text-muted py-4">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            No hay circulares publicadas
        </div>
        @endforelse
    </div>
</div>
