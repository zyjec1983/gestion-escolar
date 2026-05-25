<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_registros', function (Blueprint $table) {
            $table->string('alumno_numero_cedula', 20)->nullable()->after('alumno_numero_documento');
            $table->string('direccion_domiciliaria')->nullable()->after('alumno_apellidos');
            $table->string('alumno_tipo_sangre', 5)->nullable()->after('alumno_genero');
            $table->string('alumno_etnia', 50)->nullable()->after('alumno_tipo_sangre');
            $table->string('alumno_nacionalidad', 50)->nullable()->after('alumno_etnia');
            $table->string('alumno_telefono_convencional', 20)->nullable()->after('alumno_nacionalidad');
            $table->string('alumno_celular', 20)->nullable()->after('alumno_telefono_convencional');
            $table->string('alumno_email_activo')->nullable()->after('alumno_celular');
            $table->tinyInteger('alumno_numero_hermanos')->nullable()->after('alumno_email_activo');
            $table->tinyInteger('alumno_lugar_entre_hermanos')->nullable()->after('alumno_numero_hermanos');
            $table->string('alumno_con_quien_vive', 100)->nullable()->after('alumno_lugar_entre_hermanos');
            $table->string('alumno_parentesco_convivencia', 100)->nullable()->after('alumno_con_quien_vive');
            $table->string('alumno_institucion_procedencia')->nullable()->after('alumno_parentesco_convivencia');
            $table->text('alumno_observaciones')->nullable()->after('alumno_institucion_procedencia');

            $table->boolean('alumno_padece_enfermedad')->default(false)->after('alumno_observaciones');
            $table->string('alumno_nombre_enfermedad')->nullable()->after('alumno_padece_enfermedad');
            $table->boolean('alumno_tiene_tratamiento')->default(false)->after('alumno_nombre_enfermedad');
            $table->boolean('alumno_toma_medicina')->default(false)->after('alumno_tiene_tratamiento');
            $table->string('alumno_nombre_medicina')->nullable()->after('alumno_toma_medicina');
            $table->boolean('alumno_tiene_nee')->default(false)->after('alumno_nombre_medicina');
            $table->string('alumno_nombre_nee')->nullable()->after('alumno_tiene_nee');
            $table->string('alumno_tipo_discapacidad')->nullable()->after('alumno_nombre_nee');
            $table->boolean('alumno_tiene_carnet_discapacidad')->default(false)->after('alumno_tipo_discapacidad');
            $table->string('alumno_numero_carnet', 30)->nullable()->after('alumno_tiene_carnet_discapacidad');
            $table->boolean('alumno_tiene_informe_psicopedagogico')->default(false)->after('alumno_numero_carnet');

            $table->string('vivienda_sector', 100)->nullable()->after('alumno_tiene_informe_psicopedagogico');
            $table->string('vivienda_referencia')->nullable()->after('vivienda_sector');
            $table->string('vivienda_provincia', 50)->nullable()->after('vivienda_referencia');
            $table->string('vivienda_canton', 50)->nullable()->after('vivienda_provincia');
            $table->string('vivienda_parroquia', 50)->nullable()->after('vivienda_canton');
            $table->string('vivienda_numero_casa', 20)->nullable()->after('vivienda_parroquia');
            $table->boolean('vivienda_casa_propia')->default(false)->after('vivienda_numero_casa');
            $table->boolean('vivienda_tiene_agua')->default(false)->after('vivienda_casa_propia');
            $table->boolean('vivienda_tiene_sshh')->default(false)->after('vivienda_tiene_agua');
            $table->boolean('vivienda_tiene_luz')->default(false)->after('vivienda_tiene_sshh');

            $table->string('padre_nombres_apellidos')->nullable()->after('vivienda_tiene_luz');
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

            $table->string('facturador_nombre')->nullable()->after('madre_es_facturador');
            $table->string('facturador_cedula', 20)->nullable()->after('facturador_nombre');
            $table->string('facturador_telefono', 20)->nullable()->after('facturador_cedula');
            $table->string('facturador_direccion')->nullable()->after('facturador_telefono');
            $table->string('facturador_email')->nullable()->after('facturador_direccion');

            $table->string('alterno_nombre')->nullable()->after('facturador_email');
            $table->string('alterno_parentesco', 50)->nullable()->after('alterno_nombre');
            $table->string('alterno_cedula', 20)->nullable()->after('alterno_parentesco');
            $table->string('alterno_telefono', 20)->nullable()->after('alterno_cedula');
            $table->string('alterno_email')->nullable()->after('alterno_telefono');
            $table->string('alterno_direccion')->nullable()->after('alterno_email');

            $table->string('especializacion', 100)->nullable()->after('periodo_id');

            $table->enum('tipo_solicitud', ['nuevo_ingreso', 'separacion', 'cambio_colegio'])->default('nuevo_ingreso')->after('codigo_solicitud');
            $table->foreignId('registrado_por')->nullable()->after('tipo_solicitud')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('pre_registros', function (Blueprint $table) {
            $columns = [
                'alumno_numero_cedula',
                'direccion_domiciliaria', 'alumno_tipo_sangre', 'alumno_etnia', 'alumno_nacionalidad',
                'alumno_telefono_convencional', 'alumno_celular', 'alumno_email_activo',
                'alumno_numero_hermanos', 'alumno_lugar_entre_hermanos', 'alumno_con_quien_vive',
                'alumno_parentesco_convivencia', 'alumno_institucion_procedencia', 'alumno_observaciones',
                'alumno_padece_enfermedad', 'alumno_nombre_enfermedad', 'alumno_tiene_tratamiento',
                'alumno_toma_medicina', 'alumno_nombre_medicina', 'alumno_tiene_nee', 'alumno_nombre_nee',
                'alumno_tipo_discapacidad', 'alumno_tiene_carnet_discapacidad', 'alumno_numero_carnet',
                'alumno_tiene_informe_psicopedagogico',
                'vivienda_sector', 'vivienda_referencia', 'vivienda_provincia', 'vivienda_canton', 'vivienda_parroquia',
                'vivienda_numero_casa', 'vivienda_casa_propia', 'vivienda_tiene_agua', 'vivienda_tiene_sshh', 'vivienda_tiene_luz',
                'padre_nombres_apellidos', 'padre_cedula', 'padre_telefono', 'padre_profesion', 'padre_direccion', 'padre_email',
                'padre_es_facturador',
                'madre_nombres_apellidos', 'madre_cedula', 'madre_telefono', 'madre_profesion', 'madre_direccion', 'madre_email',
                'madre_es_facturador',
                'facturador_nombre', 'facturador_cedula', 'facturador_telefono', 'facturador_direccion', 'facturador_email',
                'alterno_nombre', 'alterno_parentesco', 'alterno_cedula', 'alterno_telefono', 'alterno_email', 'alterno_direccion',
                'especializacion', 'tipo_solicitud', 'registrado_por',
            ];
            $table->dropColumn($columns);
        });
    }
};
