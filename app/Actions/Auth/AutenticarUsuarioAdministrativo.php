<?php

namespace App\Actions\Auth;

use App\Models\UsuarioAdministrativo;
use App\Actions\Auth\GestionarIntentosFallidos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Aplicamos inyección de dependencias mediante el constructor para recibir
 * GestionarIntentosFallidos, evitando crear sus instancias directamente.
 * Esto reduce el acoplamiento entre clases y permite que Laravel gestione
 * automáticamente la creación y entrega de las dependencias necesarias.
 */

class AutenticarUsuarioAdministrativo
{

    public function __construct(
        private GestionarIntentosFallidos $gestorIntentos
    ){}
    
    
    //?UsuarioAdministrativo indica que el método puede devolver un objeto UsuarioAdministrativo o null
    public function ejecutar(Request $request): ?UsuarioAdministrativo
    {
        $nombreUsuario = $request->input('usuario');

        //DB::transaction() y lockForUpdate() permiten coordinar las actualizaciones del contador y del bloqueo.
        //Una transacción permite agrupar varias operaciones sobre la base de datos para que se ejecuten como una unidad.
        //lockForUpdate solicita un bloqueo de las filas seleccionadas durante la transacción, para que otras transacciones no puedan modificarlas libremente mientras mantenemos ese bloqueo.
        return DB::transaction(function () use ($nombreUsuario, $request) {
            $usuario = UsuarioAdministrativo::where(
                'usuario',
                $nombreUsuario
            )->lockForUpdate()->first();

            if (! $usuario || ! $usuario->activo) {
                return null;
            }

            //Rechazamos usuarios con bloqueo temporal vigente
            if($this->gestorIntentos->estaBloqueado($usuario)){
                return null;
            }

            //Si el bloqueo ya expiró, reiniciamos el contador antes de evaluar el nuevo intento
            $this->gestorIntentos->reiniciarSiBloqueoExpirado($usuario);

            //Verificamos la contraseña recibida con el hash almacenado
            if (! Hash::check(
                $request->input('password'),
                $usuario->password
            )) {
                $this->gestorIntentos->registrarIntentoFallido($usuario);
                return null;
            }

            //Restablecemos los intentos fallidos después de una autenticación correcta
            $this->gestorIntentos->reiniciarIntentos($usuario);

            return $usuario;
        });
    }
}
