<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('asunto');
            $table->text('cuerpo_contenido')->nullable();
            $table->string('carpeta')->default('no_leidos');
            $table->unsignedBigInteger('emisor_id');
            $table->string('emisor_type');
            $table->foreignId('destinatario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contexto_estudiante_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $table->boolean('leido')->default(false);
            $table->dateTime('fecha_envio')->nullable();
            $table->timestamps();

            $table->index(['carpeta', 'destinatario_id']);
            $table->index(['emisor_id', 'emisor_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
