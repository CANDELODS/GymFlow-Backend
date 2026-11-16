<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembresiaAdquiridaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_membresia_adquirida' => $this->id_membresia_adquirida,
            'id_cliente' => $this->id_cliente,
            'id_tipo_membresia' => $this->id_tipo_membresia,
            'id_usuario_registro' => $this->id_usuario_registro,

            // Datos históricos del tipo de membresía adquirido.
            'nombre_tipo_snapshot' => $this->nombre_tipo_snapshot,
            'duracion_dias_snapshot' => $this->duracion_dias_snapshot,

            // Información de vigencia y pago.
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d H:i:s'),
            'valor_pagado' => $this->valor_pagado,
            'metodo_pago' => $this->metodo_pago,
        ];
    }
}
