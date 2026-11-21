<?php

namespace Database\Seeders;

use App\Models\UsuarioAdministrativo;
use Illuminate\Database\Seeder;

class UsuarioAdministrativoSeeder extends Seeder
{
    /**
     * Ejecuta los registros iniciales de usuarios administrativos.
     */
    public function run(): void
    {
        UsuarioAdministrativo::factory()->dueno()->create([
            'usuario' => 'admin',
        ]);

        UsuarioAdministrativo::factory()->count(3)->create();
    }
}