@php
    $badges = [
        'programada' => 'bg-primary',
        'asistio' => 'bg-success',
        'no_asistio' => 'bg-danger',
        'cancelada' => 'bg-secondary',
        'activo' => 'bg-success',
        'cumplido' => 'bg-primary',
        'incumplido' => 'bg-danger',
        'vencido' => 'bg-warning text-dark',
        'abierta' => 'bg-primary',
        'cerrada' => 'bg-secondary',
        'pendiente' => 'bg-warning text-dark',
        'en_proceso' => 'bg-info text-dark',
        'atendida' => 'bg-success',
        'interno' => 'bg-primary',
        'externo' => 'bg-warning text-dark',
    ];
@endphp

<span class="badge {{ $badges[$estado] ?? 'bg-secondary' }}">{{ $label ?? $estado }}</span>
