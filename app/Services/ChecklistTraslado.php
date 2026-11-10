<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\ExpedienteTraslado;
use App\Models\PeriodoLectivo;

/**
 * Reglas del check-list documental de traslados. Centraliza RF-02, RN-01 y
 * el requisito adicional de octavo (certificado de terminacion de 7mo).
 */
class ChecklistTraslado
{
    public const LABELS = [
        'pase_ano' => 'Certificado de matrícula / pase de año',
        'actas_promocion' => 'Actas o certificados de promoción de años anteriores',
        'historial_calificaciones' => 'Historial de calificaciones acumulado',
        'certificado_conducta' => 'Certificado de conducta / disciplina',
        'paz_salvo_origen' => 'Paz y salvo financiero / académico de la institución de origen',
        'libreta_parcial' => 'Libreta o informe parcial de calificaciones del año en curso',
        'registro_asistencia' => 'Registro de asistencia acumulada (porcentaje / horas)',
        'ficha_retiro' => 'Ficha de retiro o desvinculación del sistema origen',
        'dece_nee' => 'Ficha o expediente psicopedagógico (DECE / NEE) si aplica',
        'certificado_terminacion_7mo' => 'Certificado de terminación de 7mo / EGB Media',
        'solicitud_retiro' => 'Solicitud de retiro firmada por el representante',
        'autorizacion_representante' => 'Autorización e identidad del representante legal',
    ];

    public const DESCRIPCIONES = [
        'pase_ano' => 'Documento oficial que acredita la promoción al nivel que cursa.',
        'actas_promocion' => 'Actas de grados anteriores debidamente legalizadas.',
        'historial_calificaciones' => 'Concentrado de calificaciones de años previos.',
        'certificado_conducta' => 'Constancia de comportamiento emitida por la institución de origen.',
        'paz_salvo_origen' => 'Constancia de no adeudar a la institución de origen.',
        'libreta_parcial' => 'Obligatoria cuando el traslado ocurre con el período lectivo activo.',
        'registro_asistencia' => 'Obligatorio cuando el traslado ocurre con el período lectivo activo.',
        'ficha_retiro' => 'Prueba de la desvinculación del estudiante en la institución de origen.',
        'dece_nee' => 'Adjuntar cuando el estudiante tenga necesidades educativas especiales.',
        'certificado_terminacion_7mo' => 'Requisito adicional para ingresos a octavo año de EGB.',
        'solicitud_retiro' => 'Documento firmado que autoriza el retiro del estudiante.',
        'autorizacion_representante' => 'Copia de cédula y autorización del representante legal.',
    ];

    public function requisitos(
        string $tipo,
        ?string $momento = null,
        ?Curso $curso = null,
        ?bool $periodoVigente = null
    ): array {
        if ($tipo === ExpedienteTraslado::TIPO_SALIDA) {
            return $this->requisitosSalida();
        }

        $periodoVigente ??= $this->periodoVigente();

        return $momento === ExpedienteTraslado::MOMENTO_TRANSICURSO
            ? $this->requisitosTranscurso($curso, $periodoVigente)
            : $this->requisitosInicio($curso);
    }

    /** @return array<int, array{clave: string, label: string, obligatorio: bool, descripcion: string}> */
    private function requisitosInicio(?Curso $curso): array
    {
        $items = [
            $this->item('pase_ano', true),
            $this->item('actas_promocion', true),
            $this->item('historial_calificaciones', true),
            $this->item('certificado_conducta', true),
            $this->item('paz_salvo_origen', true),
        ];

        return $this->agregarOctavo($items, $curso);
    }

    /** @return array<int, array{clave: string, label: string, obligatorio: bool, descripcion: string}> */
    private function requisitosTranscurso(?Curso $curso, bool $periodoVigente): array
    {
        $items = [
            // RN-01: sin periodo activo estos dos documentos no se exigen.
            $this->item('libreta_parcial', $periodoVigente),
            $this->item('registro_asistencia', $periodoVigente),
            $this->item('ficha_retiro', true),
            $this->item('actas_promocion', true),
            $this->item('paz_salvo_origen', true),
            $this->item('dece_nee', false),
        ];

        return $this->agregarOctavo($items, $curso);
    }

    /** @return array<int, array{clave: string, label: string, obligatorio: bool, descripcion: string}> */
    private function requisitosSalida(): array
    {
        return [
            $this->item('solicitud_retiro', true),
            $this->item('autorizacion_representante', true),
            $this->item('certificado_conducta', false),
        ];
    }

    private function agregarOctavo(array $items, ?Curso $curso): array
    {
        if ($curso?->grado() === 'OCTAVO') {
            $items[] = $this->item('certificado_terminacion_7mo', true);
        }

        return $items;
    }

    private function item(string $clave, bool $obligatorio): array
    {
        return [
            'clave' => $clave,
            'label' => self::LABELS[$clave] ?? $clave,
            'obligatorio' => $obligatorio,
            'descripcion' => self::DESCRIPCIONES[$clave] ?? '',
        ];
    }

    public function periodoVigente(): bool
    {
        $periodo = PeriodoLectivo::where('activo', true)->first();

        if (! $periodo?->fecha_inicio || ! $periodo->fecha_fin) {
            return false;
        }

        $hoy = now()->toDateString();

        return $hoy >= $periodo->fecha_inicio->toDateString()
            && $hoy <= $periodo->fecha_fin->toDateString();
    }

    /** @return array<int, string> */
    public function clavesSubidas(ExpedienteTraslado $expediente): array
    {
        $documentos = $expediente->relationLoaded('documentos')
            ? $expediente->documentos
            : $expediente->documentos()->get();

        return $documentos->pluck('tipo_documento')->unique()->values()->all();
    }

    /** @return array<int, array{clave: string, label: string, obligatorio: bool, descripcion: string}> */
    public function faltantes(ExpedienteTraslado $expediente): array
    {
        $subidas = $this->clavesSubidas($expediente);

        return array_values(array_filter(
            $this->requisitos(
                $expediente->tipo,
                $expediente->momento,
                $expediente->curso,
                $this->periodoVigente()
            ),
            fn (array $requisito) => $requisito['obligatorio'] && ! in_array($requisito['clave'], $subidas, true)
        ));
    }

    public function estaCompleto(ExpedienteTraslado $expediente): bool
    {
        return $this->faltantes($expediente) === [];
    }

    public function etiqueta(string $clave): string
    {
        return self::LABELS[$clave] ?? $clave;
    }
}
