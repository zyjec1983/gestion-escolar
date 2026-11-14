<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Documento del Centro de Descargas.
 *
 * Qué hace: guarda título, descripción, fecha, peso y archivo de cada
 * documento oficial, agrupado por categoría.
 * Conecta con: Admin\SitioController y portal/descargas.blade.php.
 * Rutas: CRUD /admin/sitio/descargas.
 */
class DescargaDocumento extends Model
{
    protected $table = 'descargas_documentos';

    /** Categorías visibles en el portal. */
    public const CATEGORIAS = [
        'horarios' => 'Horarios',
        'cronograma' => 'Cronograma escolar',
        'convivencia' => 'Código de convivencia',
        'uniformes' => 'Uniformes',
        'varios' => 'Varios',
    ];

    protected $fillable = [
        'categoria', 'titulo', 'descripcion', 'fecha_texto', 'peso',
        'archivo_path', 'orden', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** URL pública del archivo adjunto. */
    public function archivoUrl(): ?string
    {
        return $this->archivo_path ? Storage::url($this->archivo_path) : null;
    }
}
