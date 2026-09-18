<?php

use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\MatriculaController;
use App\Http\Controllers\Admin\PreRegistroController as AdminPreRegistroController;
use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Docente\ActaController;
use App\Http\Controllers\Docente\AsistenciaController;
use App\Http\Controllers\Docente\CitaController;
use App\Http\Controllers\Docente\NotaController;
use App\Http\Controllers\Docente\ObservacionController;
use App\Http\Controllers\PreRegistroController;
use App\Models\HeroSlide;
use App\Models\Institucion;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $slides = HeroSlide::where('activo', true)->orderBy('orden')->get();
    $institucion = Institucion::first();
    return view('welcome', compact('slides', 'institucion'));
});

Route::get('/pre-registro', [PreRegistroController::class, 'create'])->name('pre-registro.create');
Route::post('/pre-registro', [PreRegistroController::class, 'store'])->name('pre-registro.store');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $dashboard = app(DashboardController::class)->index(request());
        $data = $dashboard->toArray(request());
        return view('dashboard.index', $data);
    })->name('dashboard');

    Route::get('/clases', function () {
        $clases = app(ClaseController::class)->index(request());
        $data = $clases->toArray(request());
        return view('clases.index', $data);
    })->name('clases.index');

    Route::get('/clases/{id}', function ($id) {
        $clase = app(ClaseController::class)->show($id);
        $data = $clase->toArray(request());
        $data['layout'] = 'aula';
        return view('aula.virtual', $data);
    })->name('clases.show');
});

Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
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

    Route::prefix('hero-slides')->name('hero-slides.')->group(function () {
        Route::get('/', [HeroSlideController::class, 'index'])->name('index');
        Route::get('/crear', [HeroSlideController::class, 'create'])->name('create');
        Route::post('/', [HeroSlideController::class, 'store'])->name('store');
        Route::get('/{id}/editar', [HeroSlideController::class, 'edit'])->name('edit');
        Route::put('/{id}', [HeroSlideController::class, 'update'])->name('update');
        Route::delete('/{id}', [HeroSlideController::class, 'destroy'])->name('destroy');
        Route::post('/velocidad', [HeroSlideController::class, 'updateVelocidad'])->name('velocidad');
    });

    Route::post('/configuracion/sistema-evaluacion', [ConfiguracionController::class, 'updateSistemaEvaluacion'])->name('configuracion.sistema-evaluacion');
});

Route::middleware(['auth', 'role:DOCENTE'])->prefix('docente')->name('docente.')->group(function () {
    Route::get('/asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('/asistencia', [AsistenciaController::class, 'store'])->name('asistencia.store');

    Route::get('/observaciones', [ObservacionController::class, 'index'])->name('observaciones.index');
    Route::post('/observaciones', [ObservacionController::class, 'store'])->name('observaciones.store');
    Route::get('/observaciones/{alumno}/print', [ObservacionController::class, 'print'])->name('observaciones.print');

    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');

    Route::get('/notas', [NotaController::class, 'index'])->name('notas.index');
    Route::post('/notas', [NotaController::class, 'store'])->name('notas.store');

    Route::get('/actas', [ActaController::class, 'index'])->name('actas.index');
    Route::get('/actas/pdf', [ActaController::class, 'exportarPdf'])->name('actas.pdf');
    Route::get('/actas/excel', [ActaController::class, 'exportarExcel'])->name('actas.excel');
});

Route::get('/debug-data', function () {
    $dashboard = app(DashboardController::class)->index(request());
    return $dashboard->toArray(request());
});
