<?php

namespace App\Http\Requests\MembresiaAdquirida;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarMembresiaAdquiridaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para actualizar la membresía.
     */
    public function authorize(): bool
    {
        // Permitimos la actualización exclusivamente al usuario con rol Dueño.
        return $this->user()?->rol === 'dueno';
    }

    /**
     * Retorna las reglas de validación de la actualización.
     */
    public function rules(): array
    {
        return [
            'id_cliente' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('cliente', 'id_cliente')
                    ->where('activo', true),
            ],

            'id_tipo_membresia' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('tipo_membresia', 'id_tipo_membresia')
                    ->where('activo', true),
            ],

            'fecha_inicio' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'valor_pagado' => [
                'sometimes',
                'required',
                'numeric',
                'gt:0',
            ],

            'metodo_pago' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    'efectivo',
                    'nequi',
                    'bancolombia',
                    'otro',
                ]),
            ],

            'motivo' => [
                'required',
                'string',
                'max:255',
                'regex:/\S/',
            ],
        ];
    }

    /**
     * Retorna los mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            // Cliente.
            'id_cliente.required' => 'El cliente es obligatorio.',
            'id_cliente.integer' => 'El cliente seleccionado no es válido.',
            'id_cliente.exists' => 'El cliente seleccionado no existe o se encuentra inactivo.',

            // Tipo de membresía.
            'id_tipo_membresia.required' => 'El tipo de membresía es obligatorio.',
            'id_tipo_membresia.integer' => 'El tipo de membresía seleccionado no es válido.',
            'id_tipo_membresia.exists' => 'El tipo de membresía seleccionado no existe o se encuentra inactivo.',

            // Fecha de inicio.
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_inicio.before_or_equal' => 'La fecha de inicio no puede ser posterior a la fecha actual.',

            // Valor pagado.
            'valor_pagado.required' => 'El valor pagado es obligatorio.',
            'valor_pagado.numeric' => 'El valor pagado debe ser numérico.',
            'valor_pagado.gt' => 'El valor pagado debe ser mayor que cero.',

            // Método de pago.
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.string' => 'El método de pago debe ser una cadena de texto.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',

            // Motivo.
            'motivo.required' => 'El motivo de la modificación es obligatorio.',
            'motivo.string' => 'El motivo de la modificación debe ser un texto válido.',
            'motivo.max' => 'El motivo de la modificación no puede superar los 255 caracteres.',
            'motivo.regex' => 'El motivo de la modificación no puede contener únicamente espacios.',
        ];
    }

    /**
     * Retorna los nombres personalizados de los atributos.
     */
    public function attributes(): array
    {
        return [
            'id_cliente' => 'Cliente',
            'id_tipo_membresia' => 'Tipo de membresía',
            'fecha_inicio' => 'Fecha de inicio',
            'valor_pagado' => 'Valor pagado',
            'metodo_pago' => 'Método de pago',
            'motivo' => 'Motivo de la modificación',
        ];
    }


    /**
     * Agrega validaciones posteriores a las reglas principales.
     */
    public function withValidator($validator): void
    {
        // // Ejecutamos estas validaciones después (after) de aplicar las reglas principales.
        $validator->after(function ($validator) {
            // Definimos los campos que pueden modificarse.
            $camposModificables = [
                'id_cliente',
                'id_tipo_membresia',
                'fecha_inicio',
                'valor_pagado',
                'metodo_pago',
            ];

            // Verificamos que se haya enviado al menos uno de los campos.
            if (!$this->hasAny($camposModificables)) {
                $validator->errors()->add(
                    'membresia',
                    'Debe proporcionar al menos un campo para modificar la membresía.'
                );
            }
        });
    }
}