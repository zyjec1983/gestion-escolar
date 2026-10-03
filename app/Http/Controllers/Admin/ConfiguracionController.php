<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return view('admin.configuracion.index', [
            'institucion' => Institucion::firstOrFail(),
            'desfases' => desfasesHorariosDisponibles(),
        ]);
    }

    public function updateSistemaEvaluacion(Request $request)
    {
        $validated = $request->validate([
            'sistema_evaluacion' => 'required|in:quimestre,trimestre',
        ]);

        $institucion = Institucion::firstOrFail();
        $institucion->update($validated);

        $label = $validated['sistema_evaluacion'] === 'quimestre' ? 'Quimestre' : 'Trimestre';

        return back()->with('success', "Sistema de evaluación actualizado a {$label}.");
    }

    /**
     * Cambia el desfase horario del centro. Aplica a todas las fechas que
     * registra la aplicación, incluidas las horas de envío de los mensajes.
     */
    public function updateZonaHoraria(Request $request)
    {
        $permitidos = array_map(
            fn (array $d) => valorDesfaseHorario($d['minutos']),
            desfasesHorariosDisponibles()
        );

        $validated = $request->validate([
            'zona_horaria' => ['required', 'string', Rule::in($permitidos)],
        ], [
            'zona_horaria.in' => 'El desfase seleccionado no es válido.',
        ]);

        Institucion::firstOrFail()->update($validated);

        $texto = formatearDesfase(minutosDesfaseZona($validated['zona_horaria']));

        return back()->with('success', "Zona horaria actualizada a {$texto}.");
    }
}