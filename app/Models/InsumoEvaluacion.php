<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoEvaluacion extends Model
{
    protected $table = 'insumos_evaluacion';

    protected $fillable = ['nombre', 'codigo', 'tipo', 'clase_docente_id', 'peso_porcentual'];

    protected function casts(): array
    {
        return [
            'peso_porcentual' => 'decimal:2',
        ];
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }
}
