<?php

namespace App\Services;

use App\Models\Nota;

class CalculoNotas
{
    public function notaParcialDe(?Nota $n): ?float
    {
        if (!$n) return null;

        $fii = (float) $n->fii;
        $ffi = (float) $n->ffi;
        $fig = (float) $n->fig;
        $ffg = (float) $n->ffg;
        $pi = (float) $n->proyecto_integrador;
        $ep = (float) $n->evaluacion_parcial;

        if ($fii + $ffi + $fig + $ffg === 0 && $pi + $ep === 0) {
            return null;
        }

        return ($fii + $ffi + $fig + $ffg) / 40 * 7 + ($pi + $ep) / 20 * 3;
    }

    public function resumen($alumnos, $notasClase, string $sistema): array
    {
        $resumen = [];

        foreach ($alumnos as $alumno) {
            $notas = $notasClase[$alumno->id] ?? collect();

            if ($sistema === 'trimestre') {
                $t1 = $this->notaParcialDe($notas->firstWhere('parcial', 1));
                $t2 = $this->notaParcialDe($notas->firstWhere('parcial', 2));
                $t3 = $this->notaParcialDe($notas->firstWhere('parcial', 3));
                $anio = ($t1 !== null && $t2 !== null && $t3 !== null) ? ($t1 + $t2 + $t3) / 3 : null;

                $resumen[] = [
                    'alumno' => $alumno,
                    't1' => $t1, 't2' => $t2, 't3' => $t3, 'anio' => $anio,
                ];
                continue;
            }

            $p1 = $this->notaParcialDe($notas->firstWhere('parcial', 1));
            $p2 = $this->notaParcialDe($notas->firstWhere('parcial', 2));
            $p3 = $this->notaParcialDe($notas->firstWhere('parcial', 4));
            $p4 = $this->notaParcialDe($notas->firstWhere('parcial', 5));

            $te1 = $notas->firstWhere('parcial', 3);
            $te2 = $notas->firstWhere('parcial', 6);
            $te1 = $te1?->nota_examen !== null ? (float) $te1->nota_examen : null;
            $te2 = $te2?->nota_examen !== null ? (float) $te2->nota_examen : null;

            $q1 = ($p1 !== null && $p2 !== null && $te1 !== null) ? (($p1 + $p2) / 2) * 0.7 + $te1 * 0.3 : null;
            $q2 = ($p3 !== null && $p4 !== null && $te2 !== null) ? (($p3 + $p4) / 2) * 0.7 + $te2 * 0.3 : null;
            $anio = ($q1 !== null && $q2 !== null) ? ($q1 + $q2) / 2 : null;

            $resumen[] = [
                'alumno' => $alumno,
                'p1' => $p1, 'p2' => $p2, 'te1' => $te1, 'q1' => $q1,
                'p3' => $p3, 'p4' => $p4, 'te2' => $te2, 'q2' => $q2, 'anio' => $anio,
            ];
        }

        return $resumen;
    }
}