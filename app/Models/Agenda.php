<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $fillable = [
        'clase_docente_id', 'tipo_actividad_id', 'insumo_evaluacion_id',
        'titulo_agenda', 'descripcion_instrucciones_html',
        'fecha_inicio', 'fecha_entrega_limite', 'enviar_a_todos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_entrega_limite' => 'datetime',
            'enviar_a_todos' => 'boolean',
        ];
    }

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function tipoActividad()
    {
        return $this->belongsTo(TipoActividad::class);
    }

    public function insumoEvaluacion()
    {
        return $this->belongsTo(InsumoEvaluacion::class);
    }

    public function archivosAdjuntos()
    {
        return $this->morphMany(ArchivoAdjunto::class, 'attachable');
    }
}
