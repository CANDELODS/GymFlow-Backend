<?php

namespace App\Providers;

use App\Actions\Auth\AutenticarUsuarioAdministrativo;
use App\Http\Requests\UsuarioAdministrativoLoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Reemplaza la clase LoginRequest por UsuarioAdministrativoLoginRequest para personalizar las reglas de validación y los mensajes de error
        /*
        Bind registra la asociación entre la clase LoginRequest y UsuarioAdministrativoLoginRequest
        en el contenedor de servicios de Laravel.
        Esto significa que cuando Fortify necesite una instancia de LoginRequest,
        Laravel proporcionará una instancia de UsuarioAdministrativoLoginRequest en su lugar.
        */
        $this->app->bind(
            //Identifica la clase que Fortify solicita
            LoginRequest::class,
            //Indica qué clase debe proporcionar Laravel
            UsuarioAdministrativoLoginRequest::class
        );
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            // Identificamos la combinación de usuario e IP
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username()))
                    . '|'
                    . $request->ip()
            );

            // Aplicamos dos límites independientes al inicio de sesión
            return [
                // Límite global: 15 solicitudes por minuto desde una misma IP
                Limit::perMinute(15)->by('ip|' . $request->ip()),

                // Límite individual: 5 solicitudes por minuto por usuario e IP
                Limit::perMinute(5)->by($throttleKey),
            ];
        });


        Fortify::authenticateUsing(function (Request $request) {
            return app(AutenticarUsuarioAdministrativo::class)->ejecutar($request);
        });
    }
}
