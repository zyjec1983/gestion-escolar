<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->string('tipo_nota', 20)->default('parcial')->after('parcial');
            $table->decimal('nota_examen', 7, 4)->nullable()->after('evaluacion_parcial');
        });
    }

    public function down(): void
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn(['tipo_nota', 'nota_examen']);
        });
    }
};