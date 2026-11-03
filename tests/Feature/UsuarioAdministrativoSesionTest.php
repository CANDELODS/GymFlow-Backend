<?php

namespace Tests\Feature;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioAdministrativoSesionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sesion_tiene_duracion_de_treinta_minutos(): void
    {
        // Comprobamos que Laravel tenga configurada la duración de 30 minutos
        $this->assertSame(30, config('session.lifetime'));
    }

    public function test_sesion_expira_despues_de_treinta_minutos_de_inactividad(): void
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

        // Iniciamos sesión con las credenciales correctas
        $respuesta = $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Comprobamos que el usuario haya sido autenticado
        $respuesta->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario, 'web');

        // Accedemos al panel para registrar la primera actividad
        $this->get('/panel')->assertOk();

        // Simulamos 31 minutos de inactividad
        $this->travel(31)->minutes();

        // Intentamos acceder nuevamente al panel
        $respuesta = $this->get('/panel');

        // Comprobamos que el middleware rechace el acceso
        $respuesta->assertUnauthorized();

        // Verificamos que el usuario haya cerrado su sesión
        $this->assertGuest('web');
    }

    public function test_sesion_renueva_tiempo_de_inactividad_cuando_hay_actividad(): void
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

        // Iniciamos sesión con las credenciales correctas
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Accedemos al panel para registrar la primera actividad
        $this->get('/panel')->assertOk();

        // Simulamos 20 minutos de inactividad
        $this->travel(20)->minutes();

        // Realizamos una nueva solicitud que renueva el tiempo de inactividad
        $this->get('/panel')->assertOk();

        // Simulamos otros 20 minutos sin realizar solicitudes
        $this->travel(20)->minutes();

        // Comprobamos que el usuario todavía pueda acceder al panel
        $this->get('/panel')->assertOk();

        // Verificamos que continúe autenticado
        $this->assertAuthenticatedAs($usuario, 'web');
    }

    public function test_sesion_expira_exactamente_al_cumplir_treinta_minutos(): void
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

        // Iniciamos sesión con las credenciales correctas
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Accedemos al panel para registrar la primera actividad
        $this->get('/panel')->assertOk();

        // Avanzamos el reloj hasta un segundo antes de cumplir los 30 minutos
        $this->travel(29)->minutes();
        $this->travel(59)->seconds();

        // Comprobamos que el administrador todavía pueda acceder
        $this->get('/panel')->assertOk();

        // Verificamos que continúe autenticado
        $this->assertAuthenticatedAs($usuario, 'web');
    }

    public function test_sesion_se_invalida_al_cumplir_exactamente_treinta_minutos_de_inactividad(): void
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

        // Iniciamos sesión con las credenciales correctas
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Accedemos al panel para registrar la primera actividad
        $this->get('/panel')->assertOk();

        // Avanzamos exactamente 30 minutos desde la última actividad
        $this->travel(30)->minutes();

        // Intentamos acceder nuevamente al panel
        $respuesta = $this->get('/panel');

        // Comprobamos que el middleware rechace el acceso
        $respuesta->assertUnauthorized();

        // Verificamos que el usuario haya perdido la autenticación
        $this->assertGuest('web');
    }

    public function test_solicitud_api_actualiza_la_ultima_actividad_de_la_sesion(): void
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

        // Iniciamos sesión con las credenciales correctas
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Definimos los encabezados para simular solicitudes desde Vue
        $encabezados = [
            'Origin' => 'http://gymflow.test:8080',
            'Referer' => 'http://gymflow.test:8080/',
        ];

        // Avanzamos el reloj para identificar la actividad de la solicitud API
        $this->travel(5)->minutes();

        // Consultamos el usuario autenticado mediante la API
        $respuesta = $this->withHeaders($encabezados)->getJson('/api/user');

        // Comprobamos que la solicitud haya sido autorizada
        $respuesta->assertOk();

        // Comprobamos que la respuesta corresponda al usuario autenticado
        $respuesta->assertJsonPath('id_usuario', $usuario->id_usuario);

        // Verificamos que la sesión haya registrado la actividad de la solicitud API
        $this->assertSame(
            now()->timestamp,
            session('ultima_actividad')
        );
    }


    public function test_sesion_renueva_tiempo_de_inactividad_mediante_solicitudes_api(): void
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

        // Iniciamos sesión con las credenciales correctas
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Definimos los encabezados para simular solicitudes desde Vue
        $encabezados = [
            'Origin' => 'http://gymflow.test:8080',
            'Referer' => 'http://gymflow.test:8080/',
        ];

        // Realizamos la primera solicitud API para registrar la actividad
        $this->withHeaders($encabezados)->getJson('/api/user')->assertOk();

        // Simulamos 20 minutos de inactividad
        $this->travel(20)->minutes();

        // Realizamos una nueva solicitud API que renueva el tiempo de inactividad
        $this->getJson('/api/user')->assertOk();

        // Simulamos otros 20 minutos sin realizar solicitudes
        $this->travel(20)->minutes();

        // Comprobamos que el usuario todavía pueda acceder mediante la API
        $respuesta = $this->getJson('/api/user');

        $respuesta->assertOk();

        // Verificamos que el usuario continúe autenticado
        $this->assertAuthenticatedAs($usuario, 'web');
    }


    public function test_sesion_expira_despues_de_treinta_minutos_de_inactividad_en_solicitudes_api(): void
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

        // Iniciamos sesión con las credenciales correctas
        $this->post('/login', [
            'usuario' => 'admin_prueba',
            'password' => 'Password123!',
        ]);

        // Definimos los encabezados para simular solicitudes desde Vue
        $encabezados = [
            'Origin' => 'http://gymflow.test:8080',
            'Referer' => 'http://gymflow.test:8080/',
        ];

        // Realizamos una solicitud API para registrar la actividad inicial
        $this->withHeaders($encabezados)->getJson('/api/user')->assertOk();

        // Avanzamos exactamente 30 minutos sin realizar solicitudes
        $this->travel(30)->minutes();

        // Intentamos consultar nuevamente al usuario autenticado
        $respuesta = $this->getJson('/api/user');

        // Comprobamos que el middleware rechace el acceso
        $respuesta->assertUnauthorized();

        // Verificamos que el usuario haya perdido su autenticación
        $this->assertGuest('web');
    }
}
