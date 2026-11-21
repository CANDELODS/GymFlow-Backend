<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Ejecuta los registros iniciales de clientes.
     */
    public function run(): void
    {
        Cliente::factory()->count(20)->create();
    }
}