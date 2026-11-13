<?php

namespace Database\Seeders;

use App\Models\DeceDiagnostico;
use Illuminate\Database\Seeder;

/**
 * Catalogo inicial de diagnosticos frecuentes en el ambito educativo
 * ecuatoriano. Se siembra de forma idempotente para poder ejecutarlo varias
 * veces sin duplicar codigos.
 */
class DeceDiagnosticoSeeder extends Seeder
{
    public function run(): void
    {
        $diagnosticos = [
            ['F90.0', 'Trastorno por déficit de atención con hiperactividad, tipo combinado', 'Trastornos del Neurodesarrollo'],
            ['F90.1', 'Trastorno por déficit de atención con hiperactividad, tipo predominantemente hiperactivo-impulsivo', 'Trastornos del Neurodesarrollo'],
            ['F90.2', 'Trastorno por déficit de atención con hiperactividad, tipo predominantemente inatento', 'Trastornos del Neurodesarrollo'],
            ['F84.0', 'Trastorno del espectro autista', 'Trastornos del Neurodesarrollo'],
            ['F80.0', 'Trastorno específico de la pronunciación', 'Trastornos del Aprendizaje'],
            ['F80.1', 'Trastorno del lenguaje expresivo', 'Trastornos del Aprendizaje'],
            ['F80.2', 'Trastorno de la recepción del lenguaje', 'Trastornos del Aprendizaje'],
            ['F81.0', 'Trastorno específico de la lectura (dislexia)', 'Trastornos del Aprendizaje'],
            ['F81.1', 'Trastorno específico de la ortografía (disgrafía)', 'Trastornos del Aprendizaje'],
            ['F81.2', 'Trastorno específico de las habilidades aritméticas (discalculia)', 'Trastornos del Aprendizaje'],
            ['F81.3', 'Trastorno mixto de las habilidades escolares', 'Trastornos del Aprendizaje'],
            ['F70', 'Discapacidad intelectual leve', 'Discapacidad Intelectual / Sensorial'],
            ['F71', 'Discapacidad intelectual moderada', 'Discapacidad Intelectual / Sensorial'],
            ['F72', 'Discapacidad intelectual grave', 'Discapacidad Intelectual / Sensorial'],
            ['H90.3', 'Hipoacusia bilateral', 'Discapacidad Intelectual / Sensorial'],
            ['H54.0', 'Ceguera bilateral', 'Discapacidad Intelectual / Sensorial'],
            ['H54.2', 'Baja visión bilateral', 'Discapacidad Intelectual / Sensorial'],
            ['F32.0', 'Episodio depresivo leve', 'Trastornos del Estado de Ánimo'],
            ['F32.1', 'Episodio depresivo moderado', 'Trastornos del Estado de Ánimo'],
            ['F41.1', 'Trastorno de ansiedad generalizada', 'Trastornos del Estado de Ánimo'],
            ['F41.0', 'Trastorno de pánico', 'Trastornos del Estado de Ánimo'],
            ['F43.1', 'Trastorno de estrés postraumático', 'Trastornos del Estado de Ánimo'],
            ['F43.2', 'Trastorno de adaptación', 'Factores Psicosociales'],
            ['Z60.4', 'Exclusión y rechazo social (acoso escolar)', 'Factores Psicosociales'],
            ['Z61.4', 'Problemas relacionados con abuso sexual de menor', 'Factores Psicosociales'],
            ['Z62.0', 'Supervisión y control inadecuados de los padres', 'Factores Psicosociales'],
            ['Z62.6', 'Presión parental inapropiada', 'Factores Psicosociales'],
            ['Z63.0', 'Problemas en la relación conyugal de los padres', 'Factores Psicosociales'],
            ['Z63.4', 'Desaparición o fallecimiento de un familiar', 'Factores Psicosociales'],
            ['Z65.8', 'Problemas relacionados con otras circunstancias psicosociales', 'Factores Psicosociales'],
        ];

        foreach ($diagnosticos as [$codigo, $nombre, $categoria]) {
            DeceDiagnostico::updateOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'categoria' => $categoria, 'activo' => true]
            );
        }
    }
}
