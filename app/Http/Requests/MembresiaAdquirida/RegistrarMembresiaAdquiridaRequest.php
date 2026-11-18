<?php

namespace App\Http\Requests\MembresiaAdquirida;

use App\Rules\ReglasMembresiaAdquirida;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarMembresiaAdquiridaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->rol,
            ['dueno', 'empleado'],
            true
        );
    }

    public function rules(): array
    {
        // array_merge nos permite combinar las reglas de validación de la clase ReglasMembresiaAdquirida con las reglas específicas de este request.
        return array_merge(
            [
                'id_cliente' => [
                    'required',
                    'integer',
                    Rule::exists('cliente', 'id_cliente')
                        ->where('activo', true),
                ],
            ],
            ReglasMembresiaAdquirida::rules()
        );
    }

    public function messages(): array
    {
        return array_merge(
            [
                'id_cliente.required' => 'El cliente es obligatorio.',
                'id_cliente.integer' => 'El cliente seleccionado no es válido.',
                'id_cliente.exists' => 'El cliente seleccionado no existe o se encuentra inactivo.',
            ],
            ReglasMembresiaAdquirida::messages()
        );
    }

    public function attributes(): array
    {
        return array_merge(
            [
                'id_cliente' => 'Cliente',
            ],
            ReglasMembresiaAdquirida::attributes()
        );
    }
}
