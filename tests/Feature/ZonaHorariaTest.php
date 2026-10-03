<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Institucion;
use App\Models\Mensaje;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZonaHorariaTest extends TestCase
{
    use RefreshDatabase;

    private function institucion(string $zona = '+00:00'): Institucion
    {
        return Institucion::firstOrCreate([], [
            'nombre' => 'Unidad Educativa',
            'zona_horaria' => $zona,
        ]);
    }

    private function horaEn(string $zona): string
    {
        return (new \DateTime('now', new \DateTimeZone($zona)))->format('Y-m-d H:i:s');
    }

    public function test_la_migracion_deja_utc_por_defecto(): void
    {
        $inst = $this->institucion();

        $this->assertSame('+00:00', $inst->fresh()->zona_horaria);
    }

    public function test_el_desfase_elegido_aplica_a_now(): void
    {
        $this->institucion('+11:00');

        AppServiceProvider::aplicarZonaHorariaDelCentro();

        // PHP solo admite identificadores de zona, no un desfase suelto.
        $this->assertSame('+11:00', config('app.timezone'));
        $this->assertSame('Etc/GMT-11', date_default_timezone_get());
        $this->assertSame($this->horaEn('Etc/GMT-11'), now()->format('Y-m-d H:i:s'));
    }

    public function test_ecuador_usa_utc_menos_cinco(): void
    {
        $this->institucion('-05:00');

        AppServiceProvider::aplicarZonaHorariaDelCentro();

        $this->assertSame($this->horaEn('America/Guayaquil'), now()->format('Y-m-d H:i:s'));
    }

    public function test_el_mensaje_se_guarda_con_la_hora_del_desfase_elegido(): void
    {
        $this->institucion('-05:00');
        AppServiceProvider::aplicarZonaHorariaDelCentro();

        $inspector = User::factory()->create(['rol' => 'INSPECCION']);
        $alumno = Alumno::factory()->create();

        $estudiante = User::factory()->estudiante()->create();
        $representante = User::factory()->representante()->create();
        $alumno->update(['user_id' => $estudiante->id, 'representante_user_id' => $representante->id]);

        $this->actingAs($inspector)
            ->post(route('mensajes.store'), [
                'asunto' => 'Cita de padres',
                'cuerpo_contenido' => 'Contenido',
                'alcance' => 'estudiante',
                'alumno_id' => $alumno->id,
            ])
            ->assertRedirect();

        $this->assertSame(2, Mensaje::count());

        $mensaje = Mensaje::latest('id')->first();

        $this->assertNotNull($mensaje);
        $this->assertEqualsWithDelta(
            strtotime($this->horaEn('America/Guayaquil')),
            strtotime($mensaje->fecha_envio->format('Y-m-d H:i:s')),
            5
        );
    }

    public function test_el_admin_puede_guardar_el_desfase(): void
    {
        $this->institucion();
        $admin = User::factory()->create(['rol' => 'ADMIN']);

        $this->actingAs($admin)
            ->post(route('admin.configuracion.zona-horaria'), ['zona_horaria' => '+11:00'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('+11:00', Institucion::first()->zona_horaria);
    }

    public function test_rechaza_un_desfase_inexistente(): void
    {
        $this->institucion();
        $admin = User::factory()->create(['rol' => 'ADMIN']);

        $this->actingAs($admin)
            ->post(route('admin.configuracion.zona-horaria'), ['zona_horaria' => '-13:00'])
            ->assertSessionHasErrors('zona_horaria');

        $this->assertSame('+00:00', Institucion::first()->zona_horaria);
    }

    public function test_rechaza_un_nombre_de_ciudad(): void
    {
        $this->institucion();
        $admin = User::factory()->create(['rol' => 'ADMIN']);

        // Antes se guardaban ciudades; ahora solo el desfase.
        $this->actingAs($admin)
            ->post(route('admin.configuracion.zona-horaria'), ['zona_horaria' => 'Europe/Oslo'])
            ->assertSessionHasErrors('zona_horaria');

        $this->actingAs($admin)
            ->post(route('admin.configuracion.zona-horaria'), ['zona_horaria' => 'US/Eastern'])
            ->assertSessionHasErrors('zona_horaria');
    }

    public function test_solo_el_admin_puede_cambiarla(): void
    {
        $this->institucion();

        foreach (['DOCENTE', 'INSPECCION', 'ESTUDIANTE', 'REPRESENTANTE'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol]);

            $this->actingAs($usuario)
                ->post(route('admin.configuracion.zona-horaria'), ['zona_horaria' => '+11:00'])
                ->assertForbidden();
        }

        $this->assertSame('+00:00', Institucion::first()->zona_horaria);
    }

    public function test_la_pagina_de_configuracion_solo_la_ve_el_admin(): void
    {
        $this->institucion();
        $admin = User::factory()->create(['rol' => 'ADMIN']);
        $docente = User::factory()->create(['rol' => 'DOCENTE']);

        $this->actingAs($admin)
            ->get(route('admin.configuracion.index'))
            ->assertOk()
            ->assertSee('zona_horaria')
            ->assertSee('UTC-05:00');

        $this->actingAs($docente)
            ->get(route('admin.configuracion.index'))
            ->assertForbidden();
    }

    public function test_el_selector_ofrece_los_23_desfases(): void
    {
        $desfases = desfasesHorariosDisponibles();

        // De -11 a +1 son 23, no 24: 11 negativas, UTC y 11 positivas.
        $this->assertCount(23, $desfases);
        $this->assertSame('-11:00', valorDesfaseHorario(-660));
        $this->assertSame('+00:00', valorDesfaseHorario(0));
        $this->assertSame('+11:00', valorDesfaseHorario(660));

        $valores = desfasesHorariosValidos();
        $this->assertContains('-05:00', $valores);
        $this->assertNotContains('-12:00', $valores);
        $this->assertNotContains('+12:00', $valores);
    }

    public function test_cada_desfase_trae_ciudades_representativas(): void
    {
        foreach (desfasesHorariosDisponibles() as $desfase) {
            $this->assertNotEmpty($desfase['ciudades']);
        }

        $this->assertStringContainsString('Quito', etiquetaDesfaseHorario('-05:00'));
        $this->assertStringContainsString('Sídney', etiquetaDesfaseHorario('+10:00'));
        $this->assertStringContainsString('Londres', etiquetaDesfaseHorario('+00:00'));
    }

    public function test_los_23_desfases_dan_la_hora_correcta(): void
    {
        $original = date_default_timezone_get();

        foreach (desfasesHorariosDisponibles() as $desfase) {
            $valor = valorDesfaseHorario($desfase['minutos']);
            $zonaPhp = zonaPhpDesdeDesfase($valor);

            // La zona traducida tiene que existir de verdad en PHP.
            try {
                $tz = new \DateTimeZone($zonaPhp);
            } catch (\Exception $e) {
                $this->fail("La zona {$zonaPhp} del desfase {$valor} no existe en PHP.");
            }

            $this->assertSame(
                $desfase['minutos'],
                (int) round($tz->getOffset(new \DateTime('now', $tz)) / 60),
                "El desfase {$valor} no coincide con su zona."
            );

            date_default_timezone_set($zonaPhp);

            $instante = new \DateTime('now');
            $esperado = (new \DateTime('now', new \DateTimeZone($zonaPhp)))->format('H:i:s');

            $this->assertSame($esperado, $instante->format('H:i:s'), "Falla el desfase {$valor}");
        }

        date_default_timezone_set($original);
    }

    public function test_el_signo_de_etc_gmt_esta_invertido(): void
    {
        // Etc/GMT va al revés: Etc/GMT+5 es UTC-5.
        $this->assertSame('Etc/GMT+5', zonaPhpDesdeDesfase('-05:00'));
        $this->assertSame('Etc/GMT-11', zonaPhpDesdeDesfase('+11:00'));
        $this->assertSame('UTC', zonaPhpDesdeDesfase('+00:00'));
    }

    public function test_las_ciudades_se_muestran_en_el_selector(): void
    {
        $this->institucion('-05:00');

        $this->actingAs(User::factory()->create(['rol' => 'ADMIN']))
            ->get(route('admin.configuracion.index'))
            ->assertOk()
            ->assertSee('Quito')
            ->assertSee('Canberra')
            ->assertSee('Kyiv')
            ->assertSee('value="-05:00"', false);
    }

    public function test_el_desfase_se_formatea_con_horas_y_minutos(): void
    {
        $this->assertSame('UTC-05:00', formatearDesfase(-300));
        $this->assertSame('UTC+00:00', formatearDesfase(0));
        $this->assertSame('UTC+11:00', formatearDesfase(660));
        $this->assertSame('UTC-11:00', formatearDesfase(-660));
    }

    public function test_un_desfase_invalido_guardado_cae_a_utc(): void
    {
        // Si alguien deja un valor roto en la BD, la app no debe romperse.
        $this->institucion('NoExiste');

        AppServiceProvider::aplicarZonaHorariaDelCentro();

        $this->assertSame(0, minutosDesfaseZona(config('app.timezone')));
        $this->assertSame('UTC', date_default_timezone_get());
    }

    public function test_el_app_timezone_solo_se_usa_si_no_hay_desfase_guardado(): void
    {
        $this->institucion();

        // Instalación nueva: todavía no hay desfase elegido por el admin.
        \Illuminate\Support\Facades\DB::table('instituciones')->update(['zona_horaria' => '']);

        // El .env trae America/Guayaquil; debe traducirse a -05:00.
        config(['app.timezone' => 'America/Guayaquil']);

        AppServiceProvider::aplicarZonaHorariaDelCentro();

        $this->assertSame('-05:00', config('app.timezone'));
        $this->assertSame('Etc/GMT+5', date_default_timezone_get());
    }
}