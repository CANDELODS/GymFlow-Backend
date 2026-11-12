<?php

namespace App\Actions\TipoMembresia;

use App\Models\TipoMembresia;
use Illuminate\Support\Facades\DB;

class CambiarEstadoTipoMembresia
{
    public function ejecutar(
        TipoMembresia $tipoMembresia,
        bool $activo // Booleano que indica si el tipo de membresía debe estar activo o inactivo
    ): TipoMembresia {
        return DB::transaction(function () use ($tipoMembresia, $activo) {

            // Obtenemos el registro y bloqueamos su modificación.
            $tipoMembresia = TipoMembresia::query()
                ->whereKey($tipoMembresia->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Actualizamos el estado del tipo de membresía.
            $tipoMembresia->update([
                'activo' => $activo,
            ]);

            // Devolvemos el registro actualizado.
            return $tipoMembresia->refresh();
        });
    }
}
