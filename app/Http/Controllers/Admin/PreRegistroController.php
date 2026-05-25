<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumno;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Matricula;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\PreRegistro;
use App\Models\User;
use App\Notifications\SolicitudAprobada;
use App\Notifications\SolicitudRechazada;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PreRegistroController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->query('filtro', 'pendiente');

        $preRegistros = PreRegistro::with(['curso', 'paralelo', 'periodo'])
            ->when($filtro !== 'todos', function ($q) use ($filtro) {
                $q->where('estado', $filtro);
            })
            ->latest()
            ->paginate(15);

        return view('admin.pre-registros.index', compact('preRegistros', 'filtro'));
    }

    public function show($id)
    {
        $preRegistro = PreRegistro::with(['curso', 'paralelo', 'periodo', 'aprobadoPor', 'registradoPor'])->findOrFail($id);
        return view('admin.pre-registros.show', compact('preRegistro'));
    }

    public function createForm()
    {
        $cursos = Curso::all();
        $paralelos = Paralelo::all();
        $periodo = PeriodoLectivo::where('activo', true)->first();
        $adminMode = true;

        return view('pre-registro.form', compact('cursos', 'paralelos', 'periodo', 'adminMode'));
    }

    public function storeManual(Request $request)
    {
        $validated = app(\App\Http\Controllers\PreRegistroController::class)->validarFormulario($request);

        $periodo = PeriodoLectivo::where('activo', true)->firstOrFail();

        $preRegistro = PreRegistro::create([
            ...$validated,
            'periodo_id' => $periodo->id,
            'codigo_solicitud' => 'SOL-' . strtoupper(Str::random(8)),
            'estado' => 'pendiente',
            'tipo_solicitud' => $request->input('tipo_solicitud', 'cambio_colegio'),
            'registrado_por' => auth()->id(),
        ]);

        $pdf = Pdf::loadView('pre-registro.pdf', compact('preRegistro'));
        $pdfPath = "public/pre-registros/solicitud_{$preRegistro->id}.pdf";
        $pdf->save(storage_path("app/{$pdfPath}"));
        $preRegistro->update(['pdf_path' => $pdfPath]);

        return redirect()->route('admin.pre-registros.show', $preRegistro->id)
            ->with('success', 'Solicitud registrada manualmente.');
    }

    public function toggle()
    {
        $institucion = Institucion::firstOrFail();
        $institucion->update(['pre_registro_activo' => !$institucion->pre_registro_activo]);

        $estado = $institucion->pre_registro_activo ? 'activadas' : 'desactivadas';
        return back()->with('success', "Inscripciones {$estado} correctamente.");
    }

    public function aprobar($id)
    {
        $preRegistro = PreRegistro::with(['curso', 'paralelo', 'periodo'])->findOrFail($id);

        if ($preRegistro->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $password = Str::password(10);

        $userData = [
            'name' => $preRegistro->rep_nombres,
            'apellidos' => $preRegistro->rep_apellidos,
            'email' => $preRegistro->rep_email,
            'password' => Hash::make($password),
            'rol' => 'REPRESENTANTE',
            'institucion_id' => 1,
        ];

        $representante = User::where('email', $preRegistro->rep_email)->first()
            ?: User::create($userData);

        $ultimoCodigo = Alumno::max('id') ?? 0;
        $codigoAlumno = now()->year . str_pad($ultimoCodigo + 1, 6, '0', STR_PAD_LEFT);

        $alumnoData = [
            'codigo' => $codigoAlumno,
            'numero_cedula' => $preRegistro->alumno_numero_cedula ?: $preRegistro->alumno_numero_documento,
            'nombres' => $preRegistro->alumno_nombres,
            'apellidos' => $preRegistro->alumno_apellidos,
            'fecha_nacimiento' => $preRegistro->alumno_fecha_nacimiento,
            'genero' => $preRegistro->alumno_genero,
            'tipo_sangre' => $preRegistro->alumno_tipo_sangre,
            'etnia' => $preRegistro->alumno_etnia,
            'nacionalidad' => $preRegistro->alumno_nacionalidad,
            'telefono_convencional' => $preRegistro->alumno_telefono_convencional,
            'celular' => $preRegistro->alumno_celular,
            'email_activo' => $preRegistro->alumno_email_activo,
            'numero_hermanos' => $preRegistro->alumno_numero_hermanos,
            'lugar_que_ocupa_entre_hermanos' => $preRegistro->alumno_lugar_entre_hermanos,
            'con_quien_vive' => $preRegistro->alumno_con_quien_vive,
            'parentesco_convivencia' => $preRegistro->alumno_parentesco_convivencia,
            'institucion_procedencia' => $preRegistro->alumno_institucion_procedencia,
            'observaciones_generales' => $preRegistro->alumno_observaciones,
            'padece_enfermedad' => $preRegistro->alumno_padece_enfermedad,
            'nombre_enfermedad' => $preRegistro->alumno_nombre_enfermedad,
            'tiene_tratamiento_medico' => $preRegistro->alumno_tiene_tratamiento,
            'toma_medicina' => $preRegistro->alumno_toma_medicina,
            'nombre_medicina' => $preRegistro->alumno_nombre_medicina,
            'necesidades_educativas_especificas_nee' => $preRegistro->alumno_tiene_nee,
            'nombre_nee' => $preRegistro->alumno_nombre_nee,
            'tipo_discapacidad' => $preRegistro->alumno_tipo_discapacidad,
            'tiene_carnet' => $preRegistro->alumno_tiene_carnet_discapacidad,
            'numero_carnet' => $preRegistro->alumno_numero_carnet,
            'tiene_informe_psicopedagogico_actualizado' => $preRegistro->alumno_tiene_informe_psicopedagogico,
            'direccion_domiciliaria' => $preRegistro->direccion_domiciliaria,
            'sector' => $preRegistro->vivienda_sector,
            'referencia_domicilio' => $preRegistro->vivienda_referencia,
            'provincia' => $preRegistro->vivienda_provincia,
            'canton' => $preRegistro->vivienda_canton,
            'parroquia' => $preRegistro->vivienda_parroquia,
            'numero_casa' => $preRegistro->vivienda_numero_casa,
            'casa_propia' => $preRegistro->vivienda_casa_propia,
            'tiene_agua' => $preRegistro->vivienda_tiene_agua,
            'tiene_sshh_letrina' => $preRegistro->vivienda_tiene_sshh,
            'tiene_luz_electrica' => $preRegistro->vivienda_tiene_luz,
            'padre_nombres_apellidos' => $preRegistro->padre_nombres_apellidos,
            'padre_cedula' => $preRegistro->padre_cedula,
            'padre_telefono' => $preRegistro->padre_telefono,
            'padre_profesion' => $preRegistro->padre_profesion,
            'padre_direccion' => $preRegistro->padre_direccion,
            'padre_email' => $preRegistro->padre_email,
            'padre_es_facturador' => $preRegistro->padre_es_facturador,
            'madre_nombres_apellidos' => $preRegistro->madre_nombres_apellidos,
            'madre_cedula' => $preRegistro->madre_cedula,
            'madre_telefono' => $preRegistro->madre_telefono,
            'madre_profesion' => $preRegistro->madre_profesion,
            'madre_direccion' => $preRegistro->madre_direccion,
            'madre_email' => $preRegistro->madre_email,
            'madre_es_facturador' => $preRegistro->madre_es_facturador,
            'facturador_nombre' => $preRegistro->facturador_nombre,
            'facturador_cedula' => $preRegistro->facturador_cedula,
            'facturador_telefono' => $preRegistro->facturador_telefono,
            'facturador_direccion' => $preRegistro->facturador_direccion,
            'facturador_email' => $preRegistro->facturador_email,
            'representante_alterno_nombre' => $preRegistro->alterno_nombre,
            'representante_alterno_parentesco' => $preRegistro->alterno_parentesco,
            'representante_alterno_cedula' => $preRegistro->alterno_cedula,
            'representante_alterno_telefono' => $preRegistro->alterno_telefono,
            'representante_alterno_email' => $preRegistro->alterno_email,
            'representante_alterno_direccion' => $preRegistro->alterno_direccion,
            'representante_user_id' => $representante->id,
        ];

        $alumno = Alumno::create($alumnoData);

        $matricula = Matricula::create([
            'codigo_matricula' => "MAT-{$alumno->id}",
            'alumno_id' => $alumno->id,
            'curso_id' => $preRegistro->curso_id,
            'paralelo_id' => $preRegistro->paralelo_id,
            'periodo_id' => $preRegistro->periodo_id,
            'especializacion' => $preRegistro->especializacion,
        ]);

        $preRegistro->update([
            'estado' => 'aprobado',
            'fecha_aprobacion' => now(),
            'aprobado_por' => auth()->id(),
        ]);

        $representante->notify(new SolicitudAprobada($preRegistro, $representante->email, $password));

        return redirect()->route('admin.matriculas.edit', $matricula->id)
            ->with('success', "Solicitud aprobada. Complete la ficha de matrícula.");
    }

    public function rechazar(Request $request, $id)
    {
        $validated = $request->validate([
            'comentario_rechazo' => 'required|string|max:500',
        ]);

        $preRegistro = PreRegistro::findOrFail($id);

        if ($preRegistro->estado !== 'pendiente') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $preRegistro->update([
            'estado' => 'rechazado',
            'comentario_rechazo' => $validated['comentario_rechazo'],
        ]);

        \Illuminate\Support\Facades\Notification::route('mail', $preRegistro->rep_email)
            ->notify(new SolicitudRechazada($preRegistro, $validated['comentario_rechazo']));

        return back()->with('success', 'Solicitud rechazada. Se ha notificado al representante por email.');
    }

    public function pdf($id)
    {
        $preRegistro = PreRegistro::findOrFail($id);

        if (!$preRegistro->pdf_path || !file_exists(storage_path("app/{$preRegistro->pdf_path}"))) {
            $pdf = Pdf::loadView('pre-registro.pdf', compact('preRegistro'));
            return $pdf->download("solicitud_{$preRegistro->codigo_solicitud}.pdf");
        }

        return response()->file(storage_path("app/{$preRegistro->pdf_path}"));
    }
}
