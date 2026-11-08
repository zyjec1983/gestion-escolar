<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Reglas de los datos de un alumno. Las usa la ficha de matricula y la de
 * actualizacion de datos, para que las dos pantallas no se separen.
 */
trait ValidaDatosAlumno
{
    protected function reglasAlumno(): array
    {
        return [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'numero_cedula' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:masculino,femenino',
            'tipo_sangre' => ['nullable', Rule::in(Alumno::TIPOS_SANGRE)],
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
        ];
    }

    /**
     * Campos de texto que se guardan siempre en mayusculas. Se excluyen correos,
     * fechas, numeros y opciones de catalogo. mb_strtoupper conserva las tildes.
     *
     * @return array<int, string>
     */
    protected function camposMayusculas(): array
    {
        return [
            'nombres', 'apellidos', 'numero_cedula', 'etnia', 'nacionalidad',
            'telefono_convencional', 'celular',
            'con_quien_vive', 'parentesco_convivencia', 'institucion_procedencia',
            'observaciones_generales',
            'nombre_enfermedad', 'nombre_medicina', 'nombre_nee', 'tipo_discapacidad', 'numero_carnet',
            'direccion_domiciliaria', 'sector', 'referencia_domicilio', 'provincia', 'canton',
            'parroquia', 'numero_casa',
            'padre_nombres_apellidos', 'padre_cedula', 'padre_telefono', 'padre_profesion', 'padre_direccion',
            'madre_nombres_apellidos', 'madre_cedula', 'madre_telefono', 'madre_profesion', 'madre_direccion',
            'representante_alterno_nombre', 'representante_alterno_parentesco',
            'representante_alterno_parentesco_otro', 'representante_alterno_cedula',
            'representante_alterno_telefono', 'representante_alterno_direccion',
            'facturador_nombre', 'facturador_cedula', 'facturador_telefono', 'facturador_direccion',
            'representante_nombres', 'representante_apellidos', 'representante_cedula',
            'representante_telefono',
        ];
    }

    /**
     * Normaliza a mayusculas los campos de texto sin tocar correos ni catalogos.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    protected function normalizarMayusculas(array $datos): array
    {
        foreach ($this->camposMayusculas() as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = trim(mb_strtoupper($datos[$campo], 'UTF-8'));
            }
        }

        return $datos;
    }

    /**
     * Aplica la normalizacion sobre la peticion antes de validar, para que las
     * reglas, los valores guardados y old() trabajen con el mismo texto.
     */
    protected function normalizarMayusculasRequest(Request $request): void
    {
        $request->merge($this->normalizarMayusculas($request->all()));
    }

    /** Solo los campos del alumno, para no guardar de mas. */
    protected function datosAlumno(array $validated): array
    {
        $datos = collect($validated)->only(array_keys($this->reglasAlumno()))->toArray();

        return $this->normalizarMayusculas($datos);
    }

    /**
     * El representante legal tiene que ser una persona mayor de edad. Las
     * cuentas viejas pueden no tener fecha de nacimiento: en ese caso el
     * sistema no puede afirmar que sea menor, asi que se avisa en lugar de
     * bloquear la actualizacion de la ficha.
     */
    protected function reglasRepresentanteLegal(): array
    {
        return [
            'representante_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('rol', 'REPRESENTANTE')),
            ],
            'representante_nombres' => 'nullable|string|max:255',
            'representante_apellidos' => 'nullable|string|max:255',
            'representante_email' => 'nullable|email|max:255',
            'representante_telefono' => 'nullable|string|max:20',
            'representante_cedula' => 'nullable|string|max:20',
            'representante_fecha_nacimiento' => 'nullable|date',
        ];
    }

    /**
     * Devuelve una lista de errores para los representatives que no pueden
     * ser representante legal. Vacio si todo esta bien.
     *
     * @return array<string, string>
     */
    protected function erroresRepresentante(array $validated): array
    {
        $errores = [];

        if (! empty($validated['representante_fecha_nacimiento'])) {
            $mayor = Carbon::parse($validated['representante_fecha_nacimiento'])->age;
            if ($mayor < 18) {
                $errores['representante_fecha_nacimiento'] = 'El representante legal debe ser mayor de edad (18 o mas anos).';
            }
        }

        $yaUsado = User::where('numero_cedula', $validated['representante_cedula'] ?? null)
            ->where('rol', 'REPRESENTANTE')
            ->when(
                ! empty($validated['representante_user_id']),
                fn ($q) => $q->where('id', '!=', $validated['representante_user_id'])
            )
            ->exists();

        if (! empty($validated['representante_cedula']) && $yaUsado) {
            $errores['representante_cedula'] = 'Ya existe otra cuenta de representante con esa cedula.';
        }

        return $errores;
    }
}
