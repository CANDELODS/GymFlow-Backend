<?php

namespace Tests\Feature\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrarUsuarioAdministrativoTest extends TestCase
{
    use RefreshDatabase;
    public function test_dueno_puede_registrar_un_usuario_administrativo(): void
    {
        // Creamos un usuario administrativo con rol de dueño.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos la solicitud para registrar un empleado.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'Empleado*123',
            'password_confirmation' => 'Empleado*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Usuario administrativo registrado correctamente.')
            ->assertJsonPath('usuario.usuario', 'empleado.test')
            ->assertJsonPath('usuario.rol', 'empleado')
            ->assertJsonPath('usuario.activo', true)
            ->assertJsonMissingPath('usuario.password');

        // Comprobamos que el usuario fue almacenado en la base de datos.
        $this->assertDatabaseHas('usuario_administrativo', [
            'usuario' => 'empleado.test',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que la contraseña se almacenó correctamente como hash.
        $empleado = UsuarioAdministrativo::where('usuario', 'empleado.test')->firstOrFail(); //404 si no existe

        $this->assertNotEquals('Empleado*123', $empleado->password);
        $this->assertTrue(Hash::check('Empleado*123', $empleado->password));
    }


    public function test_dueno_puede_registrar_otro_usuario_con_rol_dueno(): void
    {
        // Creamos el usuario administrativo que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos la solicitud para registrar otro dueño.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'nuevo.dueno',
            'password' => 'NuevoDueno*123',
            'password_confirmation' => 'NuevoDueno*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('usuario.usuario', 'nuevo.dueno')
            ->assertJsonPath('usuario.rol', 'dueno');

        // Comprobamos que el nuevo dueño fue almacenado.
        $this->assertDatabaseHas('usuario_administrativo', [
            'usuario' => 'nuevo.dueno',
            'rol' => 'dueno',
            'activo' => true,
        ]);
    }

    public function test_empleado_no_puede_registrar_usuarios_administrativos(): void
    {
        // Creamos un usuario administrativo con rol de empleado.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Intentamos registrar otro usuario administrativo.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'nuevo.usuario',
            'password' => 'NuevoUsuario*123',
            'password_confirmation' => 'NuevoUsuario*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que el acceso fue rechazado.
        $response->assertForbidden();

        // Comprobamos que el usuario no fue registrado.
        $this->assertDatabaseMissing('usuario_administrativo', [
            'usuario' => 'nuevo.usuario',
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_registrar_usuarios_administrativos(): void
    {
        // Intentamos registrar un usuario sin haber iniciado sesión.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'nuevo.usuario',
            'password' => 'NuevoUsuario*123',
            'password_confirmation' => 'NuevoUsuario*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que la solicitud fue rechazada por falta de autenticación.
        $response->assertUnauthorized();

        // Comprobamos que el usuario no fue registrado.
        $this->assertDatabaseMissing('usuario_administrativo', [
            'usuario' => 'nuevo.usuario',
        ]);
    }


    public function test_no_se_puede_registrar_un_usuario_con_nombre_duplicado(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un usuario que ya existe.
        UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar otro usuario con el mismo nombre.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'NuevoUsuario*123',
            'password_confirmation' => 'NuevoUsuario*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que la validación rechazó el registro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario']);

        // Comprobamos que no se creó otro registro.
        $this->assertDatabaseCount('usuario_administrativo', 2);
    }

    public function test_no_se_puede_registrar_un_usuario_con_contrasena_que_incumple_la_politica(): void
    {
        // Creamos el dueño que realizará los registros.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Definimos contraseñas que incumplen diferentes condiciones.
        $contrasenasInvalidas = [
            'Ab*123',       // Menos de 8 caracteres.
            'Abcdefgh*',    // Sin números.
            'Abcdefgh1',    // Sin caracteres especiales.
        ];

        // Intentamos registrar un usuario con cada contraseña inválida.
        foreach ($contrasenasInvalidas as $indice => $contrasena) {
            $response = $this->postJson('/api/usuarios-administrativos', [
                'usuario' => "empleado{$indice}.test",
                'password' => $contrasena,
                'password_confirmation' => $contrasena,
                'rol' => 'empleado',
                'activo' => true,
            ]);

            // Comprobamos que la política de contraseña rechaza el registro.
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        }

        // Comprobamos que ningún usuario fue registrado.
        $this->assertDatabaseCount('usuario_administrativo', 1);
    }

    public function test_no_se_puede_registrar_un_usuario_con_confirmacion_de_contrasena_incorrecta(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar un usuario con contraseñas diferentes.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'Empleado*123',
            'password_confirmation' => 'OtraPassword*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que la validación rechaza la confirmación incorrecta.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password_confirmation']);

        // Comprobamos que el usuario no fue registrado.
        $this->assertDatabaseMissing('usuario_administrativo', [
            'usuario' => 'empleado.test',
        ]);
    }

    public function test_no_se_puede_registrar_un_usuario_con_un_rol_no_permitido(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar un usuario con un rol inexistente.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'Empleado*123',
            'password_confirmation' => 'Empleado*123',
            'rol' => 'administrador',
            'activo' => true,
        ]);

        // Comprobamos que la validación rechaza el rol.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rol']);

        // Comprobamos que el usuario no fue registrado.
        $this->assertDatabaseMissing('usuario_administrativo', [
            'usuario' => 'empleado.test',
        ]);
    }

    public function test_no_se_puede_registrar_un_usuario_con_un_estado_no_valido(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar un usuario con un estado no válido.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'Empleado*123',
            'password_confirmation' => 'Empleado*123',
            'rol' => 'empleado',
            'activo' => 'habilitado',
        ]);

        // Comprobamos que la validación rechaza el estado.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que el usuario no fue registrado.
        $this->assertDatabaseMissing('usuario_administrativo', [
            'usuario' => 'empleado.test',
        ]);
    }

    public function test_todos_los_campos_del_registro_son_obligatorios(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Enviamos una solicitud sin datos.
        $response = $this->postJson('/api/usuarios-administrativos', []);

        // Comprobamos que la validación rechaza los campos obligatorios.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'usuario',
                'password',
                'password_confirmation',
                'rol',
                'activo',
            ]);

        // Comprobamos que solamente existe el dueño inicial.
        $this->assertDatabaseCount('usuario_administrativo', 1);
    }

    public function test_el_nombre_de_usuario_debe_cumplir_con_los_limites_de_longitud(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Definimos nombres de usuario que incumplen los límites.
        $usuariosInvalidos = [
            'abcd',             // Menos de 5 caracteres.
            str_repeat('a', 101), // Más de 100 caracteres.
        ];

        // Intentamos registrar usuarios con nombres inválidos.
        foreach ($usuariosInvalidos as $usuario) {
            $response = $this->postJson('/api/usuarios-administrativos', [
                'usuario' => $usuario,
                'password' => 'Empleado*123',
                'password_confirmation' => 'Empleado*123',
                'rol' => 'empleado',
                'activo' => true,
            ]);

            // Comprobamos que la validación rechaza el nombre de usuario.
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['usuario']);
        }

        // Comprobamos que solamente permanece el dueño inicial.
        $this->assertDatabaseCount('usuario_administrativo', 1);
    }

    public function test_la_respuesta_no_expone_informacion_sensible_del_usuario(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Registramos un nuevo usuario administrativo.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'Empleado*123',
            'password_confirmation' => 'Empleado*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated();

        // Comprobamos que la respuesta no exponga información sensible.
        $response->assertJsonMissingPath('usuario.password')
            ->assertJsonMissingPath('usuario.intentos_fallidos')
            ->assertJsonMissingPath('usuario.bloqueado_hasta');
    }

    public function test_dueno_puede_registrar_un_usuario_administrativo_desactivado(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Registramos un usuario con la cuenta desactivada.
        $response = $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.inactivo',
            'password' => 'Empleado*123',
            'password_confirmation' => 'Empleado*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('usuario.usuario', 'empleado.inactivo')
            ->assertJsonPath('usuario.activo', false);

        // Comprobamos que la cuenta fue almacenada desactivada.
        $this->assertDatabaseHas('usuario_administrativo', [
            'usuario' => 'empleado.inactivo',
            'activo' => false,
        ]);
    }

    public function test_el_usuario_se_registra_con_los_valores_iniciales_de_seguridad(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Registramos un nuevo usuario administrativo.
        $this->postJson('/api/usuarios-administrativos', [
            'usuario' => 'empleado.test',
            'password' => 'Empleado*123',
            'password_confirmation' => 'Empleado*123',
            'rol' => 'empleado',
            'activo' => true,
        ])->assertCreated();

        // Recuperamos el usuario registrado.
        $empleado = UsuarioAdministrativo::where('usuario', 'empleado.test')->firstOrFail();

        // Comprobamos los valores iniciales de seguridad.
        $this->assertSame(0, $empleado->intentos_fallidos);
        $this->assertNull($empleado->bloqueado_hasta);
    }
}
