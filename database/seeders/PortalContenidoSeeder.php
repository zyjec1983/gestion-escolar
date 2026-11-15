<?php

namespace Database\Seeders;

use App\Models\ComunidadMiembro;
use App\Models\DescargaDocumento;
use App\Models\HistoriaHito;
use App\Models\ListaUtil;
use App\Models\SiteContent;
use App\Models\UtilItem;
use App\Support\PortalContenido;
use Illuminate\Database\Seeder;

/**
 * Contenido inicial del portal institucional.
 *
 * Qué hace: carga los textos, hitos, integrantes, útiles y documentos que
 * trae el template franciscano. Usa updateOrCreate para poder ejecutarse
 * varias veces sin duplicar información.
 * Conecta con: App\Support\PortalContenido y los modelos del portal.
 */
class PortalContenidoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PortalContenido::campos() as $clave => $campo) {
            SiteContent::updateOrCreate(
                ['clave' => $clave],
                [
                    'valor' => $campo['por_defecto'],
                    'tipo' => $campo['tipo'],
                    'grupo' => $campo['grupo'],
                    'etiqueta' => $campo['etiqueta'],
                ]
            );
        }

        SiteContent::limpiarCache();

        $this->hitos();
        $this->comunidad();
        $this->utiles();
        $this->descargas();
    }

    private function hitos(): void
    {
        $hitos = [
            ['anio' => '1893', 'titulo' => 'Fundación de la Congregación', 'descripcion' => 'La Beata Madre Caridad Brader funda en Túquerres (Colombia) la Congregación de Hermanas Franciscanas de María Inmaculada dedicada a la educación cristiana.'],
            ['anio' => 'Expansión en Ecuador', 'titulo' => 'Presencia Educativa Franciscana', 'descripcion' => 'Apertura de obras educativas en territorio ecuatoriano, brindando formación integral de calidad a niñas, niños y jóvenes.'],
            ['anio' => 'Actualidad', 'titulo' => 'Innovación Digital y Excelencia', 'descripcion' => 'Integración de plataformas tecnológicas modernas, bachillerato de alto rendimiento y pastoral juvenil comprometida con la sociedad contemporánea.'],
        ];

        foreach ($hitos as $i => $hito) {
            HistoriaHito::updateOrCreate(
                ['titulo' => $hito['titulo']],
                [...$hito, 'orden' => $i + 1]
            );
        }
    }

    private function comunidad(): void
    {
        $miembros = [
            ['cargo' => 'Rectorado', 'nombre' => 'Hna. María Elena Villacís, fmi', 'responsabilidad' => 'Dirección general, animación carismática y gestión estratégica institucional.', 'area' => 'Equipo directivo'],
            ['cargo' => 'Vicerrectorado Académico', 'nombre' => 'MSc. Carlos Andrés Mendoza', 'responsabilidad' => 'Coordinación curricular, innovación pedagógica y evaluación de la calidad educativa.', 'area' => 'Equipo directivo'],
            ['cargo' => 'Pastoral Educativa', 'nombre' => 'Hna. Lucía Fernanda Paredes, fmi', 'responsabilidad' => 'Formación espiritual, catequesis sacramental, infancia y juventud misionera franciscana.', 'area' => 'Pastoral'],
            ['cargo' => 'Departamento DECE', 'nombre' => 'Psic. Valeria Torres & Equipo', 'responsabilidad' => 'Acompañamiento psicoemocional, orientación vocacional y cultura de paz y convivencia.', 'area' => 'Consejería estudiantil'],
            ['cargo' => 'Consejo Estudiantil', 'nombre' => 'Liderazgo Juvenil Franciscano', 'responsabilidad' => 'Representación democrática de los estudiantes, proyectos culturales, deportivos y ecológicos.', 'area' => 'Organismos de participación'],
            ['cargo' => 'Comité de Padres de Familia', 'nombre' => 'Alianza Familia – Escuela', 'responsabilidad' => 'Participación activa de los representantes legales en la formación integral de sus hijos.', 'area' => 'Organismos de participación'],
        ];

        foreach ($miembros as $i => $miembro) {
            ComunidadMiembro::updateOrCreate(
                ['nombre' => $miembro['nombre']],
                [...$miembro, 'orden' => $i + 1, 'activo' => true]
            );
        }
    }

    private function utiles(): void
    {
        $niveles = [
            'inicial' => [
                ['Plastilina escolar no tóxica', 'Caja x 12 colores suaves', '2 cajas', 'Motricidad Fina'],
                ['Crayones triangulares jumbo', 'Caja x 12 unidades', '1 caja', 'Expresión Artística'],
                ['Témperas lavables', 'Frascos 250 ml (colores primarios)', '4 frascos', 'Arte y Creatividad'],
                ['Papelógrafo de papel bond', 'Pliegos blancos 75g', '10 pliegos', 'Trabajo Grupal'],
                ['Goma blanca escolar', 'Frasco 250g con aplicador', '1 frasco', 'Manualidades'],
                ['Mandil institucional antifluidos', 'Con nombre bordado', '1 unidad', 'Cuidado Personal'],
            ],
            'elemental' => [
                ['Cuadernos universitarios 100 hojas', '4 cuadros y 2 cuatro líneas', '6 unidades', 'Asignaturas Básicas'],
                ['Caja de lápices de colores', '24 colores mina resistente', '1 caja', 'Estudios Sociales / Arte'],
                ['Diccionario Escolar Ilustrado', 'Lengua Española actualizado', '1 unidad', 'Lengua y Literatura'],
                ['Juego geométrico flexible', 'Regla 30cm, escuadras y graduador', '1 set', 'Matemática'],
                ['Biblia Católica Latinoamericana', 'Edición juvenil / escolar', '1 unidad', 'Formación Cristiana'],
            ],
            'media' => [
                ['Cuadernos universitarios 100 hojas', 'Cuadros grandes (cosidos)', '8 unidades', 'Tronco Común'],
                ['Carpeta archivadora con vincha', 'Tamaño A4 con 50 hojas perforadas', '2 unidades', 'Portafolio Estudiantil'],
                ['Compás de precisión metálico', 'Con adaptador y minas de repuesto', '1 unidad', 'Geometría'],
                ['Diccionario Español – Inglés', 'Bilingüe intermedio', '1 unidad', 'Lengua Extranjera'],
                ['Mandil blanco de laboratorio', 'Manga larga con sello institucional', '1 unidad', 'Ciencias Naturales'],
            ],
            'superior' => [
                ['Cuadernos universitarios 100 hojas', 'Cuadros (Matemática, Física, Lengua)', '9 unidades', 'Áreas Académicas'],
                ['Calculadora científica', 'Funciones trigonométricas básicas', '1 unidad', 'Matemática'],
                ['Tabla Periódica de los Elementos', 'Plastificada actualizada IUPAC', '1 unidad', 'Ciencias / Química'],
                ['Memoria USB 32 GB', 'Identificada con etiqueta', '1 unidad', 'Informática y Robótica'],
            ],
            'bachillerato' => [
                ['Cuadernos universitarios 100 hojas', 'Cuadros (Cálculo, Química, Biología)', '10 unidades', 'Especialidad BGU'],
                ['Calculadora científica avanzada', 'Estadística y matrices', '1 unidad', 'Matemática y Física'],
                ['Mandil de laboratorio + Gafas', 'Norma de bioseguridad escolar', '1 kit', 'Química y Biología'],
                ['Constitución del Ecuador (Bolsillo)', 'Edición vigente', '1 unidad', 'Educación para la Ciudadanía'],
            ],
        ];

        foreach ($niveles as $ordenLista => $items) {
            $lista = ListaUtil::updateOrCreate(
                ['nombre' => UtilItem::NIVELES[$ordenLista]],
                ['finalizada' => true, 'orden' => $ordenLista]
            );

            foreach ($items as $i => [$nombre, $especificacion, $cantidad, $area]) {
                UtilItem::updateOrCreate(
                    ['lista_id' => $lista->id, 'nombre' => $nombre, 'especificacion' => $especificacion],
                    [
                        'nivel' => 'lista',
                        'cantidad' => $cantidad,
                        'materia' => $area,
                        'area' => $area,
                        'orden' => $i + 1,
                    ]
                );
            }
        }
    }

    private function descargas(): void
    {
        $documentos = [
            'horarios' => [
                ['Horario General Educación Inicial y Básica Elemental', 'Actualizado: Mayo 2026', '420 KB', 'Distribución horaria de jornadas matutinas, recesos y actividades extracurriculares.'],
                ['Horario General Básica Media y Superior', 'Actualizado: Mayo 2026', '510 KB', 'Carga horaria semanal por paralelos, laboratorios y tutorías académicas.'],
                ['Horario Bachillerato General Unificado (1.º - 3.º BGU)', 'Actualizado: Mayo 2026', '485 KB', 'Jornada académica BGU, clubes de investigación y preparación preuniversitaria.'],
            ],
            'cronograma' => [
                ['Cronograma Escolar Anual – Primer Trimestre', 'Actualizado: Abril 2026', '680 KB', 'Fechas de inicio de clases, evaluaciones parciales, fiestas franciscanas y juntas de curso.'],
                ['Calendario Pastoral y Celebraciones Marianas', 'Actualizado: Abril 2026', '390 KB', 'Eucaristías institucionales, convivencias espirituales y semana de San Francisco de Asís.'],
            ],
            'convivencia' => [
                ['Código de Convivencia Institucional Armónica', 'Vigente 2026 - 2028', '1.4 MB', 'Acuerdos, compromisos, derechos y deberes de estudiantes, docentes y padres de familia.'],
                ['Protocolos de Prevención y Cultura de Paz (DECE)', 'Actualizado: Marzo 2026', '890 KB', 'Rutas de actuación frente a conflictos escolares y promoción del buen trato.'],
            ],
            'uniformes' => [
                ['Reglamento y Especificaciones del Uniforme Diario', 'Vigente 2026', '1.1 MB', 'Modelos oficiales, telas autorizadas, calzado y presentación personal institucional.'],
                ['Especificaciones Uniforme de Cultura Física y Gala', 'Vigente 2026', '950 KB', 'Calentador institucional, camiseta deportiva y uniforme de parada para actos cívicos.'],
            ],
            'varios' => [
                ['Autorización de Salidas Pedagógicas y Giras', 'Formato 2026', '240 KB', 'Formulario imprimible de consentimiento firmado por el representante legal.'],
                ['Ficha Médica y Alergias del Estudiante', 'Formato 2026', '310 KB', 'Documento obligatorio para el Departamento Médico institucional.'],
                ['Guía de Uso del Portal Académico y Aula Virtual', 'Manual v3.2', '1.8 MB', 'Instructivo paso a paso para padres de familia y estudiantes.'],
            ],
        ];

        foreach ($documentos as $categoria => $items) {
            foreach ($items as $i => [$titulo, $fecha, $peso, $descripcion]) {
                DescargaDocumento::updateOrCreate(
                    ['categoria' => $categoria, 'titulo' => $titulo],
                    ['fecha_texto' => $fecha, 'peso' => $peso, 'descripcion' => $descripcion, 'orden' => $i + 1, 'activo' => true]
                );
            }
        }
    }
}
