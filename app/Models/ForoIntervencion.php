<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForoIntervencion extends Model
{
    /** @use HasFactory<\Database\Factories\ForoIntervencionFactory> */
    use HasFactory;

    protected $table = 'foro_intervenciones';

    protected $fillable = [
        'foro_id',
        'user_id',
        'tipo',
        'padre_id',
        'cuerpo',
    ];

    public function foro(): BelongsTo
    {
        return $this->belongsTo(Foro::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }
}