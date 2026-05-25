<?php

namespace Database\Seeders;

use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Database\Seeder;

class MensajeSeeder extends Seeder
{
    public function run(): void
    {
        $representante = User::where('rol', 'REPRESENTANTE')->first();

        Mensaje::create([
            'asunto' => 'Consulta sobre tarea - Noveno A',
            'cuerpo_contenido' => 'Estimado profesor, mi hijo tiene una duda sobre la tarea asignada. ¿Podría indicarme cuándo tiene disponibilidad para una reunión?',
            'carpeta' => 'no_leidos',
            'emisor_id' => $representante->id,
            'emisor_type' => User::class,
            'destinatario_id' => 1,
            'contexto_estudiante_id' => 2,
            'leido' => false,
            'fecha_envio' => '2026-05-23 14:30:00',
        ]);
    }
}
