<?php

namespace Database\Seeders;

use App\Models\Institucion;
use Illuminate\Database\Seeder;

class InstitucionSeeder extends Seeder
{
    public function run(): void
    {
        Institucion::create([
            'nombre' => 'Unidad Educativa',
            'subtitulo' => 'Institución Educativa',
            'plataforma_proveedor' => 'Comunidad',
            'pre_registro_activo' => true,
            'carousel_interval' => 2000,
        ]);
    }
}
