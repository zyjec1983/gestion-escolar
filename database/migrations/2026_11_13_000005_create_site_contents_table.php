<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenidos editables del portal institucional.
 *
 * Qué hace: guarda en formato clave/valor todos los textos e imágenes que el
 * administrador edita en "Sitio web". Cada registro indica su grupo (inicio,
 * historia, comunidad, útiles, descargas, admisiones) y el tipo de campo.
 * Conecta con: App\Models\SiteContent, Admin\SitioController y welcome.blade.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->longText('valor')->nullable();
            $table->string('tipo', 20)->default('texto');
            $table->string('grupo', 40)->default('sitio');
            $table->string('etiqueta');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('grupo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
