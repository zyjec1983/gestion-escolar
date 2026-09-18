<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->decimal('fii', 7, 4)->default(0)->change();
            $table->decimal('ffi', 7, 4)->default(0)->change();
            $table->decimal('fig', 7, 4)->default(0)->change();
            $table->decimal('ffg', 7, 4)->default(0)->change();
            $table->decimal('proyecto_integrador', 7, 4)->default(0)->change();
            $table->decimal('evaluacion_parcial', 7, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->decimal('fii', 5, 2)->default(0)->change();
            $table->decimal('ffi', 5, 2)->default(0)->change();
            $table->decimal('fig', 5, 2)->default(0)->change();
            $table->decimal('ffg', 5, 2)->default(0)->change();
            $table->decimal('proyecto_integrador', 5, 2)->default(0)->change();
            $table->decimal('evaluacion_parcial', 5, 2)->default(0)->change();
        });
    }
};
