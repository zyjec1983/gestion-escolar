<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Desfase horario del centro. El administrador lo elige en Configuración y
     * rige todas las fechas que registra la aplicación, para que una
     * instalación en otro país solo tenga que seleccionarlo.
     */
    public function up(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->string('zona_horaria', 16)->default('+00:00')->after('sistema_evaluacion');
        });
    }

    public function down(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->dropColumn('zona_horaria');
        });
    }
};