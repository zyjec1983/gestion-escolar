<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ExpedienteDocumento extends Model
{
    protected $table = 'expediente_documentos';

    protected $fillable = [
        'expediente_id', 'tipo_documento', 'nombre_original', 'ruta',
        'mime', 'tamano', 'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
        ];
    }

    public function expediente()
    {
        return $this->belongsTo(ExpedienteTraslado::class, 'expediente_id');
    }

    public function subidor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }

    public function pesoLegible(): string
    {
        $bytes = max(0, (int) $this->tamano);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return $bytes.' B';
    }

    public function esImagen(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
