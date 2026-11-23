<?php

namespace App\Actions\Cliente;

use App\Models\Cliente;

class ActualizarCliente
{
    public function ejecutar(Cliente $cliente, array $datos): Cliente
    {
        // Actualizamos únicamente los campos recibidos.
        $cliente->update($datos);

        // Devolvemos el cliente actualizado.
        return $cliente->refresh();
    }
}