<?php

namespace App\Actions\Cliente;

use App\Models\Cliente;

class CambiarEstadoCliente
{
    public function ejecutar(Cliente $cliente, bool $activo): Cliente
    {
        // Actualizamos únicamente el estado del cliente.
        $cliente->update([
            'activo' => $activo,
        ]);

        // Devolvemos el cliente con su estado actualizado.
        return $cliente->refresh();
    }
}