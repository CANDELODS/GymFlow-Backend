<?php

namespace Tests\Feature;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultarUsuariosAdministrativosTest extends TestCase
{
    use RefreshDatabase;


    public function test_dueno_puede_consultar_los_usuarios_administrativos(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos usuarios administrativos para consultar.
        UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        UsuarioAdministrativo::create([
            'usuario' => 'empleado.inactivo',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos los usuarios administrativos.
        $response = $this->getJson('/api/usuarios-administrativos');

        // Comprobamos que la consulta fue exitosa.
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                'usuario' => 'empleado.test',
                'rol' => 'empleado',
                'activo' => true,
            ])
            ->assertJsonFragment([
                'usuario' => 'empleado.inactivo',
                'rol' => 'empleado',
                'activo' => false,
            ]);
    }

    public function test_empleado_no_puede_consultar_los_usuarios_administrativos(): void
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

        // Intentamos consultar los usuarios administrativos.
        $response = $this->getJson('/api/usuarios-administrativos');

        // Comprobamos que el acceso fue rechazado.
        $response->assertForbidden();
    }

    public function test_usuario_no_autenticado_no_puede_consultar_los_usuarios_administrativos(): void
    {
        // Intentamos consultar los usuarios administrativos sin autenticación.
        $response = $this->getJson('/api/usuarios-administrativos');

        // Comprobamos que la solicitud fue rechazada por falta de autenticación.
        $response->assertUnauthorized();
    }

    public function test_dueno_puede_buscar_usuarios_por_coincidencias_parciales(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos usuarios administrativos con diferentes nombres.
        UsuarioAdministrativo::create([
            'usuario' => 'juan.candelo',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        UsuarioAdministrativo::create([
            'usuario' => 'candelo.juan',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        UsuarioAdministrativo::create([
            'usuario' => 'candelaria',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        UsuarioAdministrativo::create([
            'usuario' => 'pedro.gomez',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Buscamos usuarios que contengan la palabra "cand".
        $response = $this->getJson('/api/usuarios-administrativos?usuario=cand');

        // Comprobamos que la búsqueda fue exitosa y devolvió tres coincidencias.
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['usuario' => 'juan.candelo'])
            ->assertJsonFragment(['usuario' => 'candelo.juan'])
            ->assertJsonFragment(['usuario' => 'candelaria']);

        // Comprobamos que el usuario que no coincide no aparezca.
        $response->assertJsonMissing([
            'usuario' => 'pedro.gomez',
        ]);
    }

    public function test_la_busqueda_sin_coincidencias_devuelve_una_coleccion_vacia(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un usuario administrativo que no coincide con la búsqueda.
        UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Buscamos un nombre de usuario que no existe.
        $response = $this->getJson('/api/usuarios-administrativos?usuario=inexistente');

        // Comprobamos que la consulta fue exitosa y no encontró coincidencias.
        $response->assertOk()
            ->assertExactJsonStructure([
                'data',
                'links',
                'meta',
            ])
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function test_los_usuarios_administrativos_se_paginan_de_cinco_en_cinco(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos siete usuarios administrativos.
        for ($i = 1; $i <= 7; $i++) {
            UsuarioAdministrativo::create([
                'usuario' => "empleado{$i}.test",
                'password' => 'Password*123',
                'rol' => 'empleado',
                'activo' => true,
            ]);
        }

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos la primera página.
        $response = $this->getJson('/api/usuarios-administrativos');

        // Comprobamos que la primera página contiene cinco registros.
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.last_page', 2);

        // Consultamos la segunda página.
        $response = $this->getJson('/api/usuarios-administrativos?page=2');

        // Comprobamos que la segunda página contiene los tres registros restantes.
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 8);
    }

    public function test_la_busqueda_se_conserva_al_cambiar_de_pagina(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos siete usuarios que coinciden con la búsqueda.
        for ($i = 1; $i <= 7; $i++) {
            UsuarioAdministrativo::create([
                'usuario' => "empleado.cand{$i}",
                'password' => 'Password*123',
                'rol' => 'empleado',
                'activo' => true,
            ]);
        }

        // Creamos un usuario que no coincide con la búsqueda.
        UsuarioAdministrativo::create([
            'usuario' => 'pedro.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos la primera página aplicando el filtro.
        $response = $this->getJson('/api/usuarios-administrativos?usuario=cand');

        // Comprobamos que la primera página contiene cinco coincidencias.
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.current_page', 1);

        // Consultamos la segunda página conservando el filtro.
        $response = $this->getJson('/api/usuarios-administrativos?usuario=cand&page=2');

        // Comprobamos que devuelve las dos coincidencias restantes.
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonMissing([
                'usuario' => 'pedro.test',
            ]);
    }

    public function test_no_se_permiten_numeros_de_pagina_menores_que_uno(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos consultar una página con número cero.
        $response = $this->getJson('/api/usuarios-administrativos?page=0');

        // Comprobamos que la validación rechace el número de página.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_no_se_permiten_valores_no_enteros_en_el_numero_de_pagina(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos consultar una página con un valor no entero.
        $response = $this->getJson('/api/usuarios-administrativos?page=abc');

        // Comprobamos que la validación rechace el valor recibido.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_el_nombre_de_usuario_debe_ser_un_texto(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos realizar una búsqueda enviando un arreglo como nombre de usuario.
        $response = $this->getJson('/api/usuarios-administrativos?usuario[]=juan');

        // Comprobamos que la validación rechace el valor recibido.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario']);
    }

    public function test_el_nombre_de_usuario_no_puede_superar_los_cien_caracteres(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Generamos un nombre de usuario de 101 caracteres.
        $usuario = str_repeat('a', 101);

        // Intentamos realizar una búsqueda con el nombre demasiado largo.
        $response = $this->getJson('/api/usuarios-administrativos?' . http_build_query([
            'usuario' => $usuario,
        ]));

        // Comprobamos que la validación rechace el valor recibido.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario']);
    }

    public function test_la_respuesta_no_expone_informacion_sensible_de_los_usuarios(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos otro usuario administrativo.
        UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos los usuarios administrativos.
        $response = $this->getJson('/api/usuarios-administrativos');

        // Comprobamos que la respuesta no exponga información sensible.
        $response->assertOk()
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.1.password')
            ->assertJsonMissingPath('data.0.intentos_fallidos')
            ->assertJsonMissingPath('data.1.intentos_fallidos')
            ->assertJsonMissingPath('data.0.bloqueado_hasta')
            ->assertJsonMissingPath('data.1.bloqueado_hasta');
    }
}
