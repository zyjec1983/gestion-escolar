<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Área de conocimiento y estado de la materia.
 *
 * `activo` permite dejar de ofertar una materia sin borrarla: las notas y
 * clases históricas se conservan y solo se impide volver a asignarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->string('area')->nullable()->after('nombre');
            $table->boolean('activo')->default(true)->after('color_tarjeta');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropColumn(['area', 'activo']);
        });
    }
};
