<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rol adicional asignado a un usuario.
 *
 * Qué hace: representa una fila de la tabla pivote `user_roles`. El rol
 * principal sigue viviendo en `users.rol`; esta tabla habilita el multirol
 * configurable desde Administración → Usuarios.
 *
 * Conecta con: App\Models\User (relación rolesAdicionales).
 * Ruta: PUT /admin/usuarios/{user} (admin.usuarios.update).
 */
class UserRole extends Model
{
    protected $table = 'user_roles';

    protected $fillable = ['user_id', 'rol'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
