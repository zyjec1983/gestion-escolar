<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    /** Clave del ajuste que habilita la edicion del catalogo de tipos de observacion. */
    public const TIPOS_OBS_EDICION = 'tipos_obs_edicion';

    protected $table = 'ajustes';

    protected $fillable = ['clave', 'valor'];

    /** Lee un ajuste por su clave. Devuelve $defecto si no existe. */
    public static function obtener(string $clave, ?string $defecto = null): ?string
    {
        $ajuste = self::query()->where('clave', $clave)->first();

        return $ajuste ? $ajuste->valor : $defecto;
    }

    /** Guarda (o actualiza) un ajuste por su clave. */
    public static function guardar(string $clave, ?string $valor): self
    {
        return self::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }

    /** ¿La edicion del catalogo de tipos de observacion esta habilitada? */
    public static function edicionTiposHabilitada(): bool
    {
        return self::obtener(self::TIPOS_OBS_EDICION, '0') === '1';
    }
}
