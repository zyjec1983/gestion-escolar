<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materiales';

    protected $fillable = [
        'clase_docente_id', 'titulo', 'descripcion',
        'nombre_archivo', 'url', 'subido_por',
    ];

    public function claseDocente()
    {
        return $this->belongsTo(ClaseDocente::class);
    }

    public function subidor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}