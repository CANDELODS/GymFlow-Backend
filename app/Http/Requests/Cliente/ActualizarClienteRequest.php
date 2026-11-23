<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitimos actualizar a los usuarios administrativos.
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
            'nombres' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'documento' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('cliente', 'documento')
                    ->ignore($this->route('cliente'), 'id_cliente'),
            ],

            'telefono' => [
                'sometimes',
                'required',
                'string',
                'max:20',
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
            // Nombres.
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.string' => 'Los nombres deben ser un texto válido.',
            'nombres.max' => 'Los nombres no pueden superar los 100 caracteres.',

            // Apellidos.
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string' => 'Los apellidos deben ser un texto válido.',
            'apellidos.max' => 'Los apellidos no pueden superar los 100 caracteres.',

            // Documento.
            'documento.required' => 'El documento es obligatorio.',
            'documento.string' => 'El documento debe ser un texto válido.',
            'documento.max' => 'El documento no puede superar los 30 caracteres.',
            'documento.unique' => 'Ya existe un cliente registrado con este documento.',

            // Teléfono.
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.string' => 'El teléfono debe ser un texto válido.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',

            // Estado.
            'activo.required' => 'El estado es obligatorio.',
            'activo.boolean' => 'El estado debe ser un valor booleano.',
        ];
    }
}