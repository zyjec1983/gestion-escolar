<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = [
        'alumno_id', 'tipo_observacion_id', 'user_id',
        'descripcion_texto', 'notificar_representante',
    ];

    protected function casts(): array
    {
        return [
            'notificar_representante' => 'boolean',
        ];
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function tipoObservacion()
    {
        return $this->belongsTo(TipoObservacion::class, 'tipo_observacion_id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
