<?php

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
