<?php

namespace App\Http\Resources;

use App\Support\EstadoMembresia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cliente' => $this->id_cliente,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'activo' => $this->activo,
            'autorizacion_tratamiento' => $this->autorizacion_tratamiento,
            'fecha_autorizacion' => $this->fecha_autorizacion?->format('Y-m-d H:i:s'),

            // Incluimos la última membresía adquirida si la relación fue cargada.
            'membresia' => $this->whenLoaded(
                'ultimaMembresiaAdquirida',
                fn() => $this->ultimaMembresiaAdquirida
                    ? new MembresiaAdquiridaResource($this->ultimaMembresiaAdquirida)
                    : null
            ),

            // Calculamos el estado de la última membresía adquirida.
            'estado_membresia' => $this->when(
                $this->resource->relationLoaded('ultimaMembresiaAdquirida'),
                fn() => $this->calcularEstadoMembresia()
            ),
        ];
    }

    /**
     * Calcula el estado de la última membresía adquirida del cliente.
     */
    private function calcularEstadoMembresia(): string
    {
        return EstadoMembresia::calcular(
            $this->ultimaMembresiaAdquirida?->fecha_vencimiento
        );
    }
}
