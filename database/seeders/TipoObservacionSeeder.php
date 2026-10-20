<?php

namespace Database\Seeders;

use App\Models\TipoObservacion;
use Illuminate\Database\Seeder;

class TipoObservacionSeeder extends Seeder
{
    public function run(): void
    {
        // Se usa updateOrCreate para que re-ejecutar el seeder no duplique
        // ni rompa los tipos que ya estan referenciados por observaciones.
        $tipos = [
            [
                'codigo' => 'OBS_ACAD_POS',
                'nombre' => 'OBSERVACION ACADEMICA POSITIVA',
                'icono' => 'star-yellow',
                'clasificacion' => 'positiva',
                'articulo_ley' => 'Codigo de Convivencia Institucional',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_ACAD_NEG',
                'nombre' => 'OBSERVACION ACADEMICA POR MEJORAR',
                'icono' => 'smile-green',
                'clasificacion' => 'leve',
                'articulo_ley' => 'Reglamento General a la LOEI - Seguimiento academico',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_CONDUCTA',
                'nombre' => 'OBSERVACION DE CONDUCTA',
                'icono' => 'warning-red',
                'clasificacion' => 'grave',
                'articulo_ley' => 'Reglamento General a la LOEI - Convivencia escolar',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_DISC_POS',
                'nombre' => 'DISCIPLINARIA POSITIVA',
                'icono' => 'star-yellow',
                'clasificacion' => 'positiva',
                'articulo_ley' => 'Codigo de Convivencia Institucional',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_DISC_MEJ',
                'nombre' => 'DISCIPLINARIA POR MEJORAR',
                'icono' => 'smile-green',
                'clasificacion' => 'leve',
                'articulo_ley' => 'Reglamento General a la LOEI',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_UNIFORME',
                'nombre' => 'USO INCORRECTO DEL UNIFORME',
                'icono' => 'person-workspace',
                'clasificacion' => 'leve',
                'articulo_ley' => 'Art. 330 del Reglamento General a la LOEI (Faltas leves)',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_DISPOSITIVOS',
                'nombre' => 'USO DE DISPOSITIVOS ELECTRONICOS',
                'icono' => 'phone',
                'clasificacion' => 'leve',
                'articulo_ley' => 'Art. 330 del Reglamento General a la LOEI (Faltas leves, Num. 1)',
                'activo' => true,
            ],
            [
                'codigo' => 'OBS_ATRASO',
                'nombre' => 'ATRASO DE HORA DE INGRESO',
                'icono' => 'clock-history',
                'clasificacion' => 'leve',
                'articulo_ley' => 'Art. 330 del Reglamento General a la LOEI (Faltas leves)',
                'activo' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoObservacion::updateOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }
    }
}
