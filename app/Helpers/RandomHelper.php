<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RandomHelper
{
    public static function generateUniqueDigits(int $digits, string $table, string $column = 'code'): string
    {
        if ($digits < 1) {
            throw new InvalidArgumentException('La cantidad de dígitos debe ser mayor que cero.');
        }

        if (! self::isValidIdentifier($table) || ! self::isValidIdentifier($column)) {
            throw new InvalidArgumentException('La tabla y la columna deben ser identificadores válidos.');
        }

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $value = '';

            for ($digit = 0; $digit < $digits; $digit++) {
                $value .= random_int(0, 9);
            }

            if (! DB::table($table)->where($column, $value)->exists()) {
                return $value;
            }
        }

        throw new RuntimeException('No fue posible generar un valor único.');
    }

    public static function generateUniqueAlphanumeric(int $characters, string $table, string $prefix = '', string $column = 'code'): string
    {
        if ($characters < 1) {
            throw new InvalidArgumentException('La cantidad de caracteres debe ser mayor que cero.');
        }

        if ($prefix !== '' && preg_match('/^[A-Za-z0-9]+$/', $prefix) !== 1) {
            throw new InvalidArgumentException('El prefijo debe contener únicamente letras y números.');
        }

        if (! self::isValidIdentifier($table) || ! self::isValidIdentifier($column)) {
            throw new InvalidArgumentException('La tabla y la columna deben ser identificadores válidos.');
        }

        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $value = '';

            for ($character = 0; $character < $characters; $character++) {
                $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $value = $prefix === '' ? $value : $prefix.'-'.$value;

            if (! DB::table($table)->where($column, $value)->exists()) {
                return $value;
            }
        }

        throw new RuntimeException('No fue posible generar un valor único.');
    }

    private static function isValidIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) === 1;
    }
}
