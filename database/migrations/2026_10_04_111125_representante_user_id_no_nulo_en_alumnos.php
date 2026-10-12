<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Por regla ningun alumno puede quedar sin representante. Antes la columna
     * admitia NULL y la FK era nullOnDelete, asi que borrar la cuenta de un
     * representante dejaba huerfanos a todos sus hijos sin avisar. Ahora la
     * columna es NOT NULL y la FK bloquea el borrado.
     */
    public function up(): void
    {
        $huerfanos = DB::table('alumnos')->whereNull('representante_user_id')->count();

        if ($huerfanos > 0) {
            throw new RuntimeException(
                "No se puede aplicar: hay {$huerfanos} alumno(s) sin representante. "
                . 'Asignales un representante antes de correr esta migracion.'
            );
        }

        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropForeign(['representante_user_id']);
            $table->foreign('representante_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
            $table->foreignId('representante_user_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropForeign(['representante_user_id']);
            $table->foreign('representante_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
            $table->foreignId('representante_user_id')->nullable(true)->change();
        });
    }
};