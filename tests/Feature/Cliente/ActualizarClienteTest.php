<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizarClienteTest extends TestCase
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

    public function test_dueno_puede_actualizar_un_cliente(): void
    {
        // Creamos un usuario administrativo con rol de dueño.
        $usuario = $this->crearUsuario();

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Actualizamos únicamente los nombres.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => 'Juan Carlos',
            ]);

        // Verificamos la respuesta.
        $response->assertOk()
            ->assertJsonPath('mensaje', 'Cliente actualizado correctamente.')
            ->assertJsonPath('cliente.nombres', 'Juan Carlos');

        // Comprobamos que los demás datos conservaron sus valores.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Juan Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
        ]);
    }

    public function test_empleado_puede_actualizar_un_cliente(): void
    {
        // Creamos un usuario administrativo con rol de empleado.
        $usuario = $this->crearUsuario('empleado');

        $cliente = $this->crearCliente();

        // Actualizamos el teléfono del cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'telefono' => '3119876543',
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.telefono', '3119876543');

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'telefono' => '3119876543',
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_actualizar_un_cliente(): void
    {
        $cliente = $this->crearCliente();

        // Intentamos actualizar sin iniciar sesión.
        $response = $this->patchJson(
            route('cliente.update', $cliente),
            ['nombres' => 'Juan']
        );

        $response->assertUnauthorized();

        // Comprobamos que el cliente no haya sido modificado.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Carlos',
        ]);
    }

    public function test_usuario_inactivo_no_puede_actualizar_un_cliente(): void
    {
        $usuario = $this->crearUsuario('dueno', false);
        $cliente = $this->crearCliente();

        // Intentamos actualizar con un usuario inactivo.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => 'Juan',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Carlos',
        ]);
    }

    public function test_se_pueden_actualizar_todos_los_campos_simultaneamente(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Actualizamos todos los campos editables.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => 'Juan Carlos',
                'apellidos' => 'Perez',
                'documento' => '9876543210',
                'telefono' => '3119876543',
                'activo' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.nombres', 'Juan Carlos')
            ->assertJsonPath('cliente.apellidos', 'Perez')
            ->assertJsonPath('cliente.documento', '9876543210')
            ->assertJsonPath('cliente.telefono', '3119876543')
            ->assertJsonPath('cliente.activo', false);

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Juan Carlos',
            'apellidos' => 'Perez',
            'documento' => '9876543210',
            'telefono' => '3119876543',
            'activo' => false,
        ]);
    }

    public function test_se_pueden_actualizar_individualmente_los_campos_del_cliente(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Actualizamos únicamente los apellidos.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'apellidos' => 'Rodriguez',
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.apellidos', 'Rodriguez');

        // Actualizamos únicamente el documento.
        $response = $this->patchJson(route('cliente.update', $cliente), [
            'documento' => '9876543210',
        ]);

        $response->assertOk()
            ->assertJsonPath('cliente.documento', '9876543210');

        // Actualizamos únicamente el estado.
        $response = $this->patchJson(route('cliente.update', $cliente), [
            'activo' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('cliente.activo', false);

        // Comprobamos que las actualizaciones se conservaron.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Carlos',
            'apellidos' => 'Rodriguez',
            'documento' => '9876543210',
            'telefono' => '3001234567',
            'activo' => false,
        ]);
    }

    public function test_se_permite_actualizar_con_una_solicitud_vacia(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Enviamos una solicitud sin campos para actualizar.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), []);

        $response->assertOk()
            ->assertJsonPath('mensaje', 'Cliente actualizado correctamente.')
            ->assertJsonPath('cliente.nombres', 'Carlos')
            ->assertJsonPath('cliente.apellidos', 'Gomez')
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('cliente.telefono', '3001234567')
            ->assertJsonPath('cliente.activo', true);

        // Comprobamos que los datos permanezcan intactos.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
        ]);
    }

    public function test_no_se_permite_actualizar_con_campos_obligatorios_vacios(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Intentamos actualizar enviando campos vacíos.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => '',
                'apellidos' => '',
                'documento' => '',
                'telefono' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombres',
                'apellidos',
                'documento',
                'telefono',
            ]);

        // Comprobamos que los datos originales se conserven.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
        ]);
    }

    public function test_no_se_permite_actualizar_con_valores_que_superen_las_longitudes_maximas(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Intentamos enviar valores que superan los límites establecidos.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => str_repeat('A', 101),
                'apellidos' => str_repeat('B', 101),
                'documento' => str_repeat('1', 31),
                'telefono' => str_repeat('3', 21),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombres',
                'apellidos',
                'documento',
                'telefono',
            ]);
    }

    public function test_no_se_permite_actualizar_con_valores_que_no_sean_cadenas(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Enviamos valores que no corresponden al tipo de dato esperado.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => 123,
                'apellidos' => 456,
                'documento' => 789,
                'telefono' => 3001234567,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombres',
                'apellidos',
                'documento',
                'telefono',
            ]);
    }

    public function test_no_se_permite_utilizar_el_documento_de_otro_cliente(): void
    {
        $usuario = $this->crearUsuario();

        $cliente = $this->crearCliente();

        // Creamos otro cliente con un documento diferente.
        $otroCliente = $this->crearCliente([
            'nombres' => 'Pedro',
            'documento' => '9876543210',
        ]);

        // Intentamos utilizar el documento que pertenece al otro cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'documento' => $otroCliente->documento,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['documento']);

        // Comprobamos que ambos clientes conserven sus documentos.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'documento' => '1234567890',
        ]);

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $otroCliente->id_cliente,
            'documento' => '9876543210',
        ]);
    }

    public function test_se_permite_conservar_el_documento_actual_del_cliente(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Enviamos el mismo documento que ya tiene el cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'documento' => '1234567890',
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.documento', '1234567890');

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'documento' => '1234567890',
        ]);
    }

    public function test_no_se_permite_actualizar_con_un_estado_invalido(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Intentamos enviar un estado que no sea booleano.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'activo' => 'inactivo',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activo']);

        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'activo' => true,
        ]);
    }

    public function test_no_se_puede_actualizar_un_cliente_inexistente(): void
    {
        $usuario = $this->crearUsuario();

        // Intentamos actualizar un cliente que no existe.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', 999), [
                'nombres' => 'Juan',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('cliente', 0);
    }

    public function test_no_se_permite_modificar_los_datos_de_autorizacion_de_tratamiento(): void
    {
        $usuario = $this->crearUsuario();
        $cliente = $this->crearCliente();

        // Intentamos modificar campos que no forman parte de la actualización.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => 'Juan',
                'autorizacion_tratamiento' => false,
                'fecha_autorizacion' => null,
            ]);

        $response->assertOk()
            ->assertJsonPath('cliente.nombres', 'Juan')
            ->assertJsonPath('cliente.autorizacion_tratamiento', true);

        // Comprobamos que la autorización y su fecha permanezcan intactas.
        $clienteActualizado = $cliente->fresh();

        $this->assertTrue($clienteActualizado->autorizacion_tratamiento);
        $this->assertNotNull($clienteActualizado->fecha_autorizacion);
    }

    public function test_actualizar_un_cliente_no_modifica_su_historial_de_membresias(): void
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

        // Actualizamos la información del cliente.
        $response = $this->actingAs($usuario, 'web')
            ->patchJson(route('cliente.update', $cliente), [
                'nombres' => 'Juan Carlos',
                'documento' => '9876543210',
            ]);

        $response->assertOk();

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