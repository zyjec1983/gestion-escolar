<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->string('sistema_evaluacion', 20)->default('quimestre')->after('plataforma_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('instituciones', function (Blueprint $table) {
            $table->dropColumn('sistema_evaluacion');
        });
    }
};