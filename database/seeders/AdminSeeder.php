<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Antes creaba secretaria@ y rector@ ambos como ADMIN.
 *
 * Qué hace ahora: delega en UsuariosPruebaSeeder, que crea una cuenta por rol
 * y mantiene separado el administrador del sistema del rectorado y del
 * vicerrectorado.
 *
 * Conecta con: UsuariosPruebaSeeder y DatabaseSeeder.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UsuariosPruebaSeeder::class);
    }
}
