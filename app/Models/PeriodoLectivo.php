<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoLectivo extends Model
{
    use HasFactory;

    protected $table = 'periodos_lectivos';

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'periodo_id');
    }
}
