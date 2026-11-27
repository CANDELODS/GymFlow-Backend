<?php

namespace App\Events;

use App\Models\MembresiaAdquirida;
// Necesitamos que el evento se procese de manera síncrona cuando lo despachemos, por lo cual...
// No usaremos ShouldQueue ni ShouldBroadcast

class MembresiaAdquiridaModificadaEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public MembresiaAdquirida $membresiaAdquirida, // Modelo con la info actualizada
        public int $idUsuario, // Usuario que realizó la modificación
        public string $motivo, // Justificación proporcionada en la solicitud
        public array $cambios // Campos modificados con sus valores anteriores y nuevos
    )
    {}
}
