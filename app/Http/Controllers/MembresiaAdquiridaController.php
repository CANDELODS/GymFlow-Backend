<?php

namespace App\Http\Controllers;

use App\Actions\MembresiaAdquirida\ActualizarMembresiaAdquirida;
use App\Http\Requests\MembresiaAdquirida\ActualizarMembresiaAdquiridaRequest;
use App\Models\MembresiaAdquirida;
use App\Actions\Cliente\ConsultarMembresiasCliente;
use App\Actions\MembresiaAdquirida\RegistrarMembresiaAdquirida;
use App\Http\Requests\Cliente\ConsultarMembresiasClienteRequest;
use App\Http\Requests\MembresiaAdquirida\RegistrarMembresiaAdquiridaRequest;
use App\Http\Resources\MembresiaAdquiridaResource;
use App\Models\Cliente;

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

    /**
     * Actualiza una membresía adquirida existente.
     */
    public function update(
        ActualizarMembresiaAdquiridaRequest $request,
        MembresiaAdquirida $membresiaAdquirida,
        ActualizarMembresiaAdquirida $actualizarMembresiaAdquirida
    ) {
        // Ejecutamos la acción con los datos validados del request.
        $membresiaActualizada = $actualizarMembresiaAdquirida->ejecutar(
            $membresiaAdquirida,
            $request->validated()
        );

        // Cargamos las relaciones necesarias para devolver la información completa.
        $membresiaActualizada->load([
            'cliente', // Relación BelongsTo con cliente
            'tipoMembresia', // Relación BelongsTo con TipoMembresia
            'usuarioRegistro', // Relación BelongsTo con UsuarioAdministrativo
        ]);

        // Devolvemos la membresía actualizada.
        return response()->json([
            'mensaje' => 'Membresía adquirida actualizada correctamente.',
            'membresia_adquirida' => new MembresiaAdquiridaResource($membresiaActualizada),
        ], 200);
    }

    /**
     * Consulta la membresía vigente y el historial de adquisiciones de un cliente.
     */
    public function porCliente(
        ConsultarMembresiasClienteRequest $request,
        Cliente $cliente,
        ConsultarMembresiasCliente $consultarMembresiasCliente
    ) {
        // Ejecutamos la consulta de membresías del cliente.
        $resultado = $consultarMembresiasCliente->ejecutar($cliente);

        // Transformamos la membresía vigente si existe, si no, devolvemos null.
        $membresiaVigente = $resultado['membresia_vigente']
            ? (new MembresiaAdquiridaResource(
                $resultado['membresia_vigente']
            ))->resolve($request) // Obtenemos el arreglo transformado por el resource.
            : null;

        // Transformamos el historial conservando la estructura de paginación.
        $historial = MembresiaAdquiridaResource::collection(
            $resultado['historial']
        )
            // response($request) genera la respuesta del Resource Collection,
            // incluyendo la estructura de paginación de Laravel.
            // getData(true) nos permite recuperar esa respuesta como un arreglo, con esto logramos
            // Incluirla dentro de $historial sin perder los enlaces ni metadatos de paginación.
            ->response($request)->getData(true);

        // Retornamos la información de membresías del cliente.
        return response()->json([
            'mensaje' => 'Información de membresías consultada correctamente.',
            'membresia_vigente' => $membresiaVigente,
            'historial' => $historial,
        ], 200);
    }
}
