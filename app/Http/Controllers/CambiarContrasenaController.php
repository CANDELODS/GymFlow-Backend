<?php

namespace App\Http\Controllers;

use App\Actions\UsuarioAdministrativo\CambiarContrasenaUsuarioAdministrativo;
use App\Http\Requests\Auth\CambiarContrasenaRequest;
use App\Models\UsuarioAdministrativo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CambiarContrasenaController extends Controller
{
    // Gestionamos el cambio de contraseña del usuario autenticado.
    public function update(
        CambiarContrasenaRequest $request,
        CambiarContrasenaUsuarioAdministrativo $accion
    ): JsonResponse {
    // Obtenemos el usuario autenticado.
        /** @var UsuarioAdministrativo $usuario */
        $usuario = $request->user();

        // Ejecutamos la acción de cambio de contraseña.
        $accion->handle(
            $usuario,
            $request->validated('contrasena_actual'),
            $request->validated('contrasena_nueva')
        );

        // Cerramos la sesión de autenticación actual.
        Auth::guard('web')->logout();

        // Comprobamos si la solicitud tiene una sesión disponible.
        if ($request->hasSession()) {
            // Invalidamos la sesión actual y regeneramos el token CSRF.
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Informamos que la contraseña fue actualizada correctamente.
        return response()->json([
            'mensaje' => 'La contraseña se ha actualizado correctamente.',
        ], 200);
    }
}
