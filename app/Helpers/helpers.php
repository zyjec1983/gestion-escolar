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

if (!function_exists('desfasesHorariosDisponibles')) {
    /**
     * Desfases que puede elegir el administrador, de UTC-11:00 a UTC+11:00.
     *
     * Son 23, no 24: 11 negativas, UTC y 11 positivas. Cada uno lleva ciudades
     * representativas para poder reconocerlo de un vistazo.
     *
     * Se guarda el desfase ("-05:00") y no el nombre de una ciudad porque la
     * pregunta real es "¿cuántas horas lleva mi centro respecto a UTC?".
     *
     * @return array<int, array<string, mixed>>
     */
    function desfasesHorariosDisponibles(): array
    {
        return [
            ['minutos' => -660, 'ciudades' => 'Isla Midway, Samoa'],
            ['minutos' => -600, 'ciudades' => 'Hawái, Islas Aleutianas'],
            ['minutos' => -540, 'ciudades' => 'Alaska'],
            ['minutos' => -480, 'ciudades' => 'Hora del Pacífico (EE. UU. y Canadá), Tijuana'],
            ['minutos' => -420, 'ciudades' => 'Chihuahua, Mazatlán, Arizona, Hora de las Montañas (EE. UU. y Canadá)'],
            ['minutos' => -360, 'ciudades' => 'Guadalajara, Ciudad de México, Monterrey, Hora Central (EE. UU. y Canadá), América Central'],
            ['minutos' => -300, 'ciudades' => 'Bogotá, Lima, Quito, Río Branco, Hora del Este (EE. UU. y Canadá)'],
            ['minutos' => -240, 'ciudades' => 'Georgetown, La Paz, Manaos, San Juan, Asunción, Santiago'],
            ['minutos' => -180, 'ciudades' => 'Buenos Aires, Brasilia, Montevideo, Cayenne, Fortaleza'],
            ['minutos' => -120, 'ciudades' => 'Atlántico Central'],
            ['minutos' => -60, 'ciudades' => 'Azores, Cabo Verde'],
            ['minutos' => 0, 'ciudades' => 'Dublín, Edimburgo, Lisboa, Londres, hora universal coordinada'],
            ['minutos' => 60, 'ciudades' => 'Ámsterdam, Berlín, Berna, Roma, Estocolmo, Viena, Bruselas, Madrid, París, Varsovia'],
            ['minutos' => 120, 'ciudades' => 'Atenas, Bucarest, Estambul, El Cairo, Helsinki, Kyiv, Riga, Sofía, Tallín, Vilna, Jerusalén'],
            ['minutos' => 180, 'ciudades' => 'Moscú, San Petersburgo, Volgogrado, Nairobi, Bagdad, Kuwait, Riad'],
            ['minutos' => 240, 'ciudades' => 'Abu Dabi, Mascate, Bakú, Tiflis, Ereván'],
            ['minutos' => 300, 'ciudades' => 'Islambad, Karachi, Taskent, Ekaterimburgo'],
            ['minutos' => 360, 'ciudades' => 'Astaná, Almaty, Daca, Omsk'],
            ['minutos' => 420, 'ciudades' => 'Bangkok, Hanói, Yakarta, Krasnoyarsk, Novosibirsk'],
            ['minutos' => 480, 'ciudades' => 'Pekín, Chongqing, Hong Kong, Urumqi, Kuala Lumpur, Singapur, Taipéi, Perth, Ulán Bator'],
            ['minutos' => 540, 'ciudades' => 'Tokio, Seúl, Osaka, Sapporo, Yakutsk'],
            ['minutos' => 600, 'ciudades' => 'Canberra, Melbourne, Sídney, Brisbane, Vladivostok, Guam, Puerto Moresby'],
            ['minutos' => 660, 'ciudades' => 'Magadán, Islas Salomón, Nueva Caledonia, Isla de Bougainville, Chokurdakh'],
        ];
    }
}

if (!function_exists('valorDesfaseHorario')) {
    /**
     * Texto que se guarda en la BD: -300 -> "-05:00". PHP no acepta
     * "UTC-05:00" como zona, pero sí "-05:00".
     */
    function valorDesfaseHorario(int $minutos): string
    {
        $signo = $minutos < 0 ? '-' : '+';

        return sprintf('%s%02d:00', $signo, intdiv(abs($minutos), 60));
    }
}

if (!function_exists('esDesfaseHorarioValido')) {
    /**
     * ¿El valor guardado es uno de los 23 desfases de la lista?
     */
    function esDesfaseHorarioValido(?string $valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }

        return in_array($valor, desfasesHorariosValidos(), true);
    }
}

if (!function_exists('desfasesHorariosValidos')) {
    /**
     * Los 23 valores guardables: "-11:00" ... "+11:00".
     *
     * @return array<int, string>
     */
    function desfasesHorariosValidos(): array
    {
        return array_map(
            fn (array $d) => valorDesfaseHorario($d['minutos']),
            desfasesHorariosDisponibles()
        );
    }
}

if (!function_exists('etiquetaDesfaseHorario')) {
    /**
     *Ciudades de un desfase guardado: "-05:00" -> "Bogotá, Lima, ...".
     */
    function etiquetaDesfaseHorario(string $valor): string
    {
        foreach (desfasesHorariosDisponibles() as $desfase) {
            if (valorDesfaseHorario($desfase['minutos']) === $valor) {
                return $desfase['ciudades'];
            }
        }

        return 'Desfase no reconocido';
    }
}

if (!function_exists('minutosDesfaseZona')) {
    /**
     * Minutos de un valor guardado ("-05:00" -> -300). Si el valor no es un
     * desfase se interpreta como nombre de zona de PHP, para poder leer lo que
     * hubiera guardado antes del cambio.
     */
    function minutosDesfaseZona(?string $valor = null): int
    {
        $valor = $valor ?: config('app.timezone');

        if (preg_match('/^([+-])(\d{1,2}):(\d{2})$/', $valor, $m)) {
            $minutos = ((int) $m[2]) * 60 + (int) $m[3];

            return $m[1] === '-' ? -$minutos : $minutos;
        }

        try {
            $tz = new DateTimeZone($valor);
        } catch (Exception) {
            return 0;
        }

        return (int) round($tz->getOffset(new DateTime('now', $tz)) / 60);
    }
}

if (!function_exists('zonaPhpDesdeDesfase')) {
    /**
     * PHP no deja pasar un desfase suelto a date_default_timezone_set(); solo
     * acepta identificadores de zona. Para un desfase fijo se usa Etc/GMT, que
     * es de offset fijo y no aplica horario de verano.
     *
     * Ojo con el signo: Etc/GMT va invertido, Etc/GMT+5 son UTC-5.
     */
    function zonaPhpDesdeDesfase(string $valor): string
    {
        $minutos = minutosDesfaseZona($valor);

        if ($minutos === 0) {
            return 'UTC';
        }

        return 'Etc/GMT' . ($minutos < 0 ? '+' : '-') . intdiv(abs($minutos), 60);
    }
}

if (!function_exists('desfaseZonaHoraria')) {
    /**
     * Diferencia entre la zona del centro y UTC, en horas y con signo.
     * Ej: "-05:00" -> -5, "+11:00" -> +11.
     */
    function desfaseZonaHoraria(?string $zona = null): float
    {
        return minutosDesfaseZona($zona) / 60;
    }
}

if (!function_exists('formatearDesfase')) {
    /**
     * Minutos de desfase en texto: -300 -> "UTC-05:00".
     */
    function formatearDesfase(int $minutos): string
    {
        $signo = $minutos < 0 ? '-' : '+';
        $abs = abs($minutos);

        return sprintf('UTC%s%02d:%02d', $signo, intdiv($abs, 60), $abs % 60);
    }
}

if (!function_exists('etiquetaZonaHoraria')) {
    /**
     * Zona en formato legible: "UTC-05:00 (Bogotá, Lima, Quito)".
     */
    function etiquetaZonaHoraria(?string $zona = null): string
    {
        $zona = $zona ?: config('app.timezone');

        if (!esDesfaseHorarioValido($zona)) {
            return formatearDesfase(minutosDesfaseZona($zona));
        }

        return formatearDesfase(minutosDesfaseZona($zona))
            . ' (' . etiquetaDesfaseHorario($zona) . ')';
    }
}