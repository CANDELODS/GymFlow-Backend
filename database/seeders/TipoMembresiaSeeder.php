<?php

namespace Database\Seeders;

use App\Models\TipoMembresia;
use Illuminate\Database\Seeder;

class TipoMembresiaSeeder extends Seeder
{
    /**
     * Ejecuta los registros iniciales de tipos de membresía.
     */
    public function run(): void
    {
        TipoMembresia::factory()->createMany([
            [
                'nombre' => 'Mensual',
                'duracion_dias' => 30,
                'precio' => 80000,
            ],
            [
                'nombre' => 'Trimestral',
                'duracion_dias' => 90,
                'precio' => 210000,
            ],
            [
                'nombre' => 'Semestral',
                'duracion_dias' => 180,
                'precio' => 380000,
            ],
            [
                'nombre' => 'Anual',
                'duracion_dias' => 365,
                'precio' => 700000,
            ],
        ]);
    }
}