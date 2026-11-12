<?php

namespace Tests\Feature\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CambiarEstadoUsuarioAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un dueño puede desactivar la cuenta de un empleado.
     */
    public function test_dueno_puede_desactivar_cuenta_de_empleado(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos el empleado que será desactivado.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos la solicitud para desactivar al empleado.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}/estado",
            [
                'activo' => false,
            ]
        );

        // Verificamos que la solicitud haya sido exitosa.
        $response->assertOk();

        // Comprobamos que el empleado haya quedado desactivado.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'activo' => false,
        ]);
    }

    /**
     * Un dueño puede activar la cuenta de un empleado.
     */
    public function test_dueno_puede_activar_cuenta_de_empleado(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un empleado con su cuenta desactivada.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos la solicitud para activar al empleado.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}/estado",
            [
                'activo' => true,
            ]
        );

        // Verificamos que la solicitud haya sido exitosa.
        $response->assertOk();

        // Comprobamos que el empleado haya quedado activo.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'activo' => true,
        ]);
    }

    /**
     * Un dueño puede desactivar a otro dueño cuando existen más dueños activos.
     */
    public function test_dueno_puede_desactivar_a_otro_dueno_si_existen_mas_duenos_activos(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos otro dueño que será desactivado.
        $otroDueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno456',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos la solicitud para desactivar al otro dueño.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$otroDueno->id_usuario}/estado",
            [
                'activo' => false,
            ]
        );

        // Verificamos que la solicitud haya sido exitosa.
        $response->assertOk();

        // Comprobamos que el otro dueño haya quedado desactivado.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $otroDueno->id_usuario,
            'rol' => 'dueno',
            'activo' => false,
        ]);

        // Comprobamos que el dueño autenticado siga activo.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $dueno->id_usuario,
            'rol' => 'dueno',
            'activo' => true,
        ]);
    }

    /**
     * Un dueño no puede desactivar su propia cuenta.
     */
    public function test_dueno_no_puede_desactivar_su_propia_cuenta(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos desactivar nuestra propia cuenta.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$dueno->id_usuario}/estado",
            [
                'activo' => false,
            ]
        );

        // Verificamos que se rechace la operación.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que la cuenta conserve su estado activo.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $dueno->id_usuario,
            'rol' => 'dueno',
            'activo' => true,
        ]);
    }

    /**
     * Se rechazan los valores que no representan un estado booleano válido.
     */
    public function test_se_rechazan_valores_invalidos_para_estado(): void
    {
        // La regla boolean de laravel admite: true, false, 1, 0, '1', '0'
        $valoresInvalidos = [
            'texto' => 'inactivo', // Es una cadena que no representa un booleano válido.
            'numero' => 2, // No es uno de los valores booleanos admitidos por Laravel.
            'arreglo' => ['activo' => true], // Es un arreglo, no un booleano.
        ];

        // Creamos el dueño que realizará las solicitudes.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos el empleado cuyo estado intentaremos modificar.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        foreach ($valoresInvalidos as $valor) {

            $response = $this->patchJson(
                "/api/usuarios-administrativos/{$empleado->id_usuario}/estado",
                [
                    'activo' => $valor,
                ]
            );

            // Verificamos que cada valor sea rechazado.
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['activo']);
        }

        // Comprobamos que el estado original del empleado se conserve.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'activo' => true,
        ]);
    }

    /**
     * Un empleado no puede cambiar el estado de otros usuarios administrativos.
     */
    public function test_empleado_no_puede_cambiar_estado_de_usuarios_administrativos(): void
    {
        // Creamos el empleado que realizará la solicitud.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos otro empleado cuyo estado intentaremos modificar.
        $otroEmpleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado456',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Intentamos desactivar al otro empleado.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$otroEmpleado->id_usuario}/estado",
            [
                'activo' => false,
            ]
        );

        // Verificamos que el acceso sea rechazado.
        $response->assertForbidden();

        // Comprobamos que el otro empleado conserve su estado activo.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $otroEmpleado->id_usuario,
            'activo' => true,
        ]);
    }

    /**
     * Un usuario no autenticado no puede cambiar el estado de usuarios administrativos.
     */
    public function test_usuario_no_autenticado_no_puede_cambiar_estado_de_usuarios_administrativos(): void
    {
        // Creamos el usuario cuyo estado intentaremos modificar.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // No autenticamos ningún usuario.

        // Intentamos desactivar al empleado.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}/estado",
            [
                'activo' => false,
            ]
        );

        // Verificamos que se rechace la solicitud por falta de autenticación.
        $response->assertUnauthorized();

        // Comprobamos que el estado original del empleado se conserve.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'activo' => true,
        ]);
    }
}
