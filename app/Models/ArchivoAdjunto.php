<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoAdjunto extends Model
{
    protected $table = 'archivos_adjuntos';

    protected $fillable = ['attachable_type', 'attachable_id', 'nombre', 'url'];

    public function attachable()
    {
        return $this->morphTo();
    }
}
