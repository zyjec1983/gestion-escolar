<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de docente tutor.
 *
 * Un docente tutor recibe horas de gestión (reunión de área, atención a
 * padres y tutoría) que se cuentan como horas académicas de 40 minutos y se
 * suman a su carga de plantel en el distributivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_tutor')->default(false)->after('periodos_tutoria');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('es_tutor');
        });
    }
};
