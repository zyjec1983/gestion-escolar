<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrantes de la Comunidad Educativa.
 *
 * Qué hace: guarda cargo, foto, nombres y responsabilidad de cada persona.
 * El campo `area` agrupa las tarjetas (Rectorado, DECE, Matemáticas, Inglés,
 * etc.) para que el administrador pueda agregar tantas filas como necesite.
 * Conecta con: App\Models\ComunidadMiembro y Admin\ComunidadController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunidad_miembros', function (Blueprint $table) {
            $table->id();
            $table->string('cargo');
            $table->string('nombre');
            $table->text('responsabilidad')->nullable();
            $table->string('area')->default('Equipo directivo');
            $table->string('foto_path')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['area', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunidad_miembros');
    }
};
