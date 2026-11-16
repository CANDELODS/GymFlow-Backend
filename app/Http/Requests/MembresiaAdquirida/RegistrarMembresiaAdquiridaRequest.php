<?php

namespace App\Http\Requests\MembresiaAdquirida;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarMembresiaAdquiridaRequest extends FormRequest
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
            'id_cliente' => [
                'required',
                'integer',
                Rule::exists('cliente', 'id_cliente')
                    ->where('activo', true),
            ],

            'id_tipo_membresia' => [
                'required',
                'integer',
                Rule::exists('tipo_membresia', 'id_tipo_membresia')
                    ->where('activo', true),
            ],

            'fecha_inicio' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today', // Impedimos fechas futuras, solo anteriores o actuales.
            ],

            'valor_pagado' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'metodo_pago' => [
                'required',
                'string',
                Rule::in([
                    'efectivo',
                    'nequi',
                    'bancolombia',
                    'otro',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // Cliente
            'id_cliente.required' => 'El cliente es obligatorio.',
            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists' => 'El cliente seleccionado no existe o se encuentra inactivo.',

            // Tipo de membresía
            'id_tipo_membresia.required' => 'El tipo de membresía es obligatorio.',
            'id_tipo_membresia.integer' => 'El tipo de membresía seleccionado no es válido.',
            'id_tipo_membresia.exists' => 'El tipo de membresía seleccionado no existe o se encuentra inactivo.',

            // Fecha de inicio
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede ser posterior a la fecha actual.',

            // Valor pagado
            'valor_pagado.required' => 'El valor pagado es obligatorio.',
            'valor_pagado.numeric' => 'El valor pagado debe ser numérico.',
            'valor_pagado.gt' => 'El valor pagado debe ser mayor que cero.',

            // Método de pago
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.string' => 'El método de pago debe ser una cadena de texto.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_cliente' => 'Cliente',
            'id_tipo_membresia' => 'Tipo de membresía',
            'fecha_inicio' => 'Fecha de inicio',
            'valor_pagado' => 'Valor pagado',
            'metodo_pago' => 'Método de pago',
        ];
    }
}
