<?php

namespace Tests\Feature\TipoMembresia;

use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizarTipoMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_actualizar_un_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo con rol de dueño.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Realizamos la solicitud para actualizar el nombre.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'nombre' => 'Premium',
            ]);

        // Verificamos la respuesta.
        $response->assertStatus(200)
            ->assertJsonPath('mensaje', 'Tipo de membresía actualizado correctamente.')
            ->assertJsonPath('tipo_membresia.nombre', 'Premium');

        // Comprobamos que los datos se actualizaron en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Premium',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_empleado_puede_actualizar_un_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo con rol de empleado.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'empleado',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Actualizamos el precio.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'precio' => 60000,
            ]);

        // Verificamos la respuesta.
        $response->assertStatus(200)
            ->assertJsonPath('tipo_membresia.precio', '60000.00');

        // Comprobamos que el precio se actualizó.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 60000,
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_actualizar_un_tipo_de_membresia(): void
    {
        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar sin iniciar sesión.
        $response = $this->patchJson(
            route('tipos-membresia.update', $tipoMembresia),
            ['nombre' => 'Premium']
        );

        // Verificamos que el acceso sea rechazado.
        $response->assertUnauthorized();

        // Comprobamos que el registro no haya sido modificado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_usuario_inactivo_no_puede_actualizar_un_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo inactivo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'inactivo',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => false,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar con el usuario inactivo.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'nombre' => 'Premium',
            ]);

        // Verificamos que el acceso sea rechazado.
        $response->assertForbidden();

        // Comprobamos que el registro permanezca sin modificaciones.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_se_puede_actualizar_unicamente_la_duracion(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Actualizamos únicamente la duración.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'duracion_dias' => 60,
            ]);

        // Verificamos la respuesta.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.duracion_dias', 60);

        // Comprobamos que los demás campos conservaron sus valores.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 60,
            'precio' => 50000,
        ]);
    }

    public function test_se_permite_actualizar_con_una_solicitud_vacia(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Enviamos una solicitud sin campos para actualizar.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), []);

        // Verificamos que la solicitud sea aceptada.
        $response->assertOk()
            ->assertJsonPath('mensaje', 'Tipo de membresía actualizado correctamente.')
            ->assertJsonPath('tipo_membresia.nombre', 'Básica')
            ->assertJsonPath('tipo_membresia.duracion_dias', 30)
            ->assertJsonPath('tipo_membresia.precio', '50000.00');

        // Comprobamos que los datos permanezcan intactos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_no_se_permite_actualizar_con_un_nombre_vacio(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar enviando un nombre vacío.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'nombre' => '',
            ]);

        // Verificamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        // Comprobamos que el registro no haya sido modificado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_no_se_permite_actualizar_con_una_duracion_menor_o_igual_a_cero(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar con una duración igual a cero.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'duracion_dias' => 0,
            ]);

        // Verificamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['duracion_dias']);

        // Comprobamos que el registro conserve sus datos originales.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_no_se_permite_actualizar_con_una_duracion_que_no_sea_entera(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar con una duración decimal.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'duracion_dias' => 30.5,
            ]);

        // Verificamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['duracion_dias']);

        // Comprobamos que el registro conserve sus datos originales.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_no_se_permite_actualizar_con_un_precio_menor_o_igual_a_cero(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar con un precio igual a cero.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'precio' => 0,
            ]);

        // Verificamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['precio']);

        // Comprobamos que el registro conserve sus datos originales.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_no_se_permite_actualizar_con_un_precio_de_mas_de_dos_decimales(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Intentamos actualizar con un precio de tres decimales.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'precio' => 50000.123,
            ]);

        // Verificamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['precio']);

        // Comprobamos que el registro conserve sus datos originales.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_no_se_permite_actualizar_con_el_nombre_de_otro_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos dos tipos de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        TipoMembresia::create([
            'nombre' => 'Premium',
            'duracion_dias' => 60,
            'precio' => 90000,
        ]);

        // Intentamos asignar el nombre de otro tipo de membresía.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'nombre' => 'Premium',
            ]);

        // Verificamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        // Comprobamos que el registro original no haya sido modificado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);
    }

    public function test_se_permite_conservar_el_nombre_actual_del_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Actualizamos enviando el mismo nombre.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'nombre' => 'Básica',
            ]);

        // Verificamos que la solicitud sea aceptada.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.nombre', 'Básica');

        // Comprobamos que el registro conserve su nombre.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Básica',
        ]);
    }

    public function test_no_se_puede_actualizar_un_tipo_de_membresia_inexistente(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Intentamos actualizar un tipo de membresía inexistente.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', 999), [
                'nombre' => 'Premium',
            ]);

        // Verificamos que el registro no sea encontrado.
        $response->assertNotFound();

        // Comprobamos que no se haya creado ningún tipo de membresía.
        $this->assertDatabaseCount('tipo_membresia', 0);
    }

    public function test_se_permite_actualizar_con_un_precio_de_dos_decimales(): void
    {
        // Creamos un usuario administrativo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Básica',
            'duracion_dias' => 30,
            'precio' => 50000,
        ]);

        // Actualizamos el precio con dos decimales.
        $response = $this->actingAs($usuario)
            ->patchJson(route('tipos-membresia.update', $tipoMembresia), [
                'precio' => 50000.75,
            ]);

        // Verificamos que la solicitud sea aceptada.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.precio', '50000.75');

        // Comprobamos que el precio se haya actualizado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'precio' => 50000.75,
        ]);
    }
    public function test_se_puede_actualizar_unicamente_el_estado(): void
    {
        // Creamos el dueño que realizará la actualización.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Actualizamos únicamente el estado del tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.update', $tipoMembresia), [
            'activo' => false,
        ]);

        // Comprobamos que la actualización haya sido exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que el estado se haya actualizado en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => false,
        ]);
    }
    public function test_se_puede_actualizar_el_precio_y_el_estado_simultaneamente(): void
    {
        // Creamos el dueño que realizará la actualización.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Actualizamos el precio y desactivamos el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.update', $tipoMembresia), [
            'precio' => 60000,
            'activo' => false,
        ]);

        // Comprobamos que la actualización haya sido exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.precio', '60000.00')
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que ambos atributos se hayan actualizado en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'precio' => 60000,
            'activo' => false,
        ]);
    }
    public function test_se_pueden_actualizar_todos_los_atributos_simultaneamente(): void
    {
        // Creamos el dueño que realizará la actualización.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Actualizamos todos los atributos del tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.update', $tipoMembresia), [
            'nombre' => 'Anual',
            'duracion_dias' => 365,
            'precio' => 500000,
            'activo' => false,
        ]);

        // Comprobamos que la actualización haya sido exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.nombre', 'Anual')
            ->assertJsonPath('tipo_membresia.duracion_dias', 365)
            ->assertJsonPath('tipo_membresia.precio', '500000.00')
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que todos los atributos se hayan actualizado en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Anual',
            'duracion_dias' => 365,
            'precio' => 500000,
            'activo' => false,
        ]);
    }
    public function test_no_se_puede_actualizar_con_un_estado_no_booleano(): void
    {
        // Creamos el dueño que realizará la actualización.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos actualizar el estado utilizando un valor no booleano.
        $response = $this->patchJson(route('tipos-membresia.update', $tipoMembresia), [
            'activo' => 'invalido',
        ]);

        // Comprobamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que los datos del tipo de membresía no hayan cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);
    }
    public function test_no_se_puede_actualizar_con_el_estado_vacio(): void
    {
        // Creamos el dueño que realizará la actualización.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos actualizar enviando el estado vacío.
        $response = $this->patchJson(route('tipos-membresia.update', $tipoMembresia), [
            'activo' => '',
        ]);

        // Comprobamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que los datos del tipo de membresía no hayan cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);
    }
    public function test_se_conserva_el_estado_actual_cuando_no_se_envia_el_campo_activo(): void
    {
        // Creamos el dueño que realizará la actualización.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía inactivo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Actualizamos el precio sin enviar el campo activo.
        $response = $this->patchJson(route('tipos-membresia.update', $tipoMembresia), [
            'precio' => 60000,
        ]);

        // Comprobamos que la actualización haya sido exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.precio', '60000.00')
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que el precio haya cambiado y el estado se haya conservado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 60000,
            'activo' => false,
        ]);
    }
}
