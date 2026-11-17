<?php

namespace App\Actions\Cliente;

use App\Models\Cliente;

class RegistrarCliente
{
    /**
     * Registra un nuevo cliente.
     */
    public function ejecutar(array $datos): Cliente
    {
        return Cliente::create([
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'documento' => $datos['documento'],
            'telefono' => $datos['telefono'],
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);
    }
}
