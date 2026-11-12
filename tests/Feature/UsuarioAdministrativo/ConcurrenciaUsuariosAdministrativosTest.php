<?php

namespace Tests\Feature\UsuarioAdministrativo;

use App\Models\UsuarioAdministrativo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrenciaUsuariosAdministrativosTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Dos dueños no pueden desactivar simultáneamente
     * sus cuentas y dejar el sistema sin dueños activos.
     */
    public function test_no_se_pueden_desactivar_simultaneamente_los_dos_ultimos_duenos(): void
    {
        // Creamos los dos últimos dueños activos.
        $duenoA = $this->crearDueno('dueno123');
        $duenoB = $this->crearDueno('dueno456');

        // Ejecutamos ambas operaciones simultáneamente.
        $this->ejecutarOperacionesConcurrentes(
            $duenoA,
            $duenoB,
            'desactivar'
        );

        // Comprobamos que permanezca exactamente un dueño activo.
        $this->assertSame(
            1,
            UsuarioAdministrativo::where('rol', 'dueno')
                ->where('activo', true)
                ->count()
        );
    }

    /**
     * Dos dueños no pueden cambiar simultáneamente sus roles
     * a empleado y dejar el sistema sin dueños activos.
     */
    public function test_no_se_pueden_cambiar_simultaneamente_los_roles_de_los_dos_ultimos_duenos(): void
    {
        // Creamos los dos últimos dueños activos.
        $duenoA = $this->crearDueno('dueno123');
        $duenoB = $this->crearDueno('dueno456');

        // Ejecutamos ambas operaciones simultáneamente.
        $this->ejecutarOperacionesConcurrentes(
            $duenoA,
            $duenoB,
            'cambiar_rol'
        );

        // Comprobamos que permanezca exactamente un dueño activo.
        $this->assertSame(
            1,
            UsuarioAdministrativo::where('rol', 'dueno')
                ->where('activo', true)
                ->count()
        );
    }

    /**
     * Crea un dueño activo para los escenarios de concurrencia.
     */
    private function crearDueno(string $nombre): UsuarioAdministrativo
    {
        return UsuarioAdministrativo::create([
            'usuario' => $nombre,
            'password' => 'Password123!',
            'rol' => 'dueno',
            'activo' => true,
        ]);
    }

    /**
     * Ejecuta dos operaciones independientes y comprueba sus resultados.
     */
    private function ejecutarOperacionesConcurrentes(
        UsuarioAdministrativo $duenoA,
        UsuarioAdministrativo $duenoB,
        string $operacion
    ): void {

        // Creamos un directorio temporal para sincronizar los procesos.
        $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'gymflow_' . bin2hex(random_bytes(8));

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
            $script = base_path('tests/Support/ejecutar-operacion-concurrente.php');

            // Iniciamos un proceso independiente para cada dueño.
            foreach ([$duenoA, $duenoB] as $dueno) {

                $proceso = new Process(
                    [
                        PHP_BINARY,
                        $script,
                        (string) $dueno->id_usuario,
                        $directorio,
                        $operacion,
                    ],
                    base_path(),
                    $entorno
                );

                $proceso->setTimeout(30);
                $proceso->start();

                $procesos[$dueno->id_usuario] = $proceso;
            }

            // Esperamos hasta que ambos procesos estén preparados.
            $limiteEspera = microtime(true) + 30;

            while (true) {

                $todosPreparados = true;

                foreach ($procesos as $id => $proceso) {

                    if (!file_exists($directorio . "/listo_{$id}")) {

                        if (!$proceso->isRunning()) {
                            $this->fail(
                                'Un proceso terminó antes de estar preparado: '
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
            foreach ($procesos as $proceso) {

                $proceso->wait();

                $this->assertTrue(
                    $proceso->isSuccessful(),
                    'Error en el proceso secundario: ' . $proceso->getErrorOutput()
                );

                $resultados[] = json_decode(
                    trim($proceso->getOutput()),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            }

            // Comprobamos que una operación haya sido exitosa.
            $this->assertSame(
                1,
                count(array_filter(
                    $resultados,
                    fn($resultado) => $resultado['resultado'] === 'exito'
                ))
            );

            // Comprobamos que la otra haya sido rechazada.
            $this->assertSame(
                1,
                count(array_filter(
                    $resultados,
                    fn($resultado) => $resultado['resultado'] === 'rechazado'
                ))
            );
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
