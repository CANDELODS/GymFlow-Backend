<?php

namespace App\Http\Controllers;

use App\Actions\TipoMembresia\ActualizarTipoMembresia;
use App\Actions\TipoMembresia\CambiarEstadoTipoMembresia;
use App\Actions\TipoMembresia\ConsultarTiposMembresia;
use App\Actions\TipoMembresia\RegistrarTipoMembresia;
use App\Http\Requests\TipoMembresia\ActualizarTipoMembresiaRequest;
use App\Http\Requests\TipoMembresia\CambiarEstadoTipoMembresiaRequest;
use App\Http\Requests\TipoMembresia\ConsultarTiposMembresiaRequest;
use App\Http\Requests\TipoMembresia\RegistrarTipoMembresiaRequest;
use App\Http\Resources\TipoMembresiaResource;
use App\Models\TipoMembresia;

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
    public function update(
        ActualizarTipoMembresiaRequest $request,
        TipoMembresia $tipoMembresia,
        ActualizarTipoMembresia $actualizarTipoMembresia
    ) {
        // Ejecutamos la acción con los datos validados para actualizar el tipo de membresía.
        $tipoMembresiaActualizado = $actualizarTipoMembresia->ejecutar(
            $tipoMembresia,
            $request->validated()
        );

        // Devolvemos el tipo de membresía actualizado
        return response()->json([
            'mensaje' => 'Tipo de membresía actualizado correctamente.',
            'tipo_membresia' => new TipoMembresiaResource($tipoMembresiaActualizado)
        ], 200);
    }


    /**
     * Cambia el estado de un tipo de membresía.
     */
    public function cambiarEstado(
        CambiarEstadoTipoMembresiaRequest $request,
        TipoMembresia $tipoMembresia,
        CambiarEstadoTipoMembresia $cambiarEstadoTipoMembresia
    ) {
        // Ejecutamos la acción con el estado validado.
        $tipoMembresiaActualizado = $cambiarEstadoTipoMembresia->ejecutar(
            $tipoMembresia,
            $request->validated()['activo']
        );

        // Devolvemos el tipo de membresía actualizado.
        return response()->json([
            'mensaje' => 'Estado del tipo de membresía actualizado correctamente.',
            'tipo_membresia' => new TipoMembresiaResource($tipoMembresiaActualizado),
        ], 200);
    }
}
