<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Las tablas `articulo` de las sucursales usan latin1. Un texto con emojis u otros
 * símbolos fuera de ese juego se guarda en el maestro (utf8mb4) pero las sucursales
 * lo rechazan con el error 3988. Esta regla lo detecta antes de guardar.
 */
class Latin1 implements ValidationRule
{
    public static function cabe(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $ida = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        return mb_convert_encoding($ida, 'UTF-8', 'Windows-1252') === $value;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && !self::cabe($value)) {
            $fail('El campo :attribute tiene caracteres no permitidos (emojis o símbolos especiales).');
        }
    }
}
