<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Control de intentos fallidos de inicio de sesión.
     *
     * Regla institucional:
     * - Al tercer intento fallido la cuenta se bloquea 1 hora.
     * - Si vuelve a fallar tres veces después de ese bloqueo, la cuenta queda
     *   bloqueada hasta que Administración la desbloquee.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('intentos_fallidos')->default(0)->after('password');
            $table->timestamp('bloqueado_hasta')->nullable()->after('intentos_fallidos');
            $table->unsignedTinyInteger('bloqueos_consecutivos')->default(0)->after('bloqueado_hasta');
            $table->boolean('requiere_desbloqueo')->default(false)->after('bloqueos_consecutivos');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'intentos_fallidos',
                'bloqueado_hasta',
                'bloqueos_consecutivos',
                'requiere_desbloqueo',
            ]);
        });
    }
};
