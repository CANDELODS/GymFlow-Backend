<?php

namespace App\Http\Controllers;

use App\Actions\UsuarioAdministrativo\ActualizarUsuarioAdministrativo;
use App\Actions\UsuarioAdministrativo\CambiarEstadoUsuarioAdministrativo;
use App\Actions\UsuarioAdministrativo\ConsultarUsuariosAdministrativos;
use App\Actions\UsuarioAdministrativo\RegistrarUsuarioAdministrativo;
use App\Http\Requests\UsuarioAdministrativo\ActualizarUsuarioAdministrativoRequest;
use App\Http\Requests\UsuarioAdministrativo\CambiarEstadoUsuarioAdministrativoRequest;
use App\Http\Requests\UsuarioAdministrativo\ConsultarUsuariosAdministrativosRequest;
use App\Http\Requests\UsuarioAdministrativo\RegistrarUsuarioAdministrativoRequest;
use App\Http\Resources\UsuarioAdministrativoResource;
use App\Models\UsuarioAdministrativo;

class UsuarioAdministrativoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        ConsultarUsuariosAdministrativosRequest $request,
        ConsultarUsuariosAdministrativos $consultarUsuarios
    ) {
        // Ejecutamos la acción con los filtros previamente validados.
        $usuarios = $consultarUsuarios->ejecutar($request->validated());

        // Transformamos los usuarios y devolvemos la información de paginación.
        return UsuarioAdministrativoResource::collection($usuarios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        RegistrarUsuarioAdministrativoRequest $request,
        RegistrarUsuarioAdministrativo $registrarUsuario
    ) {
        // Ejecutamos la acción con los datos previamente validados.
        $usuario = $registrarUsuario->ejecutar($request->validated());

        // Devolvemos el usuario creado con código HTTP 201.
        return response()->json([
            'mensaje' => 'Usuario administrativo registrado correctamente.',
            'usuario' => new UsuarioAdministrativoResource($usuario),
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        ActualizarUsuarioAdministrativoRequest $request,
        UsuarioAdministrativo $usuarioAdministrativo,
        ActualizarUsuarioAdministrativo $actualizarUsuario
    ) {
        // Ejecutamos la acción con los datos previamente validados.
        $usuario = $actualizarUsuario->ejecutar(
            $usuarioAdministrativo,
            $request->validated()
        );

        // Devolvemos el usuario actualizado.
        return response()->json([
            'mensaje' => 'Usuario administrativo actualizado correctamente.',
            'usuario' => new UsuarioAdministrativoResource($usuario),
        ], 200);
    }

    /**
     * Cambia el estado del usuario administrativo.
     */
    public function cambiarEstado(
        CambiarEstadoUsuarioAdministrativoRequest $request,
        UsuarioAdministrativo $usuarioAdministrativo,
        CambiarEstadoUsuarioAdministrativo $cambiarEstado
    ) {
        // Ejecutamos la acción con el nuevo estado validado.
        $usuario = $cambiarEstado->ejecutar(
            $usuarioAdministrativo,
            $request->validated()['activo']
        );

        // Devolvemos el usuario con su estado actualizado.
        return response()->json([
            'mensaje' => 'Estado del usuario administrativo actualizado correctamente.',
            'usuario' => new UsuarioAdministrativoResource($usuario),
        ], 200);
    }
}
