<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    protected $fillable = [
        'asunto', 'cuerpo_contenido', 'carpeta',
        'emisor_id', 'emisor_type', 'destinatario_id',
        'hilo_id', 'contexto_estudiante_id', 'leido', 'es_circular', 'fecha_envio',
        'borrado_emisor', 'borrado_destinatario',
    ];

    protected function casts(): array
    {
        return [
            'leido' => 'boolean',
            'es_circular' => 'boolean',
            'fecha_envio' => 'datetime',
            'hilo_id' => 'integer',
            'borrado_emisor' => 'boolean',
            'borrado_destinatario' => 'boolean',
        ];
    }

    public function hilo()
    {
        return $this->belongsTo(Mensaje::class, 'hilo_id');
    }

    public function emisor()
    {
        return $this->morphTo();
    }

    public function destinatario()
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }

    public function contextoEstudiante()
    {
        return $this->belongsTo(Alumno::class, 'contexto_estudiante_id');
    }

    public function archivosAdjuntos()
    {
        return $this->morphMany(ArchivoAdjunto::class, 'attachable');
    }
}
