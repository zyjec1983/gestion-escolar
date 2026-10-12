<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\ClaseDocente;
use App\Models\Curso;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Paralelo;
use App\Models\PeriodoLectivo;
use App\Models\User;
use App\Services\CalculoNotas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotasQuimestreTest extends TestCase
{
    use RefreshDatabase;

    private function crearClase(User $docente): array
    {
        $curso = Curso::factory()->create();
        $paralelo = Paralelo::factory()->create();
        $materia = Materia::create(['nombre' => 'MatemÃ¡tica', 'codigo' => 'MAT', 'color_tarjeta' => '#000000']);
        $periodo = PeriodoLectivo::factory()->create(['activo' => true]);

        $clase = ClaseDocente::create([
            'user_id' => $docente->id,
            'materia_id' => $materia->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
        ]);

        $alumno = Alumno::factory()->create();
        $matricula = Matricula::factory()->create([
            'alumno_id' => $alumno->id,
            'curso_id' => $curso->id,
            'paralelo_id' => $paralelo->id,
            'periodo_id' => $periodo->id,
        ]);

        return compact('clase', 'alumno', 'matricula');
    }

    public function test_admin_can_change_sistema_evaluacion(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.sistema-evaluacion'), ['sistema_evaluacion' => 'trimestre'])
            ->assertRedirect();

        $this->assertDatabaseHas('instituciones', ['sistema_evaluacion' => 'trimestre']);
    }

    public function test_notas_index_shows_quimestre_options_and_examen_grid(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 3]))
            ->assertOk()
            ->assertSee('1er Quimestre')
            ->assertSee('2do Quimestre')
            ->assertSee('Examen Quimestral')
            ->assertSee($data['alumno']->apellidos);
    }

    public function test_store_partial_and_examen_with_calculation(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $payload = [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                    'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                ],
            ],
        ];

        $this->actingAs($docente)->post(route('docente.notas.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $data['matricula']->id,
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_nota' => 'parcial',
            'estado' => 'final',
        ]);

        $payload['parcial'] = 3;
        $payload['notas'][$data['alumno']->id] = ['alumno_id' => $data['alumno']->id, 'nota_examen' => 8.0];

        $this->actingAs($docente)->post(route('docente.notas.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('notas', [
            'matricula_id' => $data['matricula']->id,
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 3,
            'tipo_nota' => 'examen',
            'estado' => 'final',
        ]);

        $examen = Nota::where('matricula_id', $data['matricula']->id)->where('parcial', 3)->first();
        $this->assertEquals(8.0, (float) $examen->nota_examen);
    }

    public function test_store_final_rejects_missing_fields(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $payload = [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => ['alumno_id' => $data['alumno']->id, 'fii' => '', 'ffi' => '', 'fig' => '', 'ffg' => '', 'proyecto_integrador' => '', 'evaluacion_parcial' => ''],
            ],
        ];

        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $payload)
            ->assertSessionHasErrors('notas');

        $this->assertDatabaseCount('notas', 0);
    }

    public function test_resumen_quimestral_matches_user_example(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);
        $aid = $data['alumno']->id;

        $fii = 10; $ffi = 10; $fig = 10; $ffg = 10;
        $prom10 = fn() => [
            'alumno_id' => $aid,
            'fii' => $fii, 'ffi' => $ffi, 'fig' => $fig, 'ffg' => $ffg,
            'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
        ];

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [$aid => [...$prom10(), 'fii' => 7.67, 'ffi' => 7.67, 'fig' => 7.67, 'ffg' => 7.67, 'proyecto_integrador' => 7.67, 'evaluacion_parcial' => 7.67]],
        ])->assertRedirect();

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 2,
            'tipo_guardado' => 'final',
            'notas' => [$aid => $prom10()],
        ])->assertRedirect();

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 3,
            'tipo_guardado' => 'final',
            'notas' => [$aid => ['alumno_id' => $aid, 'nota_examen' => 8.0]],
        ])->assertRedirect();

        $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 1]))
            ->assertOk();

        $this->assertDatabaseHas('notas', ['matricula_id' => $data['matricula']->id, 'parcial' => 1]);
        $this->assertDatabaseHas('notas', ['matricula_id' => $data['matricula']->id, 'parcial' => 2]);
        $this->assertDatabaseHas('notas', ['matricula_id' => $data['matricula']->id, 'parcial' => 3, 'nota_examen' => '8.0000']);

        $q1 = app(\App\Http\Controllers\Docente\NotaController::class);
        $resumen = $this->invocarResumen($q1, $data, $docente);

        $this->assertNotNull($resumen[0]['p1']);
        $this->assertNotNull($resumen[0]['p2']);
        $this->assertNotNull($resumen[0]['te1']);
        $this->assertEqualsWithDelta(8.58, (float) $resumen[0]['q1'], 0.02);
    }

    public function test_acta_parcial_renders(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                    'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($docente)
            ->get(route('docente.actas.index', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'parcial', 'parcial' => 1]))
            ->assertOk()
            ->assertSee('Acta Parcial')
            ->assertSee('F. Docente')
            ->assertSee($data['alumno']->apellidos);
    }

    public function test_acta_quimestral_renders(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)
            ->get(route('docente.actas.index', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'quimestral']))
            ->assertOk()
            ->assertSee('Acta Quimestral')
            ->assertSee('Quimestre 1')
            ->assertSee('AÑO');
    }

    public function test_acta_excel_downloads(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)
            ->get(route('docente.actas.excel', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'parcial', 'parcial' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_acta_pdf_downloads(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('pdf', 200));

        $this->actingAs($docente)
            ->get(route('docente.actas.pdf', ['clase_docente_id' => $data['clase']->id, 'tipo' => 'quimestral']))
            ->assertOk();
    }

    public function test_leyendas_sin_porcentaje_en_fii_ffi_fig_ffg_pero_conservan_las_demas(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $html = $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 1]))
            ->assertOk()
            ->getContent();

        // FII, FFI, FIG y FFG conservan el nombre pero pierden el "10%".
        foreach (['FII', 'FFI', 'FIG', 'FFG'] as $leyenda) {
            $this->assertMatchesRegularExpression(
                '/<th class="text-center">' . $leyenda . '<\/th>/',
                $html,
                "El encabezado de {$leyenda} debe quedar sin porcentaje."
            );
        }

        $this->assertStringNotContainsString('<small>10%</small>', $html);

        // Los demas porcentajes se conservan intactos.
        $this->assertStringContainsString('Form. 70%', $html);
        $this->assertStringContainsString('Pr. Integrador<br><small>15%</small>', $html);
        $this->assertStringContainsString('Eval. Parcial<br><small>15%</small>', $html);
        $this->assertStringContainsString('Eval. 30%', $html);
    }

    public function test_la_validacion_de_rango_se_aplica_a_guardado_parcial_y_final(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $html = $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 1]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Notas fuera de rango', $html);
        $this->assertStringContainsString('Formato de nota inválido', $html);
        $this->assertStringContainsString('Cada nota debe estar <b>', $html);

        // Regresion: "Guardado Parcial" no debe enviar el formulario sin validar.
        $this->assertStringContainsString("if (revisarNotas(inputsEditables(), tipo === 'final')) {", $html);
        $this->assertMatchesRegularExpression(
            '/const tipo = this\.dataset\.tipo;.*?if \(revisarNotas\(inputsEditables\(\), tipo === \'final\'\)\) \{\s*return;\s*\}\s*if \(tipo === \'parcial\'\) \{/s',
            $html,
            'La validacion debe ejecutarse antes de guardar parcialmente.'
        );
    }

    public function test_store_rechaza_notas_fuera_de_rango_y_decimales_con_coma(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $base = [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
        ];

        $notas = fn(array $campos) => [
            $data['alumno']->id => array_merge(['alumno_id' => $data['alumno']->id], $campos),
        ];

        $validas = ['fii' => 10, 'ffi' => 10, 'fig' => 10, 'ffg' => 10, 'proyecto_integrador' => 10, 'evaluacion_parcial' => 10];
        $clave = 'notas.' . $data['alumno']->id . '.';

        // Fuera de rango por encima.
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $base + ['notas' => $notas(array_merge($validas, ['fii' => 12]))])
            ->assertSessionHasErrors($clave . 'fii');

        // Fuera de rango por debajo (0 no es una nota valida).
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $base + ['notas' => $notas(array_merge($validas, ['fii' => 0]))])
            ->assertSessionHasErrors($clave . 'fii');

        // Negativo.
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $base + ['notas' => $notas(array_merge($validas, ['ffi' => -1]))])
            ->assertSessionHasErrors($clave . 'ffi');

        // Se admiten coma o punto: la coma se normaliza antes de validar.
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $base + ['notas' => $notas(array_merge($validas, ['fig' => '9,5']))])
            ->assertSessionHasNoErrors();

        // Mas de 2 decimales no cabe en decimal(5,2): se rechaza.
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $base + ['notas' => $notas(array_merge($validas, ['fii' => '9.555']))])
            ->assertSessionHasErrors($clave . 'fii');

        // Examen fuera de rango.
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), [
                'clase_docente_id' => $data['clase']->id,
                'parcial' => 3,
                'tipo_guardado' => 'final',
                'notas' => $notas(['nota_examen' => 10.5]),
            ])
            ->assertSessionHasErrors($clave . 'nota_examen');

        // Decimal con punto: se acepta y se guarda con punto.
        $this->actingAs($docente)
            ->post(route('docente.notas.store'), $base + ['notas' => $notas(array_merge($validas, ['ffg' => 9.5]))])
            ->assertSessionHasNoErrors();

        $nota = Nota::where('matricula_id', $data['matricula']->id)->where('parcial', 1)->first();
        $this->assertSame(9.5, round((float) $nota->ffg, 2));
        $this->assertStringNotContainsString(',', (string) $nota->getRawOriginal('ffg'));
    }

    public function test_la_validacion_se_dispara_mientras_se_digita_y_no_muestra_totales_inventados(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $html = $this->actingAs($docente)
            ->get(route('docente.notas.index', ['clase_docente_id' => $data['clase']->id, 'parcial' => 1]))
            ->assertOk()
            ->getContent();

        // Escucha el evento "input" de cada casillero, no solo al guardar.
        $this->assertStringContainsString(
            "form.querySelectorAll('.nota-input, .examen-input').forEach(nota => {",
            $html
        );
        $this->assertStringContainsString("nota.addEventListener('input', function () {", $html);
        $this->assertStringContainsString('marcarNota(this);', $html);

        // SweetAlert al salir del campo, para no interrumpir cada tecla.
        $this->assertStringContainsString("nota.addEventListener('change', function () {", $html);
        $this->assertStringContainsString('Nota inválida', $html);

        // El mensaje en vivo declara el rango y explica el 0.
        $this->assertStringContainsString('La nota debe estar ', $html);
        $this->assertStringContainsString('const RANGO_MIN = 0.01;', $html);
        $this->assertStringContainsString('const RANGO_MAX = 10;', $html);
        $this->assertStringContainsString("const RANGO_TEXTO = 'entre ' + RANGO_MIN + ' y ' + RANGO_MAX;", $html);
        $this->assertStringContainsString('deja el campo vacío', $html);

        // Con un valor invalido la fila no muestra un total calculado.
        $this->assertStringContainsString("if (!filaEsValida(row)) {", $html);
        $this->assertStringContainsString("celda.textContent = '—';", $html);

        // La coma se normaliza a punto en el navegador, no se rechaza.
        $this->assertStringContainsString("if (nota.value.includes(',')) {", $html);
        $this->assertStringContainsString("nota.value = nota.value.replace(/,/g, '.');", $html);
        $this->assertStringContainsString('normalizarComa(this);', $html);

        // El formato se valida con una expresion regular estricta.
        $this->assertStringContainsString("if (!/^-?\\d+(\\.\\d+)?$/.test(bruto))", $html);
        $this->assertStringContainsString('if (decimales.length > 2) {', $html);
    }

    public function test_la_coma_se_normaliza_a_punto_y_se_guarda_y_calcula_igual(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $base = [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
        ];

        // Todo escrito con coma, como lo haria una docente en Ecuador.
        $this->actingAs($docente)->post(route('docente.notas.store'), $base + [
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => '9,5', 'ffi' => '8', 'fig' => '7,25', 'ffg' => '10',
                    'proyecto_integrador' => '9,5', 'evaluacion_parcial' => '8',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $nota = Nota::where('matricula_id', $data['matricula']->id)->where('parcial', 1)->first();

        // 1) Se guardo como numero, no como texto con coma.
        $this->assertIsNumeric($nota->getRawOriginal('fii'));
        $this->assertStringNotContainsString(',', (string) $nota->getRawOriginal('fii'));
        $this->assertSame(9.5, round((float) $nota->fii, 2));
        $this->assertSame(7.25, round((float) $nota->fig, 2));

        // 2) El calculo sale igual que si se hubiera escrito con punto.
        // Ojo: la app TRUNCA con floor, no redondea (ver $trunc en la vista).
        $trunc = fn($v) => floor(($v * 100) + 1e-9) / 100;
        $form70 = $trunc(((9.5 + 8 + 7.25 + 10) / 40) * 7);
        $eval30 = $trunc(((9.5 + 8) / 20) * 3);
        $this->assertSame(6.08, round($form70, 2));
        $this->assertSame(2.62, round($eval30, 2));
        $this->assertSame(8.7, round($form70 + $eval30, 2));

        // 3) El servicio calcula exactamente lo mismo. No hay columna "total":
        // el total se calcula con CalculoNotas::notaParcialDe(), que devuelve
        // el valor sin truncar (la vista trunca solo al mostrar).
        $crudo = ((9.5 + 8 + 7.25 + 10) / 40 * 7) + ((9.5 + 8) / 20 * 3);
        $this->assertSame(round($crudo, 2), round(app(CalculoNotas::class)->notaParcialDe($nota), 2));

        // 4) Y es identico a escribirlo con punto, que es la prueba de que la
        // coma no altera el calculo.
        $this->actingAs($docente)->post(route('docente.notas.store'), $base + [
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => '9.5', 'ffi' => '8', 'fig' => '7.25', 'ffg' => '10',
                    'proyecto_integrador' => '9.5', 'evaluacion_parcial' => '8',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $notaPunto = Nota::where('matricula_id', $data['matricula']->id)->where('parcial', 1)->first();
        $this->assertEquals(
            app(CalculoNotas::class)->notaParcialDe($notaPunto),
            app(CalculoNotas::class)->notaParcialDe($nota)
        );
        $this->assertSame(
            $notaPunto->getRawOriginal('fii'),
            $nota->getRawOriginal('fii')
        );
    }

    public function test_la_coma_se_acepta_tambien_en_el_examen(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 3,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => ['alumno_id' => $data['alumno']->id, 'nota_examen' => '7,75'],
            ],
        ])->assertSessionHasNoErrors();

        $examen = Nota::where('matricula_id', $data['matricula']->id)->where('parcial', 3)->first();
        $this->assertSame(7.75, round((float) $examen->nota_examen, 2));
    }

    public function test_mas_de_dos_decimales_se_rechaza_por_ambos_caminos(): void
    {
        Institucion::factory()->create(['sistema_evaluacion' => 'quimestre']);
        $docente = User::factory()->docente()->create();
        $data = $this->crearClase($docente);

        $this->actingAs($docente)->post(route('docente.notas.store'), [
            'clase_docente_id' => $data['clase']->id,
            'parcial' => 1,
            'tipo_guardado' => 'final',
            'notas' => [
                $data['alumno']->id => [
                    'alumno_id' => $data['alumno']->id,
                    'fii' => '0,01123', 'ffi' => 10, 'fig' => 10, 'ffg' => 10,
                    'proyecto_integrador' => 10, 'evaluacion_parcial' => 10,
                ],
            ],
        ])->assertSessionHasErrors('notas.' . $data['alumno']->id . '.fii');
    }

    private function invocarResumen($controller, array $data, User $docente): array
    {
        $alumnos = collect([$data['alumno']]);
        $notasClase = \App\Models\Nota::where('clase_docente_id', $data['clase']->id)
            ->get()
            ->groupBy('alumno_id');
        $ref = new \ReflectionMethod($controller, 'resumenNotas');
        $ref->setAccessible(true);
        return $ref->invoke($controller, $alumnos, $notasClase, 'quimestre');
    }
}