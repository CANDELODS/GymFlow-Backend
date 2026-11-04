<?php

namespace App\Actions\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class CambiarContrasenaUsuarioAdministrativo
{
    // Utilizamos Hasher en lugar de la fachada Hash porque es un contrato que permite depender de una abstracción y no de una implementación concreta.
    public function __construct(
        private Hasher $hasher
    ) {
        //
    }

    // Verificamos la contraseña actual y actualizamos la contraseña del usuario.
    public function handle(
        UsuarioAdministrativo $usuario,
        string $contrasenaActual,
        string $contrasenaNueva
    ): void {
        // Comprobamos que la contraseña actual coincida con la almacenada.
        if (!$this->hasher->check($contrasenaActual, $usuario->password)) {
            throw ValidationException::withMessages([
                'contrasena_actual' => [
                    'La contraseña actual es incorrecta.',
                ],
            ]);
        }

        // Asignamos la nueva contraseña al usuario.
        // El cast "hashed" del modelo se encarga de aplicar el hashing.
        $usuario->password = $contrasenaNueva;

        // Persistimos los cambios del usuario.
        $usuario->save();

        // Invalidamos las sesiones de otros dispositivos mediante el hash de la nueva contraseña.
        //Este método provoca que las demás sesiones detecten que el hash de contraseña cambió y se invaliden cuando realicen su siguiente solicitud.
        Auth::guard('web')->logoutOtherDevices($contrasenaNueva);
    }
}
