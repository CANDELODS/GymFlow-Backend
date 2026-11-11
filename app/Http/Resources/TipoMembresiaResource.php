<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoMembresiaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_tipo_membresia' => $this->id_tipo_membresia,
            'nombre' => $this->nombre,
            'duracion_dias' => $this->duracion_dias,
            'precio' => $this->precio,
            'activo' => $this->activo,
        ];
    }
}
