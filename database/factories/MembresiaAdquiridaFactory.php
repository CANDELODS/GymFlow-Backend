<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<MembresiaAdquirida>
 */
class MembresiaAdquiridaFactory extends Factory
{
    protected $model = MembresiaAdquirida::class;

    public function definition(): array
    {
        $tipoMembresia = TipoMembresia::query()
            ->inRandomOrder()
            ->first() ?? TipoMembresia::factory()->create();

        $fechaInicio = fake()->dateTimeBetween('-6 months', 'now');

        $fechaVencimiento = Carbon::parse($fechaInicio)
            ->addDays($tipoMembresia->duracion_dias - 1)
            ->setTime(23, 59, 59);

        return [
            'id_cliente' => Cliente::factory(),
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => UsuarioAdministrativo::factory(),

            'nombre_tipo_snapshot' => $tipoMembresia->nombre,
            'duracion_dias_snapshot' => $tipoMembresia->duracion_dias,

            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaVencimiento,

            'valor_pagado' => $tipoMembresia->precio,

            'metodo_pago' => fake()->randomElement([
                'efectivo',
                'nequi',
                'bancolombia',
                'otro',
            ]),
        ];
    }


    public function vencida(): static
    {
        return $this->state(function (array $attributes) {
            $duracion = $attributes['duracion_dias_snapshot'];

            // La membresía venció ayer.
            $fechaVencimiento = Carbon::yesterday()->setTime(23, 59, 59);

            $fechaInicio = Carbon::parse($fechaVencimiento)
                ->subDays($duracion - 1)
                ->startOfDay();

            return [
                'fecha_inicio' => $fechaInicio,
                'fecha_vencimiento' => $fechaVencimiento,
            ];
        });
    }

    public function porVencer(): static
    {
        return $this->state(function (array $attributes) {
            $duracion = $attributes['duracion_dias_snapshot'];

            // La membresía vencerá entre hoy y los próximos 10 días.
            $diasHastaVencer = fake()->numberBetween(0, 10);

            $fechaVencimiento = Carbon::today()
                ->addDays($diasHastaVencer)
                ->setTime(23, 59, 59);

            $fechaInicio = Carbon::parse($fechaVencimiento)
                ->subDays($duracion - 1)
                ->startOfDay();

            return [
                'fecha_inicio' => $fechaInicio,
                'fecha_vencimiento' => $fechaVencimiento,
            ];
        });
    }

    public function activa(): static
    {
        return $this->state(function (array $attributes) {
            $duracion = $attributes['duracion_dias_snapshot'];

            // La membresía vencerá dentro de 11 a 30 días.
            $diasHastaVencer = fake()->numberBetween(11, 30);

            $fechaVencimiento = Carbon::today()
                ->addDays($diasHastaVencer)
                ->setTime(23, 59, 59);

            $fechaInicio = Carbon::parse($fechaVencimiento)
                ->subDays($duracion - 1)
                ->startOfDay();

            return [
                'fecha_inicio' => $fechaInicio,
                'fecha_vencimiento' => $fechaVencimiento,
            ];
        });
    }
}
