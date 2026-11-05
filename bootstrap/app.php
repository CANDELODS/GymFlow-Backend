<?php

use App\Http\Middleware\VerificarRolUsuarioAdministrativo;
use App\Http\Middleware\VerificarExpiracionSesion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        //Habilita el tratamiento de solicitudes API provenientes de nuestro frontend
        //como solicitudes de primera parte, permitiendo utilizar cookies, sesiones y protección CSRF.
        $middleware->statefulApi();

        // Habilita la verificación del hash de contraseña almacenado en las sesiones autenticadas.
        $middleware->authenticateSessions();

        // Registramos el alias del middleware para verificar los roles administrativos.
        $middleware->alias([
            'rol.administrativo' => VerificarRolUsuarioAdministrativo::class,
        ]);

        // Agregamos la verificación de expiración al grupo web
        $middleware->appendToGroup('web', [
            VerificarExpiracionSesion::class,
        ]);

        // Agregamos la verificación de expiración al grupo api
        $middleware->appendToGroup('api', [
            VerificarExpiracionSesion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
