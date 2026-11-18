<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de contraseña temporal.
 *
 * Regla institucional:
 * - Toda cuenta creada por Administración, por generación masiva o por
 *   aprobación de pre-registro nace con `debe_cambiar_password = true`.
 * - El middleware EnsurePasswordIsChanged obliga a cambiarla antes de usar
 *   cualquier módulo.
 * - Las cuentas de los seeders de desarrollo quedan en false para no romper
 *   las pruebas manuales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('debe_cambiar_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('debe_cambiar_password');
        });
    }
};
