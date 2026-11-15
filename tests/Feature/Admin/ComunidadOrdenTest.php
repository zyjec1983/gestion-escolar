<?php

namespace Tests\Feature\Admin;

use App\Models\ComunidadMiembro;
use App\Models\Institucion;
use App\Models\User;
use Tests\TestCase;

/**
 * Orden de los grupos de la Comunidad Educativa.
 *
 * Qué hace: comprueba que el administrador pueda arrastrar los bloques de área,
 * que el orden se guarde y que el portal lo respete. También valida que un área
 * nueva se agregue al final y que otros roles no puedan reordenar.
 * Conecta con: Admin\ComunidadController, ComunidadMiembro y PortalController.
 * Rutas: POST /admin/comunidad/areas/reordenar y GET /comunidad.
 */
class ComunidadOrdenTest extends TestCase
{
    private function admin(): User
    {
        $institucion = Institucion::factory()->create();

        return User::factory()->admin()->create(['institucion_id' => $institucion->id]);
    }

    private function miembro(string $area, int $ordenArea, string $nombre = 'Integrante'): ComunidadMiembro
    {
        return ComunidadMiembro::create([
            'cargo' => 'Cargo',
            'nombre' => $nombre,
            'area' => $area,
            'area_orden' => $ordenArea,
        ]);
    }

    /** @test */
    public function el_admin_reordena_los_grupos_y_el_portal_respeta_el_orden()
    {
        $admin = $this->admin();

        $this->miembro('Rectorado', 0, 'Ana Rectora');
        $this->miembro('Matemáticas', 1, 'Luis Docente');
        $this->miembro('DECE', 2, 'Eva Psicóloga');

        $this->actingAs($admin)
            ->postJson(route('admin.comunidad.areas.reordenar'), [
                'areas' => ['DECE', 'Rectorado', 'Matemáticas'],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, ComunidadMiembro::where('area', 'DECE')->value('area_orden'));
        $this->assertSame(1, ComunidadMiembro::where('area', 'Rectorado')->value('area_orden'));
        $this->assertSame(2, ComunidadMiembro::where('area', 'Matemáticas')->value('area_orden'));

        $this->assertDatabaseHas('historial_cambios', [
            'modulo' => 'Comunidad educativa',
            'accion' => 'reordenar',
        ]);

        $this->get(route('portal.comunidad'))
            ->assertOk()
            ->assertSeeInOrder(['DECE', 'Rectorado', 'Matemáticas']);
    }

    /** @test */
    public function un_area_nueva_se_coloca_al_final()
    {
        $admin = $this->admin();

        $this->miembro('Rectorado', 0);
        $this->miembro('Matemáticas', 1);

        $this->actingAs($admin)->post(route('admin.comunidad.store'), [
            'cargo' => 'Entrenador',
            'nombre' => 'Carlos Deportes',
            'area' => 'Deportes',
        ])->assertRedirect(route('admin.comunidad.index'));

        $this->assertSame(2, ComunidadMiembro::where('area', 'Deportes')->value('area_orden'));
    }

    /** @test */
    public function los_demás_roles_no_pueden_reordenar_los_grupos()
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->postJson(route('admin.comunidad.areas.reordenar'), [
                'areas' => ['Rectorado'],
            ])
            ->assertForbidden();
    }
}
