
<?php

use App\Actions\UsuarioAdministrativo\ActualizarUsuarioAdministrativo;
use App\Actions\UsuarioAdministrativo\CambiarEstadoUsuarioAdministrativo;
use App\Models\UsuarioAdministrativo;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

// Recibimos los argumentos enviados por el proceso principal.
$idUsuario = (int) $argv[1];
$directorioSincronizacion = $argv[2];
$operacion = $argv[3] ?? 'desactivar';

// Cargamos Laravel.
require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

try {

    // Indicamos que este proceso ya está preparado.
    file_put_contents(
        $directorioSincronizacion . "/listo_{$idUsuario}",
        'ready'
    );

    // Esperamos hasta que el proceso principal nos permita continuar.
    $archivoInicio = $directorioSincronizacion . '/iniciar';

    $limiteEspera = microtime(true) + 30;

    while (!file_exists($archivoInicio)) {

        if (microtime(true) >= $limiteEspera) {
            throw new RuntimeException('Tiempo de espera agotado.');
        }

        usleep(10000);
    }

    // Obtenemos el usuario desde nuestra propia conexión a MySQL.
    $usuario = UsuarioAdministrativo::findOrFail($idUsuario);

    // Ejecutamos la operación correspondiente.
    if ($operacion === 'desactivar') {

        // Ejecutamos el Action para desactivar al dueño.
        app(CambiarEstadoUsuarioAdministrativo::class)->ejecutar(
            $usuario,
            false
        );

    } elseif ($operacion === 'cambiar_rol') {

        // Ejecutamos el Action para cambiar el rol del dueño a empleado.
        app(ActualizarUsuarioAdministrativo::class)->ejecutar(
            $usuario,
            [
                'usuario' => $usuario->usuario,
                'rol' => 'empleado',
            ]
        );

    } else {
        throw new RuntimeException('Operación no reconocida.');
    }

    // Informamos que la operación terminó correctamente.
    echo json_encode([
        'resultado' => 'exito',
        'id_usuario' => $idUsuario,
        'operacion' => $operacion,
    ]);

} catch (ValidationException $exception) {

    // Informamos que la operación fue rechazada por una regla de negocio.
    echo json_encode([
        'resultado' => 'rechazado',
        'id_usuario' => $idUsuario,
        'operacion' => $operacion,
        'errores' => $exception->errors(),
    ]);

} catch (Throwable $exception) {

    // Informamos cualquier otro error inesperado.
    fwrite(STDERR, $exception->getMessage());

    exit(1);
}