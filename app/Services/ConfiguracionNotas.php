<?php

namespace App\Services;

use App\Models\Institucion;
use App\Models\VentanaNota;
use Illuminate\Http\Request;

/**
 * Unica fuente de verdad para el sistema de evaluacion, los parciales y la
 * normalizacion de notas. La usan el modulo docente y el de correcciones
 * extraordinarias para que ambos hablen exactamente el mismo idioma.
 */
class ConfiguracionNotas
{
    public const CAMPOS_NOTA = [
        'fii', 'ffi', 'fig', 'ffg',
        'proyecto_integrador', 'evaluacion_parcial',
        'nota_examen',
    ];

    public const CAMPOS_PARCIAL = [
        'fii', 'ffi', 'fig', 'ffg',
        'proyecto_integrador', 'evaluacion_parcial',
    ];

    public function sistema(): string
    {
        return Institucion::first()?->sistema_evaluacion ?? 'quimestre';
    }

    /**
     * @return array<int, array{parcial: int, label: string, grupo: string}>
     */
    public function opciones(?string $sistema = null): array
    {
        if (($sistema ?? $this->sistema()) === 'trimestre') {
            return [
                ['parcial' => 1, 'label' => 'Trimestre 1', 'grupo' => 'Trimestres'],
                ['parcial' => 2, 'label' => 'Trimestre 2', 'grupo' => 'Trimestres'],
                ['parcial' => 3, 'label' => 'Trimestre 3', 'grupo' => 'Trimestres'],
            ];
        }

        return [
            ['parcial' => 1, 'label' => 'Parcial 1', 'grupo' => '1er Quimestre'],
            ['parcial' => 2, 'label' => 'Parcial 2', 'grupo' => '1er Quimestre'],
            ['parcial' => 3, 'label' => 'Examen Quimestral', 'grupo' => '1er Quimestre'],
            ['parcial' => 4, 'label' => 'Parcial 1', 'grupo' => '2do Quimestre'],
            ['parcial' => 5, 'label' => 'Parcial 2', 'grupo' => '2do Quimestre'],
            ['parcial' => 6, 'label' => 'Examen Quimestral', 'grupo' => '2do Quimestre'],
        ];
    }

    /** @return array<int, int> */
    public function parcialesValidos(?string $sistema = null): array
    {
        return array_column($this->opciones($sistema), 'parcial');
    }

    public function tipoDeParcial(int $parcial, ?string $sistema = null): string
    {
        return ($sistema ?? $this->sistema()) === 'quimestre' && in_array($parcial, [3, 6], true)
            ? 'examen'
            : 'parcial';
    }

    public function etiquetaParcial(int $parcial, ?string $sistema = null): string
    {
        foreach ($this->opciones($sistema) as $opcion) {
            if ($opcion['parcial'] === $parcial) {
                return $opcion['label'];
            }
        }

        return 'Parcial '.$parcial;
    }

    /** @return array<int, string> */
    public function camposParaTipo(string $tipo): array
    {
        return $tipo === 'examen' ? ['nota_examen'] : self::CAMPOS_PARCIAL;
    }

    /**
     * Convierte comas decimales a punto antes de validar. Asi un 9,5 escrito
     * a mano no es rechazado por la regla numeric.
     */
    public function normalizarDecimales(Request $request): void
    {
        $notas = $request->input('notas');

        if (! is_array($notas)) {
            return;
        }

        $formateadas = [];

        foreach ($notas as $alumnoId => $item) {
            if (is_array($item)) {
                foreach (self::CAMPOS_NOTA as $campo) {
                    if (isset($item[$campo]) && is_string($item[$campo])) {
                        $item[$campo] = str_replace(',', '.', trim($item[$campo]));
                    }
                }
            }

            $formateadas[$alumnoId] = $item;
        }

        $request->merge(['notas' => $formateadas]);
    }

    /** Normaliza un unico valor recibido por formulario. */
    public function decimal(mixed $valor): mixed
    {
        return is_string($valor) ? str_replace(',', '.', trim($valor)) : $valor;
    }

    /**
     * Indica si el parcial admite carga de notas en este momento.
     *
     * Qué hace: si no existe ninguna ventana configurada para el parcial se
     * mantiene el comportamiento anterior (carga permitida). Cuando sí existe,
     * solo se permite guardar si hay una ventana activa y dentro de sus fechas.
     * Conecta con: App\Models\VentanaNota y Docente\NotaController@store.
     */
    public function ventanaAbierta(int $parcial): bool
    {
        $existe = VentanaNota::paraParcial($parcial)->exists();

        if (! $existe) {
            return true;
        }

        return VentanaNota::paraParcial($parcial)->vigente()->exists();
    }

    /** Mensaje que se muestra al docente cuando la ventana está cerrada. */
    public function mensajeVentanaCerrada(int $parcial): string
    {
        return 'La carga de notas para '.$this->etiquetaParcial($parcial)
            .' no está habilitada en este momento. Consulte las fechas con Secretaría.';
    }
}
