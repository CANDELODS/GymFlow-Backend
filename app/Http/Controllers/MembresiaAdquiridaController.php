<?php

namespace App\Http\Controllers;

use App\Actions\MembresiaAdquirida\RegistrarMembresiaAdquirida;
use App\Http\Requests\MembresiaAdquirida\RegistrarMembresiaAdquiridaRequest;
use App\Http\Resources\MembresiaAdquiridaResource;

class MembresiaAdquiridaController extends Controller
{
    /**
     * Registra una membresía adquirida.
     */
    public function store(
        RegistrarMembresiaAdquiridaRequest $request,
        RegistrarMembresiaAdquirida $registrarMembresiaAdquirida
    ) {
        // Ejecutamos la acción con los datos previamente validados.
        $membresiaAdquirida = $registrarMembresiaAdquirida->ejecutar(
            $request->validated()
        );

        // Retornamos la respuesta con el recurso correspondiente.
        return response()->json([
            'mensaje' => 'Membresía adquirida registrada correctamente.',
            'membresia_adquirida' => new MembresiaAdquiridaResource($membresiaAdquirida),
        ], 201);
    }
}
