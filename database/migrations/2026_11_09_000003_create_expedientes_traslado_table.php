<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes_traslado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->foreignId('paralelo_id')->nullable()->constrained('paralelos')->nullOnDelete();

            $table->string('tipo', 20);
            $table->string('momento', 20)->nullable();
            $table->date('fecha_traslado');
            $table->string('institucion', 255);
            $table->string('anio_lectivo', 20);
            $table->text('observaciones')->nullable();

            $table->string('estado', 30)->default('por_regularizar');
            $table->boolean('documentos_completos')->default(false);
            $table->boolean('paz_y_salvo_financiero')->default(false);
            $table->boolean('paz_y_salvo_biblioteca')->default(false);
            $table->text('paz_y_salvo_observacion')->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_at')->nullable();
            $table->timestamp('cerrado_at')->nullable();
            $table->timestamps();

            $table->index(['alumno_id', 'estado']);
            $table->index(['tipo', 'estado']);
            $table->index('fecha_traslado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes_traslado');
    }
};
