<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitimos cambiar el estado a los usuarios administrativos.
        return in_array(
            $this->user()?->rol,
            ['dueno', 'empleado'],
            true
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'activo' => [
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
            'activo.required' => 'El estado del cliente es obligatorio.',
            'activo.boolean' => 'El estado debe ser un valor booleano válido.',
        ];
    }
}