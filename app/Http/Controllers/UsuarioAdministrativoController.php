<?php

namespace App\Http\Controllers;

use App\Actions\UsuarioAdministrativo\ConsultarUsuariosAdministrativos;
use App\Http\Requests\UsuarioAdministrativo\ConsultarUsuariosAdministrativosRequest;
use App\Http\Resources\UsuarioAdministrativoResource;
use App\Actions\UsuarioAdministrativo\RegistrarUsuarioAdministrativo;
use App\Http\Requests\UsuarioAdministrativo\RegistrarUsuarioAdministrativoRequest;

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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id)
    {
        //
    }
}
