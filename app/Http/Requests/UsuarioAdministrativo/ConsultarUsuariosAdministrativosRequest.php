<?php

namespace App\Http\Requests\UsuarioAdministrativo;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarUsuariosAdministrativosRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'usuario' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'usuario.string' => 'El nombre de usuario debe ser un texto.',
            'usuario.max' => 'El nombre de usuario no puede superar los 100 caracteres.',

            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser como mínimo 1.',
        ];
    }
}
