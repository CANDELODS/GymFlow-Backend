<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioActivo
{
    /**
     * Verifica que el usuario autenticado tenga su cuenta activa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtenemos el usuario autenticado.
        $usuario = $request->user();

        // Verificamos si su cuenta se encuentra desactivada.
        if ($usuario && !$usuario->activo) {

            // Cerramos la sesión del usuario.
            Auth::guard('web')->logout();

            // Invalidamos la sesión y regeneramos el token CSRF.
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // Rechazamos el acceso a la funcionalidad protegida.
            return response()->json([
                'mensaje' => 'Tu cuenta administrativa se encuentra desactivada. Contacta con el dueño del sistema.',
            ], 403);
        }

        return $next($request);
    }
}
