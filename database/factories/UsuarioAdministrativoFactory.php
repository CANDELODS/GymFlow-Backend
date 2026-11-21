<?php

namespace Database\Factories;

use App\Models\UsuarioAdministrativo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioAdministrativo>
 */
class UsuarioAdministrativoFactory extends Factory
{
    protected $model = UsuarioAdministrativo::class;

    public function definition(): array
    {
        return [
            'usuario' => fake()->unique()->userName(),
            'password' => 'GymFlow2026!',
            'rol' => 'empleado',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ];
    }
    public function dueno(): static
    {
        return $this->state(fn(array $attributes) => [
            'rol' => 'dueno',
        ]);
    }
}