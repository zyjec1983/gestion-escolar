<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cuentas de prueba con un usuario por rol.
 *
 * Qué hace: garantiza que el administrador del sistema, el rectorado y el
 * vicerrectorado tengan cuentas y roles distintos (antes rector@ era ADMIN).
 * Usa updateOrCreate manual (firstOrNew) para no duplicar correos y conserva
 * el nombre de las cuentas que ya existen.
 *
 * Conecta con: App\Models\User, DatabaseSeeder y AdminSeeder (que delega aquí).
 * Se ejecuta con: php artisan db:seed --class=UsuariosPruebaSeeder
 *
 * Genera además el archivo USUARIOS_PRUEBA.txt en la raíz del proyecto.
 */
class UsuariosPruebaSeeder extends Seeder
{
    /**
     * Email, nombre, rol y contraseña de cada cuenta de prueba.
     *
     * Las cuentas institucionales usan admin123; el resto usa password,
     * igual que el resto de datos de prueba del sistema.
     *
     * @var array<int, array{email: string, name: string, apellidos: string, rol: string, password: string}>
     */
    public const CUENTAS = [
        [
            'email' => 'admin@fake.edu.ec',
            'name' => 'ADMINISTRADOR',
            'apellidos' => 'DEL SISTEMA',
            'rol' => 'ADMIN',
            'password' => 'admin123',
        ],
        [
            'email' => 'rector@fake.edu.ec',
            'name' => 'RECTOR',
            'apellidos' => 'UNIDAD EDUCATIVA',
            'rol' => 'RECTORA',
            'password' => 'admin123',
        ],
        [
            'email' => 'vicerrector@fake.edu.ec',
            'name' => 'VICERRECTOR',
            'apellidos' => 'UNIDAD EDUCATIVA',
            'rol' => 'VICERRECTORA',
            'password' => 'admin123',
        ],
        [
            'email' => 'secretaria@fake.edu.ec',
            'name' => 'SECRETARIA',
            'apellidos' => 'UNIDAD EDUCATIVA',
            'rol' => 'SECRETARIA',
            'password' => 'admin123',
        ],
        [
            'email' => 'inspector@ue.edu.ec',
            'name' => 'INSPECTOR',
            'apellidos' => 'UNIDAD EDUCATIVA',
            'rol' => 'INSPECCION',
            'password' => 'password',
        ],
        [
            'email' => 'dece@fake.edu.ec',
            'name' => 'PSICOLOGA DECE',
            'apellidos' => 'CONSEJERIA ESTUDIANTIL',
            'rol' => 'DECE',
            'password' => 'password',
        ],
        [
            'email' => 'crodrigue@fake.edu.ec',
            'name' => 'CHRISTIAN WILFRIDO',
            'apellidos' => 'RODRIGUEZ RIVADENEIRA',
            'rol' => 'DOCENTE',
            'password' => 'password',
        ],
        [
            'email' => 'cmendoza@email.com',
            'name' => 'MENDOZA ANDRADE',
            'apellidos' => 'CARLOS LUIS',
            'rol' => 'REPRESENTANTE',
            'password' => 'password',
        ],
        [
            'email' => 'est.0900000001@fake.edu.ec',
            'name' => 'LUIS ALBERTO',
            'apellidos' => 'ZAMBRANO MENDOZA',
            'rol' => 'ESTUDIANTE',
            'password' => 'password',
        ],
    ];

    public function run(): void
    {
        $lineas = [];

        foreach (self::CUENTAS as $cuenta) {
            // Se busca por email para no duplicar cuentas si el seeder se
            // ejecuta varias veces. Si ya existe, se conserva su nombre.
            $user = User::firstOrNew(['email' => $cuenta['email']]);

            if (! $user->exists) {
                $user->name = $cuenta['name'];
                $user->apellidos = $cuenta['apellidos'];
            }

            $user->password = bcrypt($cuenta['password']);
            $user->rol = $cuenta['rol'];
            $user->institucion_id = $user->institucion_id ?: 1;
            $user->save();

            $lineas[] = str_pad($cuenta['email'], 38).str_pad($cuenta['password'], 14).$cuenta['rol'];
        }

        // Documento de apoyo para las pruebas manuales.
        file_put_contents(
            database_path('../USUARIOS_PRUEBA.txt'),
            "USUARIOS DE PRUEBA - SISTEMA DE GESTION ESCOLAR\n".
            "Generado por database/seeders/UsuariosPruebaSeeder.php\n\n".
            "EMAIL                                  CONTRASENA    ROL\n".
            str_repeat('-', 70)."\n".
            implode(PHP_EOL, $lineas).PHP_EOL
        );

        $this->command?->info('Usuarios de prueba actualizados. Ver USUARIOS_PRUEBA.txt');
    }
}
