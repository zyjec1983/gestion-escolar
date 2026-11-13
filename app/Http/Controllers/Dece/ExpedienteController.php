<?php

namespace App\Http\Controllers\Dece;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dece\Concerns\AutorizaDece;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\DeceAuditoria;
use App\Services\Dece\RegistrarAuditoria;
use App\Services\Dece\ResumenExpediente;
use Illuminate\Http\Request;

/**
 * Buscador y vista integral del expediente estudiantil. Las autoridades ven el
 * resumen academico y disciplinario, pero no las relatorias confidenciales.
 */
class ExpedienteController extends Controller
{
    use AutorizaDece;

    public function __construct(
        private readonly ResumenExpediente $resumen,
        private readonly RegistrarAuditoria $auditoria
    ) {}

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
        ]);

        $alumnos = Alumno::query()
            ->with(['representante', 'matriculas.curso', 'matriculas.paralelo'])
            ->when($filtros['buscar'] ?? null, function ($query, string $buscar) {
                $query->where(function ($sub) use ($buscar) {
                    $sub->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('numero_cedula', 'like', "%{$buscar}%")
                        ->orWhere('codigo', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['curso_id'] ?? null, function ($query, int $cursoId) {
                $query->whereHas('matriculas', fn ($matricula) => $matricula->where('curso_id', $cursoId));
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();

        $cursos = Curso::enOrdenEscolar()->get(['id', 'nombre']);

        return view('dece.expedientes.index', compact('alumnos', 'cursos', 'filtros'));
    }

    public function show(Request $request, Alumno $alumno)
    {
        $datos = $this->resumen->para($alumno);

        $this->auditoria->registrar(
            $request,
            $alumno,
            DeceAuditoria::ACCION_CONSULTA,
            'Consulta del expediente integral del estudiante.',
            null,
            null,
            $alumno->id
        );

        $puedeVerConfidencial = $this->puedeVerConfidencial($request);

        return view('dece.expedientes.show', array_merge($datos, compact('puedeVerConfidencial')));
    }
}
