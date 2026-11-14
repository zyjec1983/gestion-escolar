<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logo institucional.
 *
 * Se sube desde Configuración → Institución y se usa en el login, el inicio,
 * el menú lateral y como favicon de las pestañas del navegador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('subtitulo');
        });
    }

    public function down(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
