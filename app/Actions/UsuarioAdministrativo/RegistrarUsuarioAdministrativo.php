<?php

namespace App\Actions\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;

class RegistrarUsuarioAdministrativo
{
    /**
     * Registra un nuevo usuario administrativo.
     */
    public function ejecutar(array $datos): UsuarioAdministrativo
    {
        // Creamos el usuario administrativo con los datos validados.
        return UsuarioAdministrativo::create([
            'usuario' => $datos['usuario'],
            'password' => $datos['password'],
            'rol' => $datos['rol'],
            'activo' => $datos['activo'],
        ]);
    }
}
