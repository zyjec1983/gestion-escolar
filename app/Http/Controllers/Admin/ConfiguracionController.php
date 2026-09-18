<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
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
}