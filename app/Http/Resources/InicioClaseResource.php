<?php

namespace App\Http\Resources;

use App\Models\ClaseDocente;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InicioClaseResource extends JsonResource
{
    public static function unidadesDeClase(ClaseDocente $clase): Collection
    {
        return $clase->materia->unidades()
            ->where(fn ($q) => $q->whereNull('clase_docente_id')->orWhere('clase_docente_id', $clase->id))
            ->orderBy('fecha_inicio')
            ->get();
    }

    public static function unidadActual(ClaseDocente $clase): ?Unidad
    {
        $unidades = self::unidadesDeClase($clase);

        $hoy = Carbon::today();

        $activa = $unidades->first(fn ($u) => $u->fecha_inicio && $u->fecha_fin
            && $u->fecha_inicio->lte($hoy) && $u->fecha_fin->gte($hoy));

        if ($activa) {
            return $activa;
        }

        $proxima = $unidades->first(fn ($u) => $u->fecha_inicio && $u->fecha_inicio->gt($hoy));

        return $proxima ?? $unidades->last();
    }

    private function urlCitas(Request $request): ?string
    {
        return match ($request->user()?->perfilActivo()) {
            'DOCENTE' => route('docente.citas.index'),
            'REPRESENTANTE' => route('representante.citas'),
            default => null,
        };
    }

    public function toArray(Request $request): array
    {
        $unidad = self::unidadActual($this->resource);

        return [
            'clase_actual' => [
                'id' => $this->id,
                'curso_paralelo' => "{$this->curso->nombre} {$this->paralelo->nombre}",
                'materia' => $this->materia->nombre,
                'unidad_actual' => $unidad ? [
                    'titulo' => $unidad->titulo,
                    'fecha_inicio' => $unidad->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $unidad->fecha_fin?->format('Y-m-d'),
                ] : null,
            ],
            'menu_aula_virtual' => [
                ['label' => 'Inicio', 'icon' => 'house-door', 'url' => "/clases/{$this->id}", 'key' => 'inicio'],
                ['label' => 'Agenda', 'icon' => 'clipboard-check', 'url' => "/clases/{$this->id}/agenda", 'key' => 'agenda'],
                ['label' => 'Foros', 'icon' => 'chat-dots', 'url' => "/clases/{$this->id}/foros", 'key' => 'foros'],
                ['label' => 'Grupos de Clase', 'icon' => 'people-fill', 'url' => "/clases/{$this->id}/grupos", 'key' => 'grupos'],
                ['label' => 'Materiales', 'icon' => 'folder', 'url' => "/clases/{$this->id}/materiales", 'key' => 'materiales'],
                ['label' => 'Mensajes', 'icon' => 'envelope', 'url' => '/mensajes', 'key' => 'mensajes'],
                ['label' => 'Citas', 'icon' => 'calendar-event', 'url' => $this->urlCitas($request), 'key' => 'citas'],
            ],
            'estudiantes_inscritos' => $this->curso->matriculas()
                ->where('paralelo_id', $this->paralelo_id)
                ->with('alumno')
                ->get()
                ->sortBy(fn ($m) => mb_strtolower(trim((string) $m->alumno->apellidos).' '.trim((string) $m->alumno->nombres)))
                ->values()
                ->map(function ($matricula) {
                    return [
                        'matricula_id' => $matricula->codigo_matricula ?? "MAT-{$matricula->id}",
                        'matricula_row_id' => $matricula->id,
                        'alumno_id' => $matricula->alumno->id,
                        'apellidos_nombres' => "{$matricula->alumno->apellidos} {$matricula->alumno->nombres}",
                        'nee' => (bool) $matricula->alumno->necesidades_educativas_especificas_nee,
                        'discapacidad' => (bool) $matricula->alumno->tiene_discapacidad,
                    ];
                }),
        ];
    }
}
