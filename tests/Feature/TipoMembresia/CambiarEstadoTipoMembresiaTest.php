<?php

namespace Tests\Feature\TipoMembresia;

use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CambiarEstadoTipoMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_desactivar_un_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo con rol de dueño.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Premium',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Desactivamos el tipo de membresía.
        $response = $this->actingAs($usuario)
            ->patchJson(
                route('tipos-membresia.estado', $tipoMembresia),
                ['activo' => false]
            );

        // Verificamos la respuesta.
        $response->assertOk()
            ->assertJsonPath(
                'mensaje',
                'Estado del tipo de membresía actualizado correctamente.'
            )
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que el estado haya cambiado en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Premium',
            'activo' => false,
        ]);
    }

    public function test_empleado_puede_desactivar_un_tipo_de_membresia(): void
    {
        // Creamos un usuario administrativo con rol de empleado.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'empleado',
            'password' => 'Password123!',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Premium',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Desactivamos el tipo de membresía.
        $response = $this->actingAs($usuario)
            ->patchJson(
                route('tipos-membresia.estado', $tipoMembresia),
                ['activo' => false]
            );

        // Verificamos la respuesta.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que el tipo de membresía quedó inactivo.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => false,
        ]);
    }

    public function test_dueno_puede_activar_un_tipo_de_membresia(): void
    {
        // Creamos el dueño que realizará la activación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía que se encuentra inactivo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos activar el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => true,
        ]);

        // Comprobamos que la activación fue exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', true);

        // Comprobamos que el registro se haya actualizado en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_empleado_puede_activar_un_tipo_de_membresia(): void
    {
        // Creamos el usuario administrativo con rol de empleado.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía que se encuentra inactivo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => false,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Intentamos activar el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => true,
        ]);

        // Comprobamos que la activación fue exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', true);

        // Comprobamos que el registro se haya actualizado en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_se_puede_desactivar_un_tipo_de_membresia_que_ya_esta_inactivo(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía que ya se encuentra inactivo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos desactivar nuevamente el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => false,
        ]);

        // Comprobamos que la operación sea exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que el registro permanezca inactivo en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => false,
        ]);
    }
    public function test_se_puede_activar_un_tipo_de_membresia_que_ya_esta_activo(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía que ya se encuentra activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos activar nuevamente el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => true,
        ]);

        // Comprobamos que la operación sea exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', true);

        // Comprobamos que el registro permanezca activo en la base de datos.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_usuario_no_autenticado_no_puede_cambiar_el_estado_de_un_tipo_de_membresia(): void
    {
        // Creamos un tipo de membresía para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Intentamos cambiar el estado sin autenticación.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => false,
        ]);

        // Comprobamos que la solicitud sea rechazada por falta de autenticación.
        $response->assertUnauthorized();

        // Comprobamos que el estado del tipo de membresía no haya cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_usuario_inactivo_no_puede_cambiar_el_estado_de_un_tipo_de_membresia(): void
    {
        // Creamos un empleado con la cuenta administrativa inactiva.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.inactivo',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Creamos un tipo de membresía para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Intentamos desactivar el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => false,
        ]);

        // Comprobamos que el acceso sea rechazado.
        $response->assertForbidden();

        // Comprobamos que la sesión haya sido cerrada.
        $this->assertGuest('web');

        // Comprobamos que el estado del tipo de membresía no haya cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_no_se_puede_cambiar_el_estado_sin_enviar_el_campo_activo(): void
    {
        // Creamos el dueño que realizará la operación.
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

        // Intentamos cambiar el estado sin enviar el campo activo.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), []);

        // Comprobamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que el estado del tipo de membresía no haya cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_no_se_puede_cambiar_el_estado_con_un_valor_no_booleano(): void
    {
        // Creamos el dueño que realizará la operación.
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

        // Intentamos cambiar el estado utilizando un valor no booleano.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => 'invalido',
        ]);

        // Comprobamos que la validación rechace la solicitud.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que el estado del tipo de membresía no haya cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => true,
        ]);
    }
    public function test_no_se_puede_cambiar_el_estado_de_un_tipo_de_membresia_inexistente(): void
    {
        // Creamos el dueño que realizará la operación.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos cambiar el estado de un tipo de membresía inexistente.
        $response = $this->patchJson(route('tipos-membresia.estado', 999), [
            'activo' => false,
        ]);

        // Comprobamos que la API responda que el recurso no existe.
        $response->assertNotFound();
    }
    public function test_cambiar_el_estado_no_modifica_los_demas_datos_del_tipo_de_membresia(): void
    {
        // Creamos el dueño que realizará la operación.
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

        // Desactivamos el tipo de membresía.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => false,
        ]);

        // Comprobamos que la operación haya sido exitosa.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que los demás datos permanezcan sin modificaciones.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => false,
        ]);
    }
    public function test_cambiar_el_estado_ignora_los_atributos_adicionales_enviados(): void
    {
        // Creamos el dueño que realizará la operación.
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

        // Intentamos cambiar el estado enviando atributos adicionales.
        $response = $this->patchJson(route('tipos-membresia.estado', $tipoMembresia), [
            'activo' => false,
            'nombre' => 'Anual',
            'duracion_dias' => 365,
            'precio' => 200000,
        ]);

        // Comprobamos que el cambio de estado haya sido exitoso.
        $response->assertOk()
            ->assertJsonPath('tipo_membresia.activo', false);

        // Comprobamos que únicamente se haya modificado el estado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 50000,
            'activo' => false,
        ]);
    }
}
