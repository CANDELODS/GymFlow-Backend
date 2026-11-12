<?php

namespace App\Actions\TipoMembresia;

use App\Models\TipoMembresia;

class ActualizarTipoMembresia
{
    public function ejecutar(TipoMembresia $tipoMembresia, array $datos): TipoMembresia
    {
        // Actualizamos únicamente los campos recibidos.
        $tipoMembresia->update($datos);

        // Devolvemos el registro actualizado.
        return $tipoMembresia->refresh();
    }
}
