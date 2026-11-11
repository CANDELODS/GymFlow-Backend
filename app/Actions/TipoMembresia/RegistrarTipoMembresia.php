<?php

namespace App\Actions\TipoMembresia;

use App\Models\TipoMembresia;

class RegistrarTipoMembresia
{
    /**
     * Registra un nuevo tipo de membresía.
     */
    public function ejecutar(array $datos): TipoMembresia
    {
        // Creamos el tipo de membresía.
        $tipoMembresia = TipoMembresia::create($datos);

        // Actualizamos la instancia con los valores almacenados en la base de datos.
        // incluyendo aquellos asignados automáticamente mediante valores predeterminados.
        return $tipoMembresia->refresh();
    }
}
