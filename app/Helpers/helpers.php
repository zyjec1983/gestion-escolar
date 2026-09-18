<?php

if (!function_exists('nombreCorto')) {
    function nombreCorto(?string $nombre): string
    {
        $nombre = strtoupper(trim(preg_replace('/\s+/', ' ', $nombre ?? '')));
        $parts = explode(' ', $nombre);
        $count = count($parts);
        if ($count < 3) return $nombre;
        return $parts[0] . ' ' . $parts[$count - 2];
    }
}

if (!function_exists('truncarNota')) {
    function truncarNota(?float $valor): ?float
    {
        if ($valor === null) return null;
        return floor(($valor * 100) + 1e-9) / 100;
    }
}

if (!function_exists('formatearNota')) {
    function formatearNota(?float $valor): string
    {
        $truncado = truncarNota($valor);
        return $truncado !== null ? number_format($truncado, 2, '.', '') : '—';
    }
}