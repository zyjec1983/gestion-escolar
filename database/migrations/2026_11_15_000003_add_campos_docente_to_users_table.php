<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos profesionales y de carga horaria del docente.
 *
 * El distributivo calcula la carga con períodos de 40 minutos: un contrato de
 * 40 horas reloj admite 30 horas de plantel (45 períodos) y uno de 20 horas
 * admite 15 horas de plantel (22,5 períodos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('titulo')->nullable()->after('apellidos');
            $table->unsignedTinyInteger('horas_contrato')->default(40)->after('titulo');
            $table->unsignedTinyInteger('periodos_atencion_padres')->default(0)->after('horas_contrato');
            $table->unsignedTinyInteger('periodos_reunion_area')->default(0)->after('periodos_atencion_padres');
            $table->unsignedTinyInteger('periodos_tutoria')->default(0)->after('periodos_reunion_area');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'titulo',
                'horas_contrato',
                'periodos_atencion_padres',
                'periodos_reunion_area',
                'periodos_tutoria',
            ]);
        });
    }
};
