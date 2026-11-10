<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PoliticaContrasena implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Verifica que el valor sea una cadena no vacía.
        if (!is_string($value) || $value === '') {
            $fail('La contraseña debe ser un texto válido.');
            return;
        }

        // Verifica que la contraseña tenga al menos 8 caracteres.
        if (strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');
        }

        // Verifica que la contraseña contenga al menos un número.
        if (!preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un número.');
        }

        // Verifica que la contraseña contenga al menos un carácter especial distinto de un espacio en blanco.
        if (!preg_match('/[^a-zA-Z0-9\s]/', $value)) {
            $fail('La contraseña debe contener al menos un carácter especial.');
        }
    }
}
