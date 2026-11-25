<?php

namespace Tests\Feature\MembresiaAdquirida;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultarMembresiasClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_dueno_puede_consultar_las_membresias_de_un_cliente(): void
    {
        // Creamos el dueño que realizará la consulta.
        $dueno = $this->crearUsuario('dueno', 'dueno.consulta');

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Creamos una membresía vigente.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->toDateString()
        );

        // Autenticamos al dueño.
        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que la consulta sea exitosa.
        $response->assertOk()
            ->assertJsonPath(
                'mensaje',
                'Información de membresías consultada correctamente.'
            )
            ->assertJsonPath('membresia_vigente.nombre_tipo_snapshot', 'Mensual')
            ->assertJsonPath('membresia_vigente.estado', 'activa')
            ->assertJsonPath(
                'membresia_vigente.usuario_registro.id_usuario',
                $dueno->id_usuario
            )
            ->assertJsonCount(0, 'historial.data');

        // Comprobamos que el historial conserve su estructura de paginación.
        $response->assertJsonStructure([
            'mensaje',
            'membresia_vigente',
            'historial' => [
                'data',
                'links',
                'meta',
            ],
        ]);
    }

    public function test_empleado_puede_consultar_las_membresias_de_un_cliente(): void
    {
        // Creamos el empleado que realizará la consulta.
        $empleado = $this->crearUsuario('empleado', 'empleado.consulta');

        // Creamos un cliente sin membresías.
        $cliente = $this->crearCliente();

        // Autenticamos al empleado.
        $this->actingAs($empleado, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que el empleado pueda realizar la consulta.
        $response->assertOk()
            ->assertJsonPath('membresia_vigente', null)
            ->assertJsonCount(0, 'historial.data');
    }

    public function test_usuario_no_autenticado_no_puede_consultar_membresias(): void
    {
        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Intentamos consultar sus membresías sin autenticación.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que el acceso sea rechazado.
        $response->assertUnauthorized();
    }

    public function test_usuario_inactivo_no_puede_consultar_membresias(): void
    {
        // Creamos un empleado con su cuenta desactivada.
        $empleado = $this->crearUsuario(
            'empleado',
            'empleado.inactivo',
            false
        );

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Autenticamos al empleado inactivo.
        $this->actingAs($empleado, 'web');

        // Intentamos consultar las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que el acceso sea rechazado.
        $response->assertForbidden();
    }

    public function test_no_se_pueden_consultar_membresias_de_un_cliente_inexistente(): void
    {
        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario('dueno', 'dueno.inexistente');

        $this->actingAs($dueno, 'web');

        // Intentamos consultar un cliente inexistente.
        $response = $this->getJson(
            route('cliente.membresias.index', 999999)
        );

        // Comprobamos que no se encuentre el cliente.
        $response->assertNotFound();
    }

    public function test_se_pueden_consultar_membresias_de_un_cliente_inactivo(): void
    {
        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario(
            'dueno',
            'dueno.cliente.inactivo'
        );

        // Creamos un cliente deshabilitado.
        $cliente = $this->crearCliente(false);

        // Registramos una adquisición vencida.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(39)->toDateString()
        );

        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente inactivo.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que la consulta sea exitosa y conserve el historial.
        $response->assertOk()
            ->assertJsonPath('membresia_vigente', null)
            ->assertJsonCount(1, 'historial.data')
            ->assertJsonPath('historial.data.0.estado', 'vencida');
    }

    public function test_cliente_sin_membresias_adquiridas_recibe_historial_vacio(): void
    {
        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario(
            'dueno',
            'dueno.sin.membresias'
        );

        // Creamos un cliente sin adquisiciones.
        $cliente = $this->crearCliente();

        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que no exista membresía vigente ni historial.
        $response->assertOk()
            ->assertJsonPath('membresia_vigente', null)
            ->assertJsonCount(0, 'historial.data')
            ->assertJsonPath('historial.meta.total', 0);
    }

    public function test_membresia_vigente_se_muestra_separada_del_historial(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario('dueno', 'dueno.historial');

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Registramos una adquisición anterior ya vencida.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(60)->toDateString()
        );

        // Registramos la adquisición vigente.
        $vigente = $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->toDateString()
        );

        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que la membresía vigente aparezca separadamente.
        $response->assertOk()
            ->assertJsonPath(
                'membresia_vigente.id_membresia_adquirida',
                $vigente->id_membresia_adquirida
            )
            ->assertJsonPath('membresia_vigente.estado', 'activa')
            ->assertJsonCount(1, 'historial.data')
            ->assertJsonPath('historial.data.0.estado', 'vencida');

        // Comprobamos que la membresía vigente no se repita en el historial.
        $this->assertNotContains(
            $vigente->id_membresia_adquirida,
            collect($response->json('historial.data'))
                ->pluck('id_membresia_adquirida')
                ->all()
        );
    }

    public function test_se_calcula_correctamente_el_estado_de_las_membresias(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario('dueno', 'dueno.estados');

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Registramos una adquisición vencida.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(30)->toDateString()
        );

        // Registramos una adquisición por vencer.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(24)->toDateString()
        );

        // Registramos una adquisición activa.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->toDateString()
        );

        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que la membresía vigente tenga el estado esperado.
        $response->assertOk()
            ->assertJsonPath('membresia_vigente.estado', 'activa')
            ->assertJsonCount(2, 'historial.data');

        // Comprobamos los estados del historial.
        $estados = collect($response->json('historial.data'))
            ->pluck('estado')
            ->all();

        $this->assertContains('vencida', $estados);
        $this->assertContains('por_vencer', $estados);
    }

    public function test_se_conserva_la_informacion_del_usuario_que_registro_la_adquisicion(): void
    {
        // Creamos un usuario administrativo que registrará la adquisición.
        $usuarioRegistro = $this->crearUsuario(
            'empleado',
            'empleado.registro',
            false
        );

        // Creamos y autenticamos al dueño que realizará la consulta.
        $dueno = $this->crearUsuario(
            'dueno',
            'dueno.consulta.usuario'
        );

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Registramos una adquisición con el usuario deshabilitado.
        $this->crearAdquisicion(
            $cliente,
            $usuarioRegistro,
            now()->subDays(39)->toDateString()
        );

        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que se conserve la información del usuario responsable.
        $response->assertOk()
            ->assertJsonPath(
                'historial.data.0.usuario_registro.id_usuario',
                $usuarioRegistro->id_usuario
            )
            ->assertJsonPath(
                'historial.data.0.usuario_registro.usuario',
                'empleado.registro'
            )
            ->assertJsonPath(
                'historial.data.0.usuario_registro.activo',
                false
            );
    }

    public function test_el_historial_se_ordena_desde_la_adquisicion_mas_reciente(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario('dueno', 'dueno.orden');

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Registramos tres adquisiciones vencidas en fechas diferentes.
        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(90)->toDateString()
        );

        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(60)->toDateString()
        );

        $this->crearAdquisicion(
            $cliente,
            $dueno,
            now()->subDays(30)->toDateString()
        );

        $this->actingAs($dueno, 'web');

        // Consultamos las membresías del cliente.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que las adquisiciones estén ordenadas de más reciente a más antigua.
        $response->assertOk()
            ->assertJsonCount(3, 'historial.data');

        $fechas = collect($response->json('historial.data'))
            ->pluck('fecha_inicio')
            ->all();

        $this->assertSame([
            now()->subDays(30)->toDateString(),
            now()->subDays(60)->toDateString(),
            now()->subDays(90)->toDateString(),
        ], $fechas);
    }

    public function test_el_historial_se_pagina_de_cinco_en_cinco_registros(): void
    {
        // Fijamos la fecha actual para que la prueba sea predecible.
        $this->travelTo(now()->startOfDay());

        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario('dueno', 'dueno.paginacion');

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        // Registramos siete adquisiciones vencidas.
        for ($i = 1; $i <= 7; $i++) {
            $this->crearAdquisicion(
                $cliente,
                $dueno,
                now()->subDays($i * 30)->toDateString()
            );
        }

        $this->actingAs($dueno, 'web');

        // Consultamos la primera página del historial.
        $response = $this->getJson(
            route('cliente.membresias.index', $cliente->id_cliente)
        );

        // Comprobamos que la primera página contenga cinco registros.
        $response->assertOk()
            ->assertJsonCount(5, 'historial.data')
            ->assertJsonPath('historial.meta.current_page', 1)
            ->assertJsonPath('historial.meta.per_page', 5)
            ->assertJsonPath('historial.meta.total', 7)
            ->assertJsonPath('historial.meta.last_page', 2);

        // Consultamos la segunda página del historial.
        $response = $this->getJson(
            route('cliente.membresias.index', [
                'cliente' => $cliente->id_cliente,
                'page' => 2,
            ])
        );

        // Comprobamos que la segunda página contenga los dos registros restantes.
        $response->assertOk()
            ->assertJsonCount(2, 'historial.data')
            ->assertJsonPath('historial.meta.current_page', 2);
    }

    public function test_no_se_puede_consultar_una_pagina_menor_que_uno(): void
    {
        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario(
            'dueno',
            'dueno.pagina.minima'
        );

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        $this->actingAs($dueno, 'web');

        // Intentamos consultar una página menor que uno.
        $response = $this->getJson(
            route('cliente.membresias.index', [
                'cliente' => $cliente->id_cliente,
                'page' => 0,
            ])
        );

        // Comprobamos que la validación rechace el número de página.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_no_se_puede_consultar_una_pagina_que_no_sea_entera(): void
    {
        // Creamos y autenticamos al dueño.
        $dueno = $this->crearUsuario(
            'dueno',
            'dueno.pagina.entera'
        );

        // Creamos un cliente.
        $cliente = $this->crearCliente();

        $this->actingAs($dueno, 'web');

        // Intentamos consultar una página con un valor no entero.
        $response = $this->getJson(
            route('cliente.membresias.index', [
                'cliente' => $cliente->id_cliente,
                'page' => 'texto',
            ])
        );

        // Comprobamos que la validación rechace el número de página.
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    /**
     * Crea un usuario administrativo para las pruebas.
     */
    private function crearUsuario(
        string $rol,
        string $usuario,
        bool $activo = true
    ): UsuarioAdministrativo {
        return UsuarioAdministrativo::create([
            'usuario' => $usuario,
            'password' => 'Password*123',
            'rol' => $rol,
            'activo' => $activo,
        ]);
    }

    /**
     * Crea un cliente para las pruebas.
     */
    private function crearCliente(bool $activo = true): Cliente
    {
        return Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Gomez',
            'documento' => '1234567890',
            'telefono' => '3001234567',
            'activo' => $activo,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);
    }


    /**
     * Crea una adquisición de membresía para las pruebas.
     */
    private function crearAdquisicion(
        Cliente $cliente,
        UsuarioAdministrativo $usuario,
        string $fechaInicio
    ): MembresiaAdquirida {
        // Obtenemos o creamos el tipo de membresía utilizado en las pruebas.
        $tipoMembresia = TipoMembresia::firstOrCreate(
            ['nombre' => 'Mensual'],
            [
                'duracion_dias' => 30,
                'precio' => 80000,
                'activo' => true,
            ]
        );

        // Calculamos el vencimiento según la duración de la membresía.
        $fechaVencimiento = \Carbon\Carbon::parse($fechaInicio)
            ->addDays(29)
            ->endOfDay();

        // Registramos la adquisición con los datos históricos correspondientes.
        return MembresiaAdquirida::create([
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'id_usuario_registro' => $usuario->id_usuario,
            'nombre_tipo_snapshot' => 'Mensual',
            'duracion_dias_snapshot' => 30,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => $fechaVencimiento,
            'valor_pagado' => 80000,
            'metodo_pago' => 'efectivo',
        ]);
    }
}
