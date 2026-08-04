<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class Rot13Cipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $plainText = $this->transform($cipherText);
        $analysis = (new LanguageAnalyzer())->analyze($plainText);

        return [
            'plainText' => $plainText,
            'recoveredKey' => '13',
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
        return 'rot13';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'ROT13',
            'description' => 'Aplica un desplazamiento fijo de 13 posiciones.',
            'formula' => 'E(x) = (x + 13) mod 26',
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
                'Flujo ROT13',
                'ROT13 es involutivo y utiliza siempre la clave 13.',
                ['Entrada', 'Salida'],
                [['A', 'N'], ['B', 'O'], ['C', 'P']],
            );
        }

        return [
            'result' => $result,
            'keyUsed' => '13',
            'explanation' => 'La misma transformación sirve para cifrar y descifrar.',
            'steps' => $collector->all(),
        ];
    }

    private function transform(string $text): string
    {
        return CipherSupport::transformLetters($text, static fn (int $index): int => $index + 13);
    }
}
