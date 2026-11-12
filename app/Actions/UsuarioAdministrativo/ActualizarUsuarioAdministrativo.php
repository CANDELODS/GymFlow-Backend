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
        UsuarioAdministrativo $usuario,
        array $datos
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

            // Determinamos el rol y el estado que tendrá el usuario.
            $nuevoRol = $datos['rol'] ?? $usuario->rol;
            $nuevoEstado = array_key_exists('activo', $datos)
                ? (bool) $datos['activo']
                : $usuario->activo;

            // Evitamos que el dueño desactive su propia cuenta.
            if (
                array_key_exists('activo', $datos) &&
                !$nuevoEstado &&
                auth()->id() === $usuario->id_usuario
            ) {
                throw ValidationException::withMessages([
                    'activo' => 'No puedes desactivar tu propia cuenta.',
                ]);
            }

            // Verificamos si el dueño activo dejará de ser un dueño activo.
            $dejaDeSerDuenoActivo =
                $usuario->activo &&
                $usuario->rol === 'dueno' &&
                ($nuevoRol === 'empleado' || !$nuevoEstado);

            if ($dejaDeSerDuenoActivo) {

                // Contamos los dueños que tienen su cuenta activa.
                $cantidadDuenosActivos = $usuariosBloqueados
                    ->where('rol', 'dueno')
                    ->where('activo', true)
                    ->count();

                // Evitamos que el sistema se quede sin dueños activos.
                if ($cantidadDuenosActivos <= 1) {

                    // Identificamos qué atributo provoca la restricción.
                    $campo = !$nuevoEstado ? 'activo' : 'rol';

                    $mensaje = !$nuevoEstado
                        ? 'No puedes desactivar al último dueño activo del sistema.'
                        : 'No puedes cambiar el rol del último dueño activo del sistema.';

                    throw ValidationException::withMessages([
                        $campo => $mensaje,
                    ]);
                }
            }

            // Actualizamos el nombre de usuario solamente si fue enviado.
            if (array_key_exists('usuario', $datos)) {
                $usuario->usuario = $datos['usuario'];
            }

            // Actualizamos el rol solamente si fue enviado.
            if (array_key_exists('rol', $datos)) {
                $usuario->rol = $nuevoRol;
            }

            // Actualizamos la contraseña solamente si fue enviada.
            if (isset($datos['password'])) {
                $usuario->password = $datos['password'];
            }

            // Actualizamos el estado solamente si fue enviado.
            if (array_key_exists('activo', $datos)) {
                $usuario->activo = $nuevoEstado;
            }

            // Guardamos los cambios en la base de datos.
            $usuario->save();

            return $usuario;
        });
    }
}
