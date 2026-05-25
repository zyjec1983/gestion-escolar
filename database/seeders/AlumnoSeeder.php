<?php

namespace Database\Seeders;

use App\Models\Alumno;
use Illuminate\Database\Seeder;

class AlumnoSeeder extends Seeder
{
    public function run(): void
    {
        Alumno::create([
            'codigo' => '2025000016',
            'nombres' => 'DANIELA MARIANA',
            'apellidos' => 'ALVAREZ VALENZUELA',
            'genero' => 'femenino',
            'representante_user_id' => 3,
        ]);

        Alumno::create([
            'codigo' => '2025000291',
            'nombres' => 'VICTORIA ANGELICA',
            'apellidos' => 'ANDRADE RODRIGUEZ',
            'genero' => 'femenino',
            'representante_user_id' => 2,
        ]);
    }
}
