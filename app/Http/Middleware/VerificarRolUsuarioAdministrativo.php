<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRolUsuarioAdministrativo
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    // El operador ... permite recibir una cantidad variable de argumentos, ejemplo: 'rol.administrativo:dueno,empleado'
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Obtenemos el usuario autenticado que realiza la solicitud.
        $usuario = $request->user();

        // Verificamos que exita un usuario autenticado y que tenga uno de los roles permitidos.
        if (!$usuario || !in_array($usuario->rol, $roles, true)) { // True compara valor y tipo de dato
            abort(403, 'No tienes permisos para realizar esta operación.');
        }

        // Si tiene autorización, continuamos con la solicitud.
        return $next($request);
    }
}
