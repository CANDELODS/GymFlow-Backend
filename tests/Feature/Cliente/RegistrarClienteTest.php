<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_registrar_un_cliente(): void
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

        // Enviamos la solicitud para registrar un cliente.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
        ]);

        // Comprobamos que el registro fue exitoso.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Cliente registrado correctamente.')
            ->assertJsonPath('cliente.nombres', 'Juan')
            ->assertJsonPath('cliente.apellidos', 'Perez')
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('cliente.telefono', '3001234567')
            ->assertJsonPath('cliente.activo', true)
            ->assertJsonPath('cliente.autorizacion_tratamiento', true)
            ->assertJsonMissingPath('cliente.created_at');

        // Comprobamos que la fecha de autorización fue generada por el backend.
        $this->assertNotNull(
            Cliente::where('documento', '1234567890')->value('fecha_autorizacion')
        );

        // Comprobamos que el cliente fue almacenado correctamente.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Juan',
            'apellidos' => 'Perez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
        ]);

        // Comprobamos que solamente se registró un cliente.
        $this->assertDatabaseCount('cliente', 1);

        // Comprobamos que no se registró ninguna membresía adquirida.
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_empleado_puede_registrar_un_cliente(): void
    {
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.test',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        $this->actingAs($empleado, 'web');

        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '9876543210',
            'telefono' => '3101234567',
            'autorizacion_tratamiento' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Cliente registrado correctamente.')
            ->assertJsonPath('cliente.nombres', 'Carlos')
            ->assertJsonPath('cliente.apellidos', 'Gomez')
            ->assertJsonPath('cliente.documento', '9876543210')
            ->assertJsonPath('cliente.telefono', '3101234567')
            ->assertJsonPath('cliente.activo', true)
            ->assertJsonPath('cliente.autorizacion_tratamiento', true)
            ->assertJsonMissingPath('cliente.created_at');

        $this->assertNotNull(
            Cliente::where('documento', '9876543210')->value('fecha_autorizacion')
        );

        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '9876543210',
            'telefono' => '3101234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
        ]);

        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_usuario_no_autenticado_no_puede_registrar_un_cliente(): void
    {
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_usuario_inactivo_no_puede_registrar_un_cliente(): void
    {
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'usuario.inactivo',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        $this->actingAs($usuario, 'web');

        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_no_se_puede_registrar_un_cliente_sin_los_campos_obligatorios(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.validacion',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente sin proporcionar ningún dato.
        $response = $this->postJson('/api/clientes', []);

        // Se verifica que la solicitud sea rechazada por errores de validación.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombres',
                'apellidos',
                'documento',
                'telefono',
                'autorizacion_tratamiento',
            ]);

        // Se comprueba que no se haya registrado ningún cliente ni membresía.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_no_se_puede_registrar_un_cliente_con_campos_que_superan_la_longitud_maxima(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.longitud',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se envían datos que superan la longitud máxima permitida para cada campo.
        $response = $this->postJson('/api/clientes', [
            'nombres' => str_repeat('A', 101),
            'apellidos' => str_repeat('B', 101),
            'documento' => str_repeat('1', 31),
            'telefono' => str_repeat('3', 21),
            'autorizacion_tratamiento' => true,
        ]);

        // Se verifica que Laravel rechace los campos que superan sus límites.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombres',
                'apellidos',
                'documento',
                'telefono',
            ]);

        // Se comprueba que no se haya registrado ningún cliente ni membresía.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_no_se_puede_registrar_un_cliente_con_tipos_de_datos_incorrectos(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipos',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se envían arreglos en lugar de cadenas de texto en los campos correspondientes.
        $response = $this->postJson('/api/clientes', [
            'nombres' => ['Carlos'],
            'apellidos' => ['Gomez'],
            'documento' => ['1234567890'],
            'telefono' => ['3001234567'],
            'autorizacion_tratamiento' => true,
        ]);

        // Se verifica que los campos sean rechazados por no cumplir la regla string.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombres',
                'apellidos',
                'documento',
                'telefono',
            ]);

        // Se comprueba que no se haya registrado ningún cliente ni membresía.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_no_se_puede_registrar_un_cliente_con_un_documento_ya_existente(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.documento',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se registra previamente un cliente con un documento determinado.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Se intenta registrar otro cliente utilizando el mismo documento.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Andres',
            'apellidos' => 'Rodriguez',
            'documento' => '1234567890',
            'telefono' => '3109876543',
            'autorizacion_tratamiento' => true,
        ]);

        // Se verifica que la API rechace el documento duplicado.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'documento',
            ]);

        // Se comprueba que el cliente original permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'documento' => '1234567890',
        ]);

        // Se comprueba que no se haya creado el segundo cliente.
        $this->assertDatabaseMissing('cliente', [
            'nombres' => 'Andres',
            'documento' => '1234567890',
        ]);

        // Se verifica que solamente exista el cliente registrado inicialmente.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_no_se_puede_registrar_un_cliente_sin_autorizar_el_tratamiento_de_datos_personales(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.autorizacion',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente sin aceptar el tratamiento de sus datos personales.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => false,
        ]);

        // Se verifica que la API rechace la solicitud por falta de autorización.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'autorizacion_tratamiento',
            ]);

        // Se comprueba que no se haya registrado ningún cliente ni membresía.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_no_se_puede_registrar_un_cliente_con_un_valor_invalido_en_registrar_membresia(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.registro.membresia',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente enviando un valor no booleano
        // en el campo opcional registrar_membresia.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => 'invalido',
        ]);

        // Se verifica que la API rechace el valor enviado.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'registrar_membresia',
            ]);

        // Se comprueba que no se haya registrado ningún cliente ni membresía.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_puede_registrar_un_cliente_con_su_primera_membresia_en_una_sola_solicitud(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.cliente.membresia',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para asociarlo al nuevo cliente.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se envían los datos del cliente y de su primera membresía en una sola solicitud.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 80000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que la solicitud haya creado correctamente el cliente.
        $response->assertCreated()
            ->assertJsonPath('cliente.nombres', 'Carlos')
            ->assertJsonPath('cliente.apellidos', 'Gomez')
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('cliente.telefono', '3001234567');

        // Se comprueba que la respuesta incluya la membresía adquirida.
        $response->assertJsonStructure([
            'cliente',
            'membresia_adquirida',
        ]);

        // Se recupera el cliente creado para verificar la relación con su membresía.
        $cliente = Cliente::where('documento', '1234567890')->first();

        $this->assertNotNull($cliente);

        // Se comprueba que el cliente haya quedado registrado correctamente.
        $this->assertDatabaseHas('cliente', [
            'id_cliente' => $cliente->id_cliente,
            'documento' => '1234567890',
            'activo' => true,
            'autorizacion_tratamiento' => true,
        ]);

        // Se comprueba que la membresía pertenezca al cliente recién creado.
        $this->assertDatabaseHas('membresia_adquirida', [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'valor_pagado' => 80000,
            'metodo_pago' => 'efectivo',
        ]);

        // Se verifica que exista exactamente un cliente y una membresía adquirida.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 1);
    }
    public function test_se_registra_el_cliente_aunque_los_datos_de_la_membresia_sean_invalidos(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.membresia.invalida',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con datos de membresía incompletos.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [],
        ]);

        // Se verifica que el cliente se haya creado correctamente,
        // aunque los datos de la membresía no hayan superado la validación.
        $response->assertCreated()
            ->assertJsonPath('cliente.nombres', 'Carlos')
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonStructure([
                'errores' => [
                    'id_tipo_membresia',
                    'fecha_inicio',
                    'valor_pagado',
                    'metodo_pago',
                ],
            ]);

        // Se comprueba que el cliente permanezca registrado en la base de datos.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
        ]);

        // Se comprueba que no se haya registrado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_puede_registrar_un_cliente_sin_registrar_una_membresia(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.sin.membresia',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se envían los datos del cliente indicando que no se desea registrar una membresía.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => false,
        ]);

        // Se verifica que el cliente se haya registrado correctamente.
        $response->assertCreated()
            ->assertJsonPath('mensaje', 'Cliente registrado correctamente.')
            ->assertJsonPath('cliente.nombres', 'Carlos')
            ->assertJsonPath('cliente.documento', '1234567890');

        // Se comprueba que el cliente exista y conserve su estado activo.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
        ]);

        // Se verifica que únicamente se haya creado el cliente, sin membresías asociadas.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_tipo_de_membresia_no_exista(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipo.inexistente',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con un tipo de membresía inexistente.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => 9999,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 80000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se haya registrado,
        // pero que la membresía no haya superado la validación.
        $response->assertCreated()
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'id_tipo_membresia',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'documento' => '1234567890',
            'activo' => true,
        ]);

        // Se verifica que no se haya creado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_tipo_de_membresia_se_encuentre_inactivo(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipo.inactivo',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía que existe, pero se encuentra inactivo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => false,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente utilizando el tipo de membresía inactivo.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 80000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se haya registrado,
        // pero que la membresía no haya superado la validación.
        $response->assertCreated()
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'id_tipo_membresia',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'documento' => '1234567890',
            'activo' => true,
        ]);

        // Se comprueba que el tipo de membresía conserve su estado inactivo.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => false,
        ]);

        // Se verifica que no se haya registrado ninguna membresía adquirida.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_la_fecha_de_inicio_de_la_membresia_sea_futura(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.fecha.futura',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con una membresía cuya fecha de inicio es futura.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->addDay()->format('Y-m-d'),
                'valor_pagado' => 80000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se haya registrado,
        // pero que la membresía haya sido rechazada por su fecha futura.
        $response->assertCreated()
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'fecha_inicio',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'documento' => '1234567890',
            'activo' => true,
        ]);

        // Se verifica que no se haya registrado ninguna membresía adquirida.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_la_fecha_de_inicio_de_la_membresia_tenga_un_formato_invalido(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.fecha.formato',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con una fecha de inicio en formato incorrecto.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => '17-11-2026',
                'valor_pagado' => 80000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se haya registrado,
        // pero que la membresía haya sido rechazada por el formato de fecha.
        $response->assertCreated()
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'fecha_inicio',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'documento' => '1234567890',
            'activo' => true,
        ]);

        // Se verifica que no se haya registrado ninguna membresía adquirida.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_valor_pagado_de_la_membresia_sea_cero(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.cero',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con una membresía cuyo valor pagado es cero.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 0,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se haya registrado,
        // pero que la membresía haya sido rechazada por su valor pagado.
        $response->assertCreated()
            ->assertJsonPath('cliente.documento', '1234567890')
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'valor_pagado',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'nombres' => 'Carlos',
            'documento' => '1234567890',
            'activo' => true,
        ]);

        // Se verifica que no se haya registrado ninguna membresía adquirida.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_id_tipo_membresia_no_sea_entero(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.tipo.no.entero',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se crea un tipo de membresía activo para realizar la prueba.
        TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        // Se intenta registrar un cliente enviando un identificador no entero.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => 'invalido',
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 80000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se registre, pero la membresía sea rechazada.
        $response->assertCreated()
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'id_tipo_membresia',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'documento' => '1234567890',
        ]);

        // Se verifica que no se haya creado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_valor_pagado_de_la_membresia_sea_negativo(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.negativo',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con un valor pagado negativo.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => -50000,
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se registre, pero la membresía sea rechazada.
        $response->assertCreated()
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'valor_pagado',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'documento' => '1234567890',
        ]);

        // Se verifica que no se haya creado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_valor_pagado_de_la_membresia_no_sea_numerico(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.valor.no.numerico',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con un valor pagado no numérico.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 'no es un numero',
                'metodo_pago' => 'efectivo',
            ],
        ]);

        // Se verifica que el cliente se registre, pero la membresía sea rechazada.
        $response->assertCreated()
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'valor_pagado',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'documento' => '1234567890',
        ]);

        // Se verifica que no se haya creado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_el_metodo_de_pago_de_la_membresia_no_sea_valido(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.metodo.invalido',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Se crea un tipo de membresía activo para realizar la prueba.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual',
            'duracion_dias' => 30,
            'precio' => 80000,
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente con un método de pago no permitido.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => [
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'fecha_inicio' => now()->format('Y-m-d'),
                'valor_pagado' => 80000,
                'metodo_pago' => 'tarjeta',
            ],
        ]);

        // Se verifica que el cliente se registre, pero la membresía sea rechazada.
        $response->assertCreated()
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonValidationErrors([
                'metodo_pago',
            ], 'errores');

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'documento' => '1234567890',
        ]);

        // Se verifica que no se haya creado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_registra_el_cliente_aunque_los_datos_de_la_membresia_no_sean_un_arreglo(): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.membresia.no.arreglo',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Se intenta registrar un cliente enviando una cadena en lugar
        // de un arreglo con los datos de la membresía.
        $response = $this->postJson('/api/clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'autorizacion_tratamiento' => true,
            'registrar_membresia' => true,
            'membresia' => 'datos invalidos',
        ]);

        // Se verifica que el cliente se registre, pero la membresía sea rechazada
        // porque no se recibieron los datos en el formato esperado.
        $response->assertCreated()
            ->assertJsonPath('membresia_registrada', false)
            ->assertJsonStructure([
                'errores' => [
                    'id_tipo_membresia',
                    'fecha_inicio',
                    'valor_pagado',
                    'metodo_pago',
                ],
            ]);

        // Se comprueba que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'documento' => '1234567890',
        ]);

        // Se verifica que no se haya creado ninguna membresía.
        $this->assertDatabaseCount('cliente', 1);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
}
