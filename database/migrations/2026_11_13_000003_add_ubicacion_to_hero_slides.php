<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicación del carrusel: inicio, login o ambos.
 *
 * Permite que el administrador use imágenes distintas en la página pública
 * y en la pantalla de inicio de sesión.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('ubicacion', 20)->default('inicio')->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn('ubicacion');
        });
    }
};
