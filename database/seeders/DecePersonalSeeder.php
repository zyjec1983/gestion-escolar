<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cuenta del personal de consejería (psicólogas del DECE).
 * No pertenece al rectorado: su rol es DECE y solo accede al módulo DECE.
 */
class DecePersonalSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dece@fake.edu.ec'],
            [
                'name' => 'PSICOLOGA DECE',
                'apellidos' => 'CONSEJERIA ESTUDIANTIL',
                'password' => bcrypt('password'),
                'rol' => 'DECE',
                'institucion_id' => 1,
            ]
        );
    }
}
