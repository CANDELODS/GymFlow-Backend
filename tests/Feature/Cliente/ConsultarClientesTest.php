<?php

namespace Tests\Feature\Cliente;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsultarClientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_consultar_clientes_registrados(): void
    {
        // Creamos el usuario administrativo que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.consulta',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Registramos un cliente para disponer de información que consultar.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Enviamos la solicitud para consultar los clientes registrados.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que la consulta haya sido exitosa.
        $response->assertOk()
            ->assertJsonPath('data.0.nombres', 'Carlos')
            ->assertJsonPath('data.0.apellidos', 'Gomez')
            ->assertJsonPath('data.0.documento', '1234567890')
            ->assertJsonPath('data.0.telefono', '3001234567')
            ->assertJsonPath('data.0.activo', true)
            ->assertJsonPath('data.0.estado_membresia', 'sin_membresia')
            ->assertJsonPath('data.0.membresia', null);

        // Comprobamos que la respuesta incluya la estructura de paginación.
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);

        // Verificamos que el cliente permanezca registrado.
        $this->assertDatabaseHas('cliente', [
            'documento' => '1234567890',
        ]);

        // Comprobamos que solamente exista un cliente registrado.
        $this->assertDatabaseCount('cliente', 1);
    }
    public function test_empleado_puede_consultar_clientes_registrados(): void
    {
        // Creamos el usuario administrativo que realizará la consulta.
        $empleado = UsuarioAdministrativo::create([
            'usuario' => 'empleado.consulta',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => true,
        ]);

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Registramos un cliente para disponer de información que consultar.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Enviamos la solicitud para consultar los clientes registrados.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que el empleado pueda consultar los clientes correctamente.
        $response->assertOk()
            ->assertJsonPath('data.0.nombres', 'Carlos')
            ->assertJsonPath('data.0.documento', '1234567890');

        // Comprobamos que el cliente permanezca registrado.
        $this->assertDatabaseCount('cliente', 1);
    }
    public function test_usuario_no_autenticado_no_puede_consultar_clientes(): void
    {
        // Enviamos la solicitud sin autenticar ningún usuario.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que el acceso sea rechazado por falta de autenticación.
        $response->assertUnauthorized();

        // Verificamos que no existan registros de clientes ni membresías.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_usuario_inactivo_no_puede_consultar_clientes(): void
    {
        // Creamos un usuario administrativo que se encuentra inactivo.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'usuario.inactivo.consulta',
            'password' => 'Password*123',
            'rol' => 'empleado',
            'activo' => false,
        ]);

        // Autenticamos al usuario inactivo.
        $this->actingAs($usuario, 'web');

        // Enviamos la solicitud para consultar los clientes.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que el acceso sea rechazado.
        $response->assertForbidden();

        // Verificamos que no existan registros de clientes ni membresías.
        $this->assertDatabaseCount('cliente', 0);
        $this->assertDatabaseCount('membresia_adquirida', 0);
    }
    public function test_se_pueden_buscar_clientes_por_nombres(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.busqueda.nombres',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos los clientes que utilizaremos para comprobar la búsqueda.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Martinez',
            'documento' => '9876543210',
            'telefono' => '3109876543',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Buscamos el cliente por su nombre.
        $response = $this->getJson('/api/clientes?buscar=Carlos');

        // Comprobamos que únicamente se encuentre el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombres', 'Carlos')
            ->assertJsonPath('data.0.documento', '1234567890');
    }
    public function test_se_pueden_buscar_clientes_por_apellidos(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.busqueda.apellidos',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos los clientes que utilizaremos para comprobar la búsqueda.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Martinez',
            'documento' => '9876543210',
            'telefono' => '3109876543',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Buscamos el cliente por su apellido.
        $response = $this->getJson('/api/clientes?buscar=Gomez');

        // Comprobamos que únicamente se encuentre el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.apellidos', 'Gomez')
            ->assertJsonPath('data.0.documento', '1234567890');
    }
    public function test_se_pueden_buscar_clientes_por_documento(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.busqueda.documento',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos los clientes que utilizaremos para comprobar la búsqueda.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Martinez',
            'documento' => '9876543210',
            'telefono' => '3109876543',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Buscamos el cliente por su documento.
        $response = $this->getJson('/api/clientes?buscar=1234567890');

        // Comprobamos que únicamente se encuentre el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567890')
            ->assertJsonPath('data.0.nombres', 'Carlos');
    }

    public function test_se_pueden_buscar_clientes_por_telefono(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.busqueda.telefono',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos los clientes que utilizaremos para comprobar la búsqueda.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Martinez',
            'documento' => '9876543210',
            'telefono' => '3109876543',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Buscamos el cliente por su teléfono.
        $response = $this->getJson('/api/clientes?buscar=3001234567');

        // Comprobamos que únicamente se encuentre el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.telefono', '3001234567')
            ->assertJsonPath('data.0.nombres', 'Carlos');
    }
    public function test_la_busqueda_de_clientes_permite_coincidencias_parciales(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.busqueda.parcial',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos un cliente para comprobar la búsqueda parcial.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Buscamos utilizando solamente una parte del nombre.
        $response = $this->getJson('/api/clientes?buscar=arl');

        // Comprobamos que la búsqueda encuentre al cliente.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombres', 'Carlos');
    }
    public function test_la_busqueda_de_clientes_no_devuelve_resultados_inexistentes(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.busqueda.inexistente',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos un cliente que no coincidirá con el término de búsqueda.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Buscamos un cliente que no se encuentra registrado.
        $response = $this->getJson('/api/clientes?buscar=Juan');

        // Comprobamos que la consulta sea exitosa, pero no devuelva resultados.
        $response->assertOk()
            ->assertJsonCount(0, 'data');

        // Verificamos que el cliente original permanezca registrado.
        $this->assertDatabaseCount('cliente', 1);
    }
    public function test_se_pueden_consultar_unicamente_clientes_activos(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.filtro.activos',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos un cliente activo.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Registramos un cliente inactivo.
        Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Martinez',
            'documento' => '9876543210',
            'telefono' => '3109876543',
            'activo' => false,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Consultamos únicamente los clientes activos.
        $response = $this->getJson('/api/clientes?activo=1');

        // Comprobamos que solamente se devuelva el cliente activo.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombres', 'Carlos')
            ->assertJsonPath('data.0.activo', true);

        // Verificamos que ambos clientes continúen registrados.
        $this->assertDatabaseCount('cliente', 2);
    }
    public function test_se_pueden_consultar_unicamente_clientes_inactivos(): void
    {
        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = UsuarioAdministrativo::create([
            'usuario' => 'dueno.filtro.inactivos',
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');

        // Registramos un cliente activo.
        Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Registramos un cliente inactivo.
        Cliente::create([
            'nombres' => 'Andres',
            'apellidos' => 'Martinez',
            'documento' => '9876543210',
            'telefono' => '3109876543',
            'activo' => false,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Consultamos únicamente los clientes inactivos.
        $response = $this->getJson('/api/clientes?activo=0');

        // Comprobamos que solamente se devuelva el cliente inactivo.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombres', 'Andres')
            ->assertJsonPath('data.0.activo', false);

        // Verificamos que ambos clientes continúen registrados.
        $this->assertDatabaseCount('cliente', 2);
    }
    /**
     * Crea y autentica un dueño para realizar las consultas.
     */
    private function autenticarDueno(string $usuario): void
    {
        $dueno = UsuarioAdministrativo::create([
            'usuario' => $usuario,
            'password' => 'Password*123',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        $this->actingAs($dueno, 'web');
    }

    /**
     * Crea un cliente activo para las pruebas de consulta.
     */
    private function crearCliente(string $documento): Cliente
    {
        return Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => $documento,
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);
    }

    /**
     * Registra una membresía adquirida para un cliente.
     */
    private function crearMembresiaAdquirida(
        Cliente $cliente,
        UsuarioAdministrativo $dueno,
        string $fechaVencimiento
    ): MembresiaAdquirida {
        // Definimos la duración de la membresía.
        $duracionDias = 30;

        // Calculamos la fecha de inicio según el vencimiento y la duración.
        $fechaInicio = Carbon::parse($fechaVencimiento)
            ->subDays($duracionDias - 1)
            ->toDateString();

        // Creamos un tipo de membresía con un nombre único.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Mensual ' . $cliente->documento . ' ' . (TipoMembresia::count() + 1),
            'duracion_dias' => $duracionDias,
            'precio' => 80000,
            'activo' => true,
        ]);

        return MembresiaAdquirida::create([
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => $dueno->id_usuario,
            'nombre_tipo_snapshot' => $tipoMembresia->nombre,
            'duracion_dias_snapshot' => $duracionDias,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => \Illuminate\Support\Carbon::parse($fechaVencimiento)
                ->setTime(23, 59, 59)
                ->format('Y-m-d H:i:s'),
            'valor_pagado' => 80000,
            'metodo_pago' => 'efectivo',
        ]);
    }
    public function test_se_pueden_consultar_clientes_con_membresias_activas(): void
    {
        // Fijamos la fecha actual para controlar los vencimientos.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.membresias.activas');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.membresias.activas'
        )->firstOrFail();

        // Creamos un cliente con una membresía activa.
        $clienteActivo = $this->crearCliente('1234567801');

        $this->crearMembresiaAdquirida(
            $clienteActivo,
            $dueno,
            now()->addDays(11)->toDateString()
        );

        // Creamos otro cliente con una membresía por vencer.
        $clientePorVencer = $this->crearCliente('1234567802');

        $this->crearMembresiaAdquirida(
            $clientePorVencer,
            $dueno,
            now()->addDays(5)->toDateString()
        );

        // Consultamos únicamente los clientes con membresías activas.
        $response = $this->getJson('/api/clientes?estado_membresia=activa');

        // Comprobamos que solamente se devuelva el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567801')
            ->assertJsonPath('data.0.estado_membresia', 'activa');
    }
    public function test_se_pueden_consultar_clientes_con_membresias_por_vencer(): void
    {
        // Fijamos la fecha actual para controlar los vencimientos.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.membresias.por.vencer');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.membresias.por.vencer'
        )->firstOrFail();

        // Creamos un cliente con una membresía que vence dentro de cinco días.
        $clientePorVencer = $this->crearCliente('1234567803');

        $this->crearMembresiaAdquirida(
            $clientePorVencer,
            $dueno,
            now()->addDays(5)->toDateString()
        );

        // Creamos un cliente con una membresía activa.
        $clienteActivo = $this->crearCliente('1234567804');

        $this->crearMembresiaAdquirida(
            $clienteActivo,
            $dueno,
            now()->addDays(15)->toDateString()
        );

        // Consultamos únicamente los clientes con membresías por vencer.
        $response = $this->getJson('/api/clientes?estado_membresia=por_vencer');

        // Comprobamos que solamente se devuelva el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567803')
            ->assertJsonPath('data.0.estado_membresia', 'por_vencer');
    }
    public function test_se_pueden_consultar_clientes_con_membresias_vencidas(): void
    {
        // Fijamos la fecha actual para controlar los vencimientos.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.membresias.vencidas');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.membresias.vencidas'
        )->firstOrFail();

        // Creamos un cliente con una membresía vencida.
        $clienteVencido = $this->crearCliente('1234567805');

        $this->crearMembresiaAdquirida(
            $clienteVencido,
            $dueno,
            now()->subDay()->toDateString()
        );

        // Creamos un cliente con una membresía activa.
        $clienteActivo = $this->crearCliente('1234567806');

        $this->crearMembresiaAdquirida(
            $clienteActivo,
            $dueno,
            now()->addDays(15)->toDateString()
        );

        // Consultamos únicamente los clientes con membresías vencidas.
        $response = $this->getJson('/api/clientes?estado_membresia=vencida');

        // Comprobamos que solamente se devuelva el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567805')
            ->assertJsonPath('data.0.estado_membresia', 'vencida');
    }
    public function test_se_pueden_consultar_clientes_sin_membresias(): void
    {
        // Fijamos la fecha actual para controlar los vencimientos.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.sin.membresias');

        // Creamos un cliente sin adquisiciones registradas.
        $clienteSinMembresia = $this->crearCliente('1234567807');

        // Creamos otro cliente con una membresía adquirida.
        $clienteConMembresia = $this->crearCliente('1234567808');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.sin.membresias'
        )->firstOrFail();

        $this->crearMembresiaAdquirida(
            $clienteConMembresia,
            $dueno,
            now()->addDays(15)->toDateString()
        );

        // Consultamos únicamente los clientes sin membresías.
        $response = $this->getJson('/api/clientes?estado_membresia=sin_membresia');

        // Comprobamos que solamente se devuelva el cliente sin adquisiciones.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567807')
            ->assertJsonPath('data.0.estado_membresia', 'sin_membresia')
            ->assertJsonPath('data.0.membresia', null);
    }
    public function test_el_estado_de_membresia_se_calcula_segun_la_fecha_de_vencimiento(): void
    {
        // Fijamos la fecha actual para comprobar los límites de clasificación.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.limites.vencimiento');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.limites.vencimiento'
        )->firstOrFail();

        // Definimos los vencimientos que utilizaremos en la prueba.
        $casos = [
            [
                'documento' => '1234567809',
                'vencimiento' => now()->subDay()->toDateString(),
                'estado' => 'vencida',
            ],
            [
                'documento' => '1234567810',
                'vencimiento' => now()->toDateString(),
                'estado' => 'por_vencer',
            ],
            [
                'documento' => '1234567811',
                'vencimiento' => now()->addDays(10)->toDateString(),
                'estado' => 'por_vencer',
            ],
            [
                'documento' => '1234567812',
                'vencimiento' => now()->addDays(11)->toDateString(),
                'estado' => 'activa',
            ],
        ];

        // Registramos un cliente y una membresía para cada caso.
        foreach ($casos as $caso) {
            $cliente = $this->crearCliente($caso['documento']);

            $this->crearMembresiaAdquirida(
                $cliente,
                $dueno,
                $caso['vencimiento']
            );
        }

        // Consultamos todos los clientes.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que todos los clientes hayan sido devueltos.
        $response->assertOk()
            ->assertJsonCount(4, 'data');

        // Comprobamos que cada cliente tenga el estado correspondiente.
        foreach ($casos as $caso) {
            $response->assertJsonFragment([
                'documento' => $caso['documento'],
                'estado_membresia' => $caso['estado'],
            ]);
        }
    }
    public function test_se_consulta_el_estado_de_la_ultima_membresia_adquirida(): void
    {
        // Fijamos la fecha actual para controlar los vencimientos.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.ultima.membresia');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.ultima.membresia'
        )->firstOrFail();

        // Creamos un cliente que tendrá dos membresías adquiridas.
        $cliente = $this->crearCliente('1234567813');

        // Registramos la primera adquisición, que se encuentra vencida.
        $primeraMembresia = $this->crearMembresiaAdquirida(
            $cliente,
            $dueno,
            now()->subDays(30)->toDateString()
        );

        // Registramos una segunda adquisición, que se encuentra activa.
        $ultimaMembresia = $this->crearMembresiaAdquirida(
            $cliente,
            $dueno,
            now()->addDays(30)->toDateString()
        );

        // Consultamos los clientes registrados.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que se devuelva la última adquisición.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.membresia.id_membresia_adquirida',
                $ultimaMembresia->id_membresia_adquirida
            )
            ->assertJsonPath('data.0.estado_membresia', 'activa');

        // Verificamos que las dos adquisiciones permanezcan registradas.
        $this->assertDatabaseCount('membresia_adquirida', 2);
    }
    public function test_los_clientes_se_paginan_de_cinco_en_cinco(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.paginacion');

        // Registramos siete clientes para comprobar la paginación.
        for ($i = 1; $i <= 7; $i++) {
            $this->crearCliente('123456790' . $i);
        }

        // Consultamos los clientes registrados.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que la primera página contenga cinco clientes.
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.last_page', 2);
    }
    public function test_se_pueden_consultar_los_clientes_de_la_segunda_pagina(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.segunda.pagina');

        // Registramos siete clientes para generar dos páginas.
        for ($i = 1; $i <= 7; $i++) {
            $this->crearCliente('123456791' . $i);
        }

        // Consultamos la segunda página.
        $response = $this->getJson('/api/clientes?page=2');

        // Comprobamos que se devuelvan los dos clientes restantes.
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.last_page', 2);
    }
    public function test_la_paginacion_conserva_los_filtros_aplicados(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.filtros.paginacion');

        // Registramos seis clientes activos.
        for ($i = 1; $i <= 6; $i++) {
            $this->crearCliente('123456792' . $i);
        }

        // Consultamos la primera página aplicando los filtros.
        $response = $this->getJson(
            '/api/clientes?buscar=123456792&activo=1'
        );

        // Comprobamos que la consulta genere dos páginas.
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.total', 6)
            ->assertJsonPath('meta.last_page', 2);

        // Obtenemos el enlace para consultar la siguiente página.
        $siguientePagina = $response->json('links.next');

        // Comprobamos que el enlace conserve los filtros aplicados.
        $this->assertStringContainsString('buscar=', $siguientePagina);
        $this->assertStringContainsString('activo=1', $siguientePagina);
        $this->assertStringContainsString('page=2', $siguientePagina);
    }
    public function test_no_se_puede_buscar_clientes_con_un_termino_que_no_sea_texto(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.buscar.invalido');

        // Intentamos realizar una búsqueda con un arreglo.
        $response = $this->getJson('/api/clientes?buscar[]=carlos');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors('buscar');
    }
    public function test_no_se_pueden_realizar_busquedas_con_mas_de_cien_caracteres(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.buscar.extensa');

        // Generamos un término de búsqueda de 101 caracteres.
        $termino = str_repeat('a', 101);

        // Intentamos realizar la búsqueda.
        $response = $this->getJson('/api/clientes?' . http_build_query([
            'buscar' => $termino,
        ]));

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors('buscar');
    }
    public function test_no_se_puede_filtrar_por_un_estado_de_cliente_invalido(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.estado.cliente.invalido');

        // Intentamos filtrar utilizando un valor no booleano.
        $response = $this->getJson('/api/clientes?activo=habilitado');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors('activo');
    }
    public function test_no_se_puede_filtrar_por_un_estado_de_membresia_invalido(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.estado.membresia.invalido');

        // Intentamos filtrar utilizando un estado no permitido.
        $response = $this->getJson('/api/clientes?estado_membresia=pendiente');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors('estado_membresia');
    }
    public function test_no_se_puede_consultar_una_pagina_menor_que_uno(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.pagina.invalida');

        // Intentamos consultar una página con valor cero.
        $response = $this->getJson('/api/clientes?page=0');

        // Comprobamos que la validación rechace el parámetro.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors('page');
    }
    public function test_se_pueden_combinar_busqueda_y_estado_del_cliente(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.busqueda.estado');

        // Creamos un cliente activo.
        $clienteActivo = $this->crearCliente('1234567931');

        // Creamos un cliente inactivo.
        $clienteInactivo = $this->crearCliente('1234567932');
        $clienteInactivo->update(['activo' => false]);

        // Buscamos por documento y filtramos clientes activos.
        $response = $this->getJson('/api/clientes?buscar=123456793&activo=1');

        // Comprobamos que solamente se devuelva el cliente activo.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567931')
            ->assertJsonPath('data.0.activo', true);
    }
    public function test_se_pueden_combinar_estado_del_cliente_y_estado_de_membresia(): void
    {
        // Fijamos la fecha actual para controlar los vencimientos.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.filtros.combinados');

        $dueno = UsuarioAdministrativo::where(
            'usuario',
            'dueno.filtros.combinados'
        )->firstOrFail();

        // Creamos un cliente activo con una membresía activa.
        $clienteActivo = $this->crearCliente('1234567933');

        $this->crearMembresiaAdquirida(
            $clienteActivo,
            $dueno,
            now()->addDays(15)->toDateString()
        );

        // Creamos un cliente inactivo con una membresía activa.
        $clienteInactivo = $this->crearCliente('1234567934');
        $clienteInactivo->update(['activo' => false]);

        $this->crearMembresiaAdquirida(
            $clienteInactivo,
            $dueno,
            now()->addDays(15)->toDateString()
        );

        // Consultamos clientes activos con membresías activas.
        $response = $this->getJson(
            '/api/clientes?activo=1&estado_membresia=activa'
        );

        // Comprobamos que solamente se devuelva el cliente esperado.
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.documento', '1234567933')
            ->assertJsonPath('data.0.activo', true)
            ->assertJsonPath('data.0.estado_membresia', 'activa');
    }
    public function test_una_busqueda_sin_resultados_devuelve_una_coleccion_vacia(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.busqueda.sin.resultados');

        // Registramos un cliente.
        $this->crearCliente('1234567935');

        // Buscamos un documento que no existe.
        $response = $this->getJson('/api/clientes?buscar=9999999999');

        // Comprobamos que la respuesta no contenga clientes.
        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.current_page', 1);
    }
    public function test_se_puede_consultar_cuando_no_existen_clientes_registrados(): void
    {
        // Creamos y autenticamos al dueño.
        $this->autenticarDueno('dueno.sin.clientes');

        // Consultamos sin registrar ningún cliente.
        $response = $this->getJson('/api/clientes');

        // Comprobamos que se devuelva una colección vacía con paginación.
        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1);
    }
}
