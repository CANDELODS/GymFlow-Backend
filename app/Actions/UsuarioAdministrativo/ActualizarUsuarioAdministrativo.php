<?php

namespace App\Actions\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarUsuarioAdministrativo
{
    /**
     * Actualiza la información de un usuario administrativo.
     */
    public function ejecutar(
        UsuarioAdministrativo $usuario, // Recibimos el modelo para aprovechar el Route Model Binding
        array $datos // Datos validados de ActualizarUsuarioAdministrativoRequest
    ): UsuarioAdministrativo {
        
        return DB::transaction(function () use ($usuario, $datos) {

            // Bloqueamos los registros para evitar modificaciones simultáneas.
            $usuariosBloqueados = UsuarioAdministrativo::orderBy('id_usuario')
                ->lockForUpdate()
                ->get();

            // Obtenemos el registro actualizado desde los registros bloqueados.
            $usuario = $usuariosBloqueados->firstWhere(
                'id_usuario',
                $usuario->id_usuario
            );

            // Verificamos si se intenta cambiar el rol de un dueño activo a empleado.
            if (
                $usuario->activo &&
                $usuario->rol === 'dueno' &&
                $datos['rol'] === 'empleado'
            ) {
                // Contamos los dueños que tienen su cuenta activa.
                $cantidadDuenosActivos = $usuariosBloqueados
                    ->where('rol', 'dueno')
                    ->where('activo', true)
                    ->count();

                // Evitamos que el sistema se quede sin dueños activos.
                if ($cantidadDuenosActivos <= 1) {
                    throw ValidationException::withMessages([
                        'rol' => 'No puedes cambiar el rol del último dueño activo del sistema.',
                    ]);
                }
            }

            // Actualizamos los datos básicos del usuario.
            $usuario->usuario = $datos['usuario'];
            $usuario->rol = $datos['rol'];

            // Actualizamos la contraseña solamente si fue enviada.
            if (isset($datos['password'])) {
                $usuario->password = $datos['password'];
            }

            // Guardamos los cambios en la base de datos.
            $usuario->save();

            return $usuario;
        });
    }
}
