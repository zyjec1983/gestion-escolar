<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->foreignId('unidad_id')->nullable()->after('insumo_evaluacion_id')->constrained('unidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidad_id');
        });
    }
};