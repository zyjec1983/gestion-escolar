<?php

namespace Database\Seeders;

use App\Models\TipoObservacion;
use Illuminate\Database\Seeder;

class TipoObservacionSeeder extends Seeder
{
    public function run(): void
    {
        TipoObservacion::create([
            'codigo' => 'OBS_ACAD_NEG',
            'nombre' => 'OBSERVACIÓN ACADÉMICA POR MEJORAR',
            'icono' => 'smile-green',
        ]);

        TipoObservacion::create([
            'codigo' => 'OBS_ACAD_POS',
            'nombre' => 'OBSERVACIÓN ACADÉMICA POSITIVA',
            'icono' => 'star-yellow',
        ]);

        TipoObservacion::create([
            'codigo' => 'OBS_CONDUCTA',
            'nombre' => 'OBSERVACIÓN DE CONDUCTA',
            'icono' => 'warning-red',
        ]);
    }
}
