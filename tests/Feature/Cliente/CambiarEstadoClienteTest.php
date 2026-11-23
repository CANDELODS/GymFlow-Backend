<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CambiarEstadoClienteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un usuario administrativo para las pruebas.
     */
    private function crearUsuario(string $rol = 'dueno', bool $activo = true): UsuarioAdministrativo
    {
        return UsuarioAdministrativo::create([
            'usuario' => $rol . '.' . uniqid(),
            'password' => 'Password123!',
            'rol' => $rol,
            'activo' => $activo,
        ]);
    }

    /**
     * Crea un cliente para las pruebas.
     */
    private function crearCliente(array $datos = []): Cliente
    {
        return Cliente::create(array_merge([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ], $datos));
    }

    public function test_dueno_puede_deshabilitar_un_cliente(): void
    {
        // Creamos un usuario administrativo con rol de dueño.
        $usuario = $this->crearUsuario();

        // Creamos un cliente activo.
        $cliente = $this->crearCliente();

        // Deshabilitamos el cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => false,
            ]);

        // Verificamos la respuesta.
        $response->assertOk()
            ->assertJsonPath(
                'mensaje',
                'Estado del cliente actualizado correctamente.'
            )
            ->assertJsonPath('cliente.activo', false);

        // Comprobamos que el estado se actualizó en la base de datos.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => false,
        ]);
    }

    public function test_empleado_puede_deshabilitar_un_cliente(): void
    {
        // Creamos un usuario administrativo con rol de empleado.
        $usuario = $this->crearUsuario('empleado');

        $cliente = $this->crearCliente();

        // Deshabilitamos el cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.activo', false);

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => false,
        ]);
    }

    public function test_dueno_puede_habilitar_nuevamente_un_cliente(): void
    {
        $usuario = $this->crearUsuario();

        // Creamos un cliente que se encuentra deshabilitado.
        $cliente = $this->crearCliente([
            'activo' => false,
        ]);

        // Habilitamos nuevamente el cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.activo', true);

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => true,
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_cambiar_el_estado_de_un_cliente(): void
    {
        $cliente = $this->crearCliente();

        // Intentamos cambiar el estado sin iniciar sesión.
        $response = $this->patchJson(
            route('cliente.estado', $cliente),
            ['activo' => false]
        );

        $response->assertUnauthorized();

        // Comprobamos que el estado original se conserve.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => true,
        ]);
    }

    public function test_usuario_inactivo_no_puede_cambiar_el_estado_de_un_cliente(): void
    {
        $usuario = $this->crearUsuario('dueno', false);
        $cliente = $this->crearCliente();

        // Intentamos cambiar el estado con un usuario inactivo.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => false,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => true,
        ]);
    }

    public function test_no_se_permite_cambiar_el_estado_sin_enviar_el_campo_activo(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Intentamos cambiar el estado sin enviar el campo requerido.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que el estado original se conserve.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => true,
        ]);
    }

    public function test_no_se_permite_cambiar_el_estado_con_valores_invalidos(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Intentamos enviar valores que no representan un booleano válido.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => 'inactivo',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        // Comprobamos que el cliente no haya sido modificado.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => true,
        ]);
    }

    public function test_no_se_puede_cambiar_el_estado_de_un_cliente_inexistente(): void
    {
        $usuario = $this->crearUsuario();

        // Intentamos cambiar el estado de un cliente inexistente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', 999), [
                'activo' => false,
            ]);

        $response->assertNotFound();

        // Comprobamos que no se haya creado ningún cliente.
        $this->assertDatabaseCount('cliente', 0);
    }

    public function test_cambiar_el_estado_no_modifica_los_demas_datos_del_cliente(): void
    {
        $usuario = $this->crearUsuario();

        $cliente = $this->crearCliente();

        // Intentamos enviar otros datos junto con el cambio de estado.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => false,
                'nombres' => 'Juan',
                'apellidos' => 'Perez',
                'documento' => '9876543210',
                'telefono' => '3119876543',
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.activo', false);

        // Comprobamos que únicamente se haya modificado el estado.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => false,
        ]);
    }

    public function test_cambiar_el_estado_no_modifica_el_historial_de_membresias(): void
    {
        $usuario = $this->crearUsuario();

        $cliente = $this->crearCliente();

        // Creamos un tipo de membresía para asociarlo al historial.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Creamos una membresía adquirida asociada al cliente.
        $fechaInicio = now()->toDateString();

        $membresia = MembresiaAdquirida::create([
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => $usuario->id_usuario,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => now()->addDays(29)->endOfDay(),
            'valor_pagado' => 80000,
            'metodo_pago' => 'efectivo',
        ]);

        // Deshabilitamos el cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.estado', $cliente), [
                'activo' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.activo', false);

        // Comprobamos que la membresía siga asociada al mismo cliente.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $membresia->id_membresia_adquirida,
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre_tipo_snapshot' => 'Mensual',
            'valor_pagado' => 80000,
        ]);

        // Verificamos que no se haya eliminado ni creado historial adicional.
        $this->assertDatabaseCount('membresia_adquirida', 1);
    }
}