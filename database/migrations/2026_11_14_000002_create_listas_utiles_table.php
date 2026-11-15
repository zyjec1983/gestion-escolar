<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listas de útiles escolares por nivel o curso.
 *
 * Qué hace: crea la tabla `listas_utiles` para agrupar los artículos de cada
 * nivel (por ejemplo "Octavo" o "1ro Ciencias"), agrega la relación
 * `utiles_items.lista_id` y la columna `materia`. Los artículos existentes se
 * agrupan automáticamente en una lista por nivel para que sigan visibles.
 * Conecta con: App\Models\ListaUtil, App\Models\UtilItem,
 * Admin\SitioController y PortalController@utiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listas_utiles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->boolean('finalizada')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::table('utiles_items', function (Blueprint $table) {
            $table->unsignedBigInteger('lista_id')->nullable()->after('id')->index();
            $table->string('materia', 120)->nullable()->after('area');
        });

        // Conserva los artículos ya publicados dentro de una lista por nivel.
        $niveles = [
            'inicial' => 'Educación Inicial',
            'elemental' => 'Básica Elemental',
            'media' => 'Básica Media',
            'superior' => 'Básica Superior',
            'bachillerato' => 'Bachillerato (BGU)',
        ];

        $nivelesExistentes = DB::table('utiles_items')
            ->select('nivel')
            ->distinct()
            ->orderBy('nivel')
            ->pluck('nivel');

        foreach ($nivelesExistentes as $indice => $nivel) {
            $listaId = DB::table('listas_utiles')->insertGetId([
                'nombre' => $niveles[$nivel] ?? ucfirst((string) $nivel),
                'finalizada' => true,
                'orden' => $indice,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('utiles_items')
                ->where('nivel', $nivel)
                ->update([
                    'lista_id' => $listaId,
                    'materia' => DB::raw('area'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('utiles_items', function (Blueprint $table) {
            $table->dropIndex(['lista_id']);
            $table->dropColumn(['lista_id', 'materia']);
        });

        Schema::dropIfExists('listas_utiles');
    }
};
