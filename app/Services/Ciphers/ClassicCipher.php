<?php

namespace App\Services\Ciphers;

interface ClassicCipher
{
    /**
     * @return array<string, mixed>
     */
    public function breakCipher(string $cipherText): array;

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array;

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array;

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array;

    public function id(): string;
}
