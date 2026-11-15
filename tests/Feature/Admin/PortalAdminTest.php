<?php

namespace Tests\Feature\Admin;

use App\Models\Alumno;
use App\Models\Circular;
use App\Models\ComunidadMiembro;
use App\Models\DescargaDocumento;
use App\Models\HistoriaHito;
use App\Models\HistorialCambio;
use App\Models\Institucion;
use App\Models\ListaUtil;
use App\Models\Observacion;
use App\Models\SiteContent;
use App\Models\TipoObservacion;
use App\Models\User;
use App\Models\UtilItem;
use App\Models\VentanaNota;
use App\Support\PortalContenido;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Portal institucional y módulos administrables del rol ADMIN.
 *
 * Qué hace: valida que el sitio público muestre los contenidos cargados desde
 * el panel y que Administración pueda gestionar textos, comunidad, historia,
 * útiles, descargas, observaciones y ventanas de notas.
 * Conecta con: PortalController, Admin\SitioController, ComunidadController,
 * ObservacionAdminController, VentanaNotaController y welcome.blade.php.
 * Rutas: GET /, /admin/sitio, /admin/comunidad, /admin/observaciones,
 *        /admin/ventanas-notas y /admin/historial.
 */
class PortalAdminTest extends TestCase
{
    private function admin(): User
    {
        $institucion = Institucion::factory()->create();

        return User::factory()->admin()->create(['institucion_id' => $institucion->id]);
    }

    /** @test */
    public function el_admin_configura_la_cabecera_del_pre_registro()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.sitio.index', ['tab' => 'preregistro']))
            ->assertOk()
            ->assertSee('Cabecera del formulario de pre-registro')
            ->assertSee('preregistro.cabecera_nombre', false);

        $this->actingAs($admin)
            ->post(route('admin.sitio.contenido', 'preregistro'), [
                'campos' => [
                    'preregistro.cabecera_nombre' => 'UNIDAD EDUCATIVA EDITADA',
                    'preregistro.titulo_solicitud' => 'MATRÍCULA EDITADA',
                ],
            ])
            ->assertRedirect(route('admin.sitio.index', ['tab' => 'preregistro']));

        $this->assertSame('UNIDAD EDUCATIVA EDITADA', PortalContenido::valor('preregistro.cabecera_nombre'));
        $this->assertSame('MATRÍCULA EDITADA', PortalContenido::valor('preregistro.titulo_solicitud'));
    }

    /** @test */
    public function el_portal_muestra_los_contenidos_administrables()
    {
        $institucion = Institucion::factory()->create(['nombre' => 'Unidad de Prueba']);
        SiteContent::guardar('historia.titulo', 'Historia Editada', [
            'tipo' => 'texto', 'grupo' => 'historia', 'etiqueta' => 'Título',
        ]);

        HistoriaHito::create(['anio' => '1998', 'titulo' => 'Hito de prueba']);
        ComunidadMiembro::create([
            'cargo' => 'Rectora', 'nombre' => 'Hermana Prueba', 'area' => 'Rectorado',
        ]);
        UtilItem::create(['nivel' => 'media', 'nombre' => 'Cuaderno de prueba']);
        DescargaDocumento::create([
            'categoria' => 'horarios', 'titulo' => 'Horario de prueba',
        ]);
        Circular::create([
            'titulo' => 'Circular de prueba', 'fecha_publicacion' => now(),
        ]);

        // Cada pestaña del portal tiene su propia vista.
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('Unidad de Prueba')
            ->assertSee('Circular de prueba');

        $this->get(route('portal.historia'))
            ->assertOk()
            ->assertSee('Historia Editada')
            ->assertSee('Hito de prueba');

        $this->get(route('portal.comunidad'))
            ->assertOk()
            ->assertSee('Comunidad Educativa')
            ->assertSee('Hermana Prueba');

        $this->get(route('portal.utiles'))
            ->assertOk()
            ->assertSee('Lista de Útiles Escolares')
            ->assertSee('Cuaderno de prueba');

        $this->get(route('portal.descargas'))
            ->assertOk()
            ->assertSee('Centro de Descargas')
            ->assertSee('Horario de prueba');

        $this->get(route('portal.circulares'))
            ->assertOk()
            ->assertSee('Circular de prueba');
    }

    /** @test */
    public function el_admin_guarda_los_textos_e_imagenes_del_sitio()
    {
        Storage::fake('public');

        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.sitio.contenido', 'institucion'), [
            'campos' => [
                'institucion.nombre_corto' => 'Colegio Franciscano',
                'institucion.mision' => 'Misión actualizada desde el panel.',
            ],
        ]);

        $response->assertRedirect(route('admin.sitio.index', ['tab' => 'textos']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('site_contents', [
            'clave' => 'institucion.nombre_corto',
            'valor' => 'Colegio Franciscano',
        ]);
        $this->assertDatabaseHas('site_contents', [
            'clave' => 'institucion.mision',
            'valor' => 'Misión actualizada desde el panel.',
        ]);
        $this->assertDatabaseHas('historial_cambios', ['modulo' => 'Sitio web']);
    }

    /** @test */
    public function el_admin_administra_la_comunidad_educativa()
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.comunidad.store'), [
            'cargo' => 'Docente de Matemáticas',
            'nombre' => 'Ana Pérez',
            'responsabilidad' => 'Área de Matemáticas',
            'area' => 'Matemáticas',
            'foto' => UploadedFile::fake()->image('ana.jpg', 300, 300),
        ])->assertRedirect(route('admin.comunidad.index'));

        $miembro = ComunidadMiembro::firstOrFail();
        $this->assertSame('Matemáticas', $miembro->area);
        Storage::disk('public')->assertExists($miembro->foto_path);

        $this->actingAs($admin)->put(route('admin.comunidad.update', $miembro), [
            'cargo' => 'Coordinadora de Matemáticas',
            'nombre' => 'Ana Pérez',
            'area' => 'Matemáticas',
            'quitar_foto' => '1',
        ])->assertRedirect(route('admin.comunidad.index'));

        $miembro->refresh();
        $this->assertSame('Coordinadora de Matemáticas', $miembro->cargo);
        $this->assertNull($miembro->foto_path);

        $this->actingAs($admin)->delete(route('admin.comunidad.destroy', $miembro))
            ->assertRedirect(route('admin.comunidad.index'));

        $this->assertDatabaseMissing('comunidad_miembros', ['id' => $miembro->id]);
    }

    /** @test */
    public function el_admin_administra_hitos_utiles_y_descargas()
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.sitio.historia.store'), [
            'anio' => '1950',
            'titulo' => 'Llegada de las hermanas',
            'descripcion' => 'Inicio de la obra educativa.',
        ])->assertRedirect(route('admin.sitio.index', ['tab' => 'historia']));

        $this->actingAs($admin)->post(route('admin.sitio.utiles.listas.store'), [
            'nombre' => 'Octavo',
        ])->assertRedirect();

        $lista = ListaUtil::firstOrFail();

        $this->actingAs($admin)->put(route('admin.sitio.utiles.listas.update', $lista), [
            'nombre' => 'Octavo',
            'finalizada' => '1',
            'items' => [
                ['cantidad' => '1', 'articulo' => 'Calculadora científica', 'materia' => 'Matemáticas'],
            ],
        ])->assertRedirect(route('admin.sitio.index', ['tab' => 'utiles', 'lista' => $lista->id]));

        $this->actingAs($admin)->post(route('admin.sitio.descargas.store'), [
            'categoria' => 'convivencia',
            'titulo' => 'Código de convivencia',
            'archivo' => UploadedFile::fake()->create('convivencia.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('admin.sitio.index', ['tab' => 'descargas']));

        $this->assertDatabaseHas('historia_hitos', ['titulo' => 'Llegada de las hermanas']);
        $this->assertDatabaseHas('utiles_items', ['nombre' => 'Calculadora científica']);
        $documento = DescargaDocumento::firstOrFail();
        $this->assertSame('Código de convivencia', $documento->titulo);
        Storage::disk('public')->assertExists($documento->archivo_path);
    }

    /** @test */
    public function el_admin_habilita_y_deshabilita_ventanas_de_notas()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.ventanas-notas.store'), [
            'parcial' => '2',
            'fecha_inicio' => now()->subDay()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addWeek()->format('Y-m-d H:i:s'),
            'activo' => '1',
        ])->assertRedirect(route('admin.ventanas-notas.index'));

        $ventana = VentanaNota::firstOrFail();
        $this->assertSame(2, $ventana->parcial);
        $this->assertTrue($ventana->activo);

        $this->actingAs($admin)->post(route('admin.ventanas-notas.estado', $ventana))
            ->assertRedirect(route('admin.ventanas-notas.index'));

        $this->assertFalse($ventana->refresh()->activo);

        // Una ventana general se guarda con parcial NULL y aplica a todos.
        $this->actingAs($admin)->post(route('admin.ventanas-notas.store'), [
            'parcial' => 'general',
            'fecha_inicio' => now()->subDay()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addWeek()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.ventanas-notas.index'));

        $this->assertDatabaseHas('ventanas_notas', ['parcial' => null]);
    }

    /** @test */
    public function el_admin_elimina_una_observacion_enviada_por_error()
    {
        $admin = $this->admin();
        $alumno = Alumno::factory()->create();
        $tipo = TipoObservacion::create(['codigo' => 'COND', 'nombre' => 'Conducta']);

        $observacion = Observacion::create([
            'alumno_id' => $alumno->id,
            'tipo_observacion_id' => $tipo->id,
            'user_id' => $admin->id,
            'descripcion_texto' => 'Registro equivocado',
        ]);

        $this->actingAs($admin)->delete(route('admin.observaciones.destroy', $observacion), [
            'motivo' => 'Orden de Rectorado: registro equivocado',
        ])->assertRedirect(route('admin.observaciones.index'));

        $this->assertDatabaseMissing('observaciones', ['id' => $observacion->id]);
        $this->assertDatabaseHas('historial_cambios', [
            'modulo' => 'Observaciones',
            'accion' => 'eliminar',
        ]);
    }

    /** @test */
    public function el_panel_y_el_historial_del_admin_funcionan()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel de Administración')
            ->assertSee('Sitio web')
            ->assertSee('Comunidad educativa')
            ->assertSee('Historial de cambios')
            ->assertDontSee('Mis Clases');

        HistorialCambio::registrar('Prueba', 'crear', 'Cambio de prueba');

        $this->actingAs($admin)->get(route('admin.historial.index'))
            ->assertOk()
            ->assertSee('Cambio de prueba');
    }

    /** @test */
    public function un_usuario_sin_perfil_admin_no_entra_al_panel()
    {
        $docente = User::factory()->docente()->create();

        $this->actingAs($docente)->get(route('admin.sitio.index'))->assertForbidden();
        $this->actingAs($docente)->get(route('admin.comunidad.index'))->assertForbidden();
    }
}
