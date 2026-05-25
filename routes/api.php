<?php

use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\CircularController;
use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MensajeController;
use App\Http\Controllers\Api\ObservacionController;
use App\Http\Controllers\Api\PermisoNotaController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/clases', [ClaseController::class, 'index']);
    Route::get('/clases/{id}', [ClaseController::class, 'show']);

    Route::get('/clases/{claseId}/observaciones', [ObservacionController::class, 'index']);
    Route::post('/observaciones', [ObservacionController::class, 'store']);

    Route::get('/clases/{claseId}/notas', [PermisoNotaController::class, 'index']);

    Route::get('/mensajes', [MensajeController::class, 'index']);
    Route::post('/mensajes', [MensajeController::class, 'store']);
    Route::get('/mensajes/{id}', [MensajeController::class, 'show']);

    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::post('/agenda', [AgendaController::class, 'store']);

    Route::get('/circulares', [CircularController::class, 'index']);
});

// Ruta pública de ejemplo para probar la API
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'Sistema de Gestión Escolar API v1.0']);
});
