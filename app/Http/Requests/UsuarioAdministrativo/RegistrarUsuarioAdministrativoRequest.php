<?php

namespace App\Http\Requests\UsuarioAdministrativo;

use App\Rules\PoliticaContrasena;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarUsuarioAdministrativoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificamos que el usuario autenticado tenga el rol de dueño.
        // ?-> Operador de acceso seguro a propiedades (nullsafe operator). Si no existe un usuario autenticado, no intentará acceder a rol y devolverá null.
        return $this->user()?->rol === 'dueno';
    }

    public function rules(): array
    {
        return [
            'usuario' => [
                'required',
                'string',
                'min:5',
                'max:100',
                // Comprobamos que el nombre de usuario no exista previamente en la columna usuario de la tabla usuario_administrativo.
                'unique:usuario_administrativo,usuario',
            ],

            'password' => [
                'required',
                'string',
                'max:255',
                new PoliticaContrasena(),
            ],

            'password_confirmation' => [
                'required',
                'string',
                'same:password',
            ],

            'rol' => [
                'required',
                'string',
                // Nos permite establecer explícitamente los valores permitidos para el rol. Aunque el frontend envíe otro valor, Laravel lo rechazará.
                Rule::in(['dueno', 'empleado']),
            ],

            'activo' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            // Usuario
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.string' => 'El nombre de usuario debe ser un texto.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 5 caracteres.',
            'usuario.max' => 'El nombre de usuario no puede superar los 100 caracteres.',
            'usuario.unique' => 'El nombre de usuario ya se encuentra registrado.',

            // Contraseña
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.',
            'password.max' => 'La contraseña no puede superar los 255 caracteres.',

            // Confirmación de contraseña
            'password_confirmation.required' => 'La confirmación de la contraseña es obligatoria.',
            'password_confirmation.string' => 'La confirmación de la contraseña debe ser un texto.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',

            // Rol
            'rol.required' => 'El rol es obligatorio.',
            'rol.string' => 'El rol debe ser un texto.',
            'rol.in' => 'El rol seleccionado no es válido.',

            // Estado
            'activo.required' => 'El estado del usuario es obligatorio.',
            'activo.boolean' => 'El estado del usuario debe ser válido.',
        ];
    }
}
