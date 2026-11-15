<?php

namespace Tests\Feature\Admin;

use App\Models\Institucion;
use App\Models\ListaUtil;
use App\Models\User;
use App\Models\UtilItem;
use Tests\TestCase;

/**
 * Listas de útiles escolares por nivel o curso.
 *
 * Qué hace: valida el flujo del editor tipo Excel: crear el nivel o curso,
 * guardar las filas de cantidad/artículo/materia, terminar la lista, reabrirla
 * para edición, eliminar la lista y publicarla en el portal. También comprueba
 * que los artículos antiguos sin lista sigan visibles en /utiles.
 * Conecta con: Admin\SitioController, PortalController@utiles, ListaUtil,
 * UtilItem y admin/sitio/_utiles.blade.php.
 * Rutas: /admin/sitio/utiles/listas y /utiles.
 */
class UtilesListasTest extends TestCase
{
    private function admin(): User
    {
        $institucion = Institucion::factory()->create();

        return User::factory()->admin()->create(['institucion_id' => $institucion->id]);
    }

    /** @test */
    public function el_admin_crea_una_lista_para_un_nivel_o_curso()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.sitio.utiles.listas.store'), ['nombre' => '  Octavo  '])
            ->assertRedirect();

        $lista = ListaUtil::firstOrFail();

        $this->assertSame('Octavo', $lista->nombre);
        $this->assertFalse($lista->finalizada);
    }

    /** @test */
    public function el_admin_guarda_las_filas_y_termina_la_lista()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.sitio.utiles.listas.store'), [
            'nombre' => '1ro Ciencias',
        ]);

        $lista = ListaUtil::firstOrFail();

        $this->actingAs($admin)->put(route('admin.sitio.utiles.listas.update', $lista), [
            'nombre' => '1ro Ciencias',
            'finalizada' => '1',
            'items' => [
                ['cantidad' => '2', 'articulo' => 'Cuaderno universitario', 'materia' => 'Matemáticas'],
                ['cantidad' => '1', 'articulo' => 'Calculadora científica', 'materia' => 'Física'],
                ['cantidad' => '3', 'articulo' => '', 'materia' => 'Ignorada'],
            ],
        ])->assertRedirect(route('admin.sitio.index', ['tab' => 'utiles', 'lista' => $lista->id]));

        $lista->refresh();

        $this->assertTrue($lista->finalizada);
        $this->assertCount(2, $lista->items);

        $this->assertDatabaseHas('utiles_items', [
            'lista_id' => $lista->id,
            'nivel' => 'lista',
            'nombre' => 'Cuaderno universitario',
            'cantidad' => '2',
            'materia' => 'Matemáticas',
            'orden' => 1,
        ]);

        $this->assertDatabaseHas('utiles_items', [
            'lista_id' => $lista->id,
            'nombre' => 'Calculadora científica',
            'materia' => 'Física',
            'orden' => 2,
        ]);

        $this->assertSame(3, $lista->totalUnidades());
    }

    /** @test */
    public function no_se_puede_terminar_una_lista_sin_articulos()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.sitio.utiles.listas.store'), [
            'nombre' => 'Décimo',
        ]);

        $lista = ListaUtil::firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.sitio.utiles.listas.update', $lista), [
                'nombre' => 'Décimo',
                'finalizada' => '1',
                'items' => [
                    ['cantidad' => '1', 'articulo' => '', 'materia' => ''],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertFalse($lista->refresh()->finalizada);
        $this->assertCount(0, $lista->items);
    }

    /** @test */
    public function el_nombre_de_la_lista_debe_ser_unico()
    {
        $admin = $this->admin();

        ListaUtil::create(['nombre' => 'Octavo', 'finalizada' => true, 'orden' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.sitio.utiles.listas.store'), ['nombre' => 'Octavo'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, ListaUtil::count());
    }

    /** @test */
    public function el_admin_reabre_y_elimina_una_lista()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.sitio.utiles.listas.store'), [
            'nombre' => '2do Bachillerato',
        ]);

        $lista = ListaUtil::firstOrFail();

        $this->actingAs($admin)->put(route('admin.sitio.utiles.listas.update', $lista), [
            'nombre' => '2do Bachillerato',
            'finalizada' => '1',
            'items' => [
                ['cantidad' => '1', 'articulo' => 'Mandil de laboratorio', 'materia' => 'Química'],
            ],
        ]);

        $this->assertTrue($lista->refresh()->finalizada);

        $this->actingAs($admin)
            ->post(route('admin.sitio.utiles.listas.reabrir', $lista))
            ->assertRedirect(route('admin.sitio.index', ['tab' => 'utiles', 'lista' => $lista->id]));

        $this->assertFalse($lista->refresh()->finalizada);

        $this->actingAs($admin)
            ->delete(route('admin.sitio.utiles.listas.destroy', $lista))
            ->assertRedirect(route('admin.sitio.index', ['tab' => 'utiles']));

        $this->assertDatabaseMissing('listas_utiles', ['id' => $lista->id]);
        $this->assertDatabaseMissing('utiles_items', ['lista_id' => $lista->id]);
    }

    /** @test */
    public function el_portal_muestra_las_listas_nuevas_y_los_articulos_antiguos()
    {
        $lista = ListaUtil::create(['nombre' => 'Octavo', 'finalizada' => true, 'orden' => 1]);

        UtilItem::create([
            'lista_id' => $lista->id,
            'nivel' => 'lista',
            'nombre' => 'Compás de precisión',
            'cantidad' => '1',
            'materia' => 'Geometría',
        ]);

        // Artículo anterior a las listas: debe seguir visible por el respaldo.
        UtilItem::create([
            'nivel' => 'media',
            'nombre' => 'Cuaderno antiguo',
            'cantidad' => '4',
            'area' => 'Tronco común',
        ]);

        $this->get(route('portal.utiles'))
            ->assertOk()
            ->assertSee('Octavo')
            ->assertSee('Compás de precisión')
            ->assertSee('Geometría')
            ->assertSee('Cuaderno antiguo')
            ->assertSee('Básica Media');
    }

    /** @test */
    public function un_usuario_sin_perfil_admin_no_gestiona_listas_de_utiles()
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)
            ->post(route('admin.sitio.utiles.listas.store'), ['nombre' => 'Octavo'])
            ->assertForbidden();

        $this->assertSame(0, ListaUtil::count());
    }
}
