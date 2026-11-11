<?php

namespace Tests\Feature\TipoMembresia;

use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarTipoMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_registrar_un_tipo_de_membresia(): void
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

        // Enviamos la solicitud para registrar el tipo de membresía.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Tipo de membresía registrado correctamente.')
            ->assertJsonPath('tipo_membresia.nombre', 'Mensual')
            ->assertJsonPath('tipo_membresia.duracion_dias', 30)
            ->assertJsonPath('tipo_membresia.precio', '80000.00')
            ->assertJsonPath('tipo_membresia.activo', true);

        // Comprobamos que el tipo de membresía fue almacenado.
        $this->assertDatabaseHas('tipo_membresia', [
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);
    }
    public function test_empleado_puede_registrar_un_tipo_de_membresia(): void
    {
        // Creamos el usuario administrativo que realizará el registro.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Enviamos la solicitud para registrar el tipo de membresía.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Tipo de membresía registrado correctamente.')
            ->assertJsonPath('tipo_membresia.nombre', 'Trimestral')
            ->assertJsonPath('tipo_membresia.duracion_dias', 90)
            ->assertJsonPath('tipo_membresia.precio', '210000.00')
            ->assertJsonPath('tipo_membresia.activo', true);

        // Comprobamos que el tipo de membresía fue almacenado.
        $this->assertDatabaseHas('tipo_membresia', [
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
            'activo' => true,
        ]);
    }
    public function test_usuario_no_autenticado_no_puede_registrar_un_tipo_de_membresia(): void
    {
        // Intentamos registrar un tipo de membresía sin haber iniciado sesión.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'Semestral',
            'duracion_dias' => 180,
            'precio' => 350000,
        ]);

        // Comprobamos que la solicitud fue rechazada por falta de autenticación.
        $response->assertUnauthorized();

        // Comprobamos que el tipo de membresía no fue registrado.
        $this->assertDatabaseMissing('tipo_membresia', [
            'nombre' => 'Semestral',
        ]);
    }
    public function test_usuario_inactivo_no_puede_registrar_un_tipo_de_membresia(): void
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

        // Intentamos registrar un tipo de membresía.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'Semestral',
            'duracion_dias' => 180,
            'precio' => 350000,
        ]);

        // Comprobamos que el acceso fue rechazado.
        $response->assertForbidden();

        // Comprobamos que la sesión haya sido cerrada.
        $this->assertGuest('web');

        // Comprobamos que el tipo de membresía no fue registrado.
        $this->assertDatabaseMissing('tipo_membresia', [
            'nombre' => 'Semestral',
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
        $response = $this->postJson('/api/tipos-membresia', []);

        // Comprobamos que la validación rechaza los campos obligatorios.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre',
                'duracion_dias',
                'precio',
            ]);

        // Comprobamos que no se registró ningún tipo de membresía.
        $this->assertDatabaseCount('tipo_membresia', 0);
    }

    public function test_duracion_y_precio_deben_ser_mayores_que_cero(): void
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

        // Definimos los datos con duración y precio inválidos.
        $datosInvalidos = [
            [
                'nombre' => 'Mensual',
                'duracion_dias' => 0,
                'precio' => 80000,
            ],
            [
                'nombre' => 'Trimestral',
                'duracion_dias' => 90,
                'precio' => 0,
            ],
            [
                'nombre' => 'Semestral',
                'duracion_dias' => -30,
                'precio' => -10000,
            ],
        ];

        // Intentamos registrar tipos de membresía con valores inválidos.
        foreach ($datosInvalidos as $datos) {
            $response = $this->postJson('/api/tipos-membresia', $datos);

            // Comprobamos que se rechacen los valores iguales o inferiores a cero.
            $response->assertUnprocessable();

            if ($datos['duracion_dias'] <= 0) {
                $response->assertJsonValidationErrors(['duracion_dias']);
            }

            if ($datos['precio'] <= 0) {
                $response->assertJsonValidationErrors(['precio']);
            }
        }

        // Comprobamos que ningún tipo de membresía haya sido registrado.
        $this->assertDatabaseCount('tipo_membresia', 0);
    }
    public function test_los_campos_deben_cumplir_con_sus_tipos_de_datos(): void
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

        // Definimos los datos con tipos inválidos.
        $datosInvalidos = [
            [
                'nombre' => 123,
                'duracion_dias' => 30,
                'precio' => 80000,
            ],
            [
                'nombre' => 'Mensual',
                'duracion_dias' => 30.5,
                'precio' => 80000,
            ],
            [
                'nombre' => 'Trimestral',
                'duracion_dias' => 90,
                'precio' => 'no numerico',
            ],
        ];

        // Intentamos registrar tipos de membresía con datos inválidos.
        foreach ($datosInvalidos as $datos) {
            $response = $this->postJson('/api/tipos-membresia', $datos);

            // Comprobamos que la validación rechaza los tipos de datos incorrectos.
            $response->assertUnprocessable();

            if (!is_string($datos['nombre'])) {
                $response->assertJsonValidationErrors(['nombre']);
            }

            if (!is_int($datos['duracion_dias'])) {
                $response->assertJsonValidationErrors(['duracion_dias']);
            }

            if (!is_numeric($datos['precio'])) {
                $response->assertJsonValidationErrors(['precio']);
            }
        }

        // Comprobamos que ningún tipo de membresía haya sido registrado.
        $this->assertDatabaseCount('tipo_membresia', 0);
    }
    public function test_el_precio_no_puede_tener_mas_de_dos_decimales(): void
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

        // Definimos los precios con más de dos decimales.
        $preciosInvalidos = [
            80000.123,
            150000.456,
            250000.999,
        ];

        // Intentamos registrar tipos de membresía con precios inválidos.
        foreach ($preciosInvalidos as $indice => $precio) {
            $response = $this->postJson('/api/tipos-membresia', [
                'nombre' => "Membresia{$indice}",
                'duracion_dias' => 30,
                'precio' => $precio,
            ]);

            // Comprobamos que la validación rechaza los precios.
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['precio']);
        }

        // Comprobamos que ningún tipo de membresía haya sido registrado.
        $this->assertDatabaseCount('tipo_membresia', 0);
    }
    public function test_no_se_puede_registrar_un_tipo_de_membresia_con_nombre_duplicado(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía que ya existe.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar otro tipo de membresía con el mismo nombre.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'Mensual',
            'duracion_dias' => 60,
            'precio' => 150000,
        ]);

        // Comprobamos que la validación rechaza el nombre duplicado.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        // Comprobamos que solamente existe el registro original.
        $this->assertDatabaseCount('tipo_membresia', 1);
    }
    public function test_no_se_puede_registrar_un_tipo_de_membresia_con_nombre_que_solo_difiere_en_mayusculas(): void
    {
        // Creamos el dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía que ya existe.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar otro tipo con el mismo nombre en minúsculas.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'mensual',
            'duracion_dias' => 60,
            'precio' => 150000,
        ]);

        // Comprobamos que la validación rechaza el nombre duplicado.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        // Comprobamos que solamente existe el registro original.
        $this->assertDatabaseCount('tipo_membresia', 1);
    }
    public function test_el_nombre_no_puede_superar_los_cien_caracteres(): void
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

        // Generamos un nombre que supera el límite permitido.
        $nombreInvalido = str_repeat('a', 101);

        // Intentamos registrar un tipo de membresía con un nombre demasiado largo.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => $nombreInvalido,
            'duracion_dias' => 30,
            'precio' => 80000,
        ]);

        // Comprobamos que la validación rechaza el nombre.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        // Comprobamos que no se registró ningún tipo de membresía.
        $this->assertDatabaseCount('tipo_membresia', 0);
    }
    public function test_se_puede_registrar_un_tipo_de_membresia_con_un_nombre_de_cien_caracteres(): void
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

        // Generamos un nombre con exactamente 100 caracteres.
        $nombre = str_repeat('a', 100);

        // Intentamos registrar el tipo de membresía.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => $nombre,
            'duracion_dias' => 30,
            'precio' => 80000,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('tipo_membresia.nombre', $nombre);

        // Comprobamos que el tipo de membresía fue almacenado.
        $this->assertDatabaseHas('tipo_membresia', [
            'nombre' => $nombre,
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);
    }
    public function test_se_puede_registrar_un_tipo_de_membresia_con_dos_decimales_en_el_precio(): void
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

        // Enviamos la solicitud con un precio de dos decimales.
        $response = $this->postJson('/api/tipos-membresia', [
            'nombre' => 'Mensual Premium',
            'duracion_dias' => 30,
            'precio' => 85000.50,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('tipo_membresia.precio', '85000.50');

        // Comprobamos que el precio fue almacenado correctamente.
        $this->assertDatabaseHas('tipo_membresia', [
            'nombre' => 'Mensual Premium',
            'precio' => 85000.50,
            'activo' => true,
        ]);
    }
}
