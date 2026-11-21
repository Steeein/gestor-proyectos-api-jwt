<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProyectoApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API - Gestion de Proyectos (Tech Solutions)
|--------------------------------------------------------------------------
| Prefijo automatico: /api
|
| Los cinco endpoints del CRUD de proyectos.
| register y login son publicas; el resto exige token JWT.
*/

/*
|--------------------------------------------------------------------------
| RUTAS PUBLICAS
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']); // 201 | 422
    Route::post('/login', [AuthApiController::class, 'login']);       // 200 | 401
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS CON JWT (middleware auth:api)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    Route::get('/auth/me', [AuthApiController::class, 'me']);           // 200
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);  // 200
    Route::post('/auth/refresh', [AuthApiController::class, 'refresh']); // 200

    Route::prefix('proyectos')->group(function () {

        // Listar todos los proyectos.
        Route::get('/', [ProyectoApiController::class, 'index']);                    // 200

        // Agregar un proyecto.
        Route::post('/', [ProyectoApiController::class, 'store']);                   // 201 | 422

        // Obtener un proyecto por su id.
        Route::get('/{id}', [ProyectoApiController::class, 'show'])
             ->whereNumber('id');                                                    // 200 | 404

        // Actualizar un proyecto por su id.
        Route::match(['put', 'patch'], '/{id}', [ProyectoApiController::class, 'update'])
             ->whereNumber('id');                                                    // 200 | 404 | 422

        // Eliminar un proyecto por su id.
        Route::delete('/{id}', [ProyectoApiController::class, 'destroy'])
             ->whereNumber('id');                                                    // 204 | 404
    });
});

/*
| Cualquier ruta /api/* inexistente responde 404 en JSON,
| nunca una pagina HTML de error.
*/
Route::fallback(function () {
    return response()->json(['message' => 'Recurso no encontrado.'], 404);
});
