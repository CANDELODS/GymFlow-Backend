<?php

namespace App\Http\Requests\Auth;
//La clase LoginRequest hereda de FormRequest, por lo que podemos definir reglas de validación y mensajes personalizados para la solicitud de inicio de sesión
use Laravel\Fortify\Http\Requests\LoginRequest;
use Laravel\Fortify\Fortify;

class UsuarioAdministrativoLoginRequest extends LoginRequest
{

    public function rules(): array
    {
        return [
            //Fortify::username() = 'usuario' (config/fortify.php)
            Fortify::username() => ['required', 'string', 'min:5', 'max:100',],
            'password' => ['required', 'string', 'min:8', 'max:255',],
            'remember' => ['sometimes'],
        ];
    }

    public function messages(): array
    {
        return [
            //Fortify::username() = 'usuario' (config/fortify.php)
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.string' => 'El nombre de usuario debe ser texto.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 5 caracteres.',
            'usuario.max' => 'El nombre de usuario no puede superar los 100 caracteres.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña no puede superar los 255 caracteres.',
        ];
    }
}
