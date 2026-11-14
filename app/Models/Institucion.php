<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Datos institucionales del centro.
 *
 * Qué hace: guarda nombre, subtítulo, logo, zona horaria, sistema de
 * evaluación y preferencias del carrusel. El nombre y el logo se editan en
 * Configuración → Institución y se comparten con todas las vistas.
 * Conecta con: AppServiceProvider, ConfiguracionController y layouts.
 * Ruta: POST /admin/configuracion/institucion.
 */
class Institucion extends Model
{
    use HasFactory;

    protected $table = 'instituciones';

    protected $fillable = [
        'nombre', 'subtitulo', 'logo_path', 'plataforma_proveedor',
        'sistema_evaluacion', 'zona_horaria', 'pre_registro_activo', 'carousel_interval',
    ];

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

    /**
     * URL pública del logo, con cache-busting para que el navegador y el
     * favicon tomen la versión nueva en cuanto el administrador la cambia.
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? time();

        return Storage::url($this->logo_path).'?v='.$version;
    }
}
