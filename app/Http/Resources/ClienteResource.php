<?php

namespace App\Http\Resources;

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
        // Si el cliente no tiene membresías adquiridas, indicamos que no tiene membresía.
        if (!$this->ultimaMembresiaAdquirida) {
            return 'sin_membresia';
        }

        // Obtenemos la fecha de vencimiento de la última membresía.
        $fechaVencimiento = $this->ultimaMembresiaAdquirida
            ->fecha_vencimiento
            ->toDateString();

        // Obtenemos la fecha actual y el límite de los próximos diez días.
        $hoy = now()->toDateString();
        $limite = now()->addDays(10)->toDateString();

        // Determinamos el estado según la fecha de vencimiento.
        if ($fechaVencimiento < $hoy) {
            return 'vencida';
        }

        if ($fechaVencimiento <= $limite) {
            return 'por_vencer';
        }

        return 'activa';
    }
}
