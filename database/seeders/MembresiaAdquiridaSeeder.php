<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\UsuarioAdministrativo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MembresiaAdquiridaSeeder extends Seeder
{
    /**
     * Ejecuta los registros iniciales de membresías adquiridas.
     */
    public function run(): void
    {
        $clientes = Cliente::query()
            ->inRandomOrder()
            ->take(15)
            ->get();

        $usuarios = UsuarioAdministrativo::all();

        foreach ($clientes as $indice => $cliente) {

            // Seleccionamos el estado correspondiente al cliente.
            $estado = match (true) {
                $indice < 5 => 'vencida',
                $indice < 10 => 'porVencer',
                default => 'activa',
            };

            // Generamos la última adquisición del cliente.
            $factory = MembresiaAdquirida::factory()
                ->state([
                    'id_cliente' => $cliente->id_cliente,
                    'id_usuario_registro' => $usuarios->random()->id_usuario,
                ]);

            $ultimaMembresia = $factory->{$estado}()->create();

            // Generamos una adquisición histórica anterior.
            $fechaInicio = Carbon::parse($ultimaMembresia->fecha_inicio)
                ->subDays($ultimaMembresia->duracion_dias_snapshot)
                ->startOfDay();

            $fechaVencimiento = $fechaInicio->copy()
                ->addDays($ultimaMembresia->duracion_dias_snapshot - 1)
                ->setTime(23, 59, 59);


            MembresiaAdquirida::factory()->create([
                'id_cliente' => $cliente->id_cliente,
                'id_tipo_membresia' => $ultimaMembresia->id_tipo_membresia,
                'id_usuario_registro' => $ultimaMembresia->id_usuario_registro,
                'nombre_tipo_snapshot' => $ultimaMembresia->nombre_tipo_snapshot,
                'duracion_dias_snapshot' => $ultimaMembresia->duracion_dias_snapshot,
                'fecha_inicio' => $fechaInicio,
                'fecha_vencimiento' => $fechaVencimiento,
                'valor_pagado' => $ultimaMembresia->valor_pagado,
                'metodo_pago' => $ultimaMembresia->metodo_pago,
            ]);
        }
    }
}
