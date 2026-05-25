<?php

namespace Database\Seeders;

use App\Models\Observacion;
use Illuminate\Database\Seeder;

class ObservacionSeeder extends Seeder
{
    public function run(): void
    {
        Observacion::create([
            'alumno_id' => 1,
            'tipo_observacion_id' => 1,
            'user_id' => 3,
            'descripcion_texto' => 'Estimado representante. Deseo indicarle que su hijo tiene pendiente el taller 1, 2 y 3. Agradecería que lo realice para finalizar con sus calificaciones.',
            'notificar_representante' => true,
            'created_at' => '2026-05-19 10:00:00',
        ]);
    }
}
