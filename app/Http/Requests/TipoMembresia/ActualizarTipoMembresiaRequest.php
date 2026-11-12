<?php

namespace App\Http\Requests\TipoMembresia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarTipoMembresiaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitimos actualizar a los usuarios administrativos.
        return in_array($this->user()?->rol, ['dueno', 'empleado'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                // Permitimos conservar el nombre actual, pero no utilizar el de otro tipo.
                Rule::unique('tipo_membresia', 'nombre')
                    ->ignore($this->route('tipoMembresia'), 'id_tipo_membresia'),
            ],

            'duracion_dias' => [
                'sometimes',
                'required',
                'integer',
                'gt:0',
            ],

            'precio' => [
                'sometimes',
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
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
            // Nombre.
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un tipo de membresía con este nombre.',

            // Duración.
            'duracion_dias.required' => 'La duración es obligatoria.',
            'duracion_dias.integer' => 'La duración debe ser un número entero.',
            'duracion_dias.gt' => 'La duración debe ser mayor que cero.',

            // Precio.
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un valor numérico.',
            'precio.gt' => 'El precio debe ser mayor que cero.',
            'precio.decimal' => 'El precio debe tener máximo dos posiciones decimales.',

            // Estado.
            'activo.required' => 'El estado es obligatorio.',
            'activo.boolean' => 'El estado debe ser un valor booleano.',
        ];
    }
}
