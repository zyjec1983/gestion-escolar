<?php

namespace Database\Seeders;

use App\Models\Circular;
use Illuminate\Database\Seeder;

class CircularSeeder extends Seeder
{
    public function run(): void
    {
        Circular::create([
            'titulo' => 'CIRCULAR Nº 018',
            'numero' => 'CIRC-018',
            'fecha_publicacion' => '2026-05-19',
            'archivo_url' => '/downloads/circular_018.pdf',
        ]);

        Circular::create([
            'titulo' => 'CIRCULAR Nº 017',
            'numero' => 'CIRC-017',
            'fecha_publicacion' => '2026-05-19',
            'archivo_url' => '/downloads/circular_017.pdf',
        ]);
    }
}
