<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro del historial de cambios.
 *
 * Qué hace: deja constancia de quién creó, editó o eliminó información en los
 * módulos administrables.
 * Conecta con: Admin\HistorialController y todos los controladores del panel.
 * Ruta: GET /admin/historial.
 */
class HistorialCambio extends Model
{
    protected $table = 'historial_cambios';

    protected $fillable = [
        'user_id', 'modulo', 'accion', 'descripcion', 'datos', 'ip',
    ];

    protected function casts(): array
    {
        return ['datos' => 'array'];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Atajo para registrar un cambio desde cualquier controlador.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function registrar(string $modulo, string $accion, string $descripcion, array $datos = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'modulo' => $modulo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos' => $datos ?: null,
            'ip' => request()->ip(),
        ]);
    }
}
