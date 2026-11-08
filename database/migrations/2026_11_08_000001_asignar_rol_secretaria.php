<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El usuario de secretaria se sembraba con el rol ADMIN, por lo que recibia el
 * panel de Rectorado. Esta migracion corrige las bases que ya estaban creadas
 * sin necesidad de volver a ejecutar los seeders.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'secretaria@fake.edu.ec')
            ->where('rol', 'ADMIN')
            ->update(['rol' => 'SECRETARIA']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'secretaria@fake.edu.ec')
            ->where('rol', 'SECRETARIA')
            ->update(['rol' => 'ADMIN']);
    }
};
