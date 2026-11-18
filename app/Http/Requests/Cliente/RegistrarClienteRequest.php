<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarClienteRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],
            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],
            'documento' => [
                'required',
                'string',
                'max:30',
                'unique:cliente,documento',
            ],
            'telefono' => [
                'required',
                'string',
                'max:20',
            ],
            'autorizacion_tratamiento' => [
                'required',
                'accepted', // El campo debe ser aceptado (true, 'yes', 1, 'on').
            ],
            'registrar_membresia' => [
                'sometimes',
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
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.string' => 'Los nombres deben ser un texto válido.',
            'nombres.max' => 'Los nombres no pueden superar los 100 caracteres.',

            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string' => 'Los apellidos deben ser un texto válido.',
            'apellidos.max' => 'Los apellidos no pueden superar los 100 caracteres.',

            'documento.required' => 'El documento es obligatorio.',
            'documento.string' => 'El documento debe ser un texto válido.',
            'documento.max' => 'El documento no puede superar los 30 caracteres.',
            'documento.unique' => 'Ya existe un cliente registrado con este documento.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.string' => 'El teléfono debe ser un texto válido.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',

            'autorizacion_tratamiento.required' => 'Debe indicar su autorización para el tratamiento de datos personales.',
            'autorizacion_tratamiento.accepted' => 'Es necesario aceptar el tratamiento de datos personales para registrar al cliente.',

            'registrar_membresia.boolean' => 'El campo de registro de membresía debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'documento' => 'Documento',
            'telefono' => 'Teléfono',
            'autorizacion_tratamiento' => 'Autorización de tratamiento de datos',
            'registrar_membresia' => 'Registro de membresía',
        ];
    }
}
