<?php

namespace App\Actions\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CambiarEstadoUsuarioAdministrativo
{
    /**
     * Cambia el estado de un usuario administrativo.
     */
    public function ejecutar(
        UsuarioAdministrativo $usuario,
        bool $activo // Booleano que indica si el usuario debe estar activo o inactivo
    ): UsuarioAdministrativo {

        return DB::transaction(function () use ($usuario, $activo) {

            // Bloqueamos los registros para evitar modificaciones simultáneas.
            $usuariosBloqueados = UsuarioAdministrativo::orderBy('id_usuario')
                ->lockForUpdate()
                ->get();

            // Obtenemos el registro actualizado desde los registros bloqueados.
            $usuario = $usuariosBloqueados->firstWhere(
                'id_usuario',
                $usuario->id_usuario
            );

            // Evitamos que el dueño desactive su propia cuenta.
            // Si el usuario debe estar inactivo y el usuario actual es el mismo que se está modificando...
            if (!$activo && auth()->id() === $usuario->id_usuario) {
                throw ValidationException::withMessages([
                    'activo' => 'No puedes desactivar tu propia cuenta.',
                ]);
            }

            // Verificamos si se intenta desactivar a un dueño activo.
            // Si el usuario debe estar inactivo, y el usuario actual es un dueño activo...
            if (!$activo && $usuario->activo && $usuario->rol === 'dueno') {

                // Contamos los dueños que tienen su cuenta activa.
                $cantidadDuenosActivos = $usuariosBloqueados
                    ->where('rol', 'dueno')
                    ->where('activo', true)
                    ->count();

                // Evitamos que el sistema se quede sin dueños activos.
                if ($cantidadDuenosActivos <= 1) {
                    throw ValidationException::withMessages([
                        'activo' => 'No puedes desactivar al último dueño activo del sistema.',
                    ]);
                }
            }

            // Actualizamos el estado del usuario.
            $usuario->activo = $activo;
            $usuario->save();

            return $usuario;
        });
    }
}
