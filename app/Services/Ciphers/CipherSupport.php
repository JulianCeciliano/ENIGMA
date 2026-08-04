<?php

namespace App\Services\Ciphers;

use InvalidArgumentException;

final class CipherSupport
{
    public static function characterFromIndex(int $index, string $original): string
    {
        $letter = chr(ord('A') + self::mod($index));

        if (strlen($original) === 1 && $original >= 'a' && $original <= 'z') {
            return strtolower($letter);
        }

        return $letter;
    }

    /**
     * @return array<int, int>
     */
    public static function columnOrder(string $key): array
    {
        $indices = range(0, strlen($key) - 1);

        usort($indices, static function (int $left, int $right) use ($key): int {
            $byCharacter = $key[$left] <=> $key[$right];

            return $byCharacter !== 0 ? $byCharacter : $left <=> $right;
        });

        return $indices;
    }

    public static function integerParameter(mixed $value, string $name): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        throw new InvalidArgumentException("El parámetro {$name} debe ser un entero.");
    }

    public static function letterIndex(string $character): ?int
    {
        if (strlen($character) !== 1) {
            return null;
        }

        $code = ord($character);

        if ($code >= ord('A') && $code <= ord('Z')) {
            return $code - ord('A');
        }

        if ($code >= ord('a') && $code <= ord('z')) {
            return $code - ord('a');
        }

        return null;
    }

    public static function mod(int $value, int $modulus = 26): int
    {
        return (($value % $modulus) + $modulus) % $modulus;
    }

    public static function normalizeColumnKey(mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('La clave columnar debe ser texto.');
        }

        $key = strtoupper(trim($value));

        if (strlen($key) < 2 || preg_match('/^[A-Z0-9]+$/', $key) !== 1) {
            throw new InvalidArgumentException('La clave columnar debe tener al menos 2 caracteres A-Z o 0-9.');
        }

        return $key;
    }

    public static function normalizeLettersKey(mixed $value, string $name = 'key'): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("La clave {$name} debe ser texto.");
        }

        $key = strtoupper(trim($value));

        if ($key === '' || preg_match('/^[A-Z]+$/', $key) !== 1) {
            throw new InvalidArgumentException("La clave {$name} debe contener una o más letras A-Z.");
        }

        return $key;
    }

    public static function normalizeSubstitutionKey(mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('La clave de sustitución debe ser texto.');
        }

        $key = strtoupper(trim($value));

        if (strlen($key) !== 26 || preg_match('/^[A-Z]{26}$/', $key) !== 1 || count(array_unique(str_split($key))) !== 26) {
            throw new InvalidArgumentException('La clave de sustitución debe contener las 26 letras A-Z sin repetir.');
        }

        return $key;
    }

    /**
     * @return array<int, string>
     */
    public static function textCharacters(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $characters === false ? str_split($text) : $characters;
    }

    /**
     * @param callable(int): int $transform
     */
    public static function transformLetters(string $text, callable $transform): string
    {
        $output = '';

        foreach (str_split($text) as $character) {
            $index = self::letterIndex($character);

            if ($index === null) {
                $output .= $character;
                continue;
            }

            $output .= self::characterFromIndex($transform($index), $character);
        }

        return $output;
    }

    public static function modularInverse(int $value, int $modulus = 26): int
    {
        $normalized = self::mod($value, $modulus);

        for ($candidate = 1; $candidate < $modulus; $candidate++) {
            if (self::mod($normalized * $candidate, $modulus) === 1) {
                return $candidate;
            }
        }

        throw new InvalidArgumentException('No existe inverso modular.');
    }

    /**
     * @return array<int, string>
     */
    public static function asciiLetters(string $text): array
    {
        preg_match_all('/[A-Za-z]/', $text, $matches);

        return $matches[0];
    }
}
