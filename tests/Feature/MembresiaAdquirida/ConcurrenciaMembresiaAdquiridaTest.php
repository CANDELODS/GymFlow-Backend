<?php

namespace Tests\Feature\MembresiaAdquirida;

use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrenciaMembresiaAdquiridaTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Comprueba que registrar una adquisición y desactivar
     * su tipo de membresía produzca un resultado coherente.
     */
    public function test_registrar_membresia_y_desactivar_tipo_simultaneamente(): void
    {
        // Creamos el usuario administrativo que ejecutará el registro.
        $usuario = UsuarioAdministrativo::create([
            'usuario' => 'dueno_concurrencia',
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);

        // Creamos un cliente activo.
        $cliente = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Prueba',
            'documento' => '123456789',
            'telefono' => '3001234567',
            'activo' => true,
            'autorizacion_tratamiento' => true,
            'fecha_autorizacion' => now(),
        ]);

        // Creamos un tipo de membresía activo.
        $tipoMembresia = TipoMembresia::create([
            'nombre' => 'Membresía concurrencia',
            'duracion_dias' => 30,
            'precio' => 100000,
            'activo' => true,
        ]);

        // Preparamos los datos que utilizará la operación de registro.
        $datosRegistro = [
            'id_cliente' => $cliente->id_cliente,
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'fecha_inicio' => now()->toDateString(),
            'valor_pagado' => 100000,
            'metodo_pago' => 'efectivo',
        ];

        // Ejecutamos las dos operaciones simultáneamente.
        $resultados = $this->ejecutarOperacionesConcurrentes(
            $tipoMembresia,
            $cliente,
            $usuario,
            $datosRegistro
        );

        // Comprobamos que la desactivación siempre haya sido exitosa.
        $this->assertSame(
            'exito',
            $resultados['desactivar']['resultado']
        );

        // Comprobamos que el tipo de membresía haya quedado inactivo.
        $this->assertDatabaseHas('tipo_membresia', [
            'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
            'activo' => false,
        ]);

        // El registro puede completarse o rechazarse según
        // cuál de las dos operaciones haya obtenido primero el bloqueo.
        $this->assertContains(
            $resultados['registrar']['resultado'],
            ['exito', 'rechazado']
        );

        if ($resultados['registrar']['resultado'] === 'exito') {

            // Si el registro ganó, debe existir una adquisición.
            $this->assertDatabaseHas('membresia_adquirida', [
                'id_cliente' => $cliente->id_cliente,
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'id_usuario_registro' => $usuario->id_usuario,
            ]);

            $this->assertSame(
                1,
                MembresiaAdquirida::count()
            );
        } else {

            // Si la desactivación ganó, no debe existir una adquisición.
            $this->assertSame(
                0,
                MembresiaAdquirida::count()
            );
        }
    }

    /**
     * Ejecuta el registro y la desactivación en procesos independientes.
     */
    private function ejecutarOperacionesConcurrentes(
        TipoMembresia $tipoMembresia,
        Cliente $cliente,
        UsuarioAdministrativo $usuario,
        array $datosRegistro
    ): array {

        // Creamos un directorio temporal para sincronizar los procesos.
        $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'gymflow_membresia_' . bin2hex(random_bytes(8));

        mkdir($directorio);

        $procesos = [];

        // Preparamos las variables de conexión que utilizarán los procesos.
        $entorno = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'),
            'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        ];

        try {

            // Definimos la ruta del proceso auxiliar.
            $script = base_path(
                'tests/Support/ejecutar-operacion-concurrente-membresia.php'
            );

            // Definimos las operaciones que se ejecutarán.
            $operaciones = [
                'registrar',
                'desactivar',
            ];

            // Iniciamos un proceso independiente para cada operación.
            foreach ($operaciones as $operacion) {

                $proceso = new Process(
                    [
                        PHP_BINARY,
                        $script,
                        $operacion,
                        (string) $tipoMembresia->id_tipo_membresia,
                        (string) $cliente->id_cliente,
                        (string) $usuario->id_usuario,
                        $directorio,
                        json_encode($datosRegistro, JSON_THROW_ON_ERROR),
                    ],
                    base_path(),
                    $entorno
                );

                $proceso->setTimeout(30);
                $proceso->start();

                $procesos[$operacion] = $proceso;
            }

            // Esperamos hasta que ambos procesos estén preparados.
            $limiteEspera = microtime(true) + 30;

            while (true) {

                $todosPreparados = true;

                foreach ($procesos as $operacion => $proceso) {

                    $archivoListo = $directorio
                        . "/listo_{$operacion}";

                    if (!file_exists($archivoListo)) {

                        if (!$proceso->isRunning()) {
                            $this->fail(
                                "El proceso {$operacion} terminó antes de estar preparado: "
                                    . $proceso->getErrorOutput()
                            );
                        }

                        $todosPreparados = false;
                    }
                }

                if ($todosPreparados) {
                    break;
                }

                if (microtime(true) >= $limiteEspera) {
                    $this->fail('Los procesos no se prepararon a tiempo.');
                }

                usleep(10000);
            }

            // Liberamos ambos procesos para que ejecuten sus operaciones.
            file_put_contents($directorio . '/iniciar', 'go');

            $resultados = [];

            // Esperamos la finalización de ambos procesos.
            foreach ($procesos as $operacion => $proceso) {

                $proceso->wait();

                $this->assertTrue(
                    $proceso->isSuccessful(),
                    "Error en el proceso {$operacion}: "
                        . $proceso->getErrorOutput()
                );

                $resultados[$operacion] = json_decode(
                    trim($proceso->getOutput()),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            }

            return $resultados;
        } finally {

            // Detenemos cualquier proceso que continúe ejecutándose.
            foreach ($procesos as $proceso) {
                if ($proceso->isRunning()) {
                    $proceso->stop();
                }
            }

            // Eliminamos los archivos temporales.
            foreach (glob($directorio . DIRECTORY_SEPARATOR . '*') as $archivo) {
                unlink($archivo);
            }

            rmdir($directorio);
        }
    }
}
