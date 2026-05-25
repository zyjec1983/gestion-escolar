<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circular extends Model
{
    protected $table = 'circulares';

    protected $fillable = ['titulo', 'numero', 'fecha_publicacion', 'archivo_url'];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date',
        ];
    }
}
