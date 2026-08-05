<?php

declare(strict_types=1);

namespace App\Services\Cryptanalysis;

use InvalidArgumentException;

/**
 * Small deterministic generator used by heuristic searches.
 *
 * It intentionally is not compatible with .NET Random. Its purpose is stable
 * PHP results for the same input, not cryptographic randomness.
 */
final class DeterministicRandom
{
    private const Mask = 0xFFFFFFFF;

    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed & self::Mask;
        if ($this->state === 0) {
            $this->state = 0x6D2B79F5;
        }
    }

    public function nextInt(int $maximum): int
    {
        if ($maximum < 1) {
            throw new InvalidArgumentException('The random upper bound must be positive.');
        }

        return $this->nextUnsigned() % $maximum;
    }

    public function nextFloat(): float
    {
        return $this->nextUnsigned() / 4294967296.0;
    }

    private function nextUnsigned(): int
    {
        $state = $this->state;
        $state ^= ($state << 13) & self::Mask;
        $state ^= $state >> 17;
        $state ^= ($state << 5) & self::Mask;
        $this->state = $state & self::Mask;

        return $this->state;
    }
}
