<?php

namespace Database\Seeders;

use App\Models\ClaseDocente;
use App\Models\PeriodoLectivo;
use App\Models\Unidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanificacionSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = PeriodoLectivo::where('activo', true)->first();
        $clases = ClaseDocente::with(['materia', 'curso', 'paralelo'])->get();

        $combos = $clases->groupBy(fn ($c) => $c->materia_id . '-' . $c->curso_id);

        foreach ($combos as $items) {
            $primera = $items->first();
            $yaExiste = Unidad::where('materia_id', $primera->materia_id)
                ->whereNotNull('quimestre')
                ->whereHas('claseDocente', fn ($q) => $q->where('curso_id', $primera->curso_id))
                ->exists();
            if ($yaExiste) {
                continue;
            }

            $grupoId = Str::uuid()->toString();

            foreach ($items as $clase) {
                Unidad::create([
                    'materia_id' => $clase->materia_id,
                    'clase_docente_id' => $clase->id,
                    'titulo' => 'Unidad 1 - Fundamentos de ' . $clase->materia->nombre,
                    'descripcion' => 'Introducción a los conceptos clave de la asignatura, actividades de práctica y evaluación formativa.',
                    'fecha_inicio' => now()->subDays(15)->toDateString(),
                    'fecha_fin' => now()->addDays(30)->toDateString(),
                    'quimestre' => 'Q1',
                    'parcial' => 'P1',
                    'periodo_lectivo_id' => $periodo?->id,
                    'creado_por' => null,
                    'grupo_id' => $grupoId,
                ]);
            }
        }
    }
}