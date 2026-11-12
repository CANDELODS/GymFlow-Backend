<?php

namespace Tests\Feature\Auth;

use App\Models\UsuarioAdministrativo;
// Permite restablecer la BD entre pruebas, evitando que los registros creados en una prueba interfieran con las demás.
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioAdministrativoLogoutTest extends TestCase
{
    // Laravel preparará la BD de testing para cada prueba y revertirá los cambios al terminar
    use RefreshDatabase;

    //Comprobamos que un usuario autenticado pueda cerrar sesión correctamente
    public function test_usuario_autenticado_puede_cerrar_sesion(): void
    {
        // Creamos un usuario administrativo para la prueba
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web
        $this->actingAs($usuario, 'web');

        // Enviamos la solicitud de cierre de sesión
        $respuesta = $this->postJson('/logout');

        // Comprobamos que Laravel responda correctamente
        $respuesta->assertNoContent();

        // Verificamos que el usuario ya no esté autenticado
        $this->assertGuest('web');
    }

    // Comprobamos que un usuario no pueda acceder a rutas protegidas después de cerrar sesión
    public function test_usuario_no_puede_acceder_a_rutas_protegidas_despues_de_cerrar_sesion(): void
    {
        // Creamos un usuario administrativo para la prueba
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web
        $this->actingAs($usuario, 'web');

        // Cerramos la sesión del usuario
        $this->postJson('/logout')->assertNoContent();

        // Intentamos acceder a una ruta protegida después del cierre de sesión
        $respuesta = $this->getJson('/api/user');

        // Comprobamos que Laravel rechace el acceso por falta de autenticación
        $respuesta->assertUnauthorized();

        // Verificamos que el usuario continúe sin estar autenticado
        $this->assertGuest('web');
    }

    // Comprobamos que un usuario no autenticado no pueda ejecutar el cierre de sesión
    public function test_usuario_no_autenticado_no_puede_ejecutar_el_cierre_de_sesion(): void
    {
        // Enviamos una solicitud de cierre de sesión sin autenticación previa
        $respuesta = $this->postJson('/logout');

        // Comprobamos que Laravel rechace la solicitud por falta de autenticación
        $respuesta->assertUnauthorized();

        // Verificamos que el usuario continúe sin estar autenticado
        $this->assertGuest('web');
    }

    // Comprobamos que el cierre de sesión invalide el identificador de sesión anterior
    public function test_cerrar_sesion_invalida_el_identificador_de_sesion_anterior(): void
    {
        // Creamos un usuario administrativo para la prueba
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Autenticamos al usuario mediante el guard web
        $this->actingAs($usuario, 'web');

        // Recuperamos el identificador de la sesión antes del cierre
        $identificadorAnterior = session()->getId();

        // Enviamos la solicitud de cierre de sesión
        $this->postJson('/logout')->assertNoContent();

        // Recuperamos el identificador de la sesión después del cierre
        $identificadorActual = session()->getId();

        // Comprobamos que el identificador de sesión haya cambiado
        $this->assertNotSame($identificadorAnterior, $identificadorActual);

        // Verificamos que el usuario ya no esté autenticado
        $this->assertGuest('web');
    }
}
