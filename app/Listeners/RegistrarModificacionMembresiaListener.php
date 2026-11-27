<?php

namespace App\Listeners;

use App\Events\MembresiaAdquiridaModificadaEvent;
use App\Models\RegistroModificacion;

class RegistrarModificacionMembresiaListener
{
    /**
     * Registrar la modificación de una membresía adquirida.
     */
    public function handle(MembresiaAdquiridaModificadaEvent $event): void
    {
        // Crear el registro general de auditoría.
        $registro = RegistroModificacion::create([
            'id_membresia_adquirida' => $event->membresiaAdquirida->id_membresia_adquirida,
            'id_usuario' => $event->idUsuario,
            'fecha_hora' => now(),
            'motivo' => $event->motivo,
        ]);

        // Registrar el detalle de cada campo modificado.
        // $event->cambios = Arreglo asociativo recibido desde el evento
        foreach ($event->cambios as $campo => $valores) {
            // detalles () = Relación hasMany entre RegistroModificacion y DetalleModificacion
            // Con esto, también se crea el registro con el id del padre en DetalleModificacion.
            $registro->detalles()->create([
                'campo_modificado' => $campo,
                'valor_anterior' => $valores['anterior'],
                'valor_nuevo' => $valores['nuevo'],
            ]);
        }
    }
}