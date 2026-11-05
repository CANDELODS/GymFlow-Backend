<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioAdministrativoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'usuario' => $this->usuario,
            'rol' => $this->rol,
            'activo' => $this->activo,
        ];
    }
}
