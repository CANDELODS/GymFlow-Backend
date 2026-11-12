<?php

namespace App\Http\Requests\UsuarioAdministrativo;

use App\Rules\PoliticaContrasena;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarUsuarioAdministrativoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificamos que el usuario autenticado tenga el rol de dueño.
        return $this->user()?->rol === 'dueno';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            'usuario' => [
                'sometimes',
                'required',
                'string',
                'min:5',
                'max:100',
                // Permitimos conservar el nombre actual, pero no utilizar el de otra cuenta.
                Rule::unique('usuario_administrativo', 'usuario')
                    // Nuestra ruta usará el parametro {usuarioAdministrativo}
                    ->ignore($this->route('usuarioAdministrativo'), 'id_usuario'),
            ],

            'password' => [
                //Actualizar la contraseña es opcional, por lo que usamos 'sometimes' y 'nullable'.
                'sometimes', // Puede no enviarse en la solicitud
                'nullable', // Si el valor es null, se omiten las demás reglas de validación.
                'string',
                'max:255',
                // La política solo se aplica cuando se proporciona una contraseña nueva.
                new PoliticaContrasena(),
            ],

            'password_confirmation' => [
                'required_with:password', // Exige confirmación cuando se proporciona una contraseña nueva
                'string',
                'same:password',
            ],

            'rol' => [
                'sometimes',
                'required',
                'string',
                // Solo permitimos los roles definidos en GymFlow.
                Rule::in(['dueno', 'empleado']),
            ],

            'activo' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Nombre de usuario.
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.string' => 'El nombre de usuario debe ser un texto.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 5 caracteres.',
            'usuario.max' => 'El nombre de usuario no puede superar los 100 caracteres.',
            'usuario.unique' => 'El nombre de usuario ya se encuentra registrado.',

            // Contraseña.
            'password.string' => 'La contraseña debe ser un texto.',
            'password.max' => 'La contraseña no puede superar los 255 caracteres.',

            // Confirmación de contraseña.
            'password_confirmation.required_with' => 'Debes confirmar la nueva contraseña.',
            'password_confirmation.string' => 'La confirmación de contraseña debe ser un texto.',
            'password_confirmation.same' => 'La confirmación no coincide con la nueva contraseña.',

            // Rol.
            'rol.required' => 'El rol del usuario es obligatorio.',
            'rol.string' => 'El rol debe ser un texto.',
            'rol.in' => 'El rol seleccionado no es válido.',

            // Estado.
            'activo.required' => 'El estado de la cuenta es obligatorio.',
            'activo.boolean' => 'El estado de la cuenta debe ser un valor booleano válido.',
        ];
    }
}
