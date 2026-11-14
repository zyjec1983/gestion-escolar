<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etiqueta corta del carrusel (badge sobre el título).
 *
 * Qué hace: permite que cada slide del portal muestre una insignia como
 * "Carisma Franciscano" o "Innovación pedagógica", editable desde el panel.
 * Conecta con: App\Models\HeroSlide y Admin\HeroSlideController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('etiqueta')->nullable()->after('subtitulo');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn('etiqueta');
        });
    }
};
