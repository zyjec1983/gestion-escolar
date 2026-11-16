<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un curso bloqueado deja de ofertarse: no se agregan materias a su malla ni
 * se asignan docentes. Sus matrículas, clases y notas históricas se conservan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
