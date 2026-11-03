<?php

namespace App\Actions\Auth;

use App\Models\UsuarioAdministrativo;

class GestionarIntentosFallidos
{
    //Comprobamos si el usuario tiene un bloqueo temporal vigente
    public function estaBloqueado(UsuarioAdministrativo $usuario): bool
    {
        //now() nos devuelve la fecha y hora actuales | lt = less than (Menor que);
        return $usuario->bloqueado_hasta !== null &&
            now()->lt($usuario->bloqueado_hasta); //Devuelve true si está bloqueado y false si no
    }

    //Reiniciamos el contador si el bloqueo temporal ya expiró
    //Este método no retorna nada, ya que solo modifica el estado del usuario, no necesita devolver nada
    public function reiniciarSiBloqueoExpirado(UsuarioAdministrativo $usuario): void
    {
        //gte = greater than or equal (Mayor o igual que)
        if (
            $usuario->bloqueado_hasta !== null &&
            now()->gte($usuario->bloqueado_hasta)
        ) {
            $usuario->intentos_fallidos = 0;
            $usuario->bloqueado_hasta = null;
            $usuario->save();
        }
    }

    //Registramos un intento fallido y aplicamos el bloqueo al alcanzar cinco intentos
    public function registrarIntentoFallido(UsuarioAdministrativo $usuario): void
    {
        $usuario->intentos_fallidos++;

        if ($usuario->intentos_fallidos >= 5) {
            $usuario->bloqueado_hasta = now()->addMinutes(15);
        }

        $usuario->save();
    }

    //Restablecemos los intentos fallidos después de una autenticación correcta
    public function reiniciarIntentos(UsuarioAdministrativo $usuario): void
    {
        $usuario->intentos_fallidos = 0;
        $usuario->bloqueado_hasta = null;
        $usuario->save();
    }
}
