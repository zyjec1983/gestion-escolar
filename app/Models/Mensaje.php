<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    protected $fillable = [
        'asunto', 'cuerpo_contenido', 'carpeta',
        'emisor_id', 'emisor_type', 'destinatario_id',
        'contexto_estudiante_id', 'leido', 'fecha_envio',
    ];

    protected function casts(): array
    {
        return [
            'leido' => 'boolean',
            'fecha_envio' => 'datetime',
        ];
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
