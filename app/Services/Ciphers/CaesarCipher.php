<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class CaesarCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $analyzer = new LanguageAnalyzer();
        $best = null;

        for ($shift = 0; $shift < 26; $shift++) {
            $plainText = $this->transform($cipherText, -$shift);
            $analysis = $analyzer->analyze($plainText);

            if ($best === null || $analysis['score'] > $best['score']) {
                $best = [
                    'plainText' => $plainText,
                    'recoveredKey' => (string) $shift,
                    ...$analysis,
                ];
            }
        }

        return $best;
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $shift = CipherSupport::integerParameter($parameters['shift'] ?? null, 'shift');

        return $this->payload($this->transform($text, -$shift), $shift, false, $includeSteps);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $shift = CipherSupport::integerParameter($parameters['shift'] ?? null, 'shift');

        return $this->payload($this->transform($text, $shift), $shift, true, $includeSteps);
    }

    public function id(): string
    {
        return 'caesar';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'César',
            'description' => 'Desplaza cada letra una cantidad fija de posiciones en el alfabeto.',
            'formula' => 'E(x) = (x + shift) mod 26',
            'parameters' => [[
                'name' => 'shift',
                'label' => 'Desplazamiento',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 3,
                'placeholder' => '3',
                'helpText' => 'El backend normaliza cualquier entero módulo 26.',
                'min' => 0,
                'max' => 25,
                'options' => null,
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, int $shift, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector();

        if ($includeSteps) {
            $collector->add(
                'Flujo de César',
                $encrypting ? 'Cada letra suma el desplazamiento indicado.' : 'Cada letra resta el desplazamiento indicado.',
                ['Posición', 'Entrada', 'Shift', 'Salida'],
                [['1', 'A', (string) CipherSupport::mod($shift), $this->transform('A', $encrypting ? $shift : -$shift)]],
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'Desplazamiento: '.CipherSupport::mod($shift),
            'explanation' => 'Los caracteres que no son letras ASCII se conservan sin modificar.',
            'steps' => $collector->all(),
        ];
    }

    private function transform(string $text, int $shift): string
    {
        return CipherSupport::transformLetters($text, static fn (int $index): int => $index + $shift);
    }
}
