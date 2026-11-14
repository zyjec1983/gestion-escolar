<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos del Centro de Descargas.
 *
 * Qué hace: guarda los archivos oficiales por categoría (horarios,
 * cronograma, convivencia, uniformes y varios) con su descripción, fecha,
 * peso y archivo adjunto.
 * Conecta con: App\Models\DescargaDocumento y Admin\SitioController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descargas_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 30);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('fecha_texto', 80)->nullable();
            $table->string('peso', 30)->nullable();
            $table->string('archivo_path')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['categoria', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descargas_documentos');
    }
};
