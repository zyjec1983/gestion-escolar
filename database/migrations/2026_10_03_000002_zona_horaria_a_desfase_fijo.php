<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Antes se guardaba el nombre de una ciudad (America/Guayaquil,
     * Europe/Oslo). Ahora se guarda el desfase fijo, que es lo que el
     * administrador elige ("-05:00"), así que lo que hubiera se traduce a su
     * desfase equivalente para no perder la configuración.
     */
    public function up(): void
    {
        DB::table('instituciones')
            ->whereNotNull('zona_horaria')
            ->where('zona_horaria', 'not like', '[+-]__:__')
            ->orderBy('id')
            ->each(function ($institucion) {
                $minutos = minutosDesfaseZona($institucion->zona_horaria);
                $redondeado = (int) (round($minutos / 60) * 60);

                DB::table('instituciones')
                    ->where('id', $institucion->id)
                    ->update(['zona_horaria' => valorDesfaseHorario($redondeado)]);
            });
    }

    public function down(): void
    {
        //
    }
};