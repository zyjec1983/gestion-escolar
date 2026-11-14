<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Imagen del carrusel público o del login.
 *
 * Qué hace: guarda título, subtítulo, imagen, orden y ubicación. El campo
 * `ubicacion` permite tener carruseles distintos para el inicio y el login.
 * Conecta con: HeroSlideController y las vistas welcome/auth.login.
 * Rutas: GET / (inicio), GET /login y CRUD /admin/hero-slides.
 */
class HeroSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo', 'subtitulo', 'imagen_path', 'orden', 'activo', 'ubicacion', 'link',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** Ubicaciones válidas para un slide. */
    public const UBICACIONES = [
        'inicio' => 'Página de inicio',
        'login' => 'Inicio de sesión',
        'ambos' => 'Inicio y login',
    ];

    /**
     * Slides que deben mostrarse en una ubicación.
     *
     * Incluye los marcados como `ambos`; así un mismo slide puede reutilizarse
     * sin duplicarlo.
     */
    public function scopeParaUbicacion($query, string $ubicacion)
    {
        return $query->whereIn('ubicacion', [$ubicacion, 'ambos']);
    }
}
