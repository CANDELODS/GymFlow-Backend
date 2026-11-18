<?php

namespace App\Actions\Cliente;

use App\Models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultarClientes
{
    /**
     * Consulta los clientes registrados aplicando los filtros disponibles.
     */
    public function ejecutar(array $filtros): LengthAwarePaginator
    {
        // Inicializamos la consulta de clientes sin ejecutarla todavía.
        $consulta = Cliente::query()
            ->with('ultimaMembresiaAdquirida'); // Función HasOne entre Cliente y Membresia Adquirida

        // Aplicamos la búsqueda parcial en los campos del cliente.
        if (!empty($filtros['buscar'])) {
            $consulta->where(function ($query) use ($filtros) {
                $termino = '%' . $filtros['buscar'] . '%';

                $query->where('nombres', 'LIKE', $termino)
                    ->orWhere('apellidos', 'LIKE', $termino)
                    ->orWhere('documento', 'LIKE', $termino)
                    ->orWhere('telefono', 'LIKE', $termino);
            });
        }

        // Aplicamos el filtro por estado del cliente.
        // Usamos array_key_exists para permitir filtrar por valores falsy como false o 0.
        if (array_key_exists('activo', $filtros)) {
            $consulta->where('activo', $filtros['activo']);
        }

        // Aplicamos el filtro por estado de la última membresía adquirida.
        if (isset($filtros['estado_membresia'])) {
            $estado = $filtros['estado_membresia'];

            // Clientes que todavía no tienen membresías adquiridas.
            if ($estado === 'sin_membresia') {
                // whereDoesntHave encuentra clientes que nunca han adquirido una membresía (Relación HasMany)
                $consulta->whereDoesntHave('membresiasAdquiridas');
            } else {
                // Consultamos únicamente la última membresía adquirida.
                // whereHas busca clientes cuya última membresía cumple la condición (Relación HasOne).
                $consulta->whereHas(
                    'ultimaMembresiaAdquirida',
                    function ($query) use ($estado) {
                        // Obtenemos la fecha actual sin considerar la hora.
                        $hoy = now()->toDateString();

                        // Calculamos el límite de los diez días siguientes.
                        $limite = now()->addDays(10)->toDateString();

                        // Filtramos según el estado calculado a partir del vencimiento.
                        // whereDate compara únicamente la parte de fecha de una columna, ignorando la hora
                        switch ($estado) {
                            case 'activa':
                                $query->whereDate('fecha_vencimiento', '>', $limite);
                                break;

                            case 'por_vencer':
                                $query->whereDate('fecha_vencimiento', '>=', $hoy)
                                    ->whereDate('fecha_vencimiento', '<=', $limite);
                                break;

                            case 'vencida':
                                $query->whereDate('fecha_vencimiento', '<', $hoy);
                                break;
                        }
                    }
                );
            }
        }

        // Ordenamos los clientes alfabéticamente por nombres y apellidos.
        $consulta->orderBy('nombres')
            ->orderBy('apellidos');

        // Devolvemos los resultados paginados de cinco en cinco,
        // conservando los parámetros de consulta.
        return $consulta->paginate(5)->withQueryString();
    }
}
