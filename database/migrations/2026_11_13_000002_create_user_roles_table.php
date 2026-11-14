<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles adicionales de un usuario (multirol).
 *
 * `users.rol` sigue siendo el rol principal; esta tabla guarda los perfiles
 * extra que el administrador habilita (por ejemplo INSPECCION + DOCENTE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rol', 30);
            $table->timestamps();

            $table->unique(['user_id', 'rol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
