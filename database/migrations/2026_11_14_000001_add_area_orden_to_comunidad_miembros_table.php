<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de los grupos de la Comunidad Educativa.
 *
 * Qué hace: agrega `area_orden` para que el administrador pueda arrastrar los
 * bloques de área en /admin/comunidad y conservar ese orden en el portal. Las
 * áreas existentes se numeran alfabéticamente para no cambiar lo ya visible.
 * Conecta con: App\Models\ComunidadMiembro, Admin\ComunidadController y
 * PortalController@comunidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comunidad_miembros', function (Blueprint $table) {
            $table->unsignedSmallInteger('area_orden')->default(0)->after('area');
        });

        $areas = DB::table('comunidad_miembros')
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        foreach ($areas as $indice => $area) {
            DB::table('comunidad_miembros')
                ->where('area', $area)
                ->update(['area_orden' => $indice]);
        }
    }

    public function down(): void
    {
        Schema::table('comunidad_miembros', function (Blueprint $table) {
            $table->dropColumn('area_orden');
        });
    }
};
