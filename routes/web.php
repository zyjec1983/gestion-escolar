<?php

use App\Http\Controllers\Admin\CircularController;
use App\Http\Controllers\Admin\ComunidadController;
use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\HistorialController;
use App\Http\Controllers\Admin\MatriculaController;
use App\Http\Controllers\Admin\ObservacionAdminController;
use App\Http\Controllers\Admin\PreRegistroController as AdminPreRegistroController;
use App\Http\Controllers\Admin\SitioController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VentanaNotaController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\AulaVirtualController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dece\AcuerdoController as DeceAcuerdoController;
use App\Http\Controllers\Dece\AtencionController as DeceAtencionController;
use App\Http\Controllers\Dece\BuscadorController as DeceBuscadorController;
use App\Http\Controllers\Dece\CitaController as DeceCitaController;
use App\Http\Controllers\Dece\DerivacionController as DeceDerivacionController;
use App\Http\Controllers\Dece\DiagnosticoController as DeceDiagnosticoController;
use App\Http\Controllers\Dece\ExpedienteController as DeceExpedienteController;
use App\Http\Controllers\Dece\NeeController as DeceNeeController;
use App\Http\Controllers\Dece\ReporteController as DeceReporteController;
use App\Http\Controllers\Dece\TallerController as DeceTallerController;
use App\Http\Controllers\Docente\ActaController;
use App\Http\Controllers\Docente\AsistenciaController;
use App\Http\Controllers\Docente\CitaController;
use App\Http\Controllers\Docente\NotaController;
use App\Http\Controllers\Docente\ObservacionController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ForoController;
use App\Http\Controllers\Inspeccion\InspectorAsistenciaController;
use App\Http\Controllers\Inspeccion\InspectorAsistenciaEstadisticasController;
use App\Http\Controllers\Inspeccion\InspectorCitaController;
use App\Http\Controllers\Inspeccion\InspectorObservacionController;
use App\Http\Controllers\Inspeccion\ObservacionEstadisticasController;
use App\Http\Controllers\Inspeccion\ObservacionReporteController;
use App\Http\Controllers\Inspeccion\TipoObservacionController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\PlanificacionController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PreRegistroController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Representante\DatosController as RepresentanteDatosController;
use App\Http\Controllers\RepresentanteController;
use App\Http\Controllers\Secretaria\ActualizacionDatosController;
use App\Http\Controllers\Secretaria\NotaExtraordinariaController;
use App\Http\Controllers\Secretaria\TrasladoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'index'])->name('welcome');

// Cada pestaña del portal tiene su propia vista y su propia ruta.
Route::get('/historia', [PortalController::class, 'historia'])->name('portal.historia');
Route::get('/comunidad', [PortalController::class, 'comunidad'])->name('portal.comunidad');
Route::get('/utiles', [PortalController::class, 'utiles'])->name('portal.utiles');
Route::get('/descargas', [PortalController::class, 'descargas'])->name('portal.descargas');
Route::get('/circulares', [PortalController::class, 'circulares'])->name('portal.circulares');

Route::get('/pre-registro', [PreRegistroController::class, 'create'])->name('pre-registro.create');
Route::post('/pre-registro', [PreRegistroController::class, 'store'])->name('pre-registro.store');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Selección y cambio de perfil activo (multirol).
    Route::get('/perfil/seleccionar', [LoginController::class, 'showPerfilForm'])->name('perfil.seleccionar');
    Route::post('/perfil/activo', [LoginController::class, 'seleccionarPerfil'])->name('perfil.activo');

    Route::middleware(['role:ADMIN,DOCENTE,ESTUDIANTE'])->group(function () {
        Route::get('/clases', [AulaVirtualController::class, 'index'])->name('clases.index');
        Route::get('/clases/{id}', [AulaVirtualController::class, 'show'])->name('clases.show');

        Route::get('/clases/{clase}/agenda', [AulaVirtualController::class, 'agenda'])->name('clases.agenda');
        Route::post('/clases/{clase}/agenda', [AulaVirtualController::class, 'storeAgenda'])->name('clases.agenda.store');
        Route::get('/clases/{clase}/agenda/{agenda}/editar', [AulaVirtualController::class, 'editAgenda'])->name('clases.agenda.edit');
        Route::delete('/clases/{clase}/agenda/{agenda}', [AulaVirtualController::class, 'destroyAgenda'])->name('clases.agenda.destroy');

        Route::get('/clases/{clase}/foros', [ForoController::class, 'index'])->name('clases.foros');
        Route::get('/clases/{clase}/foros/contadores', [ForoController::class, 'contadores'])->name('clases.foros.contadores');
        Route::get('/clases/{clase}/foros/crear', [ForoController::class, 'create'])->name('clases.foros.create');
        Route::post('/clases/{clase}/foros', [ForoController::class, 'store'])->name('clases.foros.store');
        Route::get('/foros/{foro}', [ForoController::class, 'show'])->name('foros.show');
        Route::post('/foros/{foro}/intervenciones', [ForoController::class, 'storeIntervencion'])->name('foros.intervenciones.store');
        Route::delete('/foros/{foro}', [ForoController::class, 'destroy'])->name('foros.destroy');
    });

    // Materiales y grupos del aula: el representante puede consultarlos en
    // modo lectura para la clase de su representado. El controlador verifica
    // el acceso a la clase y que solo el docente pueda crear/eliminar.
    Route::middleware(['role:ADMIN,DOCENTE,ESTUDIANTE,REPRESENTANTE'])->group(function () {
        Route::get('/clases/{clase}/materiales', [AulaVirtualController::class, 'materiales'])->name('clases.materiales');
        Route::post('/clases/{clase}/materiales', [AulaVirtualController::class, 'storeMaterial'])->name('clases.materiales.store');
        Route::delete('/clases/{clase}/materiales/{material}', [AulaVirtualController::class, 'destroyMaterial'])->name('clases.materiales.destroy');

        Route::get('/clases/{clase}/grupos', [AulaVirtualController::class, 'grupos'])->name('clases.grupos');
        Route::post('/clases/{clase}/grupos', [AulaVirtualController::class, 'storeGrupos'])->name('clases.grupos.store');
        Route::delete('/clases/{clase}/grupos', [AulaVirtualController::class, 'destroyGrupos'])->name('clases.grupos.destroy');
    });

    Route::get('/mensajes', [MensajeController::class, 'index'])->name('mensajes.index');
    Route::post('/mensajes', [MensajeController::class, 'store'])->name('mensajes.store');
    Route::delete('/mensajes', [MensajeController::class, 'destroy'])->name('mensajes.destroy');
    Route::get('/mensajes/{id}/conversacion', [MensajeController::class, 'conversacion'])->name('mensajes.conversacion');
    Route::get('/mensajes/{id}', [MensajeController::class, 'show'])->name('mensajes.show');

    Route::get('/perfil', [ProfileController::class, 'index'])->name('perfil.index');
    Route::post('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/contraseÃ±a', [ProfileController::class, 'updatePassword'])->name('perfil.password');

    Route::get('/planificaciones', [PlanificacionController::class, 'index'])->name('planificaciones.index');
});

Route::middleware(['auth', 'role:DOCENTE'])->group(function () {
    Route::get('/planificaciones/crear', [PlanificacionController::class, 'create'])->name('planificaciones.create');
    Route::post('/planificaciones', [PlanificacionController::class, 'store'])->name('planificaciones.store');
    Route::delete('/planificaciones/{unidad}', [PlanificacionController::class, 'destroy'])->name('planificaciones.destroy');
});

Route::middleware(['auth', 'role:DOCENTE,ADMIN,SECRETARIA,RECTORA,VICERRECTORA'])->group(function () {
    Route::get('/planificaciones/{unidad}/editar', [PlanificacionController::class, 'edit'])->name('planificaciones.edit');
    Route::put('/planificaciones/{unidad}', [PlanificacionController::class, 'update'])->name('planificaciones.update');
    Route::get('/planificaciones/{unidad}/imprimir', [PlanificacionController::class, 'print'])->name('planificaciones.print');
});

Route::middleware(['auth', 'role:ADMIN,SECRETARIA,RECTORA,VICERRECTORA'])->group(function () {
    Route::get('/planificaciones/bitacora', [PlanificacionController::class, 'bitacora'])->name('planificaciones.bitacora');
    Route::post('/planificaciones/{unidad}/intervencion', [PlanificacionController::class, 'intervencion'])->name('planificaciones.intervencion');
});

Route::middleware(['auth', 'role:REPRESENTANTE'])->group(function () {
    Route::get('/mis-datos', [RepresentanteDatosController::class, 'index'])->name('representante.datos.index');
    Route::get('/mis-datos/{alumno}', [RepresentanteDatosController::class, 'edit'])->name('representante.datos.edit');
    Route::put('/mis-datos/{alumno}', [RepresentanteDatosController::class, 'update'])->name('representante.datos.update');

    Route::get('/observaciones', [RepresentanteController::class, 'observaciones'])->name('representante.observaciones');
    Route::get('/citas', [RepresentanteController::class, 'citas'])->name('representante.citas');
    Route::post('/citas', [RepresentanteController::class, 'storeCita'])->name('representante.citas.store');
    Route::get('/agendas', [RepresentanteController::class, 'agendas'])->name('representante.agendas');
});

Route::middleware(['auth', 'role:ESTUDIANTE'])->group(function () {
    Route::get('/estudiante/agendas', [EstudianteController::class, 'agendas'])->name('estudiante.agendas');
    Route::post('/estudiante/agendas/{agenda}/entregar', [EstudianteController::class, 'marcarEntregada'])->name('estudiante.agendas.marcar-entregada');
});

Route::middleware(['auth', 'role:ADMIN,SECRETARIA'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pre-registros', [AdminPreRegistroController::class, 'index'])->name('pre-registros.index');
    Route::post('/pre-registros/toggle', [AdminPreRegistroController::class, 'toggle'])->name('pre-registros.toggle');
    Route::get('/pre-registros/nueva', [AdminPreRegistroController::class, 'createForm'])->name('pre-registros.create');
    Route::post('/pre-registros/manual', [AdminPreRegistroController::class, 'storeManual'])->name('pre-registros.store-manual');
    Route::get('/pre-registros/{id}', [AdminPreRegistroController::class, 'show'])->name('pre-registros.show');
    Route::post('/pre-registros/{id}/aprobar', [AdminPreRegistroController::class, 'aprobar'])->name('pre-registros.aprobar');
    Route::post('/pre-registros/{id}/rechazar', [AdminPreRegistroController::class, 'rechazar'])->name('pre-registros.rechazar');
    Route::get('/pre-registros/{id}/pdf', [AdminPreRegistroController::class, 'pdf'])->name('pre-registros.pdf');

    Route::get('/matriculas/{id}/editar', [MatriculaController::class, 'edit'])->name('matriculas.edit');
    Route::put('/matriculas/{id}', [MatriculaController::class, 'update'])->name('matriculas.update');
});

// Configuracion institucional: solo Rectorado/Administracion.
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('hero-slides')->name('hero-slides.')->group(function () {
        Route::get('/', [HeroSlideController::class, 'index'])->name('index');
        Route::get('/crear', [HeroSlideController::class, 'create'])->name('create');
        Route::post('/', [HeroSlideController::class, 'store'])->name('store');
        Route::get('/{id}/editar', [HeroSlideController::class, 'edit'])->name('edit');
        Route::put('/{id}', [HeroSlideController::class, 'update'])->name('update');
        Route::delete('/{id}', [HeroSlideController::class, 'destroy'])->name('destroy');
        Route::post('/velocidad', [HeroSlideController::class, 'updateVelocidad'])->name('velocidad');
    });

    // Usuarios y multirol: solo Administración.
    Route::get('/usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios/generar-cuentas-estudiantes', [AdminUserController::class, 'generarCuentasEstudiantes'])->name('usuarios.generar-cuentas');
    Route::get('/usuarios/crear', [AdminUserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [AdminUserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{user}/editar', [AdminUserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])->name('usuarios.update');
    Route::get('/usuarios/{user}/contrasena', [AdminUserController::class, 'password'])->name('usuarios.password');
    Route::post('/usuarios/{user}/contrasena', [AdminUserController::class, 'updatePassword'])->name('usuarios.password.update');
    Route::post('/usuarios/{user}/desbloquear', [AdminUserController::class, 'desbloquear'])->name('usuarios.desbloquear');

    // Sitio web institucional: todos los textos, imágenes e ítems del portal.
    Route::prefix('sitio')->name('sitio.')->group(function () {
        // Cada sección del portal es una vista independiente, sin pestañas.
        Route::get('/', [SitioController::class, 'index'])->name('index');
        Route::get('/preregistro', [SitioController::class, 'preregistro'])->name('preregistro');
        Route::get('/historia', [SitioController::class, 'historia'])->name('historia');
        Route::get('/utiles', [SitioController::class, 'utiles'])->name('utiles');
        Route::get('/descargas', [SitioController::class, 'descargas'])->name('descargas');
        Route::post('/contenido/{grupo}', [SitioController::class, 'updateContenido'])->name('contenido');
        Route::post('/historia', [SitioController::class, 'storeHito'])->name('historia.store');
        Route::put('/historia/{hito}', [SitioController::class, 'updateHito'])->name('historia.update');
        Route::delete('/historia/{hito}', [SitioController::class, 'destroyHito'])->name('historia.destroy');
        Route::post('/utiles/listas', [SitioController::class, 'storeLista'])->name('utiles.listas.store');
        Route::put('/utiles/listas/{lista}', [SitioController::class, 'updateLista'])->name('utiles.listas.update');
        Route::post('/utiles/listas/{lista}/reabrir', [SitioController::class, 'reabrirLista'])->name('utiles.listas.reabrir');
        Route::delete('/utiles/listas/{lista}', [SitioController::class, 'destroyLista'])->name('utiles.listas.destroy');
        Route::post('/descargas', [SitioController::class, 'storeDescarga'])->name('descargas.store');
        Route::put('/descargas/{documento}', [SitioController::class, 'updateDescarga'])->name('descargas.update');
        Route::delete('/descargas/{documento}', [SitioController::class, 'destroyDescarga'])->name('descargas.destroy');
    });

    // Comunidad educativa: cargo, foto, nombres, responsabilidad y área.
    Route::get('/comunidad', [ComunidadController::class, 'index'])->name('comunidad.index');
    Route::post('/comunidad', [ComunidadController::class, 'store'])->name('comunidad.store');
    Route::post('/comunidad/areas/reordenar', [ComunidadController::class, 'reordenarAreas'])->name('comunidad.areas.reordenar');
    Route::put('/comunidad/{miembro}', [ComunidadController::class, 'update'])->name('comunidad.update');
    Route::delete('/comunidad/{miembro}', [ComunidadController::class, 'destroy'])->name('comunidad.destroy');

    // Observaciones enviadas por error.
    Route::get('/observaciones', [ObservacionAdminController::class, 'index'])->name('observaciones.index');
    Route::delete('/observaciones/{observacion}', [ObservacionAdminController::class, 'destroy'])->name('observaciones.destroy');

    // Historial de cambios del sistema.
    Route::get('/historial', [HistorialController::class, 'index'])->name('historial.index');

    // Cada sección de configuración es una vista independiente, sin pestañas.
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::get('/configuracion/institucion', [ConfiguracionController::class, 'institucion'])->name('configuracion.institucion');
    Route::get('/configuracion/carrusel', [ConfiguracionController::class, 'carrusel'])->name('configuracion.carrusel');
    Route::get('/configuracion/carrusel-login', [ConfiguracionController::class, 'carruselLogin'])->name('configuracion.carrusel-login');
    Route::get('/configuracion/apariencia', [ConfiguracionController::class, 'apariencia'])->name('configuracion.apariencia');
    Route::post('/configuracion/institucion', [ConfiguracionController::class, 'updateInstitucion'])->name('configuracion.institucion.update');
    Route::post('/configuracion/sistema_evaluacion', [ConfiguracionController::class, 'updateSistemaEvaluacion'])->name('configuracion.sistema-evaluacion');
    Route::post('/configuracion/zona_horaria', [ConfiguracionController::class, 'updateZonaHoraria'])->name('configuracion.zona-horaria');
    // Apariencia global: 10 paletas del aside y tamaño de fuente tipo Word.
    Route::post('/configuracion/apariencia', [ConfiguracionController::class, 'updateApariencia'])->name('configuracion.apariencia');
});

// Circulares: Administración, Rectorado y Secretaría pueden cargarlas y avisar
// por correo y SMS al contacto registrado.
Route::middleware(['auth', 'role:ADMIN,RECTORA,SECRETARIA'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/circulares', [CircularController::class, 'index'])->name('circulares.index');
        Route::post('/circulares', [CircularController::class, 'store'])->name('circulares.store');
        Route::delete('/circulares/{circular}', [CircularController::class, 'destroy'])->name('circulares.destroy');
    });

// Fechas de subida de notas: Administración y Secretaría habilitan o
// deshabilitan la carga por parcial.
Route::middleware(['auth', 'role:ADMIN,SECRETARIA'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/ventanas-notas', [VentanaNotaController::class, 'index'])->name('ventanas-notas.index');
        Route::post('/ventanas-notas', [VentanaNotaController::class, 'store'])->name('ventanas-notas.store');
        Route::put('/ventanas-notas/{ventana}', [VentanaNotaController::class, 'update'])->name('ventanas-notas.update');
        Route::post('/ventanas-notas/{ventana}/estado', [VentanaNotaController::class, 'estado'])->name('ventanas-notas.estado');
        Route::delete('/ventanas-notas/{ventana}', [VentanaNotaController::class, 'destroy'])->name('ventanas-notas.destroy');
    });

// Actualizacion de datos de alumnos y de su representante legal.
Route::middleware(['auth', 'role:ADMIN,SECRETARIA,RECTORA'])
    ->prefix('actualizacion')
    ->name('actualizacion.')
    ->group(function () {
        Route::get('/', [ActualizacionDatosController::class, 'index'])->name('index');
        Route::get('/{alumno}', [ActualizacionDatosController::class, 'edit'])->name('edit');
        Route::put('/{alumno}', [ActualizacionDatosController::class, 'update'])->name('update');
    });

// Correccion extraordinaria de notas: ADMIN y Secretaria. Cada cambio exige
// motivo y deja una bitacora inalterable.
Route::middleware(['auth', 'role:ADMIN,SECRETARIA'])
    ->prefix('notas-extraordinarias')
    ->name('notas-extraordinarias.')
    ->group(function () {
        Route::get('/', [NotaExtraordinariaController::class, 'index'])->name('index');
        Route::get('/bitacora', [NotaExtraordinariaController::class, 'bitacora'])->name('bitacora');
        Route::post('/', [NotaExtraordinariaController::class, 'store'])->name('store');
    });

// Traslados e historial academico: ADMIN y Secretaria.
Route::middleware(['auth', 'role:ADMIN,SECRETARIA'])
    ->prefix('traslados')
    ->name('traslados.')
    ->group(function () {
        Route::get('/', [TrasladoController::class, 'index'])->name('index');
        Route::get('/crear', [TrasladoController::class, 'create'])->name('create');
        Route::get('/buscar-alumnos', [TrasladoController::class, 'buscarAlumnos'])->name('buscar-alumnos');
        Route::get('/buscar-representantes', [TrasladoController::class, 'buscarRepresentantes'])->name('buscar-representantes');
        Route::post('/', [TrasladoController::class, 'store'])->name('store');

        Route::get('/{expediente}', [TrasladoController::class, 'show'])->name('show');
        Route::put('/{expediente}', [TrasladoController::class, 'update'])->name('update');

        Route::post('/{expediente}/documentos', [TrasladoController::class, 'subirDocumento'])->name('documentos.store');
        Route::delete('/{expediente}/documentos/{documento}', [TrasladoController::class, 'eliminarDocumento'])->name('documentos.destroy');

        Route::post('/{expediente}/promociones', [TrasladoController::class, 'storePromocion'])->name('promociones.store');
        Route::delete('/{expediente}/promociones/{promocion}', [TrasladoController::class, 'destroyPromocion'])->name('promociones.destroy');

        Route::post('/{expediente}/notas', [TrasladoController::class, 'storeNota'])->name('notas.store');
        Route::delete('/{expediente}/notas/{nota}', [TrasladoController::class, 'destroyNota'])->name('notas.destroy');

        Route::post('/{expediente}/paz-salvo', [TrasladoController::class, 'actualizarPazYSalvo'])->name('paz-salvo.update');
        Route::post('/{expediente}/cerrar', [TrasladoController::class, 'cerrar'])->name('cerrar');

        Route::get('/{expediente}/historial/pdf', [TrasladoController::class, 'historialPdf'])->name('historial.pdf');
        Route::get('/{expediente}/certificado-salida/pdf', [TrasladoController::class, 'certificadoSalidaPdf'])->name('certificado.pdf');
    });

Route::middleware(['auth', 'role:DOCENTE'])->prefix('docente')->name('docente.')->group(function () {
    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('/asistencia', [AsistenciaController::class, 'store'])->name('asistencia.store');

    Route::get('/observaciones', [ObservacionController::class, 'index'])->name('observaciones.index');
    Route::post('/observaciones', [ObservacionController::class, 'store'])->name('observaciones.store');
    Route::get('/observaciones/{alumno}/print', [ObservacionController::class, 'print'])->name('observaciones.print');

    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::post('/citas/{id}/registrar', [CitaController::class, 'registrar'])->name('citas.registrar');

    Route::get('/notas', [NotaController::class, 'index'])->name('notas.index');
    Route::post('/notas', [NotaController::class, 'store'])->name('notas.store');

    Route::get('/actas', [ActaController::class, 'index'])->name('actas.index');
    Route::get('/actas/pdf', [ActaController::class, 'exportarPdf'])->name('actas.pdf');
    Route::get('/actas/excel', [ActaController::class, 'exportarExcel'])->name('actas.excel');
});

// URL histórica: redirige al módulo de citas compartido con inspección.
Route::middleware(['auth', 'role:SECRETARIA,RECTORA,VICERRECTORA,INSPECCION,DECE'])->prefix('directivo')->name('directivo.')->group(function () {
    Route::get('/citas', fn () => redirect()->route('inspeccion.citas.historial'))->name('citas.historial');
});

// Módulo de citas (agendar + historial + registro): inspección y autoridades.
// El rector/vicerrector/dece/secretaría usan exactamente el mismo módulo.
Route::middleware(['auth', 'role:SECRETARIA,RECTORA,VICERRECTORA,INSPECCION,DECE'])
    ->prefix('inspeccion')->name('inspeccion.')->group(function () {
        Route::get('/citas/agendar', [InspectorCitaController::class, 'agendar'])->name('citas.agendar');
        Route::post('/citas/agendar', [InspectorCitaController::class, 'store'])->name('citas.store');
        Route::get('/citas/historial', [InspectorCitaController::class, 'historial'])->name('citas.historial');
        Route::post('/citas/{cita}/registrar', [InspectorCitaController::class, 'registrar'])->name('citas.registrar');
    });

Route::middleware(['auth', 'role:INSPECCION,RECTORA,VICERRECTORA,DECE'])
    ->prefix('inspeccion')->name('inspeccion.')->group(function () {
        Route::get('/observaciones', [InspectorObservacionController::class, 'index'])->name('observaciones.index');
        Route::post('/observaciones', [InspectorObservacionController::class, 'store'])->name('observaciones.store');
        Route::get('/observaciones/{alumno}/print', [InspectorObservacionController::class, 'print'])->name('observaciones.print');

        // Catálogo de tipos de observación (configuración)
        Route::get('/observaciones/tipos', [TipoObservacionController::class, 'index'])->name('observaciones.tipos.index');
        Route::post('/observaciones/tipos', [TipoObservacionController::class, 'store'])->name('observaciones.tipos.store');
        Route::patch('/observaciones/tipos/{tipo}/toggle', [TipoObservacionController::class, 'toggleActivo'])->name('observaciones.tipos.toggle');
        Route::post('/observaciones/tipos/edicion', [TipoObservacionController::class, 'alternarEdicionGlobal'])->name('observaciones.tipos.alternar');

        Route::get('/asistencia', [InspectorAsistenciaController::class, 'index'])->name('asistencia.index');
        Route::post('/asistencia', [InspectorAsistenciaController::class, 'store'])->name('asistencia.store');
    });

// Consultas y reportes en modo lectura: Inspección, autoridades y Secretaría.
Route::middleware(['auth', 'role:INSPECCION,RECTORA,VICERRECTORA,DECE,SECRETARIA'])
    ->prefix('inspeccion')->name('inspeccion.')->group(function () {
        // Reportes de observaciones
        Route::get('/observaciones/reporte/alumno', [ObservacionReporteController::class, 'reportePorAlumno'])->name('observaciones.reporte.alumno');
        Route::get('/observaciones/reporte/alumno/pdf', [ObservacionReporteController::class, 'pdfPorAlumno'])->name('observaciones.reporte.alumno.pdf');
        Route::get('/observaciones/estadisticas', [ObservacionEstadisticasController::class, 'index'])->name('observaciones.estadisticas');
        Route::get('/asistencia/estadisticas', [InspectorAsistenciaEstadisticasController::class, 'index'])->name('asistencia.estadisticas');
    });

// Modulo DECE: expedientes y reportes agregados. El panel de inicio vive en
// /dashboard (se renderiza alli para el rol DECE). Rectorado y Vicerrectorado
// entran en modo lectura sin relatorias confidenciales.
Route::middleware(['auth', 'role:DECE,RECTORA,VICERRECTORA'])
    ->prefix('dece')->name('dece.')->group(function () {
        Route::get('/expedientes', [DeceExpedienteController::class, 'index'])->name('expedientes.index');
        Route::get('/expedientes/{alumno}', [DeceExpedienteController::class, 'show'])->name('expedientes.show');
        Route::get('/reportes', [DeceReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/pdf', [DeceReporteController::class, 'pdf'])->name('reportes.pdf');
    });

// Buscadores JSON del DECE. Evitan cargar el padron completo en cada formulario.
Route::middleware(['auth', 'role:DECE,RECTORA,VICERRECTORA,DOCENTE,INSPECCION'])
    ->prefix('dece')->name('dece.')->group(function () {
        Route::get('/buscar/alumnos', [DeceBuscadorController::class, 'alumnos'])->name('buscar.alumnos');
    });

Route::middleware(['auth', 'role:DECE'])
    ->prefix('dece')->name('dece.')->group(function () {
        Route::get('/buscar/alumnos/{alumno}/citas', [DeceBuscadorController::class, 'citas'])->name('buscar.citas');
        Route::get('/buscar/alumnos/{alumno}/atenciones', [DeceBuscadorController::class, 'atenciones'])->name('buscar.atenciones');
        Route::get('/buscar/talleres', [DeceBuscadorController::class, 'talleres'])->name('buscar.talleres');
    });

// Gestion clinica y administrativa del DECE.
Route::middleware(['auth', 'role:DECE'])
    ->prefix('dece')->name('dece.')->group(function () {
        Route::get('/atenciones', [DeceAtencionController::class, 'index'])->name('atenciones.index');
        Route::get('/atenciones/crear', [DeceAtencionController::class, 'create'])->name('atenciones.create');
        Route::post('/atenciones', [DeceAtencionController::class, 'store'])->name('atenciones.store');
        Route::get('/atenciones/{atencion}', [DeceAtencionController::class, 'show'])->name('atenciones.show');
        Route::post('/atenciones/{atencion}/cerrar', [DeceAtencionController::class, 'cerrar'])->name('atenciones.cerrar');
        Route::get('/atenciones/{atencion}/pdf', [DeceAtencionController::class, 'pdf'])->name('atenciones.pdf');

        Route::get('/citas', [DeceCitaController::class, 'index'])->name('citas.index');
        Route::get('/citas/crear', [DeceCitaController::class, 'create'])->name('citas.create');
        Route::post('/citas', [DeceCitaController::class, 'store'])->name('citas.store');
        Route::get('/citas/{cita}', [DeceCitaController::class, 'show'])->name('citas.show');
        Route::post('/citas/{cita}/registrar', [DeceCitaController::class, 'registrar'])->name('citas.registrar');

        Route::get('/acuerdos', [DeceAcuerdoController::class, 'index'])->name('acuerdos.index');
        Route::get('/acuerdos/crear', [DeceAcuerdoController::class, 'create'])->name('acuerdos.create');
        Route::post('/acuerdos', [DeceAcuerdoController::class, 'store'])->name('acuerdos.store');
        Route::get('/acuerdos/{acuerdo}', [DeceAcuerdoController::class, 'show'])->name('acuerdos.show');
        Route::post('/acuerdos/{acuerdo}/firmar', [DeceAcuerdoController::class, 'firmar'])->name('acuerdos.firmar');
        Route::post('/acuerdos/{acuerdo}/seguimiento', [DeceAcuerdoController::class, 'seguimiento'])->name('acuerdos.seguimiento');
        Route::get('/acuerdos/{acuerdo}/pdf', [DeceAcuerdoController::class, 'pdf'])->name('acuerdos.pdf');

        Route::get('/nee', [DeceNeeController::class, 'index'])->name('nee.index');
        Route::get('/nee/crear', [DeceNeeController::class, 'create'])->name('nee.create');
        Route::post('/nee', [DeceNeeController::class, 'store'])->name('nee.store');
        Route::get('/nee/{nee}', [DeceNeeController::class, 'show'])->name('nee.show');
        Route::get('/nee/{nee}/editar', [DeceNeeController::class, 'edit'])->name('nee.edit');
        Route::put('/nee/{nee}', [DeceNeeController::class, 'update'])->name('nee.update');
        Route::post('/nee/{nee}/estado', [DeceNeeController::class, 'toggle'])->name('nee.toggle');

        Route::get('/diagnosticos', [DeceDiagnosticoController::class, 'index'])->name('diagnosticos.index');
        Route::post('/diagnosticos', [DeceDiagnosticoController::class, 'store'])->name('diagnosticos.store');
        Route::put('/diagnosticos/{diagnostico}', [DeceDiagnosticoController::class, 'update'])->name('diagnosticos.update');
        Route::post('/diagnosticos/{diagnostico}/estado', [DeceDiagnosticoController::class, 'toggle'])->name('diagnosticos.toggle');

        Route::post('/derivaciones/{derivacion}', [DeceDerivacionController::class, 'update'])->name('derivaciones.update');

        Route::get('/talleres', [DeceTallerController::class, 'index'])->name('talleres.index');
        Route::get('/talleres/crear', [DeceTallerController::class, 'create'])->name('talleres.create');
        Route::post('/talleres', [DeceTallerController::class, 'store'])->name('talleres.store');
        Route::get('/talleres/{taller}', [DeceTallerController::class, 'show'])->name('talleres.show');
        Route::post('/talleres/{taller}/asistencia', [DeceTallerController::class, 'registrarAsistencia'])->name('talleres.asistencia');
    });

// Derivaciones internas: docentes e inspeccion pueden remitir un caso al DECE.
Route::middleware(['auth', 'role:DECE,DOCENTE,INSPECCION'])
    ->prefix('dece')->name('dece.')->group(function () {
        Route::get('/derivaciones', [DeceDerivacionController::class, 'index'])->name('derivaciones.index');
        Route::get('/derivaciones/crear', [DeceDerivacionController::class, 'create'])->name('derivaciones.create');
        Route::post('/derivaciones', [DeceDerivacionController::class, 'store'])->name('derivaciones.store');
        Route::get('/derivaciones/{derivacion}', [DeceDerivacionController::class, 'show'])->name('derivaciones.show');
        Route::get('/derivaciones/{derivacion}/pdf', [DeceDerivacionController::class, 'pdf'])->name('derivaciones.pdf');
    });

Route::get('/debug-data', function () {
    $dashboard = app(ApiDashboardController::class)->index(request());

    return $dashboard->toArray(request());
});
