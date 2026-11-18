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
        ];
    }
}
