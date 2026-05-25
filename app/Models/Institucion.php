<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    use HasFactory;

    protected $table = 'instituciones';

    protected $fillable = ['nombre', 'subtitulo', 'plataforma_proveedor', 'pre_registro_activo', 'carousel_interval'];

    protected function casts(): array
    {
        return [
            'pre_registro_activo' => 'boolean',
            'carousel_interval' => 'integer',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
