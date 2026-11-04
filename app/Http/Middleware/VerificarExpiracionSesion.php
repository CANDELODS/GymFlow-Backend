<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarExpiracionSesion
{
    /**
     * Comprueba si la sesión del usuario administrativo ha expirado por inactividad.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Comprobamos si existe un usuario autenticado mediante el guard web y si tiene una sesión activa
        if (Auth::guard('web')->check() && $request->hasSession()) {

            // Recuperamos la fecha de la última actividad registrada en la sesión
            $ultimaActividad = $request->session()->get('ultima_actividad');

            // Obtenemos el tiempo máximo de inactividad permitido en segundos
            $tiempoExpiracion = config('session.lifetime') * 60;

            // Comprobamos si existe una actividad anterior y si han transcurrido 30 minutos o más
            if (
                $ultimaActividad !== null &&
                (now()->timestamp - $ultimaActividad) >= $tiempoExpiracion
            ) {
                // Cerramos la sesión de autenticación del usuario
                Auth::guard('web')->logout();

                // Invalidamos la sesión actual y regeneramos el token CSRF
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Rechazamos el acceso porque la sesión ha expirado
                abort(401, 'La sesión ha expirado por inactividad. Inicia sesión nuevamente.');
            }

            // Registramos la fecha y hora de la actividad actual
            $request->session()->put('ultima_actividad', now()->timestamp);
        }

        return $next($request);
    }
}
