<?php

namespace App\Actions\MembresiaAdquirida;

use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RegistrarMembresiaAdquirida
{
    public function ejecutar(array $datos): MembresiaAdquirida
    {
        return DB::transaction(function () use ($datos) {

            // Obtenemos el tipo de membresía seleccionado.
            $tipoMembresia = TipoMembresia::where('activo', true)
                ->lockForUpdate()
                ->findOrFail($datos['id_tipo_membresia']);

            // Calculamos la fecha de vencimiento.
            // Carbon transforma "2026-11-15" en 15 de noviembre de 2026
            $fechaVencimiento = Carbon::parse($datos['fecha_inicio'])
                // Añadimos los días de duración de la membresía menos 1, ya que la fecha de inicio cuenta como el primer día.
                ->addDays($tipoMembresia->duracion_dias - 1)
                // Establecemos la hora de vencimiento a las 23:59:59 del último día.
                ->setTime(23, 59, 59);

            // Registramos la membresía adquirida.
            return MembresiaAdquirida::create([
                'id_cliente' => $datos['id_cliente'],
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'id_usuario_registro' => auth()->id(),
                'nombre_tipo_snapshot' => $tipoMembresia->nombre,
                'duracion_dias_snapshot' => $tipoMembresia->duracion_dias,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_vencimiento' => $fechaVencimiento,
                'valor_pagado' => $datos['valor_pagado'],
                'metodo_pago' => $datos['metodo_pago'],
            ]);
        });
    }
}