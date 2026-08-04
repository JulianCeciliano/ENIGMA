<?php

declare(strict_types=1);

namespace App\Services\Cryptanalysis;

use App\Services\Ciphers\CipherText;

final class CryptanalysisHelpers
{
    public static function createDeterministicRandom(string $text, int $salt): DeterministicRandom
    {
        $hash = 2166136261;
        $bytes = unpack('C*', $text) ?: [];
        foreach ($bytes as $byte) {
            $hash = (($hash ^ $byte) * 16777619) & 0xFFFFFFFF;
        }

        $saltHash = ($salt * 2246822519) & 0xFFFFFFFF;
        $hash = ($hash ^ $saltHash) & 0xFFFFFFFF;

        return new DeterministicRandom($hash & 0x7FFFFFFF);
    }

    /**
     * @param  array<int, int>  $mapping
     */
    public static function applyMapping(string $text, array $mapping): string
    {
        return CipherText::transformLetters(
            $text,
            static fn (int $index): int => $mapping[$index],
        );
    }

    /**
     * @param  array<int, int>  $cipherToPlain
     */
    public static function mappingToSubstitutionAlphabet(array $cipherToPlain): string
    {
        $plainToCipher = array_fill(0, strlen(CipherText::Alphabet), '');
        foreach ($cipherToPlain as $cipherIndex => $plainIndex) {
            $plainToCipher[$plainIndex] = chr(ord('A') + $cipherIndex);
        }

        return implode('', $plainToCipher);
    }

    public static function normalizePeriodicKey(string $key): string
    {
        $keyLength = strlen($key);
        for ($period = 1; $period <= intdiv($keyLength, 2); $period++) {
            if ($keyLength % $period !== 0) {
                continue;
            }

            $candidate = substr($key, 0, $period);
            if (str_repeat($candidate, intdiv($keyLength, $period)) === $key) {
                return $candidate;
            }
        }

        return $key;
    }

    /**
     * @param  array<int, int>  $order
     */
    public static function orderToSyntheticKey(array $order): string
    {
        $key = array_fill(0, count($order), '');
        foreach ($order as $rank => $column) {
            $key[$column] = chr(ord('A') + $rank);
        }

        return implode('', $key);
    }

    /**
     * @param  array<int, int>  $order
     */
    public static function describeOrder(array $order): string
    {
        return implode(', ', array_map(
            static fn (int $column): string => (string) ($column + 1),
            $order,
        ));
    }

    /**
     * @param  array<int, mixed>  $values
     */
    public static function shuffle(array &$values, DeterministicRandom $random): void
    {
        for ($index = count($values) - 1; $index > 0; $index--) {
            $swapIndex = $random->nextInt($index + 1);
            [$values[$index], $values[$swapIndex]] = [$values[$swapIndex], $values[$index]];
        }
    }

    /**
     * @return iterable<int, array<int, int>>
     */
    public static function permutations(int $length): iterable
    {
        if ($length < 0) {
            return;
        }

        yield from self::permute(range(0, $length - 1), 0);
    }

    /**
     * @param  array<int, int>  $values
     * @return iterable<int, array<int, int>>
     */
    private static function permute(array $values, int $position): iterable
    {
        if ($position === count($values)) {
            yield $values;

            return;
        }

        for ($index = $position; $index < count($values); $index++) {
            [$values[$position], $values[$index]] = [$values[$index], $values[$position]];
            yield from self::permute($values, $position + 1);
            [$values[$position], $values[$index]] = [$values[$index], $values[$position]];
        }
    }
}
