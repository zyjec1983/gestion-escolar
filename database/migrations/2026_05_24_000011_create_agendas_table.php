<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_docente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tipo_actividad_id')->constrained('tipo_actividades')->cascadeOnDelete();
            $table->foreignId('insumo_evaluacion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('titulo_agenda');
            $table->text('descripcion_instrucciones_html')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_entrega_limite')->nullable();
            $table->boolean('enviar_a_todos')->default(true);
            $table->timestamps();

            $table->index(['clase_docente_id', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
