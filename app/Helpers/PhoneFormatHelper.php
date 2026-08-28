<?php

namespace App\Helpers;

class PhoneFormatHelper
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $length = strlen($digits);

        if ($length < 6 || $length > 16) {
            return null;
        }

        return match ($length) {
            6 => substr($digits, 0, 3).'-'.substr($digits, 3),
            7 => substr($digits, 0, 3).'-'.substr($digits, 3, 2).'-'.substr($digits, 5),
            8 => substr($digits, 0, 4).' '.substr($digits, 4),
            default => $digits,
        };
    }
}
