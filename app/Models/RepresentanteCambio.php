<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Historial de cambios de representante legal. No guarda motivo: las causas son
 * multiple (divorcio, salida del pais, prision, etc.) y no se preguntan en la
 * ficha. Sirve para responder quien representaba al alumno y cuando cambio.
 */
class RepresentanteCambio extends Model
{
    use HasFactory;

    protected $fillable = [
        'alumno_id',
        'representante_anterior_id',
        'representante_nuevo_id',
        'registrado_por',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function anterior()
    {
        return $this->belongsTo(User::class, 'representante_anterior_id');
    }

    public function nuevo()
    {
        return $this->belongsTo(User::class, 'representante_nuevo_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
