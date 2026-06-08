<?php

declare(strict_types=1);

namespace App\Support;

final class PasswordGenerator
{
    private const LETTERS = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
    private const NUMBERS = '23456789';
    private const SYMBOLS = '!@#$%^*()-_+=[]{}|;:,.?~';

    /** No instantiation — pure static utility. */
    private function __construct() {}

    /**
     * Generate a cryptographically secure password.
     * Guarantees at least 2 letters + 1 number + 1 symbol.
     */
    public static function generate(int $length = 14): string
    {
        $all = self::LETTERS . self::NUMBERS . self::SYMBOLS;

        // Guarantee minimum composition: 2 letters, 1 number, 1 symbol.
        $chars = [
            self::pickRandom(self::LETTERS),
            self::pickRandom(self::LETTERS),
            self::pickRandom(self::NUMBERS),
            self::pickRandom(self::SYMBOLS),
        ];

        for ($i = 4; $i < $length; $i++) {
            $chars[] = self::pickRandom($all);
        }

        return implode('', self::fisherYates($chars));
    }

    /** Pick one random character from a string using random_int(). */
    private static function pickRandom(string $chars): string
    {
        return $chars[random_int(0, strlen($chars) - 1)];
    }

    /**
     * Fisher-Yates shuffle using random_int() (cryptographically secure).
     * PHP's built-in shuffle() uses MT-19937 and is NOT suitable here.
     *
     * @param  array<int, string>  $array
     * @return array<int, string>
     */
    private static function fisherYates(array $array): array
    {
        $n = count($array);

        for ($i = $n - 1; $i > 0; $i--) {
            $j = random_int(0, $i);

            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }

        return $array;
    }
}
