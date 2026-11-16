
<?php

use App\Actions\MembresiaAdquirida\RegistrarMembresiaAdquirida;
use App\Actions\TipoMembresia\CambiarEstadoTipoMembresia;
use App\Models\TipoMembresia;
use App\Models\UsuarioAdministrativo;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

// Recibimos los argumentos enviados por el proceso principal.
$operacion = $argv[1];
$idTipoMembresia = (int) $argv[2];
$idCliente = (int) $argv[3];
$idUsuario = (int) $argv[4];
$directorioSincronizacion = $argv[5];
$datosRegistro = json_decode($argv[6] ?? '{}', true);

// Inicializamos Laravel.
require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    // Identificamos al usuario que ejecutará la operación.
    $usuario = UsuarioAdministrativo::findOrFail($idUsuario);

    Auth::guard('web')->login($usuario);

    // Indicamos al proceso principal que estamos listos.
    file_put_contents(
        $directorioSincronizacion . '/listo_' . $operacion,
        'listo'
    );

    // Esperamos la señal para iniciar la operación.
    $archivoInicio = $directorioSincronizacion . '/iniciar';

    $inicioEspera = microtime(true);

    while (! file_exists($archivoInicio)) {
        if (microtime(true) - $inicioEspera > 30) {
            throw new RuntimeException(
                'Tiempo de espera agotado para iniciar la operación.'
            );
        }

        usleep(10000);
    }

    // Ejecutamos la operación solicitada.
    if ($operacion === 'registrar') {
        $resultado = app(RegistrarMembresiaAdquirida::class)
            ->ejecutar($datosRegistro);

        echo json_encode([
            'resultado' => 'exito',
            'id_membresia_adquirida' => $resultado->id_membresia_adquirida,
        ], JSON_THROW_ON_ERROR);

    } elseif ($operacion === 'desactivar') {
        $tipoMembresia = TipoMembresia::findOrFail($idTipoMembresia);

        $resultado = app(CambiarEstadoTipoMembresia::class)
            ->ejecutar($tipoMembresia, false);

        echo json_encode([
            'resultado' => 'exito',
            'activo' => $resultado->activo,
        ], JSON_THROW_ON_ERROR);

    } else {
        throw new InvalidArgumentException(
            'La operación solicitada no es válida.'
        );
    }

} catch (ValidationException | ModelNotFoundException $e) {
    echo json_encode([
        'resultado' => 'rechazado',
        'errores' => $e instanceof ValidationException
            ? $e->errors()
            : [$e->getMessage()],
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}