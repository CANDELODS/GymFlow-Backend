<?php

namespace Tests\Unit;

use App\Actions\UsuarioAdministrativo\CambiarEstadoUsuarioAdministrativo;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CambiarEstadoUsuarioAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * No se puede desactivar al último dueño activo.
     */
    public function test_no_se_puede_desactivar_al_ultimo_dueno_activo(): void
    {
        // Creamos el único dueño activo.
        $ultimoDueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Ejecutamos directamente el Action y esperamos una excepción.
        try {
            // app nos permite resolver la clase y ejecutar el método.
            app(CambiarEstadoUsuarioAdministrativo::class)->ejecutar(
                $ultimoDueno,
                false // Queremos desactivar al último dueño.
            );

            // Si llegamos aquí, significa que no se lanzó la excepción esperada (El action no funcionó).
            $this->fail('Se esperaba una excepción de validación.');
        } catch (ValidationException $exception) {

            // Comprobamos que el error corresponda al campo activo.
            // errors() devuelve un array asociativo donde la clave es el nombre del campo y el valor es un array de mensajes de error.
            $this->assertArrayHasKey('activo', $exception->errors());
        }

        // Comprobamos que el dueño conserve su estado activo.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $ultimoDueno->id_usuario,
            'rol' => 'dueno',
            'activo' => true,
        ]);
    }
}
