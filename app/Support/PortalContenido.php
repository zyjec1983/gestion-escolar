<?php

namespace App\Support;

use App\Models\SiteContent;

/**
 * Catálogo de campos administrables del portal institucional.
 *
 * Qué hace: define la etiqueta, el grupo, el tipo y el valor por defecto de
 * cada texto o imagen del sitio. El panel "Sitio web" genera sus formularios
 * a partir de esta lista y welcome.blade.php usa los mismos valores por
 * defecto cuando todavía no se ha guardado nada.
 * Conecta con: App\Models\SiteContent, Admin\SitioController y welcome.
 * Rutas: GET/POST /admin/sitio.
 */
class PortalContenido
{
    /** Grupos del panel y su presentación. */
    public const GRUPOS = [
        'institucion' => [
            'titulo' => 'Identidad institucional',
            'icono' => 'bi-building',
            'descripcion' => 'Datos de contacto, lema, misión, visión y valores que aparecen en el portal.',
        ],
        'historia' => [
            'titulo' => 'Nuestra historia',
            'icono' => 'bi-book-half',
            'descripcion' => 'Encabezado, relato fundacional e imagen de la sección histórica.',
        ],
        'comunidad' => [
            'titulo' => 'Comunidad educativa',
            'icono' => 'bi-people-fill',
            'descripcion' => 'Título y subtítulo de la sección. Los integrantes se administran en el menú Comunidad educativa.',
        ],
        'utiles' => [
            'titulo' => 'Lista de útiles',
            'icono' => 'bi-backpack2',
            'descripcion' => 'Encabezado de la lista de materiales escolares.',
        ],
        'descargas' => [
            'titulo' => 'Centro de descargas',
            'icono' => 'bi-cloud-arrow-down-fill',
            'descripcion' => 'Encabezado del centro de documentos oficiales.',
        ],
        'admisiones' => [
            'titulo' => 'Proceso de admisiones',
            'icono' => 'bi-pencil-square',
            'descripcion' => 'Los tres pasos que se explican en la página de inicio.',
        ],
        'login' => [
            'titulo' => 'Textos del inicio de sesión',
            'icono' => 'bi-box-arrow-in-right',
            'descripcion' => 'Título y subtítulo del formulario de acceso.',
        ],
    ];

    /**
     * Definición de cada campo.
     *
     * tipo: texto | largo | imagen | booleano | url
     *
     * @return array<string, array{grupo:string, etiqueta:string, tipo:string, por_defecto:string, ayuda?:string}>
     */
    public static function campos(): array
    {
        return [
            // Identidad institucional.
            'institucion.nombre_corto' => [
                'grupo' => 'institucion', 'etiqueta' => 'Nombre corto', 'tipo' => 'texto',
                'por_defecto' => 'U.E. Franciscana María Inmaculada',
            ],
            'institucion.eslogan' => [
                'grupo' => 'institucion', 'etiqueta' => 'Eslogan', 'tipo' => 'texto',
                'por_defecto' => 'Fe, Ciencia, Fraternidad y Servicio',
            ],
            'institucion.lema_franciscano' => [
                'grupo' => 'institucion', 'etiqueta' => 'Lema franciscano', 'tipo' => 'texto',
                'por_defecto' => 'Paz y Bien • Congregación de Hermanas Franciscanas de María Inmaculada',
            ],
            'institucion.ciudad' => [
                'grupo' => 'institucion', 'etiqueta' => 'Ciudad / país', 'tipo' => 'texto',
                'por_defecto' => 'Ecuador',
            ],
            'institucion.telefono' => [
                'grupo' => 'institucion', 'etiqueta' => 'Teléfono', 'tipo' => 'texto',
                'por_defecto' => '(04) 255-0190',
            ],
            'institucion.correo' => [
                'grupo' => 'institucion', 'etiqueta' => 'Correo institucional', 'tipo' => 'texto',
                'por_defecto' => 'secretaria@uefranciscana.edu.ec',
            ],
            'institucion.direccion' => [
                'grupo' => 'institucion', 'etiqueta' => 'Dirección', 'tipo' => 'texto',
                'por_defecto' => 'Av. Principal y Calle Franciscana, Ecuador',
            ],
            'institucion.horario_atencion' => [
                'grupo' => 'institucion', 'etiqueta' => 'Horario de atención', 'tipo' => 'texto',
                'por_defecto' => 'Lunes a Viernes: 07h30 – 15h30',
            ],
            'institucion.periodo_lectivo' => [
                'grupo' => 'institucion', 'etiqueta' => 'Texto del período / admisiones', 'tipo' => 'texto',
                'por_defecto' => 'Proceso de Admisiones • Nuevo Año Lectivo',
            ],
            'institucion.mision' => [
                'grupo' => 'institucion', 'etiqueta' => 'Misión', 'tipo' => 'largo',
                'por_defecto' => 'Evangelizar educando con pedagogía franciscana y mariana, formando integralmente a niños y jóvenes en la fe, la ciencia, la fraternidad y el servicio solidario, a la luz del Evangelio y el carisma de la Beata Madre Caridad Brader, para construir una sociedad más justa, humana y comprometida con el cuidado de la Creación.',
            ],
            'institucion.vision' => [
                'grupo' => 'institucion', 'etiqueta' => 'Visión', 'tipo' => 'largo',
                'por_defecto' => 'Ser una comunidad educativa católica franciscana referente por su excelencia académica, innovación pedagógica y testimonio evangélico; que forma líderes cristianos con pensamiento crítico, sensibilidad social y amor a María Inmaculada, capaces de transformar su entorno con alegría, paz y bien.',
            ],
            'institucion.valores' => [
                'grupo' => 'institucion', 'etiqueta' => 'Valores franciscanos', 'tipo' => 'largo',
                'por_defecto' => 'Paz y Bien, Fraternidad, Minoridad, Solidaridad, Excelencia Académica y Amor a María Inmaculada.',
            ],
            'institucion.footer_descripcion' => [
                'grupo' => 'institucion', 'etiqueta' => 'Descripción del pie de página', 'tipo' => 'largo',
                'por_defecto' => 'Obra educativa dirigida por la Congregación de Hermanas Franciscanas de María Inmaculada. Evangelizando y educando bajo el carisma de la Beata Madre Caridad Brader.',
            ],

            // Historia.
            'historia.titulo' => [
                'grupo' => 'historia', 'etiqueta' => 'Título', 'tipo' => 'texto',
                'por_defecto' => 'Nuestra Historia y Carisma',
            ],
            'historia.subtitulo' => [
                'grupo' => 'historia', 'etiqueta' => 'Subtítulo', 'tipo' => 'texto',
                'por_defecto' => 'Legado educativo de la Congregación de Hermanas Franciscanas de María Inmaculada',
            ],
            'historia.contenido' => [
                'grupo' => 'historia', 'etiqueta' => 'Relato histórico', 'tipo' => 'largo',
                'por_defecto' => 'Nuestra institución nace inspirada en la obra evangelizadora y educativa de la Beata Madre María Caridad Brader, religiosa suiza que fundó en 1893 la Congregación de Hermanas Franciscanas de María Inmaculada. Con profundo espíritu misionero, las hermanas llegaron al Ecuador para sembrar semillas de fe, cultura y promoción humana, consolidando una propuesta educativa que armoniza el rigor académico con la espiritualidad franciscana de sencillez, alegría y servicio al prójimo.',
            ],
            'historia.cita_fundadora' => [
                'grupo' => 'historia', 'etiqueta' => 'Cita fundacional', 'tipo' => 'largo',
                'por_defecto' => '"Todo por amor a Dios y como Él lo quiere; educar el corazón y la mente para formar verdaderos apóstoles del bien."',
            ],
            'historia.imagen_principal' => [
                'grupo' => 'historia', 'etiqueta' => 'Imagen principal', 'tipo' => 'imagen',
                'por_defecto' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=900&q=80',
                'ayuda' => 'Se recomienda una foto horizontal del campus o de una actividad institucional.',
            ],

            // Comunidad.
            'comunidad.titulo' => [
                'grupo' => 'comunidad', 'etiqueta' => 'Título', 'tipo' => 'texto',
                'por_defecto' => 'Comunidad Educativa',
            ],
            'comunidad.subtitulo' => [
                'grupo' => 'comunidad', 'etiqueta' => 'Subtítulo', 'tipo' => 'texto',
                'por_defecto' => 'Directivos, pastoral, consejería y organismos de participación estudiantil y familiar',
            ],

            // Útiles.
            'utiles.titulo' => [
                'grupo' => 'utiles', 'etiqueta' => 'Título', 'tipo' => 'texto',
                'por_defecto' => 'Lista de Útiles Escolares',
            ],
            'utiles.subtitulo' => [
                'grupo' => 'utiles', 'etiqueta' => 'Subtítulo', 'tipo' => 'texto',
                'por_defecto' => 'Consulta y descarga los materiales requeridos por subnivel educativo',
            ],

            // Descargas.
            'descargas.titulo' => [
                'grupo' => 'descargas', 'etiqueta' => 'Título', 'tipo' => 'texto',
                'por_defecto' => 'Centro de Descargas Institucional',
            ],
            'descargas.subtitulo' => [
                'grupo' => 'descargas', 'etiqueta' => 'Subtítulo', 'tipo' => 'texto',
                'por_defecto' => 'Documentos oficiales, normativas, horarios y circulares para estudiantes y representantes',
            ],

            // Admisiones.
            'admisiones.paso1_titulo' => [
                'grupo' => 'admisiones', 'etiqueta' => 'Paso 1: título', 'tipo' => 'texto',
                'por_defecto' => 'Complete el formulario',
            ],
            'admisiones.paso1_desc' => [
                'grupo' => 'admisiones', 'etiqueta' => 'Paso 1: descripción', 'tipo' => 'largo',
                'por_defecto' => 'Llene sus datos personales, información del representante y los del aspirante en línea de forma rápida y segura.',
            ],
            'admisiones.paso2_titulo' => [
                'grupo' => 'admisiones', 'etiqueta' => 'Paso 2: título', 'tipo' => 'texto',
                'por_defecto' => 'Reciba su PDF',
            ],
            'admisiones.paso2_desc' => [
                'grupo' => 'admisiones', 'etiqueta' => 'Paso 2: descripción', 'tipo' => 'largo',
                'por_defecto' => 'Descargue e imprima su comprobante y solicitud oficial de matrícula inmediatamente después de registrar sus datos.',
            ],
            'admisiones.paso3_titulo' => [
                'grupo' => 'admisiones', 'etiqueta' => 'Paso 3: título', 'tipo' => 'texto',
                'por_defecto' => 'Espere la confirmación',
            ],
            'admisiones.paso3_desc' => [
                'grupo' => 'admisiones', 'etiqueta' => 'Paso 3: descripción', 'tipo' => 'largo',
                'por_defecto' => 'La institución revisará y aprobará su solicitud. Recibirá un correo electrónico con sus credenciales de acceso al portal.',
            ],

            // Login.
            'login.titulo_formulario' => [
                'grupo' => 'login', 'etiqueta' => 'Título del formulario', 'tipo' => 'texto',
                'por_defecto' => 'Iniciar sesión',
            ],
            'login.subtitulo_formulario' => [
                'grupo' => 'login', 'etiqueta' => 'Subtítulo del formulario', 'tipo' => 'texto',
                'por_defecto' => 'Ingrese sus credenciales institucionales y seleccione su perfil.',
            ],
        ];
    }

    /** Valor actual con respaldo en el valor por defecto. */
    public static function valor(string $clave): string
    {
        $defecto = self::campos()[$clave]['por_defecto'] ?? '';

        return (string) SiteContent::valor($clave, $defecto);
    }

    /** URL de una imagen, con respaldo en la imagen por defecto. */
    public static function imagen(string $clave): string
    {
        return SiteContent::imagen($clave) ?: (string) (self::campos()[$clave]['por_defecto'] ?? '');
    }

    /** Campos de un grupo, en el orden en que se muestran. */
    public static function porGrupo(string $grupo): array
    {
        return array_filter(self::campos(), fn ($campo) => $campo['grupo'] === $grupo);
    }
}
