<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_notas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_permiso', 30);
            $table->foreignId('clase_docente_id')->constrained('clases_docentes')->cascadeOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->foreignId('user_asigno_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['clase_docente_id', 'fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_notas');
    }
};
