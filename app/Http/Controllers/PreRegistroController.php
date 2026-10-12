<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PreRegistroController extends Controller
{
    public function create()
    {
        $institucion = Institucion::first();
        if (!$institucion?->pre_registro_activo) {
            return view('pre-registro.cerrado');
        }

        $cursos = Curso::enOrdenEscolar()->get();
        $paralelos = Paralelo::all();
        $periodo = PeriodoLectivo::where('activo', true)->first();
        $adminMode = false;

        return view('pre-registro.form', compact('cursos', 'paralelos', 'periodo', 'adminMode'));
    }

    public function store(Request $request)
    {
        $institucion = Institucion::first();
        if (!$institucion?->pre_registro_activo) {
            return redirect()->route('pre-registro.create')
                ->with('error', 'Las inscripciones están cerradas en este momento.');
        }

        $validated = $this->validarFormulario($request);

        $periodo = PeriodoLectivo::where('activo', true)->firstOrFail();

        $preRegistro = PreRegistro::create([
            ...$validated,
            'periodo_id' => $periodo->id,
            'codigo_solicitud' => 'SOL-' . strtoupper(Str::random(8)),
            'estado' => 'pendiente',
            'tipo_solicitud' => $request->input('tipo_solicitud', 'nuevo_ingreso'),
        ]);

        $pdf = Pdf::loadView('pre-registro.pdf', compact('preRegistro'));
        $pdfPath = "public/pre-registros/solicitud_{$preRegistro->id}.pdf";
        $pdf->save(storage_path("app/{$pdfPath}"));
        $preRegistro->update(['pdf_path' => $pdfPath]);

        return $pdf->download("solicitud_{$preRegistro->codigo_solicitud}.pdf");
    }

    public function validarFormulario(Request $request): array
    {
        return $request->validate([
            'tipo_solicitud' => 'required|in:nuevo_ingreso,separacion,cambio_colegio',
            'rep_tipo_documento' => 'required|in:cedula,pasaporte',
            'rep_numero_documento' => 'required|string|max:20',
            'rep_nombres' => 'required|string|max:255',
            'rep_apellidos' => 'required|string|max:255',
            'rep_fecha_nacimiento' => [
                'required',
                'date',
                // El representante legal debe ser mayor de edad.
                Rule::date()->before(now()->subYears(18)->addDay()->startOfDay()),
            ],
            'rep_email' => 'required|email|max:255',
            'rep_telefono' => 'nullable|string|max:20',
            'alumno_tipo_documento' => 'required|in:cedula,pasaporte',
            'alumno_numero_documento' => 'required|string|max:20',
            'alumno_nombres' => 'required|string|max:255',
            'alumno_apellidos' => 'required|string|max:255',
            'alumno_numero_cedula' => 'nullable|string|max:20',
            'alumno_fecha_nacimiento' => 'nullable|date',
            'alumno_genero' => 'nullable|in:masculino,femenino',
            'alumno_tipo_sangre' => ['nullable', Rule::in(Alumno::TIPOS_SANGRE)],
            'alumno_etnia' => 'nullable|string|max:50',
            'alumno_nacionalidad' => 'nullable|string|max:50',
            'alumno_telefono_convencional' => 'nullable|string|max:20',
            'alumno_celular' => 'nullable|string|max:20',
            'alumno_email_activo' => 'nullable|email|max:255',
            'alumno_numero_hermanos' => 'nullable|integer|min:0|max:99',
            'alumno_lugar_entre_hermanos' => 'nullable|integer|min:0|max:99',
            'alumno_con_quien_vive' => 'nullable|string|max:100',
            'alumno_parentesco_convivencia' => 'nullable|string|max:100',
            'alumno_institucion_procedencia' => 'nullable|string|max:255',
            'alumno_observaciones' => 'nullable|string',

            'alumno_padece_enfermedad' => 'boolean',
            'alumno_nombre_enfermedad' => 'nullable|string|max:255',
            'alumno_tiene_tratamiento' => 'boolean',
            'alumno_toma_medicina' => 'boolean',
            'alumno_nombre_medicina' => 'nullable|string|max:255',
            'alumno_tiene_nee' => 'boolean',
            'alumno_nombre_nee' => 'nullable|string|max:255',
            'alumno_tipo_discapacidad' => 'nullable|string|max:255',
            'alumno_tiene_carnet_discapacidad' => 'boolean',
            'alumno_numero_carnet' => 'nullable|string|max:30',
            'alumno_tiene_informe_psicopedagogico' => 'boolean',

            'vivienda_sector' => 'nullable|string|max:100',
            'vivienda_referencia' => 'nullable|string|max:255',
            'vivienda_provincia' => 'nullable|string|max:50',
            'vivienda_canton' => 'nullable|string|max:50',
            'vivienda_parroquia' => 'nullable|string|max:50',
            'vivienda_numero_casa' => 'nullable|string|max:20',
            'vivienda_casa_propia' => 'boolean',
            'vivienda_tiene_agua' => 'boolean',
            'vivienda_tiene_sshh' => 'boolean',
            'vivienda_tiene_luz' => 'boolean',

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

            'facturador_nombre' => 'nullable|string|max:255',
            'facturador_cedula' => 'nullable|string|max:20',
            'facturador_telefono' => 'nullable|string|max:20',
            'facturador_direccion' => 'nullable|string|max:255',
            'facturador_email' => 'nullable|email|max:255',

            'alterno_nombre' => 'nullable|string|max:255',
            'alterno_parentesco' => 'nullable|string|max:50',
            'alterno_cedula' => 'nullable|string|max:20',
            'alterno_telefono' => 'nullable|string|max:20',
            'alterno_email' => 'nullable|email|max:255',
            'alterno_direccion' => 'nullable|string|max:255',

            'paralelo_id' => 'required|exists:paralelos,id',
            // El formulario manda el grado (lo que ve la persona) y aparte el
            // curso real, que es el que queda guardado. Los dos tienen que
            // concordar, y la especializacion sale del catalogo y solo tiene
            // sentido en los grados que la piden.
            'grado' => ['required', Rule::in(array_keys(Curso::GRADOS))],
            'curso_id' => [
                'required',
                'exists:cursos,id',
                function ($attribute, $value, $fail) use ($request) {
                    $curso = Curso::find($value);

                    if ($curso !== null && $curso->grado() !== $request->input('grado')) {
                        $fail('El curso seleccionado no corresponde al grado elegido.');
                    }
                },
            ],
            'especializacion' => [
                Rule::requiredIf(fn () => in_array($request->input('grado'), Curso::GRADOS_CON_ESPECIALIZACION, true)),
                Rule::in(Curso::ESPECIALIZACIONES),
                function ($attribute, $value, $fail) use ($request) {
                    $curso = Curso::find($request->input('curso_id'));

                    if ($value === null || $value === '' || $curso === null) {
                        return;
                    }

                    if (! $curso->pideEspecializacion()) {
                        $fail('La especializacion solo aplica a Segundo y Tercero de bachillerato.');
                    }

                    if ($curso->especializacion() !== null && $curso->especializacion() !== $value) {
                        $fail('La especializacion no corresponde al curso elegido.');
                    }
                },
            ],
        ]);
    }
}
