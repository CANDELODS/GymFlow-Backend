<?php

namespace Tests\Feature\TipoMembresia;

use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultarTiposMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_consultar_los_tipos_de_membresia(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos tipos de membresía para consultar.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Semestral',
            'duracion_dias' => 180,
            'precio' => 350000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos los tipos de membresía.
        $response = $this->getJson('/api/tipos-membresia');

        // Comprobamos que la consulta fue exitosa.
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                'nombre' => 'Mensual',
                'duracion_dias' => 30,
                'precio' => '80000.00',
                'activo' => true,
            ])
            ->assertJsonFragment([
                'nombre' => 'Trimestral',
                'duracion_dias' => 90,
                'precio' => '210000.00',
                'activo' => true,
            ])
            ->assertJsonFragment([
                'nombre' => 'Semestral',
                'duracion_dias' => 180,
                'precio' => '350000.00',
                'activo' => false,
            ]);
    }
    public function test_empleado_puede_consultar_los_tipos_de_membresia(): void
    {
        // Creamos el usuario administrativo con rol de empleado.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía para consultar.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Consultamos los tipos de membresía.
        $response = $this->getJson('/api/tipos-membresia');

        // Comprobamos que la consulta fue exitosa.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'nombre' => 'Mensual',
                'duracion_dias' => 30,
                'precio' => '80000.00',
                'activo' => true,
            ]);
    }
    public function test_usuario_no_autenticado_no_puede_consultar_los_tipos_de_membresia(): void
    {
        // Intentamos consultar los tipos de membresía sin autenticación.
        $response = $this->getJson('/api/tipos-membresia');

        // Comprobamos que la solicitud fue rechazada por falta de autenticación.
        $response->assertUnauthorized();
    }
    public function test_usuario_inactivo_no_puede_consultar_los_tipos_de_membresia(): void
    {
        // Creamos un usuario administrativo con la cuenta inactiva.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.inactivo',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Intentamos consultar los tipos de membresía.
        $response = $this->getJson('/api/tipos-membresia');

        // Comprobamos que el acceso fue rechazado.
        $response->assertForbidden();

        // Comprobamos que la sesión haya sido cerrada.
        $this->assertGuest('web');
    }
    public function test_dueno_puede_buscar_tipos_de_membresia_por_coincidencias_parciales(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos tipos de membresía con diferentes nombres.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Mensual Premium',
            'duracion_dias' => 30,
            'precio' => 120000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Membresía Mensual',
            'duracion_dias' => 30,
            'precio' => 100000,
            'activo' => false,
        ]);

        TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Buscamos tipos de membresía que contengan la palabra "mens".
        $response = $this->getJson('/api/tipos-membresia?nombre=mens');

        // Comprobamos que la búsqueda fue exitosa y devolvió tres coincidencias.
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['nombre' => 'Mensual'])
            ->assertJsonFragment(['nombre' => 'Mensual Premium'])
            ->assertJsonFragment(['nombre' => 'Membresía Mensual']);

        // Comprobamos que el tipo que no coincide no aparezca.
        $response->assertJsonMissing([
            'nombre' => 'Trimestral',
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

        // Creamos un tipo de membresía que no coincide con la búsqueda.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Buscamos un tipo de membresía que no existe.
        $response = $this->getJson('/api/tipos-membresia?nombre=inexistente');

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
    public function test_dueno_puede_filtrar_los_tipos_de_membresia_activos(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos tipos de membresía con diferentes estados.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Semestral',
            'duracion_dias' => 180,
            'precio' => 350000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos únicamente los tipos de membresía activos.
        $response = $this->getJson('/api/tipos-membresia?activo=1');

        // Comprobamos que solo se devuelvan los tipos activos.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'nombre' => 'Mensual',
                'activo' => true,
            ])
            ->assertJsonMissing([
                'nombre' => 'Semestral',
            ]);
    }
    public function test_dueno_puede_filtrar_los_tipos_de_membresia_inactivos(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos tipos de membresía con diferentes estados.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Semestral',
            'duracion_dias' => 180,
            'precio' => 350000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos únicamente los tipos de membresía inactivos.
        $response = $this->getJson('/api/tipos-membresia?activo=0');

        // Comprobamos que solo se devuelvan los tipos inactivos.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'nombre' => 'Semestral',
                'activo' => false,
            ])
            ->assertJsonMissing([
                'nombre' => 'Mensual',
            ]);
    }
    public function test_dueno_puede_combinar_la_busqueda_por_nombre_y_el_filtro_por_estado(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos tipos de membresía que coinciden parcialmente con la búsqueda.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        TipoMembresia::create([
            'nombre' => 'Mensual Premium',
            'duracion_dias' => 30,
            'precio' => 100000,
            'activo' => false,
        ]);

        // Creamos tipos que no coinciden con la búsqueda.
        TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Buscamos tipos que contengan "mens" y estén inactivos.
        $response = $this->getJson('/api/tipos-membresia?nombre=mens&activo=0');

        // Comprobamos que únicamente se devuelva el registro que cumple ambos filtros.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'nombre' => 'Mensual Premium',
                'activo' => false,
            ])
            ->assertJsonMissing([
                'nombre' => 'Mensual',
            ])
            ->assertJsonMissing([
                'nombre' => 'Trimestral',
            ]);
    }
    public function test_los_tipos_de_membresia_se_paginan_de_cinco_en_cinco(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos siete tipos de membresía.
        for ($i = 1; $i <= 7; $i++) {
            TipoMembresia::create([
                'nombre' => "Membresia {$i}",
                'duracion_dias' => 30,
                'precio' => 80000,
                'activo' => true,
            ]);
        }

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos la primera página.
        $response = $this->getJson('/api/tipos-membresia');

        // Comprobamos que la primera página contenga cinco registros.
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.last_page', 2);

        // Consultamos la segunda página.
        $response = $this->getJson('/api/tipos-membresia?page=2');

        // Comprobamos que la segunda página contenga los dos registros restantes.
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.last_page', 2);
    }
    public function test_los_filtros_se_conservan_al_cambiar_de_pagina(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos siete tipos de membresía que coinciden con los filtros.
        for ($i = 1; $i <= 7; $i++) {
            TipoMembresia::create([
                'nombre' => "Mensual {$i}",
                'duracion_dias' => 30,
                'precio' => 80000,
                'activo' => false,
            ]);
        }

        // Creamos un tipo que no coincide con los filtros.
        TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos la primera página aplicando ambos filtros.
        $response = $this->getJson('/api/tipos-membresia?nombre=mens&activo=0');

        // Comprobamos que la primera página tenga cinco resultados.
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonMissing([
                'nombre' => 'Trimestral',
            ]);

        // Consultamos la segunda página conservando los filtros.
        $response = $this->getJson('/api/tipos-membresia?nombre=mens&activo=0&page=2');

        // Comprobamos que solo aparezcan los dos registros restantes.
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonMissing([
                'nombre' => 'Trimestral',
            ]);
    }
    public function test_no_se_puede_consultar_una_pagina_menor_a_uno(): void
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

        // Intentamos consultar una página con valor cero.
        $response = $this->getJson('/api/tipos-membresia?page=0');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }
    public function test_no_se_puede_consultar_una_pagina_con_un_valor_no_numerico(): void
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

        // Intentamos consultar una página con un valor no numérico.
        $response = $this->getJson('/api/tipos-membresia?page=abc');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }
    public function test_no_se_puede_buscar_por_nombre_con_un_valor_que_no_sea_texto(): void
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

        // Intentamos buscar utilizando un arreglo en lugar de un texto.
        $response = $this->getJson('/api/tipos-membresia?nombre[]=mens');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }
    public function test_no_se_puede_buscar_por_nombre_con_mas_de_cien_caracteres(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Generamos un nombre de 101 caracteres.
        $nombre = str_repeat('a', 101);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos buscar utilizando un nombre que supera el límite permitido.
        $response = $this->getJson('/api/tipos-membresia?' . http_build_query([
            'nombre' => $nombre,
        ]));

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }
    public function test_se_puede_buscar_por_nombre_con_exactamente_cien_caracteres(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Generamos un nombre de exactamente 100 caracteres.
        $nombre = str_repeat('a', 100);

        // Creamos un tipo de membresía con ese nombre.
        TipoMembresia::create([
            'nombre' => $nombre,
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Buscamos utilizando el nombre de 100 caracteres.
        $response = $this->getJson('/api/tipos-membresia?' . http_build_query([
            'nombre' => $nombre,
        ]));

        // Comprobamos que la consulta sea exitosa y encuentre el registro.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'nombre' => $nombre,
            ]);
    }
    public function test_no_se_puede_filtrar_por_estado_con_un_valor_no_booleano(): void
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

        // Intentamos filtrar utilizando un valor no booleano.
        $response = $this->getJson('/api/tipos-membresia?activo=abc');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);
    }
    public function test_se_devuelve_una_coleccion_vacia_cuando_no_existen_tipos_de_membresia(): void
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

        // Consultamos los tipos de membresía sin tener registros creados.
        $response = $this->getJson('/api/tipos-membresia');

        // Comprobamos que la consulta sea exitosa y devuelva una colección vacía.
        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1);
    }
}
