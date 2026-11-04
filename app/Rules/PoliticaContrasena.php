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
        // Verifica que la contraseña tenga al menos 8 caracteres.
        if (strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');
        }

        // Verifica que la contraseña contenga al menos un número.
        if (!preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un número.');
        }

        // Verifica que la contraseña contenga al menos un carácter especial.
        if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un carácter especial.');
        }
    }
}
