<?php

namespace App\Actions\Cliente;

use App\Models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsultarMembresiasCliente
{
    /**
     * Consulta la membresía vigente y el historial de adquisiciones de un cliente.
     *
     * @return array{
     *     membresia_vigente: \App\Models\MembresiaAdquirida|null,
     *     historial: LengthAwarePaginator
     * }
     */
    public function ejecutar(Cliente $cliente): array
    {
        // Consultamos la última membresía adquirida del cliente.
        // La utilizamos para determinar si existe una membresía vigente.
        $ultimaMembresia = $cliente->ultimaMembresiaAdquirida()
            ->with('usuarioRegistro') // Recuperamos el usuario administrativo que registró la adquisición
            ->first();

        // Comprobamos si la última membresía adquirida continúa vigente.
        $membresiaVigente = $ultimaMembresia &&
            $ultimaMembresia->fecha_vencimiento->toDateString() >= now()->toDateString()
                ? $ultimaMembresia
                : null;

        // Consultamos el historial de adquisiciones del cliente.
        $consulta = $cliente->membresiasAdquiridas()
            ->with('usuarioRegistro')
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('created_at')
            ->orderByDesc('id_membresia_adquirida');

        // Si existe una membresía vigente, la excluimos del historial
        // porque ya se presenta en su sección independiente.
        if ($membresiaVigente) {
            $consulta->where(
                'id_membresia_adquirida',
                '!=',
                $membresiaVigente->id_membresia_adquirida
            );
        }

        // Devolvemos el resultado con cinco registros por página,
        // conservando los parámetros de consulta.
        $historial = $consulta->paginate(5)->withQueryString();

        return [
            'membresia_vigente' => $membresiaVigente,
            'historial' => $historial,
        ];
    }
}