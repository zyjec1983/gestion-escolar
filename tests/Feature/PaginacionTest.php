<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * Paginación compartida de los listados.
 *
 * Qué hace: comprueba que Laravel use la vista Bootstrap 5 (sin los SVG
 * gigantes de Tailwind) y que los textos estén en español.
 * Conecta con: AppServiceProvider y resources/views/vendor/pagination.
 */
class PaginacionTest extends TestCase
{
    /** @test */
    public function los_listados_usan_paginacion_bootstrap_y_textos_en_espanol()
    {
        $paginador = new LengthAwarePaginator(range(1, 15), 40, 15, 1, [
            'path' => '/dece/expedientes',
        ]);

        $html = $paginador->links()->toHtml();

        $this->assertStringContainsString('Mostrando', $html);
        $this->assertStringContainsString('resultados', $html);
        $this->assertStringContainsString('class="pagination pagination-sm', $html);
        $this->assertStringContainsString('Anterior', $html);
        $this->assertStringContainsString('Siguiente', $html);
        $this->assertStringNotContainsString('Showing', $html);
        $this->assertStringNotContainsString('<svg', $html);

        // También cuando una vista pide explícitamente la vista Bootstrap 5.
        $explicita = $paginador->links('pagination::bootstrap-5')->toHtml();

        $this->assertStringContainsString('Mostrando', $explicita);
        $this->assertStringNotContainsString('Showing', $explicita);
    }
}
