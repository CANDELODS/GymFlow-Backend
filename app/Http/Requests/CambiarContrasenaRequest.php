<?php

namespace App\Http\Requests;

use App\Rules\PoliticaContrasena;
use Illuminate\Foundation\Http\FormRequest;

class CambiarContrasenaRequest extends FormRequest
{
    // Determinamos si el usuario puede realizar esta solicitud
    public function authorize(): bool
    {
        return true;
    }

    // Definimos las reglas de validación para el cambio de contraseña
    public function rules(): array
    {
        return [
            'contrasena_actual' => [
                'required',
                'string',
                'max:255',
            ],
            'contrasena_nueva' => [
                'required',
                'string',
                'max:255',
                new PoliticaContrasena(),
                'different:contrasena_actual',
            ],
            'contrasena_nueva_confirmacion' => [
                'required',
                'string',
                'same:contrasena_nueva',
            ],
        ];
    }

    // Personalizamos los mensajes de validación
    public function messages(): array
    {
        return [
            'contrasena_actual.required' => 'La contraseña actual es obligatoria.',
            'contrasena_actual.string' => 'La contraseña actual debe ser un texto.',
            'contrasena_actual.min' => 'La contraseña actual debe tener al menos 8 caracteres.',
            'contrasena_actual.max' => 'La contraseña actual no puede superar los 255 caracteres.',

            'contrasena_nueva.required' => 'La nueva contraseña es obligatoria.',
            'contrasena_nueva.string' => 'La nueva contraseña debe ser un texto.',
            'contrasena_nueva.max' => 'La nueva contraseña no puede superar los 255 caracteres.',
            'contrasena_nueva.different' => 'La nueva contraseña debe ser diferente de la contraseña actual.',

            'contrasena_nueva_confirmacion.required' => 'Debes confirmar la nueva contraseña.',
            'contrasena_nueva_confirmacion.string' => 'La confirmación debe ser un texto.',
            'contrasena_nueva_confirmacion.same' => 'La confirmación no coincide con la nueva contraseña.',
        ];
    }
}
