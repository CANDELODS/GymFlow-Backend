<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarClientesRequest extends FormRequest
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
            'buscar' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'activo' => [
                'sometimes',
                'boolean',
            ],

            'estado_membresia' => [
                'sometimes',
                'string',
                'in:activa,por_vencer,vencida,sin_membresia',
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
            // Búsqueda.
            'buscar.string' => 'El término de búsqueda debe ser un texto válido.',
            'buscar.max' => 'El término de búsqueda no puede superar los 100 caracteres.',

            // Estado del cliente.
            'activo.boolean' => 'El estado del cliente debe ser un valor booleano válido.',

            // Estado de la membresía.
            'estado_membresia.string' => 'El estado de la membresía debe ser un texto válido.',
            'estado_membresia.in' => 'El estado de la membresía seleccionado no es válido.',

            // Paginación.
            'page.integer' => 'El número de página debe ser un entero.',
            'page.min' => 'El número de página debe ser como mínimo 1.',
        ];
    }
}
