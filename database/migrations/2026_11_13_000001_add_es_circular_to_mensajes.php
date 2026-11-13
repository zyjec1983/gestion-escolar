<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca los mensajes enviados como circular.
 *
 * Qué hace: agrega `es_circular` a `mensajes` para saber si, al momento del
 * envío, correspondía avisar por correo y SMS al contacto registrado.
 * Conecta con: App\Models\Mensaje y App\Http\Controllers\MensajeController.
 * Ruta: POST /mensajes (mensajes.store).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->boolean('es_circular')->default(false)->after('leido');
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropColumn('es_circular');
        });
    }
};
