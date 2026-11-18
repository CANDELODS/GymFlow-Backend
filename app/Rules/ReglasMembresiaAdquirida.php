<?php

namespace App\Rules;

use Illuminate\Validation\Rule;

class ReglasMembresiaAdquirida
{
    // Usamos métodos estáticos para definir las reglas, mensajes y atributos de validación,
    // ya que no necesitamos instanciar la clase para acceder a ellos.
    // Esto nos permite mantener la lógica de validación organizada y reutilizable en diferentes partes de la aplicación.

    /**
     * Retorna las reglas de validación para los datos de la membresía.
     */
    public static function rules(): array
    {
        return [
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

    /**
     * Retorna los mensajes personalizados de validación.
     */
    public static function messages(): array
    {
        return [
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

    /**
     * Retorna los nombres personalizados de los atributos.
     */
    public static function attributes(): array
    {
        return [
            'id_tipo_membresia' => 'Tipo de membresía',
            'fecha_inicio' => 'Fecha de inicio',
            'valor_pagado' => 'Valor pagado',
            'metodo_pago' => 'Método de pago',
        ];
    }
}
