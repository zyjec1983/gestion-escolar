<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->foreignId('clase_docente_id')->nullable()->after('id')->constrained('clases_docentes')->nullOnDelete();
            $table->text('descripcion')->nullable()->after('titulo');
            $table->string('quimestre', 10)->nullable()->after('fecha_fin');
            $table->string('parcial', 10)->nullable()->after('quimestre');
            $table->foreignId('periodo_lectivo_id')->nullable()->after('parcial')->constrained('periodos_lectivos')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->after('periodo_lectivo_id')->constrained('users')->nullOnDelete();
            $table->string('grupo_id', 50)->nullable()->after('creado_por');

            $table->index(['materia_id', 'clase_docente_id']);
        });
    }

    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropForeign(['clase_docente_id']);
            $table->dropForeign(['periodo_lectivo_id']);
            $table->dropForeign(['creado_por']);
            $table->dropColumn(['clase_docente_id', 'descripcion', 'quimestre', 'parcial', 'periodo_lectivo_id', 'creado_por', 'grupo_id']);
        });
    }
};