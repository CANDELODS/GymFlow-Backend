<?php

namespace Database\Factories;

use App\Models\TipoMembresia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoMembresia>
 */
class TipoMembresiaFactory extends Factory
{
    protected $model = TipoMembresia::class;


    public function definition(): array
    {
        // Definimos los planes disponibles y sus características.
        $planes = [
            'Mensual' => [
                'duracion_dias' => 30,
                'precio' => 80000,
            ],
            'Trimestral' => [
                'duracion_dias' => 90,
                'precio' => 210000,
            ],
            'Semestral' => [
                'duracion_dias' => 180,
                'precio' => 380000,
            ],
            'Anual' => [
                'duracion_dias' => 365,
                'precio' => 700000,
            ],
        ];

        // Seleccionamos un nombre de plan que no se haya utilizado.
        $nombre = fake()->unique()->randomElement(array_keys($planes));

        return [
            'nombre' => $nombre,
            ...$planes[$nombre],
            'activo' => true,
        ];
    }
}
