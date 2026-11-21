<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'documento' => fake()->unique()->numerify('##########'),
            'telefono' => fake()->numerify('3#########'),
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}