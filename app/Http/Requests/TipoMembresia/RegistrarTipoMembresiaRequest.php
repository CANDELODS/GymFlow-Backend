<?php

namespace App\Http\Requests\TipoMembresia;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarTipoMembresiaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitimos el registro explícitamente a dueños y empleados.
        return in_array(
            $this->user()?->rol,
            ['dueno', 'empleado'],
            true
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:tipo_membresia,nombre',
            ],

            'duracion_dias' => [
                'required',
                'integer',
                'gt:0', // gt = greater than (Mayor que 0)
            ],

            'precio' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2', // Permite hasta 2 decimales
            ],
        ];
    }
    public function messages(): array
    {
        return [
            // Nombre.
            'nombre.required' => 'El nombre del tipo de membresía es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto válido.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe un tipo de membresía con este nombre.',

            // Duración.
            'duracion_dias.required' => 'La duración de la membresía es obligatoria.',
            'duracion_dias.integer' => 'La duración debe ser un número entero.',
            'duracion_dias.gt' => 'La duración debe ser mayor que cero.',

            // Precio.
            'precio.required' => 'El precio de la membresía es obligatorio.',
            'precio.numeric' => 'El precio debe ser un valor numérico.',
            'precio.gt' => 'El precio debe ser mayor que cero.',
            'precio.decimal' => 'El precio debe tener como máximo dos decimales.',
        ];
    }
}
