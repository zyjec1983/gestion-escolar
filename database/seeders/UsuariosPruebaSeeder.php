<?php

namespace Database\Seeders;

use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Materia;
use App\Models\Paralelo;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Cuentas de prueba con un usuario por rol y dos cuentas multirol.
 *
 * Qué hace: garantiza que el administrador del sistema, el rectorado y el
 * vicerrectorado tengan cuentas y roles distintos (antes rector@ era ADMIN).
 * Además crea dos cuentas con doble perfil:
 * - Inspector + Docente de Historia.
 * - Vicerrectora + Docente de Matemáticas.
 * Usa firstOrNew/firstOrCreate para no duplicar correos, roles ni clases.
 *
 * Conecta con: App\Models\User, UserRole, Materia, Curso, Paralelo y
 * ClaseDocente; DatabaseSeeder y AdminSeeder (que delega aquí).
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
     * `forzar_nombre` actualiza el nombre aunque la cuenta ya exista; se usa
     * en las cuentas multirol para que las vistas muestren un nombre real.
     *
     * @var array<int, array{email: string, name: string, apellidos: string, rol: string, password: string, forzar_nombre?: bool}>
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
            'name' => 'PATRICIA ELENA',
            'apellidos' => 'CABRERA VILLACÍS',
            'rol' => 'VICERRECTORA',
            'password' => 'admin123',
            'forzar_nombre' => true,
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
            'name' => 'JORGE ANDRÉS',
            'apellidos' => 'SALAZAR MONTENEGRO',
            'rol' => 'INSPECCION',
            'password' => 'password',
            'forzar_nombre' => true,
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

    /**
     * Cuentas con doble perfil y las clases que las habilitan como docentes.
     *
     * `roles` se guarda en `user_roles`; las clases se crean en
     * `clases_docentes`, que es lo que hace aparecer el perfil DOCENTE con sus
     * materias en el aula virtual.
     *
     * @var array<int, array{email: string, roles: array<int, string>, clases: array<int, array{materia: string, codigo: string, color: string, curso: string, paralelos: array<int, string>}>}>
     */
    public const MULTIROL = [
        [
            'email' => 'inspector@ue.edu.ec',
            'roles' => ['DOCENTE'],
            'clases' => [
                [
                    'materia' => 'HISTORIA',
                    'codigo' => 'HIS',
                    'color' => '#B5651D',
                    'curso' => 'NOVENO GRADO',
                    'paralelos' => ['A'],
                ],
                [
                    'materia' => 'HISTORIA',
                    'codigo' => 'HIS',
                    'color' => '#B5651D',
                    'curso' => 'DECIMO GRADO',
                    'paralelos' => ['B'],
                ],
            ],
        ],
        [
            'email' => 'vicerrector@fake.edu.ec',
            'roles' => ['DOCENTE'],
            'clases' => [
                [
                    'materia' => 'MATEMATICAS',
                    'codigo' => 'MAT',
                    'color' => '#4CC9F0',
                    'curso' => 'OCTAVO GRADO',
                    'paralelos' => ['A'],
                ],
                [
                    'materia' => 'MATEMATICAS',
                    'codigo' => 'MAT',
                    'color' => '#4CC9F0',
                    'curso' => 'DECIMO GRADO',
                    'paralelos' => ['A'],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $usuarios = [];

        foreach (self::CUENTAS as $cuenta) {
            // Se busca por email para no duplicar cuentas si el seeder se
            // ejecuta varias veces. Si ya existe, se conserva su nombre,
            // salvo en las cuentas multirol, que necesitan un nombre real.
            $user = User::firstOrNew(['email' => $cuenta['email']]);

            if (! $user->exists || ($cuenta['forzar_nombre'] ?? false)) {
                $user->name = $cuenta['name'];
                $user->apellidos = $cuenta['apellidos'];
            }

            $user->password = bcrypt($cuenta['password']);
            $user->rol = $cuenta['rol'];
            $user->institucion_id = $user->institucion_id ?: 1;
            $user->save();

            $user->limpiarPerfilesMemo();
            $usuarios[$cuenta['email']] = $user;
        }

        // Doble perfil: rol adicional en `user_roles` y clases en
        // `clases_docentes`. firstOrCreate mantiene el seeder idempotente.
        foreach (self::MULTIROL as $config) {
            $user = $usuarios[$config['email']] ?? null;

            if (! $user) {
                continue;
            }

            foreach ($config['roles'] as $rol) {
                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'rol' => $rol,
                ]);
            }

            foreach ($config['clases'] as $clase) {
                $materia = Materia::firstOrCreate(
                    ['codigo' => $clase['codigo']],
                    ['nombre' => $clase['materia'], 'color_tarjeta' => $clase['color']]
                );

                $curso = Curso::where('nombre', $clase['curso'])->first();

                if (! $curso) {
                    continue;
                }

                foreach ($clase['paralelos'] as $nombreParalelo) {
                    $paralelo = Paralelo::where('nombre', $nombreParalelo)->first();

                    if (! $paralelo) {
                        continue;
                    }

                    ClaseDocente::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'materia_id' => $materia->id,
                            'curso_id' => $curso->id,
                            'paralelo_id' => $paralelo->id,
                        ],
                        ['color_tarjeta' => $clase['color']]
                    );
                }
            }

            $user->limpiarPerfilesMemo();
        }

        // Documento de apoyo para las pruebas manuales: muestra todos los
        // perfiles que puede elegir cada cuenta al iniciar sesión.
        $lineas = [];

        foreach (self::CUENTAS as $cuenta) {
            $user = $usuarios[$cuenta['email']];
            $lineas[] = str_pad($cuenta['email'], 38)
                .str_pad($cuenta['password'], 14)
                .implode(' + ', $user->perfiles());
        }

        file_put_contents(
            database_path('../USUARIOS_PRUEBA.txt'),
            "USUARIOS DE PRUEBA - SISTEMA DE GESTION ESCOLAR\n".
            "Generado por database/seeders/UsuariosPruebaSeeder.php\n\n".
            "EMAIL                                  CONTRASENA    PERFILES\n".
            str_repeat('-', 74)."\n".
            implode(PHP_EOL, $lineas).PHP_EOL
        );

        $this->command?->info('Usuarios de prueba actualizados. Ver USUARIOS_PRUEBA.txt');
    }
}
