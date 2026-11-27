<?php

namespace Tests\Feature\MembresiaAdquirida;

use App\Actions\MembresiaAdquirida\ActualizarMembresiaAdquirida;
use App\Events\MembresiaAdquiridaModificadaEvent;
use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ActualizarMembresiaAdquiridaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El dueño puede actualizar el valor pagado de una membresía adquirida.
     */
    public function test_dueno_puede_actualizar_el_valor_pagado_de_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'valor_pagado' => 80000,
                'motivo' => 'Corrección del valor pagado.',
            ]
        );

        $respuesta->assertOk()
            ->assertJsonPath('mensaje', 'Membresía adquirida actualizada correctamente.')
            ->assertJsonPath('membresia_adquirida.valor_pagado', '80000.00');

        // Comprobamos que se actualizó el valor pagado.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 80000,
        ]);

        // Comprobamos que se registró la modificación en la auditoría.
        $this->assertDatabaseHas('registro_modificaciones', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_usuario' => $datos['dueno']->id_usuario,
            'motivo' => 'Corrección del valor pagado.',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'valor_pagado',
            'valor_anterior' => '75000.00',
            'valor_nuevo' => '80000.00',
        ]);

        $this->assertDatabaseCount('registro_modificaciones', 1);
        $this->assertDatabaseCount('detalle_modificacion', 1);
    }

    /**
     * El dueño puede actualizar el cliente de una membresía adquirida.
     */
    public function test_dueno_puede_actualizar_el_cliente_de_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        $nuevoCliente = Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Rodriguez',
            'documento' => '9876543210',
            'telefono' => '3101234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_cliente' => $nuevoCliente->id_cliente,
                'motivo' => 'Corrección del cliente asociado.',
            ]
        );

        $respuesta->assertOk();

        // Comprobamos que se actualizó el cliente.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_cliente' => $nuevoCliente->id_cliente,
        ]);

        // Comprobamos que se registró el cambio en la auditoría.
        $this->assertDatabaseHas('registro_modificaciones', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_usuario' => $datos['dueno']->id_usuario,
            'motivo' => 'Corrección del cliente asociado.',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'id_cliente',
            'valor_anterior' => $datos['cliente']->id_cliente,
            'valor_nuevo' => $nuevoCliente->id_cliente,
        ]);

        $this->assertDatabaseCount('registro_modificaciones', 1);
        $this->assertDatabaseCount('detalle_modificacion', 1);
    }

    /**
     * El dueño puede actualizar el tipo de membresía y sus snapshots.
     */
    public function test_dueno_puede_actualizar_el_tipo_de_membresia_y_sus_snapshots(): void
    {
        $datos = $this->crearDatosIniciales();

        $nuevoTipo = TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 200000,
            'activo' => true,
        ]);

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_tipo_membresia' => $nuevoTipo->id_tipo_membresia,
                'motivo' => 'Cambio del tipo de membresía.',
            ]
        );

        $respuesta->assertOk();

        // Comprobamos que se actualizó el tipo y sus snapshots.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_tipo_membresia' => $nuevoTipo->id_tipo_membresia,
            'nombre_tipo_snapshot' => 'Trimestral',
            'duracion_dias_snapshot' => 90,
            'fecha_vencimiento' => now()->addDays(89)->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
        ]);

        // Comprobamos el registro general de auditoría.
        $this->assertDatabaseHas('registro_modificaciones', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_usuario' => $datos['dueno']->id_usuario,
            'motivo' => 'Cambio del tipo de membresía.',
        ]);

        // Comprobamos los detalles del tipo y sus snapshots.
        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'id_tipo_membresia',
            'valor_anterior' => $datos['tipoMembresia']->id_tipo_membresia,
            'valor_nuevo' => $nuevoTipo->id_tipo_membresia,
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'nombre_tipo_snapshot',
            'valor_anterior' => 'Mensual',
            'valor_nuevo' => 'Trimestral',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'duracion_dias_snapshot',
            'valor_anterior' => 30,
            'valor_nuevo' => 90,
        ]);

        // También debe quedar registrado el cambio de vencimiento.
        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'fecha_vencimiento',
            'valor_anterior' => now()->addDays(29)->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            'valor_nuevo' => now()->addDays(89)->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount('registro_modificaciones', 1);
        $this->assertDatabaseCount('detalle_modificacion', 4);
    }

    /**
     * El dueño puede actualizar la fecha de inicio y recalcular el vencimiento.
     */
    public function test_dueno_puede_actualizar_la_fecha_de_inicio_y_recalcular_el_vencimiento(): void
    {
        $datos = $this->crearDatosIniciales();

        $nuevaFechaInicio = now()->subDays(10)->toDateString();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'fecha_inicio' => $nuevaFechaInicio,
                'motivo' => 'Corrección de la fecha de inicio.',
            ]
        );

        $respuesta->assertOk();

        $nuevoVencimiento = now()
            ->subDays(10)
            ->addDays(29)
            ->setTime(23, 59, 59);

        // Comprobamos que se actualizaron las fechas.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'fecha_inicio' => $nuevaFechaInicio,
            'fecha_vencimiento' => $nuevoVencimiento->format('Y-m-d H:i:s'),
        ]);

        // Comprobamos el registro general de auditoría.
        $this->assertDatabaseHas('registro_modificaciones', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_usuario' => $datos['dueno']->id_usuario,
            'motivo' => 'Corrección de la fecha de inicio.',
        ]);

        // Comprobamos el detalle de la fecha de inicio.
        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'fecha_inicio',
            'valor_anterior' => now()->toDateString(),
            'valor_nuevo' => $nuevaFechaInicio,
        ]);

        // Comprobamos el detalle del vencimiento recalculado.
        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'fecha_vencimiento',
            'valor_anterior' => now()->addDays(29)->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            'valor_nuevo' => $nuevoVencimiento->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount('registro_modificaciones', 1);
        $this->assertDatabaseCount('detalle_modificacion', 2);
    }

    /**
     * El dueño puede actualizar el método de pago de una membresía adquirida.
     */
    public function test_dueno_puede_actualizar_el_metodo_de_pago_de_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'metodo_pago' => 'nequi',
                'motivo' => 'Corrección del método de pago.',
            ]
        );

        $respuesta->assertOk();

        // Comprobamos que se actualizó el método de pago.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'metodo_pago' => 'nequi',
        ]);

        // Comprobamos el registro general de auditoría.
        $this->assertDatabaseHas('registro_modificaciones', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_usuario' => $datos['dueno']->id_usuario,
            'motivo' => 'Corrección del método de pago.',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'metodo_pago',
            'valor_anterior' => 'efectivo',
            'valor_nuevo' => 'nequi',
        ]);

        $this->assertDatabaseCount('registro_modificaciones', 1);
        $this->assertDatabaseCount('detalle_modificacion', 1);
    }

    /**
     * El dueño puede actualizar varios campos de una membresía simultáneamente.
     */
    public function test_dueno_puede_actualizar_varios_campos_de_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        $nuevoCliente = Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Rodriguez',
            'documento' => '9876543210',
            'telefono' => '3101234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        $nuevoTipo = TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 200000,
            'activo' => true,
        ]);

        $nuevaFechaInicio = now()->subDays(10)->toDateString();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_cliente' => $nuevoCliente->id_cliente,
                'id_tipo_membresia' => $nuevoTipo->id_tipo_membresia,
                'fecha_inicio' => $nuevaFechaInicio,
                'valor_pagado' => 180000,
                'metodo_pago' => 'bancolombia',
                'motivo' => 'Actualización general de la membresía.',
            ]
        );

        $respuesta->assertOk();

        $nuevoVencimiento = now()
            ->subDays(10)
            ->addDays(89)
            ->setTime(23, 59, 59);

        // Comprobamos que todos los campos fueron actualizados.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_cliente' => $nuevoCliente->id_cliente,
            'id_tipo_membresia' => $nuevoTipo->id_tipo_membresia,
            'nombre_tipo_snapshot' => 'Trimestral',
            'duracion_dias_snapshot' => 90,
            'fecha_inicio' => $nuevaFechaInicio,
            'fecha_vencimiento' => $nuevoVencimiento->format('Y-m-d H:i:s'),
            'valor_pagado' => 180000,
            'metodo_pago' => 'bancolombia',
        ]);

        // Comprobamos que se creó un solo registro general de auditoría.
        $this->assertDatabaseHas('registro_modificaciones', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_usuario' => $datos['dueno']->id_usuario,
            'motivo' => 'Actualización general de la membresía.',
        ]);

        $this->assertDatabaseCount('registro_modificaciones', 1);

        // Se registran los cambios del cliente, tipo y sus snapshots.
        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'id_cliente',
            'valor_anterior' => $datos['cliente']->id_cliente,
            'valor_nuevo' => $nuevoCliente->id_cliente,
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'id_tipo_membresia',
            'valor_anterior' => $datos['tipoMembresia']->id_tipo_membresia,
            'valor_nuevo' => $nuevoTipo->id_tipo_membresia,
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'nombre_tipo_snapshot',
            'valor_anterior' => 'Mensual',
            'valor_nuevo' => 'Trimestral',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'duracion_dias_snapshot',
            'valor_anterior' => 30,
            'valor_nuevo' => 90,
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'fecha_inicio',
            'valor_anterior' => now()->toDateString(),
            'valor_nuevo' => $nuevaFechaInicio,
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'valor_pagado',
            'valor_anterior' => '75000.00',
            'valor_nuevo' => '180000.00',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'metodo_pago',
            'valor_anterior' => 'efectivo',
            'valor_nuevo' => 'bancolombia',
        ]);

        $this->assertDatabaseHas('detalle_modificacion', [
            'campo_modificado' => 'fecha_vencimiento',
            'valor_anterior' => now()->addDays(29)->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            'valor_nuevo' => $nuevoVencimiento->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount('detalle_modificacion', 8);
    }

    /**
     * Un empleado no puede actualizar una membresía adquirida.
     */
    public function test_empleado_no_puede_actualizar_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.actualizar',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        $this->actingAs($empleado, 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'valor_pagado' => 80000,
                'motivo' => 'Intento de modificación por empleado.',
            ]
        );

        // Comprobamos que el acceso fue rechazado.
        $respuesta->assertForbidden();

        // Comprobamos que la membresía no fue modificada.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // Comprobamos que no se generaron registros de auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * Un usuario no autenticado no puede actualizar una membresía adquirida.
     */
    public function test_usuario_no_autenticado_no_puede_actualizar_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        // No autenticamos ningún usuario para realizar la petición.
        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'valor_pagado' => 80000,
                'motivo' => 'Intento de modificación sin autenticación.',
            ]
        );

        // Comprobamos que se requiere autenticación.
        $respuesta->assertUnauthorized();

        // Comprobamos que la membresía no fue modificada.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // Comprobamos que no se generaron registros de auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede actualizar una membresía sin enviar campos modificables.
     */
    public function test_no_se_puede_actualizar_una_membresia_sin_campos_modificables(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'motivo' => 'Intento de actualización sin cambios.',
            ]
        );

        $respuesta->assertUnprocessable();

        // La membresía debe conservar su información original.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede actualizar una membresía con un motivo vacío.
     */
    public function test_no_se_puede_actualizar_una_membresia_con_un_motivo_vacio(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'valor_pagado' => 80000,
                'motivo' => '   ',
            ]
        );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');

        // La membresía no debe modificarse.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede actualizar una membresía con un valor pagado negativo.
     */
    public function test_no_se_puede_actualizar_una_membresia_con_un_valor_pagado_negativo(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'valor_pagado' => -10000,
                'motivo' => 'Intento de registrar un valor negativo.',
            ]
        );

        $respuesta->assertUnprocessable();

        // El valor pagado debe conservarse.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede actualizar una membresía con un método de pago no permitido.
     */
    public function test_no_se_puede_actualizar_una_membresia_con_un_metodo_de_pago_no_permitido(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'metodo_pago' => 'tarjeta',
                'motivo' => 'Intento de registrar un método de pago no permitido.',
            ]
        );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('metodo_pago');

        // El método de pago debe conservarse.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'metodo_pago' => 'efectivo',
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede asignar un cliente inactivo a una membresía adquirida.
     */
    public function test_no_se_puede_asignar_un_cliente_inactivo_a_una_membresia_adquirida(): void
    {
        $datos = $this->crearDatosIniciales();

        $clienteInactivo = Cliente::create([
            'nombres' => 'Pedro',
            'apellidos' => 'Gomez',
            'documento' => '1122334455',
            'telefono' => '3201234567',
            'activo' => false,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_cliente' => $clienteInactivo->id_cliente,
                'motivo' => 'Intento de asignar un cliente inactivo.',
            ]
        );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('id_cliente');

        // La membresía debe conservar el cliente original.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_cliente' => $datos['cliente']->id_cliente,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede asignar un tipo de membresía inactivo a una membresía adquirida.
     */
    public function test_no_se_puede_asignar_un_tipo_de_membresia_inactivo(): void
    {
        $datos = $this->crearDatosIniciales();

        $tipoInactivo = TipoMembresia::create([
            'nombre' => 'Semestral',
            'duracion_dias' => 180,
            'precio' => 300000,
            'activo' => false,
        ]);

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_tipo_membresia' => $tipoInactivo->id_tipo_membresia,
                'motivo' => 'Intento de asignar un tipo de membresía inactivo.',
            ]
        );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('id_tipo_membresia');

        // La membresía debe conservar el tipo y snapshots originales.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_tipo_membresia' => $datos['tipoMembresia']->id_tipo_membresia,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede actualizar una membresía cuando no existen cambios reales.
     */
    public function test_no_se_puede_actualizar_una_membresia_con_los_mismos_valores(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'valor_pagado' => 75000,
                'motivo' => 'Intento de actualizar con el mismo valor.',
            ]
        );

        $respuesta->assertUnprocessable();

        // El valor pagado debe permanecer sin cambios.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // No debe generarse auditoría porque no hubo cambios reales.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede asignar un cliente inexistente a una membresía adquirida.
     */
    public function test_no_se_puede_asignar_un_cliente_inexistente(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_cliente' => 999999,
                'motivo' => 'Intento de asignar un cliente inexistente.',
            ]
        );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('id_cliente');

        // La membresía debe conservar el cliente original.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_cliente' => $datos['cliente']->id_cliente,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede asignar un tipo de membresía inexistente.
     */
    public function test_no_se_puede_asignar_un_tipo_de_membresia_inexistente(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', $datos['membresia']->id_membresia_adquirida),
            [
                'id_tipo_membresia' => 999999,
                'motivo' => 'Intento de asignar un tipo de membresía inexistente.',
            ]
        );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('id_tipo_membresia');

        // La membresía debe conservar el tipo y snapshots originales.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'id_tipo_membresia' => $datos['tipoMembresia']->id_tipo_membresia,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * No se puede actualizar una membresía adquirida inexistente.
     */
    public function test_no_se_puede_actualizar_una_membresia_adquirida_inexistente(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        $respuesta = $this->patchJson(
            route('membresias-adquiridas.update', 999999),
            [
                'valor_pagado' => 80000,
                'motivo' => 'Intento de actualizar una membresía inexistente.',
            ]
        );

        $respuesta->assertNotFound();

        // La membresía original debe conservar su valor.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // No debe generarse auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * La actualización se revierte cuando ocurre un error durante la auditoría.
     */
    public function test_se_revierte_la_actualizacion_si_falla_el_registro_de_auditoria(): void
    {
        $datos = $this->crearDatosIniciales();

        $this->actingAs($datos['dueno'], 'web');

        // Registramos un listener adicional que provoca un error
        // después de que se procese el listener de auditoría.
        app('events')->listen(
            MembresiaAdquiridaModificadaEvent::class,
            function () {
                throw new \RuntimeException('Error simulado durante la auditoría.');
            }
        );

        try {
            // Ejecutamos directamente la Action para comprobar
            // el comportamiento de la transacción.
            app(ActualizarMembresiaAdquirida::class)
                ->ejecutar($datos['membresia'], [
                    'valor_pagado' => 80000,
                    'motivo' => 'Prueba de reversión de la transacción.',
                ]);

            $this->fail('Se esperaba una excepción durante el registro de auditoría.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Error simulado durante la auditoría.',
                $exception->getMessage()
            );
        }

        // La actualización debe haberse revertido.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $datos['membresia']->id_membresia_adquirida,
            'valor_pagado' => 75000,
        ]);

        // Tampoco deben quedar registros parciales de auditoría.
        $this->assertDatabaseCount('registro_modificaciones', 0);
        $this->assertDatabaseCount('detalle_modificacion', 0);
    }

    /**
     * Crea los datos iniciales necesarios para las pruebas.
     */
    private function crearDatosIniciales(): array
    {
        // Fijamos la fecha actual al inicio del día para controlar las fechas.
        $this->travelTo(now()->startOfDay());

        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.actualizar',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $membresia = MembresiaAdquirida::create([
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => $dueno->id_usuario,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
            'fecha_inicio' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(29)->setTime(23, 59, 59),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        return [
            'dueno' => $dueno,
            'cliente' => $cliente,
            'tipoMembresia' => $tipoMembresia,
            'membresia' => $membresia,
        ];
    }
}
