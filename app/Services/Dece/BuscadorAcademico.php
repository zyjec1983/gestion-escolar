<?php

namespace App\Services\Dece;

use App\Models\Alumno;
use App\Models\DeceAtencion;
use App\Models\DeceCita;
use App\Models\DeceTaller;
use App\Models\PeriodoLectivo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Consultas de apoyo para los formularios del DECE. Mantiene fuera de los
 * controladores la busqueda de estudiantes y la carga de citas o atenciones
 * relacionadas, de modo que todas las pantallas usen el mismo criterio.
 */
class BuscadorAcademico
{
    /**
     * Busca estudiantes por nombres, apellidos, cedula o codigo.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function alumnos(?string $termino, int $limite = 15): Collection
    {
        $termino = $this->normalizarTermino($termino);

        if ($termino === null) {
            return collect();
        }

        $like = '%'.mb_strtolower(Str::ascii($termino), 'UTF-8').'%';
        $periodo = PeriodoLectivo::where('activo', true)->first();
        $nombres = $this->columnaSinAcentos('nombres');
        $apellidos = $this->columnaSinAcentos('apellidos');

        return Alumno::query()
            ->select('id', 'nombres', 'apellidos', 'numero_cedula', 'codigo', 'representante_user_id')
            ->with([
                'representante:id,name,apellidos,numero_cedula,telefono,email',
                'matriculas' => function ($query) use ($periodo) {
                    $query->when($periodo, fn ($sub) => $sub->where('periodo_id', $periodo->id))
                        ->with(['curso:id,nombre', 'paralelo:id,nombre'])
                        ->latest('id');
                },
            ])
            ->where(function ($query) use ($nombres, $apellidos, $like) {
                $query->whereRaw("{$nombres} LIKE ?", [$like])
                    ->orWhereRaw("{$apellidos} LIKE ?", [$like])
                    ->orWhere('numero_cedula', 'like', $like)
                    ->orWhere('codigo', 'like', $like);
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit($limite)
            ->get()
            ->map(fn (Alumno $alumno) => $this->resumenAlumno($alumno));
    }

    /**
     * Citas programadas del estudiante, para asociarlas a una atencion.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function citasProgramadas(Alumno $alumno): Collection
    {
        return DeceCita::where('alumno_id', $alumno->id)
            ->where('estado', DeceCita::ESTADO_PROGRAMADA)
            ->orderBy('fecha_hora')
            ->get(['id', 'fecha_hora', 'motivo'])
            ->map(fn (DeceCita $cita) => [
                'id' => $cita->id,
                'fecha' => $cita->fecha_hora->format('d/m/Y H:i'),
                'motivo' => $cita->motivo,
            ]);
    }

    /**
     * Atenciones previas del estudiante, para asociarlas a un acuerdo.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function atenciones(Alumno $alumno): Collection
    {
        return DeceAtencion::where('alumno_id', $alumno->id)
            ->latest('fecha_atencion')
            ->limit(50)
            ->get(['id', 'fecha_atencion', 'motivo', 'estado'])
            ->map(fn (DeceAtencion $atencion) => [
                'id' => $atencion->id,
                'fecha' => $atencion->fecha_atencion->format('d/m/Y'),
                'motivo' => $atencion->motivo,
                'estado' => $atencion->estadoLabel(),
            ]);
    }

    /**
     * Talleres que coinciden por titulo, facilitador o lugar. Alimenta el
     * autocompletado del listado sin cargar todo el catalogo en la vista.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function talleres(?string $termino, int $limite = 15): Collection
    {
        $termino = $this->normalizarTermino($termino);

        if ($termino === null) {
            return collect();
        }

        $like = '%'.mb_strtolower(Str::ascii($termino), 'UTF-8').'%';
        $titulo = $this->columnaSinAcentos('titulo');
        $facilitador = $this->columnaSinAcentos('facilitador');
        $lugar = $this->columnaSinAcentos('lugar');

        return DeceTaller::query()
            ->where(function ($query) use ($titulo, $facilitador, $lugar, $like) {
                $query->whereRaw("{$titulo} LIKE ?", [$like])
                    ->orWhereRaw("{$facilitador} LIKE ?", [$like])
                    ->orWhereRaw("{$lugar} LIKE ?", [$like]);
            })
            ->orderByDesc('fecha')
            ->limit($limite)
            ->get(['id', 'titulo', 'facilitador', 'lugar', 'fecha'])
            ->map(fn (DeceTaller $taller) => [
                'id' => $taller->id,
                'nombre' => $taller->titulo,
                'detalle' => collect([
                    $taller->facilitador,
                    $taller->lugar,
                    $taller->fecha?->format('d/m/Y'),
                ])->filter()->implode(' · '),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resumenAlumno(Alumno $alumno): array
    {
        $matricula = $alumno->matriculas->first();
        $representante = $alumno->representante;

        return [
            'id' => $alumno->id,
            'nombre' => $alumno->nombreCompleto(),
            'cedula' => $alumno->numero_cedula ?: $alumno->codigo,
            'curso' => trim(($matricula?->curso?->nombre ?? '').' '.($matricula?->paralelo?->nombre ?? '')),
            'representante' => $representante ? [
                'id' => $representante->id,
                'nombre' => $representante->nombreCompleto(),
                'cedula' => $representante->numero_cedula,
                'telefono' => $representante->telefono,
                'email' => $representante->email,
            ] : null,
        ];
    }

    private function normalizarTermino(?string $termino): ?string
    {
        $termino = trim(str_replace(['%', '_', '\\'], ' ', (string) $termino));

        return mb_strlen($termino) >= 2 ? $termino : null;
    }

    /**
     * Expresion SQL portable (SQLite y MySQL) que reduce una columna a
     * minusculas sin acentos, igual que el buscador de traslados.
     */
    private function columnaSinAcentos(string $columna): string
    {
        $reemplazos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ];

        $expresion = $columna;
        foreach ($reemplazos as $acento => $plano) {
            $expresion = "REPLACE({$expresion}, '{$acento}', '{$plano}')";
        }

        return "LOWER({$expresion})";
    }
}
