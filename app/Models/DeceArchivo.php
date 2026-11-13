<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Evidencia o respaldo documental del DECE. Es polimorfico para servir a
 * atenciones, acuerdos, NEE, derivaciones y talleres con una sola tabla.
 */
class DeceArchivo extends Model
{
    protected $table = 'dece_archivos';

    protected $fillable = [
        'archivoable_type', 'archivoable_id', 'nombre_original', 'ruta',
        'mime', 'tamano', 'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
        ];
    }

    public function archivoable()
    {
        return $this->morphTo();
    }

    public function subidor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }

    public function esImagen(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function tamanoLegible(): string
    {
        $bytes = max(0, (int) $this->tamano);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, '.', '').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, '.', '').' KB';
        }

        return $bytes.' B';
    }
}
