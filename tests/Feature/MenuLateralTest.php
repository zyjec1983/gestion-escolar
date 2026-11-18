<?php

namespace Tests\Feature;

use App\Models\Institucion;
use App\Models\User;
use Tests\TestCase;

/**
 * Resaltado del menú lateral.
 *
 * Qué hace: recorre las vistas principales de cada perfil y comprueba que los
 * grupos colapsables (Contenido institucional, DECE, Panel directivo,
 * Observaciones, etc.) nunca queden marcados como activos. Solo el enlace hijo
 * seleccionado debe resaltarse, tal como ocurre en el Panel académico.
 * Conecta con: resources/views/layouts/principal.blade.php.
 * Rutas: paneles directivos, DECE, inspección, secretaría y administración.
 */
class MenuLateralTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Institucion::factory()->create();
    }

    /** Crea el usuario del perfil indicado (INSPECCION no tiene estado propio). */
    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create(['rol' => $rol]);
    }

    /**
     * Vistas representativas de cada perfil y el submenú que debe abrirse.
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function casos(): array
    {
        return [
            // Administración: Apariencia del sistema.
            ['ADMIN', '/admin/configuracion', 'submenuAparienciaSistema'],
            ['ADMIN', '/admin/configuracion/carrusel', 'submenuAparienciaSistema'],
            ['ADMIN', '/admin/configuracion/carrusel-login', 'submenuAparienciaSistema'],
            ['ADMIN', '/admin/configuracion/apariencia', 'submenuAparienciaSistema'],

            // DECE: cada módulo resalta solo su enlace.
            ['DECE', '/dece/expedientes', 'submenuDece'],
            ['DECE', '/dece/atenciones', 'submenuDece'],
            ['DECE', '/dece/citas', 'submenuDece'],
            ['DECE', '/dece/acuerdos', 'submenuDece'],
            ['DECE', '/dece/nee', 'submenuDece'],
            ['DECE', '/dece/talleres', 'submenuDece'],
            ['DECE', '/dece/derivaciones', 'submenuDece'],
            ['DECE', '/dece/reportes', 'submenuDece'],

            // Rectorado y Vicerrectorado: Panel directivo y módulos compartidos.
            ['RECTORA', '/rector', 'submenuPanelDirectivo'],
            ['RECTORA', '/rector/rendimiento', 'submenuPanelDirectivo'],
            ['RECTORA', '/rector/estudiantes-riesgo', 'submenuPanelDirectivo'],
            ['RECTORA', '/dece/expedientes', 'submenuDece'],
            ['RECTORA', '/inspeccion/observaciones', 'submenuObservaciones'],
            ['VICERRECTORA', '/vicerrector', 'submenuPanelDirectivo'],
            ['VICERRECTORA', '/vicerrector/rendimiento', 'submenuPanelDirectivo'],
            ['VICERRECTORA', '/vicerrector/estudiantes-riesgo', 'submenuPanelDirectivo'],

            // Inspección: asistencia, citas y observaciones.
            ['INSPECCION', '/inspeccion/asistencia', 'submenuAsistencia'],
            ['INSPECCION', '/inspeccion/asistencia/estadisticas', 'submenuAsistencia'],
            ['INSPECCION', '/inspeccion/citas/agendar', 'submenuCitasInspeccion'],
            ['INSPECCION', '/inspeccion/citas/historial', 'submenuCitasInspeccion'],
            ['INSPECCION', '/inspeccion/observaciones', 'submenuObservaciones'],
            ['INSPECCION', '/inspeccion/observaciones/estadisticas', 'submenuObservaciones'],
            ['INSPECCION', '/inspeccion/observaciones/tipos', 'submenuObservaciones'],

            // Secretaría: matrículas, académico y citas.
            ['SECRETARIA', '/admin/pre-registros', 'submenuSecretariaMatriculas'],
            ['SECRETARIA', '/inspeccion/observaciones/estadisticas', 'submenuSecretariaAcademico'],
            ['SECRETARIA', '/inspeccion/citas/agendar', 'submenuSecretariaCitas'],
        ];
    }

    /** @test */
    public function los_grupos_del_menu_nunca_se_resaltan_solo_sus_hijos()
    {
        $usuarios = [];

        foreach ($this->casos() as [$rol, $url, $submenu]) {
            $usuario = $usuarios[$rol] ??= $this->usuarioConRol($rol);

            $response = $this->actingAs($usuario)->get($url);
            $response->assertOk();

            $html = $response->getContent();

            // El submenú correspondiente permanece abierto para no perder el contexto.
            $this->assertStringContainsString(
                'collapse show" id="' . $submenu . '"',
                $html,
                "El submenú {$submenu} no se abrió al visitar {$url}."
            );

            // Ningún disparador colapsable (categoría padre) lleva la clase active.
            preg_match_all('/<a\b[^>]*data-bs-toggle="collapse"[^>]*>/i', $html, $disparadores);

            $this->assertNotEmpty(
                $disparadores[0],
                "El menú de {$url} no tiene grupos colapsables."
            );

            foreach ($disparadores[0] as $disparador) {
                $this->assertStringNotContainsString(
                    'active',
                    $disparador,
                    "Una categoría padre quedó resaltada al visitar {$url}: {$disparador}"
                );
            }

            // Exactamente un enlace hijo del submenú está resaltado.
            preg_match_all('/<a\b[^>]*class="[^"]*nav-link text-white small py-1 active[^"]*"[^>]*>/i', $html, $activos);

            $this->assertCount(
                1,
                $activos[0],
                "Se esperaba un solo enlace hijo activo al visitar {$url}, se encontraron " . count($activos[0]) . '.'
            );
        }
    }
}
