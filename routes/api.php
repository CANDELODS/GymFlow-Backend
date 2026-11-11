<?php

use App\Http\Controllers\TipoMembresiaController;
use App\Http\Controllers\UsuarioAdministrativoController;
use App\Http\Controllers\CambiarContrasenaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'usuario.activo'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // RF3: Cambio de contraseña del usuario autenticado.
    Route::put('/usuario/contrasena', [
        CambiarContrasenaController::class,
        'update',
    ])->name('usuario.contrasena.update');

    // RF11: Registrar tipo de membresía
    Route::post(
        '/tipos-membresia',
        [TipoMembresiaController::class, 'store']
    )->name('tipos-membresia.store');

    // RF12: Consultar tipos de membresía 
    Route::get(
        '/tipos-membresia',
        [TipoMembresiaController::class, 'index']
    )->name('tipos-membresia.index');

    // Consultar un tipo de membresía específico.
    Route::get(
        '/tipos-membresia/{id}',
        [TipoMembresiaController::class, 'show']
    )->name('tipos-membresia.show');

    // RF13: Actualizar y gestionar estado del tipo de membresía (Actualización).
    Route::patch(
        '/tipos-membresia/{id}',
        [TipoMembresiaController::class, 'update']
    )->name('tipos-membresia.update');

    // RF13: Actualizar y gestionar estado del tipo de membresía (Cambio de estado).
    Route::patch(
        '/tipos-membresia/{id}/estado',
        [TipoMembresiaController::class, 'cambiarEstado']
    )->name('tipos-membresia.estado');
});

// Rutas para la administración de usuarios administrativos.
Route::middleware(['auth:sanctum', 'usuario.activo', 'rol.administrativo:dueno'])->group(function () {

    // RF4: Registrar usuario administrativo.
    Route::post('/usuarios-administrativos', [UsuarioAdministrativoController::class, 'store'])
        ->name('usuarios-administrativos.store');

    // RF5: Consultar usuarios administrativos.
    Route::get('/usuarios-administrativos', [UsuarioAdministrativoController::class, 'index'])
        ->name('usuarios-administrativos.index');

    // RF6: Actualizar y gestionar estado de usuario administrativo (Actualización).
    Route::patch(
        '/usuarios-administrativos/{usuarioAdministrativo}',
        [UsuarioAdministrativoController::class, 'update']
    )->name('usuarios-administrativos.update');

    // RF6: Actualizar y gestionar estado de usuario administrativo (Cambio de estado).
    Route::patch(
        '/usuarios-administrativos/{usuarioAdministrativo}/estado',
        [UsuarioAdministrativoController::class, 'cambiarEstado']
    )->name('usuarios-administrativos.estado');
    //Usamos Patch y no Put porque ambas operaciones modifican parcialmente un recurso, no todo el recurso.
});
