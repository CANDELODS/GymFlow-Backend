<?php

namespace Tests\Feature\MembresiaAdquirida;

use App\Actions\TipoMembresia\CambiarEstadoTipoMembresia;
use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RegistrarMembresiaAdquiridaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_registrar_una_membresia_adquirida(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos el usuario administrativo que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.test',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente activo con autorización de tratamiento de datos.
        $cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Definimos la fecha de inicio y calculamos el vencimiento esperado.
        $fechaInicio = now()->toDateString();

        $fechaVencimiento = now()
            ->addDays(29)
            ->setTime(23, 59, 59)
            ->format('Y-m-d H:i:s');

        // Enviamos la solicitud para registrar la membresía adquirida.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => $fechaInicio,
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Membresía adquirida registrada correctamente.')
            ->assertJsonPath('membresia_adquirida.id_cliente', $cliente->id_cliente)
            ->assertJsonPath('membresia_adquirida.id_tipo_membresia', $tipoMembresia->id_tipo_membresia)
            ->assertJsonPath('membresia_adquirida.id_usuario_registro', $dueno->id_usuario)
            ->assertJsonPath('membresia_adquirida.nombre_tipo_snapshot', 'Mensual')
            ->assertJsonPath('membresia_adquirida.duracion_dias_snapshot', 30)
            ->assertJsonPath('membresia_adquirida.fecha_inicio', $fechaInicio)
            ->assertJsonPath('membresia_adquirida.fecha_vencimiento', $fechaVencimiento)
            ->assertJsonPath('membresia_adquirida.valor_pagado', '75000.00')
            ->assertJsonPath('membresia_adquirida.metodo_pago', 'efectivo');

        // Comprobamos que la membresía adquirida fue almacenada correctamente.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => $dueno->id_usuario,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaVencimiento,
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que solamente se registró una membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 1);
    }

    public function test_empleado_puede_registrar_una_membresia_adquirida(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos el usuario administrativo que realizará el registro.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Creamos un cliente activo con autorización de tratamiento de datos.
        $cliente = Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567891',
            'telefono' => '3001234568',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 210000,
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Definimos la fecha de inicio y calculamos el vencimiento esperado.
        $fechaInicio = now()->toDateString();

        $fechaVencimiento = now()
            ->addDays(89)
            ->setTime(23, 59, 59)
            ->format('Y-m-d H:i:s');

        // Enviamos la solicitud para registrar la membresía adquirida.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => $fechaInicio,
            'valor_pagado' => 200000,
            'metodo_pago' => 'nequi',
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Membresía adquirida registrada correctamente.')
            ->assertJsonPath('membresia_adquirida.id_usuario_registro', $empleado->id_usuario)
            ->assertJsonPath('membresia_adquirida.nombre_tipo_snapshot', 'Trimestral')
            ->assertJsonPath('membresia_adquirida.duracion_dias_snapshot', 90)
            ->assertJsonPath('membresia_adquirida.fecha_vencimiento', $fechaVencimiento)
            ->assertJsonPath('membresia_adquirida.metodo_pago', 'nequi');

        // Comprobamos que la membresía adquirida fue almacenada correctamente.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => $empleado->id_usuario,
            'nombre_tipo_snapshot' => 'Trimestral',
            'duracion_dias_snapshot' => 90,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaVencimiento,
            'valor_pagado' => 200000,
            'metodo_pago' => 'nequi',
        ]);

        // Comprobamos que solamente se registró una membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 1);
    }

    public function test_usuario_no_autenticado_no_puede_registrar_una_membresia_adquirida(): void
    {
        // Enviamos la solicitud sin autenticar ningún usuario.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => 1,
            'id_tipo_membresia' => 1,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que el acceso fue rechazado por falta de autenticación.
        $response->assertUnauthorized();

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_usuario_inactivo_no_puede_registrar_una_membresia_adquirida(): void
    {
        // Creamos un empleado con su cuenta desactivada.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.inactivo',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Autenticamos al empleado inactivo.
        $this->actingAs($empleado, 'web');

        // Intentamos registrar una membresía adquirida.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => 1,
            'id_tipo_membresia' => 1,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que el acceso fue rechazado.
        $response->assertForbidden();

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_para_un_cliente_inactivo(): void
    {
        // Creamos un dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.cliente.inactivo',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente con su cuenta desactivada.
        $cliente = Cliente::create([
            'nombres' => 'Pedro',
            'apellidos' => 'Gomez',
            'documento' => '1234567892',
            'telefono' => '3001234569',
            'activo' => false,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar una membresía para el cliente inactivo.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que la validación rechaza al cliente inactivo.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_cliente']);

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_un_tipo_inactivo(): void
    {
        // Creamos un dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipo.inactivo',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente activo con autorización de tratamiento de datos.
        $cliente = Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Lopez',
            'documento' => '1234567893',
            'telefono' => '3001234570',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía desactivado.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => false,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar una membresía con el tipo inactivo.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que la validación rechaza el tipo de membresía inactivo.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_tipo_membresia']);

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_un_cliente_inexistente(): void
    {
        // Creamos un dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.cliente.inexistente',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar una membresía con un cliente inexistente.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => 999999,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que la validación rechaza el identificador inexistente.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_cliente']);

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_un_tipo_inexistente(): void
    {
        // Creamos un dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipo.inexistente',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente activo con autorización de tratamiento de datos.
        $cliente = Cliente::create([
            'nombres' => 'Maria',
            'apellidos' => 'Rodriguez',
            'documento' => '1234567894',
            'telefono' => '3001234571',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Intentamos registrar una membresía con un tipo inexistente.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => 999999,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que la validación rechaza el identificador inexistente.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['id_tipo_membresia']);

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_fecha_de_inicio_futura(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos un dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.fecha.futura',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente activo con autorización de tratamiento de datos.
        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567895',
            'telefono' => '3001234572',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Definimos una fecha de inicio posterior al día actual.
        $fechaFutura = now()->addDay()->toDateString();

        // Intentamos registrar una membresía con fecha de inicio futura.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => $fechaFutura,
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que la validación rechaza la fecha futura.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_inicio']);

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_formato_de_fecha_inicio_invalido(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.fecha.formato',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567896',
            'telefono' => '3001234573',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => '15-11-2026',
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_inicio']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_sin_fecha_inicio(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.fecha.faltante',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567897',
            'telefono' => '3001234574',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_inicio']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_valor_pagado_igual_a_cero(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.cero',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567898',
            'telefono' => '3001234575',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 0,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['valor_pagado']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_valor_pagado_negativo(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.negativo',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567899',
            'telefono' => '3001234576',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => -5000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['valor_pagado']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_valor_pagado_no_numerico(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.texto',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567900',
            'telefono' => '3001234577',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 'abc',
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['valor_pagado']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_sin_valor_pagado(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.faltante',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567901',
            'telefono' => '3001234578',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['valor_pagado']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_con_metodo_pago_no_permitido(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.metodo.invalido',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567902',
            'telefono' => '3001234579',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'tarjeta_credito',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['metodo_pago']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_sin_metodo_pago(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.metodo.faltante',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567903',
            'telefono' => '3001234580',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['metodo_pago']);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_no_se_puede_registrar_una_membresia_sin_los_datos_obligatorios(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.datos.faltantes',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'id_cliente',
                'id_tipo_membresia',
                'fecha_inicio',
                'valor_pagado',
                'metodo_pago',
            ]);

        $this->assertDatabaseCount('membresia_adquirida', 0);
    }

    public function test_los_snapshots_se_conservan_si_se_modifica_el_tipo_de_membresia(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.snapshot',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567904',
            'telefono' => '3001234581',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertCreated();

        $membresiaAdquirida = MembresiaAdquirida::firstOrFail();

        // Modificamos el tipo de membresía original.
        $tipoMembresia->update([
            'nombre' => 'Premium',
            'duracion_dias' => 60,
        ]);

        // Verificamos que el tipo original sí haya cambiado.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'nombre' => 'Premium',
            'duracion_dias' => 60,
        ]);

        // Verificamos que los snapshots mantengan sus valores originales.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_membresia_adquirida' => $membresiaAdquirida->id_membresia_adquirida,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
        ]);
    }

    public function test_se_pueden_registrar_membresias_con_bancolombia_y_otro_como_metodo_de_pago(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.metodos.validos',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567905',
            'telefono' => '3001234582',
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

        $this->actingAs($dueno, 'web');

        $datosBase = [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
        ];

        // Verificamos que Bancolombia sea aceptado.
        $responseBancolombia = $this->postJson(route('membresias-adquiridas.store'), [
            ...$datosBase,
            'metodo_pago' => 'bancolombia',
        ]);

        $responseBancolombia->assertCreated();

        // Verificamos que Otro también sea aceptado.
        $responseOtro = $this->postJson(route('membresias-adquiridas.store'), [
            ...$datosBase,
            'metodo_pago' => 'otro',
        ]);

        $responseOtro->assertCreated();

        $this->assertDatabaseCount('membresia_adquirida', 2);

        $this->assertDatabaseHas('membresia_adquirida', [
            'metodo_pago' => 'bancolombia',
        ]);

        $this->assertDatabaseHas('membresia_adquirida', [
            'metodo_pago' => 'otro',
        ]);
    }

    public function test_una_membresia_de_un_dia_vence_el_mismo_dia_a_las_235959(): void
    {
        $this->travelTo(now()->startOfDay());

        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.un.dia',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567906',
            'telefono' => '3001234583',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Pase diario',
            'duracion_dias' => 1,
            'precio' => 10000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        $fechaInicio = now()->toDateString();

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => $fechaInicio,
            'valor_pagado' => 10000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertCreated()
            ->assertJsonPath(
                'membresia_adquirida.fecha_vencimiento',
                $fechaInicio . ' 23:59:59'
            );

        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'duracion_dias_snapshot' => 1,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaInicio . ' 23:59:59',
        ]);
    }

    public function test_se_puede_registrar_una_membresia_con_valor_pagado_decimal(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.decimal',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567907',
            'telefono' => '3001234584',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75500.50,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertCreated()
            ->assertJsonPath(
                'membresia_adquirida.valor_pagado',
                '75500.50'
            );

        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'valor_pagado' => 75500.50,
            'metodo_pago' => 'efectivo',
        ]);
    }

    public function test_calcula_correctamente_el_vencimiento_al_cambiar_de_mes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20')->startOfDay());

        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.cambio.mes',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567908',
            'telefono' => '3001234585',
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

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => '2026-11-20',
            'valor_pagado' => 80000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertCreated()
            ->assertJsonPath(
                'membresia_adquirida.fecha_vencimiento',
                '2026-12-19 23:59:59'
            );

        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'fecha_inicio' => '2026-11-20',
            'fecha_vencimiento' => '2026-12-19 23:59:59',
        ]);
    }

    public function test_calcula_correctamente_el_vencimiento_al_cambiar_de_anio(): void
    {
        $this->travelTo(
            Carbon::parse('2026-12-15')->startOfDay()
        );

        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.cambio.anio',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567909',
            'telefono' => '3001234586',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Trimestral',
            'duracion_dias' => 90,
            'precio' => 200000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => '2026-12-15',
            'valor_pagado' => 200000,
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertCreated()
            ->assertJsonPath(
                'membresia_adquirida.fecha_vencimiento',
                '2027-03-14 23:59:59'
            );

        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'duracion_dias_snapshot' => 90,
            'fecha_inicio' => '2026-12-15',
            'fecha_vencimiento' => '2027-03-14 23:59:59',
        ]);
    }

    public function test_una_membresia_adquirida_se_conserva_al_desactivar_su_tipo(): void
    {
        // Creamos un dueño que realizará el registro.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipo.desactivado',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente activo.
        $cliente = Cliente::create([
            'nombres' => 'Laura',
            'apellidos' => 'Martinez',
            'documento' => '1234567999',
            'telefono' => '3001234599',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Registramos una membresía adquirida.
        $response = $this->postJson(route('membresias-adquiridas.store'), [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 75000,
            'metodo_pago' => 'efectivo',
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated();

        // Obtenemos la membresía adquirida registrada.
        $membresiaAdquirida = MembresiaAdquirida::firstOrFail();

        // Guardamos una copia de todos los datos originales de la adquisición.
        $datosOriginales = (array) DB::table('membresia_adquirida')
            ->where('id_membresia_adquirida', $membresiaAdquirida->id_membresia_adquirida)
            ->first();

        // Desactivamos el tipo de membresía mediante su Action.
        app(CambiarEstadoTipoMembresia::class)
            ->ejecutar($tipoMembresia, false);

        // Comprobamos que el tipo de membresía quedó inactivo.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => false,
        ]);

        // Consultamos nuevamente los datos de la adquisición.
        $datosPosteriores = (array) DB::table('membresia_adquirida')
            ->where('id_membresia_adquirida', $membresiaAdquirida->id_membresia_adquirida)
            ->first();

        // Comprobamos que todos los datos de la adquisición permanecen intactos.
        $this->assertSame($datosOriginales, $datosPosteriores);

        // Comprobamos que la adquisición sigue existiendo.
        $this->assertDatabaseCount('membresia_adquirida', 1);
    }
}
