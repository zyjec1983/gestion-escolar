<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Curso extends Model
{
    use HasFactory;

    /**
     * Orden en el que se muestran los cursos. OCTAVO, NOVENO y DECIMO son
     * basico; PRIMERO, SEGUNDO y TERCERO son bachillerato. El nombre guardado
     * es texto libre ("SEGUNDO DE BACHILLERATO - CIENCIAS"), asi que el orden
     * no sale de un ORDER BY alfabetico y se arma con un CASE sobre estas
     * palabras clave. Un curso que no contenga ninguna va al final.
     */
    public const ORDENES = [
        'OCTAVO' => 8,
        'NOVENO' => 9,
        'DECIMO' => 10,
        'PRIMERO' => 11,
        'SEGUNDO' => 12,
        'TERCERO' => 13,
    ];

    /**
     * Grados y como se escriben en el formulario. El formulario muestra el
     * grado, no el nombre completo del curso, porque los 320 alumnos ya estan
     * matriculados y los cursos no se pueden renombrar sin reasignarlos.
     */
    public const GRADOS = [
        'OCTAVO' => 'Octavo',
        'NOVENO' => 'Noveno',
        'DECIMO' => 'Décimo',
        'PRIMERO' => 'Primero',
        'SEGUNDO' => 'Segundo',
        'TERCERO' => 'Tercero',
    ];

    /**
     * Grados en los que la autoridad define una especializacion.
     */
    public const GRADOS_CON_ESPECIALIZACION = ['SEGUNDO', 'TERCERO'];

    /**
     * Oferta de especializaciones de bachillerato tecnico. Por ahora esta
     * quemada aqui y no en una tabla: cuando las autoridades cambien la
     * oferta, se edita esta constante.
     */
    public const ESPECIALIZACIONES = [
        'Ciencias',
        'Comercio',
        'Contabilidad',
        'Físico-Matemática',
        'Informática',
    ];

    protected $fillable = ['nombre', 'codigo'];

    public function scopeEnOrdenEscolar(Builder $query): Builder
    {
        $caso = collect(self::ORDENES)
            ->map(fn (int $orden, string $clave) => "WHEN UPPER(nombre) LIKE '%{$clave}%' THEN {$orden}")
            ->implode(' ');

        return $query->orderByRaw("CASE {$caso} ELSE 99 END")->orderBy('nombre');
    }

    /**
     * Clave del grado al que pertenece el curso ("OCTAVO", "SEGUNDO"...) o
     * null si el nombre no corresponde a un grado conocido.
     */
    public function grado(): ?string
    {
        $nombre = Str::upper(Str::ascii((string) $this->nombre));

        foreach (array_keys(self::GRADOS) as $clave) {
            if (Str::contains($nombre, $clave)) {
                return $clave;
            }
        }

        return null;
    }

    /**
     * Compara especializaciones sin que importen los acentos, los espacios ni
     * los guiones: "FISICO MATEMATICA" y "Físico-Matemática" son la misma.
     */
    public static function normalizarEspecializacion(string $valor): string
    {
        return Str::of(Str::upper(Str::ascii($valor)))
            ->replace([' ', '-'], '')
            ->toString();
    }

    /**
     * Especializacion del curso, tal y como el propio nombre la declara
     * ("SEGUNDO DE BACHILLERATO - CIENCIAS"), devuelta con la forma del
     * catalogo. Null si el curso no trae especializacion o si esta no
     * pertenece a la oferta vigente.
     */
    public function especializacion(): ?string
    {
        if (! Str::contains((string) $this->nombre, ' - ')) {
            return null;
        }

        $declarada = Str::of(Str::after((string) $this->nombre, ' - '))->squish()->toString();

        foreach (self::ESPECIALIZACIONES as $delCatalogo) {
            if (self::normalizarEspecializacion($declarada) === self::normalizarEspecializacion($delCatalogo)) {
                return $delCatalogo;
            }
        }

        return null;
    }

    /**
     * True en Segundo y Tercero, que son los grados donde el alumno elige
     * especializacion.
     */
    public function pideEspecializacion(): bool
    {
        return in_array($this->grado(), self::GRADOS_CON_ESPECIALIZACION, true);
    }

    /**
     * Cursos agrupados por grado, en el orden en que deben mostrarse. Cada
     * grado trae la lista de cursos reales que lo representan, con la
     * especializacion de cada uno ya normalizada al catalogo.
     *
     * @return array<string, array{etiqueta: string, cursos: array<int, array{id: int, especializacion: string|null}>}>
     */
    public static function porGrado()
    {
        $agrupados = [];

        foreach (static::enOrdenEscolar()->get() as $curso) {
            $grado = $curso->grado();

            if ($grado === null) {
                continue;
            }

            $agrupados[$grado]['cursos'][] = [
                'id' => $curso->id,
                'especializacion' => $curso->especializacion(),
            ];
        }

        $lista = [];

        foreach (self::GRADOS as $clave => $etiqueta) {
            $lista[$clave] = [
                'etiqueta' => $etiqueta,
                'cursos' => $agrupados[$clave]['cursos'] ?? [],
            ];
        }

        return $lista;
    }

    public function clasesDocentes()
    {
        return $this->hasMany(ClaseDocente::class);
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }
}
