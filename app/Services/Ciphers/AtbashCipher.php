<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class AtbashCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $plainText = $this->transform($cipherText);
        $analysis = (new LanguageAnalyzer())->analyze($plainText);

        return [
            'plainText' => $plainText,
            'recoveredKey' => 'Atbash fijo',
            ...$analysis,
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        return $this->payload($this->transform($text), $includeSteps);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        return $this->payload($this->transform($text), $includeSteps);
    }

    public function id(): string
    {
        return 'atbash';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Atbash',
            'description' => 'Sustituye cada letra por su posición espejo en el alfabeto.',
            'formula' => 'E(x) = 25 - x',
            'parameters' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, bool $includeSteps): array
    {
        $collector = new StepCollector();

        if ($includeSteps) {
            $collector->add(
                'Mapeo Atbash',
                'Cada letra se reemplaza por su equivalente espejo.',
                ['Entrada', 'Salida'],
                array_map(
                    static fn (int $index): array => [chr(ord('A') + $index), chr(ord('Z') - $index)],
                    range(0, 25),
                ),
                true,
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'Atbash fijo',
            'explanation' => 'Atbash es involutivo: la misma transformación cifra y descifra.',
            'steps' => $collector->all(),
        ];
    }

    private function transform(string $text): string
    {
        return CipherSupport::transformLetters($text, static fn (int $index): int => 25 - $index);
    }
}
