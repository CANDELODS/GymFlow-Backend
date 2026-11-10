<?php

namespace Tests\Feature;

namespace Tests\Feature;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificarUsuarioActivoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un usuario activo puede acceder a las rutas protegidas.
     */
    public function test_usuario_activo_puede_acceder_a_rutas_protegidas(): void
    {
        // Creamos un usuario administrativo activo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al usuario.
        $this->actingAs($usuario, 'web');

        // Intentamos acceder a la ruta protegida.
        $response = $this->getJson('/api/user');

        // Verificamos que el acceso sea permitido.
        $response->assertOk();

        // Comprobamos que la respuesta corresponda al usuario autenticado.
        $response->assertJson([
            'id_usuario' => $usuario->id_usuario,
            'usuario' => 'empleado123',
        ]);
    }

    /**
     * Un usuario inactivo no puede acceder a las rutas protegidas.
     */
    public function test_usuario_inactivo_no_puede_acceder_a_rutas_protegidas(): void
    {
        // Creamos un usuario administrativo inactivo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Autenticamos al usuario.
        $this->actingAs($usuario, 'web');

        // Intentamos acceder a la ruta protegida.
        $response = $this->getJson('/api/user');

        // Verificamos que el acceso sea rechazado.
        $response->assertForbidden();

        // Comprobamos que se haya invalidado la sesión.
        $this->assertGuest('web');
    }

    /**
     * Una sesión autenticada pierde el acceso cuando la cuenta es desactivada.
     */
    public function test_sesion_autenticada_pierde_acceso_cuando_la_cuenta_es_desactivada(): void
    {
        // Creamos un dueño que desactivará la cuenta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos el empleado que tiene una sesión iniciada.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Simulamos la sesión autenticada del empleado.
        $this->actingAs($empleado, 'web');

        UsuarioAdministrativo::where('id_usuario', $empleado->id_usuario)
            ->update(['activo' => false]);

        // Actualizamos la instancia del usuario con los datos de la base de datos.
        $empleado->refresh();

        // El empleado intenta acceder nuevamente a una ruta protegida.
        $response = $this->getJson('/api/user');

        // Verificamos que el middleware rechace el acceso.
        $response->assertForbidden();

        // Comprobamos que la sesión haya sido cerrada.
        $this->assertGuest('web');
    }
}
