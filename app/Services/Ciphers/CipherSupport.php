<?php

namespace App\Services\Ciphers;

final class CipherSupport
{
    public static function characterFromIndex(int $index, string $original): string
    {
        return CipherText::fromIndex($index, $original);
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

        throw new CipherValidationException("El parámetro {$name} debe ser un entero.", $name);
    }

    public static function letterIndex(string $character): ?int
    {
        return CipherText::letterIndex($character);
    }

    public static function mod(int $value, int $modulus = 26): int
    {
        return CipherText::mod($value, $modulus);
    }

    public static function normalizeColumnKey(mixed $value): string
    {
        if (! is_string($value)) {
            throw new CipherValidationException('La clave columnar debe ser texto.', 'key');
        }

        $key = strtoupper(trim($value));

        if (strlen($key) < 2 || preg_match('/^[A-Z0-9]+$/', $key) !== 1) {
            throw new CipherValidationException('La clave columnar debe tener al menos 2 caracteres A-Z o 0-9.', 'key');
        }

        if (strlen($key) > CipherText::MaximumMatrixColumns) {
            throw new CipherValidationException(
                'La clave columnar no puede superar '.CipherText::MaximumMatrixColumns.' caracteres.',
                'key',
            );
        }

        return $key;
    }

    public static function normalizeLettersKey(mixed $value, string $name = 'key'): string
    {
        if (! is_string($value)) {
            throw new CipherValidationException("La clave {$name} debe ser texto.", $name);
        }

        $key = strtoupper(trim($value));

        if ($key === '' || preg_match('/^[A-Z]+$/', $key) !== 1) {
            throw new CipherValidationException("La clave {$name} debe contener una o más letras A-Z.", $name);
        }

        return $key;
    }

    public static function normalizeSubstitutionKey(mixed $value): string
    {
        if (! is_string($value)) {
            throw new CipherValidationException('La clave de sustitución debe ser texto.', 'key');
        }

        $key = strtoupper(trim($value));

        if (strlen($key) !== 26 || preg_match('/^[A-Z]{26}$/', $key) !== 1 || count(array_unique(str_split($key))) !== 26) {
            throw new CipherValidationException('La clave de sustitución debe contener las 26 letras A-Z sin repetir.', 'key');
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

        return CipherText::characters($text);
    }

    /**
     * @param  callable(int): int  $transform
     */
    public static function transformLetters(string $text, callable $transform): string
    {
        $output = '';

        foreach (self::textCharacters($text) as $character) {
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

        throw new CipherValidationException('No existe inverso modular.', 'a');
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
