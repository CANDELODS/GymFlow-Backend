<?php

namespace Tests\Feature;

use App\Models\UsuarioAdministrativo;
//Permite restablecer la base de datos entre pruebas, evitando que los registros creados en una prueba interfieran con las demás.
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioAdministrativoLoginTest extends TestCase
{
    //Laravel preparará la BD de testing para cada prueba y revertirá los cambios al terminar
    use RefreshDatabase;

    public function test_login_rechaza_credenciales_vacias(): void
    {
        $respuesta = $this->post('/login', []);

        $respuesta->assertSessionHasErrors([
            'usuario',
            'password',
        ]);
    }

    public function test_login_rechaza_usuario_con_menos_de_cinco_caracteres(): void
    {
        $respuesta = $this->post('/login', [
            'usuario' => 'juan',
            'password' => 'Password123!',
        ]);

        $respuesta->assertSessionHasErrors([
            'usuario',
        ]);
    }

    public function test_login_rechaza_contrasena_con_menos_de_ocho_caracteres(): void
    {
        $respuesta = $this->post('/login', [
            'usuario' => 'usuario_valido',
            'password' => 'Ab1!',
        ]);

        $respuesta->assertSessionHasErrors([
            'password',
        ]);
    }

    public function test_login_autentica_usuario_con_credenciales_correctas(): void
    {
        // Creamos un usuario administrativo para la prueba
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!', //nuestro modelo tiene el cast hashed, por lo que Laravel almacena la contraseña como hash automáticamente.
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 3,
            'bloqueado_hasta' => null,
        ]);

        // Enviamos las credenciales para iniciar sesión
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el usuario haya iniciado sesión correctamente
        $respuesta->assertRedirect('/home'); //Comprueba que Fortify redirija al usuario después de autenticarse
        $this->assertAuthenticatedAs($usuario, 'web'); //Verifica que el usuario realmente haya quedado autenticado mediante el guard web.

        // Verificamos que los intentos fallidos se hayan restablecido
        //assertSame recibe ($valorEsperado, $valorObtenido) y comprueba que sean exactamente iguales 
        $this->assertSame(0, $usuario->fresh()->intentos_fallidos); //fresh() vuelve a consultar el registro en la BD
    }

    public function test_login_incrementa_intentos_fallidos_con_contrasena_incorrecta(): void
    {
        // Creamos un usuario administrativo con el contador en cero
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Intentamos iniciar sesión con una contraseña incorrecta
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'PasswordIncorrecta123!',
        ]);

        // Comprobamos que Laravel rechace las credenciales
        $respuesta->assertSessionHasErrors([
            'usuario',
        ]);

        // Verificamos que el usuario no haya sido autenticado
        $this->assertGuest('web');

        // Comprobamos que el contador haya aumentado en uno
        $this->assertSame(1, $usuario->fresh()->intentos_fallidos);

        // Verificamos que todavía no exista un bloqueo
        $this->assertNull($usuario->fresh()->bloqueado_hasta);
    }

    public function test_login_bloquea_usuario_despues_de_cinco_intentos_fallidos(): void
    {
        // Creamos un usuario administrativo con el contador en cero
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Realizamos cinco intentos de inicio de sesión con una contraseña incorrecta
        for ($intento = 0; $intento < 5; $intento++) {
            $this->post('/login', [
                'usuario' => 'admin_prueba',
                'password' => 'PasswordIncorrecta123!',
            ]);
        }

        // Recuperamos el usuario actualizado desde la base de datos
        $usuarioActualizado = $usuario->fresh();

        // Comprobamos que el contador haya llegado a cinco
        $this->assertSame(5, $usuarioActualizado->intentos_fallidos);

        // Comprobamos que se haya establecido una fecha de bloqueo
        $this->assertNotNull($usuarioActualizado->bloqueado_hasta);

        // Verificamos que el usuario no haya sido autenticado
        $this->assertGuest('web');
    }

    public function test_login_rechaza_usuario_bloqueado_aunque_la_contrasena_sea_correcta(): void
    {
        // Creamos un usuario administrativo con un bloqueo temporal vigente
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 5,
            'bloqueado_hasta' => now()->addMinutes(15),
        ]);

        // Intentamos iniciar sesión utilizando la contraseña correcta
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que Fortify rechace las credenciales
        $respuesta->assertSessionHasErrors([
            'usuario',
        ]);

        // Verificamos que el usuario no haya sido autenticado
        $this->assertGuest('web');

        // Comprobamos que el contador permanezca en cinco
        $this->assertSame(5, $usuario->fresh()->intentos_fallidos);

        // Verificamos que la fecha de bloqueo continúe vigente
        $this->assertTrue(
            //isFuture() pertenece a Carbon, "biblioteca de fechas de laravel"
            //Esta devuelve true cuando la fecha almacenada es posterior al momento actual
            //Así comprobamos que el bloqueo todavía está vigente sin depender de una fecha exacta.
            $usuario->fresh()->bloqueado_hasta->isFuture()
        );
    }

    public function test_login_permite_reintentar_cuando_el_bloqueo_ha_expirado(): void
    {
        // Creamos un usuario administrativo cuyo bloqueo ya ha expirado
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 5,
            'bloqueado_hasta' => now()->subMinute(),
        ]);

        // Intentamos iniciar sesión con las credenciales correctas
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el usuario haya sido autenticado correctamente
        $respuesta->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario, 'web');

        // Recuperamos el usuario actualizado desde la base de datos
        $usuarioActualizado = $usuario->fresh();

        // Comprobamos que el contador haya sido restablecido
        $this->assertSame(0, $usuarioActualizado->intentos_fallidos);

        // Comprobamos que la fecha de bloqueo haya sido eliminada
        $this->assertNull($usuarioActualizado->bloqueado_hasta);
    }

    public function test_login_rechaza_usuario_desactivado(): void
    {
        // Creamos un usuario administrativo con la cuenta desactivada
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => false,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Intentamos iniciar sesión con las credenciales correctas
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que Fortify rechace las credenciales
        $respuesta->assertSessionHasErrors([
            'usuario',
        ]);

        // Verificamos que el usuario no haya sido autenticado
        $this->assertGuest('web');

        // Comprobamos que el contador permanezca en cero
        $this->assertSame(0, $usuario->fresh()->intentos_fallidos);

        // Comprobamos que la cuenta no haya recibido un bloqueo
        $this->assertNull($usuario->fresh()->bloqueado_hasta);

        // Verificamos que la cuenta continúe desactivada
        $this->assertFalse($usuario->fresh()->activo);
    }

    public function test_login_rechaza_usuario_inexistente(): void
    {
        // Intentamos iniciar sesión con un usuario que no existe
        $respuesta = $this->post('/login', [
            'usuario' => 'usuario_inexistente',
            'password' => 'Password123!',
        ]);

        // Comprobamos que Fortify rechace las credenciales
        $respuesta->assertSessionHasErrors([
            'usuario',
        ]);

        // Verificamos que el usuario no haya sido autenticado
        $this->assertGuest('web');

        // Comprobamos que el usuario no exista en la base de datos
        $this->assertDatabaseMissing('usuario_administrativo', [
            'usuario' => 'usuario_inexistente',
        ]);
    }

    public function test_login_aplica_limite_por_usuario_e_ip(): void
    {
        // Realizamos cinco solicitudes con un usuario inexistente
        for ($intento = 0; $intento < 5; $intento++) {
            $respuesta = $this->post('/login', [
                'usuario' => 'usuario_inexistente',
                'password' => 'Password123!',
            ]);

            // Comprobamos que las primeras cinco solicitudes sean procesadas
            $respuesta->assertRedirect();
        }

        // Realizamos una sexta solicitud para superar el límite permitido
        $respuesta = $this->post('/login', [
            'usuario' => 'usuario_inexistente',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el limitador rechace la solicitud
        $respuesta->assertStatus(429);
    }

    public function test_login_aplica_limite_global_por_ip(): void
    {
        // Realizamos quince solicitudes distribuidas entre cinco usuarios diferentes
        for ($usuario = 1; $usuario <= 5; $usuario++) {
            for ($intento = 0; $intento < 3; $intento++) {
                $respuesta = $this->post('/login', [
                    'usuario' => 'usuario_prueba_' . $usuario,
                    'password' => 'Password123!',
                ]);

                // Comprobamos que las primeras quince solicitudes sean procesadas
                $respuesta->assertRedirect();
            }
        }

        // Realizamos una solicitud adicional desde la misma IP con otro usuario
        $respuesta = $this->post('/login', [
            'usuario' => 'usuario_prueba_6',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el límite global por IP rechace la solicitud
        $respuesta->assertStatus(429);
    }

    public function test_login_respeta_duracion_exacta_del_bloqueo(): void
    {
        // Establecemos una fecha inicial sin microsegundos para coincidir con la precisión de MySQL
        $momentoInicial = now()->setMicrosecond(0);

        $this->travelTo($momentoInicial);

        // Creamos un usuario con cuatro intentos fallidos
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 4,
            'bloqueado_hasta' => null,
        ]);

        // Realizamos el quinto intento con una contraseña incorrecta
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'PasswordIncorrecta123!',
        ]);

        // Comprobamos que el bloqueo se haya establecido exactamente 15 minutos después
        $usuarioActualizado = $usuario->fresh();

        $this->assertTrue(
            //copy() nos permite sumar 15 minutos a una copia de la fecha inicial, sin modificar el objeto $momentoInicial
            $usuarioActualizado->bloqueado_hasta->equalTo($momentoInicial->copy()->addMinutes(15))
        );

        // Avanzamos el reloj hasta un segundo antes de finalizar el bloqueo
        $this->travel(14)->minutes();
        $this->travel(59)->seconds();

        // Intentamos iniciar sesión con la contraseña correcta
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el usuario continúe bloqueado
        $this->assertGuest('web');
        $this->assertSame(5, $usuario->fresh()->intentos_fallidos);

        // Avanzamos el reloj hasta cumplir los 15 minutos exactos
        $this->travel(1)->seconds();

        // Intentamos iniciar sesión nuevamente con las credenciales correctas
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el usuario pueda autenticarse
        $respuesta->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario, 'web');

        // Verificamos que el contador y la fecha de bloqueo se hayan restablecido
        $usuarioActualizado = $usuario->fresh();

        $this->assertSame(0, $usuarioActualizado->intentos_fallidos);
        $this->assertNull($usuarioActualizado->bloqueado_hasta);
    }
}
