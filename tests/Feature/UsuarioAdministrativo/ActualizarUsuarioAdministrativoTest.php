<?php

namespace Tests\Feature\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActualizarUsuarioAdministrativoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un dueño puede actualizar el nombre de usuario de otro usuario administrativo.
     */
    public function test_dueno_puede_actualizar_nombre_de_usuario(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos el usuario administrativo que será actualizado.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos la solicitud para actualizar el usuario.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}",
            [
                'usuario' => 'empleado_actualizado',
                'rol' => 'empleado',
            ]
        );

        // Verificamos que la solicitud haya sido exitosa.
        $response->assertOk();

        // Comprobamos que el usuario se haya actualizado en la base de datos.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'usuario' => 'empleado_actualizado',
            'rol' => 'empleado',
        ]);
    }

    /**
     * Un dueño puede cambiar el rol de un empleado a dueño.
     */
    public function test_dueno_puede_convertir_empleado_en_dueno(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}",
            [
                'usuario' => 'empleado123',
                'rol' => 'dueno',
            ]
        );

        // Verificamos que la solicitud haya sido exitosa.
        $response->assertOk();

        // Comprobamos que el usuario se haya actualizado en la base de datos.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'rol' => 'dueno',
        ]);
    }

    /**
     * Un dueño puede cambiar el rol de otro dueño cuando existen más dueños activos.
     */
    public function test_dueno_puede_cambiar_rol_de_otro_dueno_si_existen_mas_duenos_activos(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $otroDueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno456',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$otroDueno->id_usuario}",
            [
                'usuario' => 'dueno456',
                'rol' => 'empleado',
            ]
        );

        // Verificamos que la solicitud haya sido exitosa.
        $response->assertOk();

        // Comprobamos que el usuario se haya actualizado en la base de datos.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $otroDueno->id_usuario,
            'rol' => 'empleado',
        ]);
    }

    /**
     * No se puede cambiar el rol del último dueño activo a empleado.
     */
    public function test_no_se_puede_cambiar_rol_del_ultimo_dueno_activo(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$dueno->id_usuario}",
            [
                'usuario' => 'dueno123',
                'rol' => 'empleado',
            ]
        );

        // Verificamos que se rechace la operación.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rol']);

        // Comprobamos que el usuario conserve su rol original.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $dueno->id_usuario,
            'rol' => 'dueno',
            'activo' => true,
        ]);
    }

    /**
     * Un dueño puede cambiar su propio rol si existe otro dueño activo.
     */
    public function test_dueno_puede_cambiar_su_propio_rol_si_existe_otro_dueno_activo(): void
    {
        // Creamos el dueño autenticado.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un segundo dueño activo.
        UsuarioAdministrativo::create([
            'usuario' => 'dueno456',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño que realizará la operación.
        $this->actingAs($dueno, 'web');

        // Intentamos cambiar nuestro propio rol a empleado.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$dueno->id_usuario}",
            [
                'usuario' => 'dueno123',
                'rol' => 'empleado',
            ]
        );

        // Verificamos que la operación sea exitosa.
        $response->assertOk();

        // Comprobamos que nuestro rol haya cambiado.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $dueno->id_usuario,
            'rol' => 'empleado',
            'activo' => true,
        ]);
    }

    /**
     * Un dueño puede actualizar la contraseña de otro usuario administrativo.
     */
    public function test_dueno_puede_actualizar_contrasena_de_otro_usuario(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}",
            [
                'usuario' => 'empleado123',
                'rol' => 'empleado',
                'password' => 'NuevaPassword123!',
                'password_confirmation' => 'NuevaPassword123!',
            ]
        );

        $response->assertOk();

        // Verificamos que la nueva contraseña sea válida y esté almacenada con hash.
        $empleadoActualizado = UsuarioAdministrativo::findOrFail($empleado->id_usuario);

        // Verificamos que la contraseña se haya actualizado correctamente y esté hasheada.
        $this->assertTrue(
            Hash::check(
                'NuevaPassword123!',
                $empleadoActualizado->password
            )
        );
    }

    /**
     * La contraseña actual se conserva cuando no se envía una nueva.
     */
    public function test_se_conserva_contrasena_actual_cuando_no_se_envia(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Guardamos el hash original para comprobar que no cambie.
        $hashOriginal = $empleado->password;

        $this->actingAs($dueno, 'web');

        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}",
            [
                'usuario' => 'empleado_actualizado',
                'rol' => 'empleado',
            ]
        );

        $response->assertOk();

        $empleadoActualizado = UsuarioAdministrativo::findOrFail($empleado->id_usuario);

        // Comprobamos que se conserve exactamente el mismo hash.
        $this->assertSame($hashOriginal, $empleadoActualizado->password);
    }

    /**
     * Se rechazan las contraseñas que no cumplen la política establecida.
     */
    public function test_se_rechazan_contrasenas_que_no_cumplen_la_politica(): void
    {
        $contrasenasInvalidas = [
            'menos de ocho caracteres' => 'Ab1!',
            'sin números' => 'Password!',
            'sin caracteres especiales' => 'Password123',
        ];

        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        foreach ($contrasenasInvalidas as $descripcion => $contrasena) {

            $response = $this->patchJson(
                "/api/usuarios-administrativos/{$empleado->id_usuario}",
                [
                    'usuario' => 'empleado123',
                    'rol' => 'empleado',
                    'password' => $contrasena,
                    'password_confirmation' => $contrasena,
                ]
            );

            // Verificamos que cada contraseña sea rechazada.
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        }
    }

    /**
     * No se permite actualizar un usuario con un nombre que ya pertenece a otro.
     */
    public function test_no_se_permite_actualizar_con_un_nombre_de_usuario_existente(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno123',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        UsuarioAdministrativo::create([
            'usuario' => 'empleado456',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}",
            [
                'usuario' => 'empleado456',
                'rol' => 'empleado',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario']);

        // Comprobamos que el nombre original se conserve.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'usuario' => 'empleado123',
        ]);
    }

    /**
     * Un empleado no puede actualizar usuarios administrativos.
     */
    public function test_empleado_no_puede_actualizar_usuarios_administrativos(): void
    {
        // Creamos un empleado que realizará la solicitud.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos otro usuario administrativo que intentará modificar.
        $otroEmpleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado456',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Intentamos actualizar la información de otro usuario.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$otroEmpleado->id_usuario}",
            [
                'usuario' => 'empleado_actualizado',
                'rol' => 'empleado',
            ]
        );

        // Verificamos que el acceso sea rechazado.
        $response->assertForbidden();

        // Comprobamos que el usuario no haya sido modificado.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $otroEmpleado->id_usuario,
            'usuario' => 'empleado456',
            'rol' => 'empleado',
        ]);
    }

    /**
     * Un usuario no autenticado no puede actualizar usuarios administrativos.
     */
    public function test_usuario_no_autenticado_no_puede_actualizar_usuarios_administrativos(): void
    {
        // Creamos el usuario administrativo que intentaremos modificar.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado123',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // No autenticamos ningún usuario.

        // Intentamos actualizar la información del empleado.
        $response = $this->patchJson(
            "/api/usuarios-administrativos/{$empleado->id_usuario}",
            [
                'usuario' => 'empleado_actualizado',
                'rol' => 'empleado',
            ]
        );

        // Verificamos que se rechace la solicitud por falta de autenticación.
        $response->assertUnauthorized();

        // Comprobamos que el usuario conserve sus datos originales.
        $this->assertDatabaseHas('usuario_administrativo', [
            'id_usuario' => $empleado->id_usuario,
            'usuario' => 'empleado123',
            'rol' => 'empleado',
        ]);
    }
}
