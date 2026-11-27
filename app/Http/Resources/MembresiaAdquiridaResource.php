<?php

namespace App\Http\Resources;

use App\Support\EstadoMembresia;
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

            // Estado calculado de la membresía adquirida.
            'estado' => EstadoMembresia::calcular($this->fecha_vencimiento),

            // Información del usuario administrativo que registró la adquisición.
            'usuario_registro' => $this->whenLoaded(
                'usuarioRegistro',
                fn() => $this->usuarioRegistro ? [
                    'id_usuario' => $this->usuarioRegistro->id_usuario,
                    'usuario' => $this->usuarioRegistro->usuario,
                    'rol' => $this->usuarioRegistro->rol,
                    'activo' => $this->usuarioRegistro->activo,
                ] : null
            ),

            // Información del cliente asociado.
            'cliente' => $this->whenLoaded(
                'cliente',
                fn() => $this->cliente ? [
                    'id_cliente' => $this->cliente->id_cliente,
                    'nombres' => $this->cliente->nombres,
                    'apellidos' => $this->cliente->apellidos,
                    'documento' => $this->cliente->documento,
                    'activo' => $this->cliente->activo,
                ] : null
            ),

            // Información del tipo de membresía asociado.
            'tipo_membresia' => $this->whenLoaded(
                'tipoMembresia',
                fn() => $this->tipoMembresia ? [
                    'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
                    'nombre' => $this->tipoMembresia->nombre,
                    'duracion_dias' => $this->tipoMembresia->duracion_dias,
                    'precio' => $this->tipoMembresia->precio,
                    'activo' => $this->tipoMembresia->activo,
                ] : null
            ),

        ];
    }
}
