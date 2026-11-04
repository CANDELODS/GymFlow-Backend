<?php

namespace Tests\Feature;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class CambiarContrasenaTest extends TestCase
{
    // Laravel preparará la BD de testing para cada prueba y revertirá los cambios al terminar.
    use RefreshDatabase;

    public function test_usuario_autenticado_puede_cambiar_su_contrasena(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Enviamos la solicitud para cambiar la contraseña.
        $respuesta = $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'NuevaPassword123!',
        ]);

        // Comprobamos que la sesión actual se haya cerrado.
        $this->assertGuest('web');

        // Comprobamos que el cambio se haya realizado correctamente.
        $respuesta->assertStatus(200);

        // Comprobamos el mensaje de confirmación.
        $respuesta->assertJson([
            'mensaje' => 'La contraseña se ha actualizado correctamente.',
        ]);
    }

    public function test_usuario_no_puede_cambiar_contrasena_si_la_actual_es_incorrecta(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Intentamos cambiar la contraseña utilizando una contraseña actual incorrecta.
        $respuesta = $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'PasswordIncorrecta123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'NuevaPassword123!',
        ]);

        // Comprobamos que Laravel devuelva un error de validación.
        $respuesta->assertStatus(422);

        // Comprobamos que el error corresponda a la contraseña actual.
        $respuesta->assertJsonValidationErrors([
            'contrasena_actual',
        ]);

        // Verificamos que la contraseña original permanezca sin cambios.
        $this->assertTrue(
            Hash::check('Password123!', $usuario->fresh()->password)
        );
    }

    public function test_usuario_no_puede_cambiar_contrasena_si_la_nueva_incumple_la_politica(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Intentamos cambiar la contraseña utilizando una nueva que incumple RNF2.
        $respuesta = $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'contraseña',
            'contrasena_nueva_confirmacion' => 'contraseña',
        ]);

        // Comprobamos que Laravel devuelva un error de validación.
        $respuesta->assertStatus(422);

        // Comprobamos que el error corresponda a la nueva contraseña.
        $respuesta->assertJsonValidationErrors([
            'contrasena_nueva',
        ]);

        // Verificamos que la contraseña original permanezca sin cambios.
        $this->assertTrue(
            Hash::check('Password123!', $usuario->fresh()->password)
        );
    }

    public function test_usuario_no_puede_cambiar_contrasena_si_la_confirmacion_no_coincide(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Intentamos cambiar la contraseña utilizando una confirmación diferente.
        $respuesta = $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'OtraPassword123!',
        ]);

        // Comprobamos que Laravel devuelva un error de validación.
        $respuesta->assertStatus(422);

        // Comprobamos que el error corresponda al campo de confirmación.
        $respuesta->assertJsonValidationErrors([
            'contrasena_nueva_confirmacion',
        ]);

        // Verificamos que la contraseña original permanezca sin cambios.
        $this->assertTrue(
            Hash::check('Password123!', $usuario->fresh()->password)
        );
    }

    public function test_usuario_no_autenticado_no_puede_cambiar_contrasena(): void
    {
        // Enviamos una solicitud para cambiar la contraseña sin iniciar sesión.
        $respuesta = $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'NuevaPassword123!',
        ]);

        // Comprobamos que Laravel rechace la solicitud por falta de autenticación.
        $respuesta->assertStatus(401);
    }

    public function test_nueva_contrasena_se_almacena_mediante_hashing(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Enviamos la solicitud para cambiar la contraseña.
        $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'NuevaPassword123!',
        ]);

        // Recuperamos la contraseña almacenada en la base de datos.
        $contrasenaAlmacenada = $usuario->fresh()->password;

        // Comprobamos que la contraseña no se haya almacenado en texto plano.
        $this->assertNotSame('NuevaPassword123!', $contrasenaAlmacenada);

        // Comprobamos que el hash corresponda a la nueva contraseña.
        $this->assertTrue(
            Hash::check('NuevaPassword123!', $contrasenaAlmacenada)
        );
    }

    public function test_nueva_contrasena_permite_autenticarse_y_la_anterior_deja_de_funcionar(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Cambiamos la contraseña del usuario.
        $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'NuevaPassword123!',
        ]);

        // Verificamos que el usuario no haya quedado autenticado.
        $this->assertGuest('web');

        // Restablecemos el guard web como predeterminado para las siguientes solicitudes.
        Auth::shouldUse('web');

        // Intentamos iniciar sesión utilizando la contraseña anterior.
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que las credenciales anteriores sean rechazadas.
        $respuesta->assertSessionHasErrors([
            'usuario',
        ]);

        // Verificamos que el usuario no haya quedado autenticado.
        $this->assertGuest('web');

        // Intentamos iniciar sesión utilizando la nueva contraseña.
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'NuevaPassword123!',
        ]);

        // Comprobamos que el usuario pueda autenticarse correctamente.
        $respuesta->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario, 'web');
    }


    public function test_una_sesion_anterior_se_invalida_despues_de_cambiar_contrasena(): void
    {
        // Creamos un usuario administrativo para la prueba.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Conservamos el hash de la contraseña original.
        $hashAnterior = $usuario->password;

        // Autenticamos al usuario mediante el guard web.
        $this->actingAs($usuario, 'web');

        // Simulamos una sesión que conserva el hash de la contraseña anterior.
        $this->withSession([
            'password_hash_web' => $hashAnterior,
        ]);

        // Cambiamos la contraseña desde la sesión actual.
        $respuesta = $this->putJson('/api/usuario/contrasena', [
            'contrasena_actual' => 'Password123!',
            'contrasena_nueva' => 'NuevaPassword123!',
            'contrasena_nueva_confirmacion' => 'NuevaPassword123!',
        ]);

        // Comprobamos que el cambio de contraseña se haya realizado correctamente.
        $respuesta->assertStatus(200);

        // Comprobamos que la sesión actual se haya cerrado.
        $this->assertGuest('web');

        // Restablecemos el guard web como predeterminado.
        Auth::shouldUse('web');

        // Simulamos una nueva solicitud desde una sesión que conserva el hash anterior.
        $this->actingAs($usuario->fresh(), 'web');

        // withSession Nos permite establecer los datos que tendrá la sesión de la siguiente solicitud.
        $this->withSession([
            'password_hash_web' => $hashAnterior,
        ]);

        // Intentamos acceder a un recurso protegido desde la sesión anterior.
        $respuesta = $this->withHeader(
            'Origin',
            'http://gymflow.test:8080'
        )->getJson('/api/user');

        // Comprobamos que Laravel rechace la sesión por el cambio de hash.
        $respuesta->assertUnauthorized();

        // Verificamos que el usuario haya quedado desconectado.
        $this->assertGuest('web');
    }
}
