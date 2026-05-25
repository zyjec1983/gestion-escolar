<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClaseCollection;
use App\Http\Resources\ClaseResource;
use App\Http\Resources\InicioClaseResource;
use App\Models\ClaseDocente;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? \App\Models\User::find(1);

        $clases = ClaseDocente::with(['curso', 'paralelo', 'materia'])
            ->where('user_id', $user?->id)
            ->get();

        return new ClaseCollection($clases);
    }

    public function show($id)
    {
        $clase = ClaseDocente::with([
            'curso', 'paralelo', 'materia.unidades',
        ])->findOrFail($id);

        return new InicioClaseResource($clase);
    }
}
