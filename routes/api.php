<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TipoRecursoController;
use App\Http\Controllers\Api\RecursoController;
use App\Http\Controllers\Api\ReservaController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\UsuariosController;
use App\Http\Controllers\Api\HistorialReservaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta de prueba opcional
Route::get('/ping', function () {
    return response()->json(['message' => 'API Reservas OK']);
});

// =======================
// AUTH (luego usaremos JWT)
// =======================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/change-password-first-login', [AuthController::class, 'changePasswordFirstLogin']);

// ==========================================
// Rutas protegidas con JWT
// ==========================================

// RUTAS PROTEGIDAS CON JWT
Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ========= USUARIOS (solo admin) =========
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuariosController::class, 'index']);
        Route::post('/', [UsuariosController::class, 'store']);
        Route::get('/{id}', [UsuariosController::class, 'show']);
        Route::put('/{id}', [UsuariosController::class, 'update']);
        Route::delete('/{id}', [UsuariosController::class, 'destroy']); // desactivar
    });

    // Perfil del usuario autenticado
    Route::put('/perfil', [UsuariosController::class, 'updateProfile']);

    // ======== TIPOS DE RECURSOS =============
    Route::prefix('tipos-recursos')->group(function () {
        Route::get('/', [TipoRecursoController::class, 'index']);
        Route::get('/{id}', [TipoRecursoController::class, 'show']);
        Route::post('/', [TipoRecursoController::class, 'store']);      // solo admin
        Route::put('/{id}', [TipoRecursoController::class, 'update']);  // solo admin
        Route::delete('/{id}', [TipoRecursoController::class, 'destroy']); // solo admin
    });

    // ============= RECURSOS ================
    Route::prefix('recursos')->group(function () {
        Route::get('/', [RecursoController::class, 'index']);
        Route::get('/{id}', [RecursoController::class, 'show']);
        Route::post('/', [RecursoController::class, 'store']);      // solo admin
        Route::put('/{id}', [RecursoController::class, 'update']);  // solo admin
        Route::delete('/{id}', [RecursoController::class, 'destroy']); // solo admin
    });

    // ============= RESERVAS ================
    Route::prefix('reservas')->group(function () {
        Route::get('/', [ReservaController::class, 'index']);
        Route::get('/{id}', [ReservaController::class, 'show']);
        Route::post('/', [ReservaController::class, 'store']);
        Route::put('/{id}', [ReservaController::class, 'update']);           // solo admin
        Route::put('/{id}/cancelar', [ReservaController::class, 'cancel']);  // admin o dueño
    });

    // ============= NOTIFICACIONES ==========
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionController::class, 'index']);
        Route::put('/{id}/leer', [NotificacionController::class, 'marcarComoLeida']);
        Route::put('/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasComoLeidas']);
    });

    // ============= HISTORIAL RESERVAS ========
    Route::get('reservas/{id}/historial', [HistorialReservaController::class, 'porReserva']);
});
