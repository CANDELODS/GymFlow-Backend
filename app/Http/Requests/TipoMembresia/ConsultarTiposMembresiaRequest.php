<?php

namespace App\Http\Requests\TipoMembresia;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarTiposMembresiaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitimos la consulta explícitamente a dueños y empleados.
        return in_array(
            $this->user()?->rol,
            ['dueno', 'empleado'],
            true
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'activo' => [
                'sometimes',
                'boolean',
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
            // Nombre.
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',

            // Estado.
            'activo.boolean' => 'El estado debe ser un valor booleano válido.',

            // Paginación.
            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser como mínimo 1.',
        ];
    }
}
