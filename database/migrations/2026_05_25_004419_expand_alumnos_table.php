<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->string('numero_cedula', 20)->nullable()->after('codigo');
            $table->date('fecha_nacimiento')->nullable()->after('apellidos');
            $table->string('tipo_sangre', 5)->nullable()->after('genero');
            $table->string('etnia', 50)->nullable()->after('tipo_sangre');
            $table->string('nacionalidad', 50)->nullable()->after('etnia');
            $table->string('telefono_convencional', 20)->nullable()->after('nacionalidad');
            $table->string('celular', 20)->nullable()->after('telefono_convencional');
            $table->string('email_activo')->nullable()->after('celular');
            $table->tinyInteger('numero_hermanos')->nullable()->after('email_activo');
            $table->tinyInteger('lugar_que_ocupa_entre_hermanos')->nullable()->after('numero_hermanos');
            $table->string('con_quien_vive', 100)->nullable()->after('lugar_que_ocupa_entre_hermanos');
            $table->string('parentesco_convivencia', 100)->nullable()->after('con_quien_vive');
            $table->string('institucion_procedencia')->nullable()->after('parentesco_convivencia');
            $table->text('observaciones_generales')->nullable()->after('institucion_procedencia');

            $table->boolean('padece_enfermedad')->default(false)->after('observaciones_generales');
            $table->string('nombre_enfermedad')->nullable()->after('padece_enfermedad');
            $table->boolean('tiene_tratamiento_medico')->default(false)->after('nombre_enfermedad');
            $table->boolean('toma_medicina')->default(false)->after('tiene_tratamiento_medico');
            $table->string('nombre_medicina')->nullable()->after('toma_medicina');
            $table->boolean('necesidades_educativas_especificas_nee')->default(false)->after('nombre_medicina');
            $table->string('nombre_nee')->nullable()->after('necesidades_educativas_especificas_nee');
            $table->boolean('tiene_discapacidad')->default(false)->after('nombre_nee');
            $table->string('tipo_discapacidad')->nullable()->after('tiene_discapacidad');
            $table->boolean('tiene_carnet')->default(false)->after('tipo_discapacidad');
            $table->string('numero_carnet', 30)->nullable()->after('tiene_carnet');
            $table->boolean('tiene_informe_psicopedagogico_actualizado')->default(false)->after('numero_carnet');

            $table->string('direccion_domiciliaria')->nullable()->after('tiene_informe_psicopedagogico_actualizado');
            $table->string('sector', 100)->nullable()->after('direccion_domiciliaria');
            $table->string('referencia_domicilio')->nullable()->after('sector');
            $table->string('provincia', 50)->nullable()->after('referencia_domicilio');
            $table->string('canton', 50)->nullable()->after('provincia');
            $table->string('parroquia', 50)->nullable()->after('canton');
            $table->string('numero_casa', 20)->nullable()->after('parroquia');
            $table->boolean('casa_propia')->default(false)->after('numero_casa');
            $table->boolean('tiene_agua')->default(false)->after('casa_propia');
            $table->boolean('tiene_sshh_letrina')->default(false)->after('tiene_agua');
            $table->boolean('tiene_luz_electrica')->default(false)->after('tiene_sshh_letrina');

            $table->string('padre_nombres_apellidos')->nullable()->after('tiene_luz_electrica');
            $table->string('padre_cedula', 20)->nullable()->after('padre_nombres_apellidos');
            $table->string('padre_telefono', 20)->nullable()->after('padre_cedula');
            $table->string('padre_profesion', 100)->nullable()->after('padre_telefono');
            $table->string('padre_direccion')->nullable()->after('padre_profesion');
            $table->string('padre_email')->nullable()->after('padre_direccion');
            $table->boolean('padre_es_facturador')->default(false)->after('padre_email');

            $table->string('madre_nombres_apellidos')->nullable()->after('padre_es_facturador');
            $table->string('madre_cedula', 20)->nullable()->after('madre_nombres_apellidos');
            $table->string('madre_telefono', 20)->nullable()->after('madre_cedula');
            $table->string('madre_profesion', 100)->nullable()->after('madre_telefono');
            $table->string('madre_direccion')->nullable()->after('madre_profesion');
            $table->string('madre_email')->nullable()->after('madre_direccion');
            $table->boolean('madre_es_facturador')->default(false)->after('madre_email');

            $table->string('representante_alterno_nombre')->nullable()->after('madre_es_facturador');
            $table->string('representante_alterno_parentesco', 50)->nullable()->after('representante_alterno_nombre');
            $table->string('representante_alterno_cedula', 20)->nullable()->after('representante_alterno_parentesco');
            $table->string('representante_alterno_telefono', 20)->nullable()->after('representante_alterno_cedula');
            $table->string('representante_alterno_email')->nullable()->after('representante_alterno_telefono');
            $table->string('representante_alterno_direccion')->nullable()->after('representante_alterno_email');

            $table->string('facturador_nombre')->nullable()->after('representante_alterno_direccion');
            $table->string('facturador_cedula', 20)->nullable()->after('facturador_nombre');
            $table->string('facturador_telefono', 20)->nullable()->after('facturador_cedula');
            $table->string('facturador_direccion')->nullable()->after('facturador_telefono');
            $table->string('facturador_email')->nullable()->after('facturador_direccion');
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $columns = [
                'numero_cedula', 'fecha_nacimiento', 'tipo_sangre', 'etnia', 'nacionalidad',
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
            ];
            $table->dropColumn($columns);
        });
    }
};
