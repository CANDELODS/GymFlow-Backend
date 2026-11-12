<?php

namespace App\Actions\TipoMembresia;

use App\Models\TipoMembresia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultarTiposMembresia
{
    /**
     * Consulta los tipos de membresía registrados.
     */
    public function ejecutar(array $filtros): LengthAwarePaginator
    {
        // Inicializamos la consulta de tipos de membresía sin ejecutarla todavía.
        $consulta = TipoMembresia::query();

        // Aplicamos la búsqueda parcial cuando se proporciona un nombre.
        if (isset($filtros['nombre'])) {
            $consulta->where(
                'nombre',
                'LIKE',
                '%' . $filtros['nombre'] . '%'
            );
        }

        // Aplicamos el filtro cuando se proporciona un estado.
        // Usamos array_key_exists para permitir filtrar por valores falsy como false o 0.
        if (array_key_exists('activo', $filtros)) {
            $consulta->where('activo', $filtros['activo']);
        }

        // Ordenamos los resultados alfabéticamente por nombre.
        $consulta->orderBy('nombre');

        // Devolvemos los resultados paginados de cinco en cinco,
        // conservando los parámetros de consulta.
        return $consulta->paginate(5)->withQueryString();
    }
}
