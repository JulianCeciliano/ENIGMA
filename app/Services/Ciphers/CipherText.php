<?php

declare(strict_types=1);

namespace App\Services\Ciphers;

use Closure;

final class CipherText
{
    public const Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public const MaximumStepRows = 160;

    public const MaximumMatrixColumns = 80;

    public static function mod(int $value, int $modulus = 26): int
    {
        if ($modulus < 1) {
            throw new CipherValidationException('El módulo debe ser positivo.', 'modulus');
        }

        return (($value % $modulus) + $modulus) % $modulus;
    }

    public static function isAsciiLetter(string $character): bool
    {
        if ($character === '') {
            return false;
        }

        $code = ord($character[0]);

        return ($code >= ord('A') && $code <= ord('Z'))
            || ($code >= ord('a') && $code <= ord('z'));
    }

    public static function isAsciiLetterOrDigit(string $character): bool
    {
        if (self::isAsciiLetter($character)) {
            return true;
        }

        if ($character === '') {
            return false;
        }

        $code = ord($character[0]);

        return $code >= ord('0') && $code <= ord('9');
    }

    public static function letterIndex(string $character): ?int
    {
        if ($character === '') {
            return null;
        }

        $code = ord($character[0]);
        if ($code >= ord('A') && $code <= ord('Z')) {
            return $code - ord('A');
        }

        if ($code >= ord('a') && $code <= ord('z')) {
            return $code - ord('a');
        }

        return null;
    }

    public static function fromIndex(int $index, string $originalCharacter): string
    {
        $result = chr(ord('A') + self::mod($index));

        if ($originalCharacter !== '') {
            $code = ord($originalCharacter[0]);
            if ($code >= ord('a') && $code <= ord('z')) {
                return strtolower($result);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function requireString(array $parameters, string $field, string $label): string
    {
        $value = $parameters[$field] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new CipherValidationException("{$label} es obligatorio.", $field);
        }

        return $value;
    }

    public static function normalizeLetters(?string $value, string $field, string $label): string
    {
        if ($value === null || trim($value) === '') {
            throw new CipherValidationException("{$label} es obligatorio.", $field);
        }

        $normalized = strtoupper(trim($value));
        if (preg_match('/\A[A-Z]+\z/D', $normalized) !== 1) {
            throw new CipherValidationException(
                "{$label} solo puede contener letras de A a Z.",
                $field,
            );
        }

        return $normalized;
    }

    public static function normalizeSubstitutionAlphabet(?string $value): string
    {
        $normalized = self::normalizeLetters($value, 'key', 'La clave de sustitución');
        if (strlen($normalized) !== strlen(self::Alphabet)) {
            throw new CipherValidationException(
                'La clave de sustitución debe tener exactamente 26 letras.',
                'key',
            );
        }

        if (count(array_unique(str_split($normalized))) !== strlen(self::Alphabet)) {
            throw new CipherValidationException(
                'La clave de sustitución debe ser una permutación sin letras repetidas.',
                'key',
            );
        }

        return $normalized;
    }

    public static function normalizeColumnarKey(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            throw new CipherValidationException('La clave columnar es obligatoria.', 'key');
        }

        $normalized = strtoupper(trim($value));
        if (strlen($normalized) < 2) {
            throw new CipherValidationException(
                'La clave columnar debe tener al menos 2 caracteres.',
                'key',
            );
        }

        if (preg_match('/\A[A-Z0-9]+\z/D', $normalized) !== 1) {
            throw new CipherValidationException(
                'La clave columnar solo puede contener letras de A a Z o dígitos.',
                'key',
            );
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function requireInteger(array $parameters, string $field, string $label): int
    {
        $value = $parameters[$field] ?? null;
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A[+-]?\d+\z/D', trim($value)) === 1) {
            $parsed = filter_var(trim($value), FILTER_VALIDATE_INT);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        throw new CipherValidationException("{$label} es obligatorio y debe ser un entero.", $field);
    }

    public static function isCoprime(int $first, int $second): bool
    {
        $first = abs($first);
        $second = abs($second);

        while ($second !== 0) {
            [$first, $second] = [$second, $first % $second];
        }

        return $first === 1;
    }

    public static function modularInverse(int $value, int $modulus = 26): int
    {
        $normalized = self::mod($value, $modulus);
        for ($candidate = 1; $candidate < $modulus; $candidate++) {
            if (self::mod($normalized * $candidate, $modulus) === 1) {
                return $candidate;
            }
        }

        throw new CipherValidationException(
            "No existe inverso modular para {$value} módulo {$modulus}.",
            'a',
        );
    }

    /**
     * @param  Closure(int): int  $transform
     */
    public static function transformLetters(string $text, Closure $transform): string
    {
        $output = '';
        foreach (self::characters($text) as $character) {
            $index = self::letterIndex($character);
            $output .= $index === null
                ? $character
                : self::fromIndex($transform($index), $character);
        }

        return $output;
    }

    /**
     * @return array<int, string>
     */
    public static function characters(string $text): array
    {
        if (preg_match('/[^\x00-\x7F]/', $text) !== 1) {
            return str_split($text);
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return $characters === false ? str_split($text) : $characters;
    }

    public static function displayCharacter(string $character): string
    {
        return match ($character) {
            ' ' => '[espacio]',
            "\t" => '[tab]',
            "\r" => '[retorno]',
            "\n" => '[salto]',
            default => $character,
        };
    }

    /**
     * @param  Closure(int): int  $transform
     * @return array{title: string, description: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public static function createAlphabetMappingStep(
        string $title,
        string $description,
        Closure $transform,
    ): array {
        $rows = [];
        foreach (str_split(self::Alphabet) as $character) {
            $index = self::letterIndex($character);
            $rows[] = [
                $character,
                self::fromIndex($transform($index ?? 0), $character),
            ];
        }

        return [
            'title' => $title,
            'description' => $description,
            'headers' => ['Entrada', 'Salida'],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Closure(string, int): array{key: string, output: string}  $process
     * @return array{title: string, description: string, headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public static function createLetterProcessStep(
        string $title,
        string $description,
        string $text,
        Closure $process,
    ): array {
        $rows = [];
        $letterPosition = 0;
        $characters = self::characters($text);

        foreach ($characters as $character) {
            if (count($rows) >= self::MaximumStepRows) {
                break;
            }

            if (self::letterIndex($character) === null) {
                $rows[] = [
                    (string) ($letterPosition + 1),
                    self::displayCharacter($character),
                    '-',
                    self::displayCharacter($character),
                ];
                continue;
            }

            $processed = $process($character, $letterPosition);
            $rows[] = [
                (string) ($letterPosition + 1),
                self::displayCharacter($character),
                self::displayCharacter($processed['key']),
                self::displayCharacter($processed['output']),
            ];
            $letterPosition++;
        }

        if (count($characters) > self::MaximumStepRows) {
            $description .= ' Se muestran como máximo '.self::MaximumStepRows.' filas.';
        }

        return [
            'title' => $title,
            'description' => $description,
            'headers' => ['Posición', 'Entrada', 'Clave', 'Salida'],
            'rows' => $rows,
        ];
    }

    public static function truncate(string $value, int $maximumLength = self::MaximumStepRows): string
    {
        $characters = self::characters($value);
        if (count($characters) <= $maximumLength) {
            return $value;
        }

        return implode('', array_slice($characters, 0, $maximumLength)).'...';
    }
}
