<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    public function edit($id)
    {
        $matricula = Matricula::with('alumno.representante', 'curso', 'paralelo', 'periodo')->findOrFail($id);
        $cursos = Curso::orderBy('nombre')->get();
        $paralelos = Paralelo::orderBy('nombre')->get();
        $periodos = PeriodoLectivo::orderByDesc('id')->get();

        return view('admin.matriculas.edit', compact('matricula', 'cursos', 'paralelos', 'periodos'));
    }

    public function update(Request $request, $id)
    {
        $matricula = Matricula::with('alumno')->findOrFail($id);

        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'numero_cedula' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:masculino,femenino',
            'tipo_sangre' => 'nullable|string|max:5',
            'etnia' => 'nullable|string|max:50',
            'nacionalidad' => 'nullable|string|max:50',
            'telefono_convencional' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email_activo' => 'nullable|email|max:255',
            'numero_hermanos' => 'nullable|integer|min:0|max:99',
            'lugar_que_ocupa_entre_hermanos' => 'nullable|integer|min:0|max:99',
            'con_quien_vive' => 'nullable|string|max:100',
            'parentesco_convivencia' => 'nullable|string|max:100',
            'institucion_procedencia' => 'nullable|string|max:255',
            'observaciones_generales' => 'nullable|string',

            'padece_enfermedad' => 'boolean',
            'nombre_enfermedad' => 'nullable|string|max:255',
            'tiene_tratamiento_medico' => 'boolean',
            'toma_medicina' => 'boolean',
            'nombre_medicina' => 'nullable|string|max:255',
            'necesidades_educativas_especificas_nee' => 'boolean',
            'nombre_nee' => 'nullable|string|max:255',
            'tiene_discapacidad' => 'boolean',
            'tipo_discapacidad' => 'nullable|string|max:255',
            'tiene_carnet' => 'boolean',
            'numero_carnet' => 'nullable|string|max:30',
            'tiene_informe_psicopedagogico_actualizado' => 'boolean',

            'direccion_domiciliaria' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:100',
            'referencia_domicilio' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:50',
            'canton' => 'nullable|string|max:50',
            'parroquia' => 'nullable|string|max:50',
            'numero_casa' => 'nullable|string|max:20',
            'casa_propia' => 'boolean',
            'tiene_agua' => 'boolean',
            'tiene_sshh_letrina' => 'boolean',
            'tiene_luz_electrica' => 'boolean',

            'padre_nombres_apellidos' => 'nullable|string|max:255',
            'padre_cedula' => 'nullable|string|max:20',
            'padre_telefono' => 'nullable|string|max:20',
            'padre_profesion' => 'nullable|string|max:100',
            'padre_direccion' => 'nullable|string|max:255',
            'padre_email' => 'nullable|email|max:255',
            'padre_es_facturador' => 'boolean',

            'madre_nombres_apellidos' => 'nullable|string|max:255',
            'madre_cedula' => 'nullable|string|max:20',
            'madre_telefono' => 'nullable|string|max:20',
            'madre_profesion' => 'nullable|string|max:100',
            'madre_direccion' => 'nullable|string|max:255',
            'madre_email' => 'nullable|email|max:255',
            'madre_es_facturador' => 'boolean',

            'representante_alterno_nombre' => 'nullable|string|max:255',
            'representante_alterno_parentesco' => 'nullable|string|max:50',
            'representante_alterno_parentesco_otro' => 'nullable|string|max:50',
            'representante_alterno_cedula' => 'nullable|string|max:20',
            'representante_alterno_telefono' => 'nullable|string|max:20',
            'representante_alterno_email' => 'nullable|email|max:255',
            'representante_alterno_direccion' => 'nullable|string|max:255',

            'facturador_nombre' => 'nullable|string|max:255',
            'facturador_cedula' => 'nullable|string|max:20',
            'facturador_telefono' => 'nullable|string|max:20',
            'facturador_direccion' => 'nullable|string|max:255',
            'facturador_email' => 'nullable|email|max:255',

            'codigo_matricula' => 'nullable|string|max:20',
            'curso_id' => 'required|exists:cursos,id',
            'paralelo_id' => 'required|exists:paralelos,id',
            'periodo_id' => 'required|exists:periodos_lectivos,id',
            'especializacion' => 'nullable|string|max:100',
            'fecha_matricula' => 'nullable|date',
            'firma_rectora_completada' => 'boolean',
            'firma_secretaria_completada' => 'boolean',
            'firma_representante_completada' => 'boolean',
        ]);

        if (empty($validated['representante_alterno_parentesco']) && !empty($validated['representante_alterno_parentesco_otro'])) {
            $validated['representante_alterno_parentesco'] = $validated['representante_alterno_parentesco_otro'];
        }

        $alumnoData = collect($validated)->only([
            'nombres', 'apellidos', 'numero_cedula', 'fecha_nacimiento', 'genero',
            'tipo_sangre', 'etnia', 'nacionalidad',
            'telefono_convencional', 'celular', 'email_activo',
            'numero_hermanos', 'lugar_que_ocupa_entre_hermanos', 'con_quien_vive', 'parentesco_convivencia',
            'institucion_procedencia', 'observaciones_generales',
            'padece_enfermedad', 'nombre_enfermedad', 'tiene_tratamiento_medico', 'toma_medicina', 'nombre_medicina',
            'necesidades_educativas_especificas_nee', 'nombre_nee',
            'tiene_discapacidad', 'tipo_discapacidad', 'tiene_carnet', 'numero_carnet', 'tiene_informe_psicopedagogico_actualizado',
            'direccion_domiciliaria', 'sector', 'referencia_domicilio', 'provincia', 'canton', 'parroquia', 'numero_casa',
            'casa_propia', 'tiene_agua', 'tiene_sshh_letrina', 'tiene_luz_electrica',
            'padre_nombres_apellidos', 'padre_cedula', 'padre_telefono', 'padre_profesion', 'padre_direccion', 'padre_email', 'padre_es_facturador',
            'madre_nombres_apellidos', 'madre_cedula', 'madre_telefono', 'madre_profesion', 'madre_direccion', 'madre_email', 'madre_es_facturador',
            'representante_alterno_nombre', 'representante_alterno_parentesco', 'representante_alterno_cedula',
            'representante_alterno_telefono', 'representante_alterno_email', 'representante_alterno_direccion',
            'facturador_nombre', 'facturador_cedula', 'facturador_telefono', 'facturador_direccion', 'facturador_email',
        ])->toArray();

        $matriculaData = collect($validated)->only([
            'codigo_matricula', 'curso_id', 'paralelo_id', 'periodo_id', 'especializacion',
            'firma_rectora_completada', 'firma_secretaria_completada', 'firma_representante_completada',
        ])->toArray();

        $matricula->alumno->update($alumnoData);
        $matricula->update($matriculaData);

        return redirect()->route('admin.matriculas.edit', $matricula->id)
            ->with('success', 'Ficha de matrícula actualizada correctamente.');
    }
}
