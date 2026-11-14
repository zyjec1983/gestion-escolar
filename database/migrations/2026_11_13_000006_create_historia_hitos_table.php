<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hitos de la línea de tiempo de "Nuestra historia".
 *
 * Qué hace: permite agregar, ordenar y quitar los años, títulos y
 * descripciones que se muestran en la sección histórica del portal.
 * Conecta con: App\Models\HistoriaHito y Admin\SitioController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historia_hitos', function (Blueprint $table) {
            $table->id();
            $table->string('anio', 40);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historia_hitos');
    }
};
