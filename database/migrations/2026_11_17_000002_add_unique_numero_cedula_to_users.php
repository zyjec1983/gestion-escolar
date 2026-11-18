<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La cédula identifica a una persona también en el login.
 *
 * Qué hace: reemplaza el índice simple de `users.numero_cedula` por uno único.
 * Así el login nunca resuelve dos cuentas distintas con la misma cédula y la
 * base de datos respalda la validación que ya hacen Administración, Secretaría
 * y Pre-registro. En SQLite y MySQL los valores NULL siguen permitidos.
 *
 * Antes de aplicar esta migración se verificó que la base real no tuviera
 * cédulas duplicadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['numero_cedula']);
            $table->unique('numero_cedula');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['numero_cedula']);
            $table->index('numero_cedula');
        });
    }
};
