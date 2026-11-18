<?php

namespace App\Http\Controllers;

use App\Actions\MembresiaAdquirida\RegistrarMembresiaAdquirida;
use App\Rules\ReglasMembresiaAdquirida;
use App\Actions\Cliente\RegistrarCliente;
use App\Http\Requests\Cliente\RegistrarClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\MembresiaAdquiridaResource;
use Throwable;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    /**
     * Registra un nuevo cliente.
     */

    public function store(
        RegistrarClienteRequest $request,
        RegistrarCliente $registrarCliente,
        RegistrarMembresiaAdquirida $registrarMembresia
    ) {
        // Ejecutamos la acción de registrar el cliente con los datos validados del request.
        $cliente = $registrarCliente->ejecutar(
            $request->validated()
        );

        // Si no se solicitó registrar una membresía, retornamos normalmente.
        if (!$request->boolean('registrar_membresia')) {
            return response()->json([
                'mensaje' => 'Cliente registrado correctamente.',
                'cliente' => new ClienteResource($cliente),
            ], 201);
        }

        // Obtenemos los datos de la membresía enviados por el frontend.
        // Estos datos no se validan en RegistrarClienteRequest,
        // ya que su validación se realiza posteriormente con Validator.
        $datosMembresia = $request->input('membresia');

        // Garantizamos que los datos recibidos sean un arreglo.
        if (!is_array($datosMembresia)) {
            $datosMembresia = [];
        }

        // Validamos los datos de la membresía.
        // Validator nos permite ejecutar validaciones manualmente, sin crear otro FormRequest
        $validador = Validator::make(
            $datosMembresia,
            ReglasMembresiaAdquirida::rules(),
            ReglasMembresiaAdquirida::messages(),
            ReglasMembresiaAdquirida::attributes()
        );

        // Si la validación falla, conservamos el cliente registrado.
        if ($validador->fails()) {
            return response()->json([
                'mensaje' => 'El cliente fue registrado, pero no se pudo registrar su membresía.',
                'cliente' => new ClienteResource($cliente),
                'membresia_registrada' => false,
                'errores' => $validador->errors(), // Coleción de errores de validación para la membresía.
            ], 201);
        }

        try {
            // Incorporamos el ID del cliente creado a los datos validados.
            $datosMembresia = array_merge(
                $validador->validated(),
                ['id_cliente' => $cliente->id_cliente]
            );

            // Ejecutamos la acción de registrar la membresía adquirida (Los datos ya están validados).
            $membresiaAdquirida = $registrarMembresia->ejecutar($datosMembresia);
            // Retornamos una respuesta indicando que tanto el cliente como la membresía fueron registrados correctamente.
            return response()->json([
                'mensaje' => 'Cliente y membresía registrados correctamente.',
                'cliente' => new ClienteResource($cliente),
                'membresia_adquirida' => new MembresiaAdquiridaResource($membresiaAdquirida),
            ], 201);
        } catch (Throwable $e) { // Capturamos cualquier excepción o error
            // Registramos el error para su revisión.
            report($e); // Función auxiliar de Laravel que envía la excepción al sistema de excepciones de Laravel (Logs)

            return response()->json([
                'mensaje' => 'El cliente fue registrado, pero ocurrió un error al registrar su membresía.',
                'cliente' => new ClienteResource($cliente),
                'membresia_registrada' => false,
            ], 201);
        }
    }
}
