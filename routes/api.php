<?php

use App\Http\Controllers\UsuarioAdministrativoController;
use App\Http\Controllers\CambiarContrasenaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    // RF3: Cambio de contraseña del usuario autenticado.
    Route::put('/usuario/contrasena', [
        CambiarContrasenaController::class,
        'update',
    ])->name('usuario.contrasena.update');
});

// Rutas para la administración de usuarios administrativos.
Route::middleware(['auth:sanctum', 'rol.administrativo:dueno'])->group(function () {

    // Registra un nuevo usuario administrativo.
    Route::post('/usuarios-administrativos', [UsuarioAdministrativoController::class, 'store'])
        ->name('usuarios-administrativos.store');

    // Consulta los usuarios administrativos registrados.
    Route::get('/usuarios-administrativos', [UsuarioAdministrativoController::class, 'index'])
        ->name('usuarios-administrativos.index');
});
