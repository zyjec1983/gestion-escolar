<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de útiles escolares por nivel educativo.
 *
 * Qué hace: cada fila es un material con su especificación, cantidad y área.
 * El nivel se usa para los botones de filtro del portal (inicial, elemental,
 * media, superior y bachillerato).
 * Conecta con: App\Models\UtilItem y Admin\SitioController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utiles_items', function (Blueprint $table) {
            $table->id();
            $table->string('nivel', 20);
            $table->string('nombre');
            $table->string('especificacion')->nullable();
            $table->string('cantidad', 60)->nullable();
            $table->string('area', 80)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['nivel', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utiles_items');
    }
};
