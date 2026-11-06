<?php

namespace App\Actions\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultarUsuariosAdministrativos
{
    /**
     * Consulta los usuarios administrativos registrados.
     */
    public function ejecutar(array $filtros): LengthAwarePaginator
    {
        // Inicializamos la consulta de usuarios administrativos sin ejecutarla todavía.
        $consulta = UsuarioAdministrativo::query();

        // Aplicamos el filtro cuando se proporciona un nombre de usuario.
        if (!empty($filtros['usuario'])) {
            $consulta->where('usuario', 'LIKE', '%' . $filtros['usuario'] . '%');
        }

        // Ordenamos los resultados por nombre de usuario.
        $consulta->orderBy('usuario');

        // Devolvemos los resultados paginados de cinco en cinco, además de sus parámetros de consulta.
        return $consulta->paginate(5)->withQueryString();
    }
}
