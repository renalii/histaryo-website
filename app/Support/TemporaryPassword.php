<?php

namespace App\Support;

final class TemporaryPassword
{
    public static function generate(int $length = 16): string
    {
        if ($length < 12 || $length > 64) {
            throw new \InvalidArgumentException('Temporary password length must be between 12 and 64 characters.');
        }

        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%&*?';
        $pool = $uppercase.$lowercase.$digits.$symbols;

        $characters = [
            self::randomCharacter($uppercase),
            self::randomCharacter($lowercase),
            self::randomCharacter($digits),
            self::randomCharacter($symbols),
        ];

        while (count($characters) < $length) {
            $characters[] = self::randomCharacter($pool);
        }

        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }

    private static function randomCharacter(string $characters): string
    {
        return $characters[random_int(0, strlen($characters) - 1)];
    }
}
