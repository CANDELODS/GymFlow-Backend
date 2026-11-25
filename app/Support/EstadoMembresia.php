<?php

namespace App\Support;

use Carbon\CarbonInterface;

class EstadoMembresia
{
    /**
     * Calcula el estado de una membresía según su fecha de vencimiento.
     */
    public static function calcular(?CarbonInterface $fechaVencimiento): string
    {
        // Si no existe una fecha de vencimiento, no hay membresía.
        if (!$fechaVencimiento) {
            return 'sin_membresia';
        }

        // Obtenemos la fecha actual y el límite de los próximos diez días.
        $hoy = now()->toDateString();
        $limite = now()->addDays(10)->toDateString();
        $fecha = $fechaVencimiento->toDateString();

        // Determinamos el estado según la fecha de vencimiento.
        if ($fecha < $hoy) {
            return 'vencida';
        }

        if ($fecha <= $limite) {
            return 'por_vencer';
        }

        return 'activa';
    }
}