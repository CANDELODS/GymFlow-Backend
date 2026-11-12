<?php

namespace App\Http\Controllers;

use App\Actions\TipoMembresia\ConsultarTiposMembresia;
use App\Actions\TipoMembresia\RegistrarTipoMembresia;
use App\Http\Requests\TipoMembresia\ConsultarTiposMembresiaRequest;
use App\Http\Requests\TipoMembresia\RegistrarTipoMembresiaRequest;
use App\Http\Resources\TipoMembresiaResource;
use Illuminate\Http\Request;

class TipoMembresiaController extends Controller
{
    /**
     * Muestra todos los tipos de membresía.
     */
    public function index(
        ConsultarTiposMembresiaRequest $request,
        ConsultarTiposMembresia $consultarTiposMembresia
    ) {
        // Ejecutamos la acción con los filtros previamente validados.
        $tiposMembresia = $consultarTiposMembresia->ejecutar($request->validated());

        // Transformamos los tipos de mebresía y devolvemos la información de paginación.
        return TipoMembresiaResource::collection($tiposMembresia);
    }

    /**
     * Registra un nuevo tipo de membresía.
     */
    public function store(
        RegistrarTipoMembresiaRequest $request,
        RegistrarTipoMembresia $registrarTipoMembresia
    ) {
        // Ejecutamos la acción para registrar el tipo de membresía.
        $tipoMembresia = $registrarTipoMembresia->ejecutar(
            $request->validated()
        );

        // Retornamos la respuesta con el recurso correspondiente.
        return response()->json([
            'mensaje' => 'Tipo de membresía registrado correctamente.',
            'tipo_membresia' => new TipoMembresiaResource($tipoMembresia),
        ], 201);
    }

    /**
     * Muestra un tipo de membresía específico.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Actualiza un tipo de membresía existente.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Cambia el estado de un tipo de membresía.
     */
    public function cambiarEstado(string $id)
    {
        //
    }
}
