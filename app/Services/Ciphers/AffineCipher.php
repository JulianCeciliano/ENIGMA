<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class AffineCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $analyzer = new LanguageAnalyzer;
        $best = null;

        for ($a = 0; $a < 26; $a++) {
            if ($this->gcd($a, 26) !== 1) {
                continue;
            }

            for ($b = 0; $b < 26; $b++) {
                $plainText = $this->transform($cipherText, $a, $b, false);
                $analysis = $analyzer->analyze($plainText);

                if ($best === null || $analysis['score'] > $best['score']) {
                    $best = [
                        'plainText' => $plainText,
                        'recoveredKey' => "a={$a}, b={$b}",
                        ...$analysis,
                    ];
                }
            }
        }

        return $best ?? [
            'plainText' => $cipherText,
            'recoveredKey' => 'a=1, b=0',
            'score' => -1_000_000.0,
            'idioma' => 'Espanol',
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $a = CipherSupport::integerParameter($parameters['a'] ?? null, 'a');
        $b = CipherSupport::integerParameter($parameters['b'] ?? null, 'b');

        if ($this->gcd(CipherSupport::mod($a), 26) !== 1) {
            throw new CipherValidationException('El multiplicador a debe ser coprimo con 26.', 'a');
        }

        return $this->payload($this->transform($text, $a, $b, false), $a, $b, false, $includeSteps);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $a = CipherSupport::integerParameter($parameters['a'] ?? null, 'a');
        $b = CipherSupport::integerParameter($parameters['b'] ?? null, 'b');

        if ($this->gcd(CipherSupport::mod($a), 26) !== 1) {
            throw new CipherValidationException('El multiplicador a debe ser coprimo con 26.', 'a');
        }

        return $this->payload($this->transform($text, $a, $b, true), $a, $b, true, $includeSteps);
    }

    public function id(): string
    {
        return 'affine';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Afín',
            'description' => 'Combina multiplicación y desplazamiento modular sobre cada letra.',
            'formula' => 'E(x) = (a * x + b) mod 26',
            'parameters' => [
                [
                    'name' => 'a',
                    'label' => 'Multiplicador a',
                    'type' => 'number',
                    'required' => true,
                    'defaultValue' => 5,
                    'placeholder' => '5',
                    'helpText' => 'Debe ser coprimo con 26.',
                    'min' => 1,
                    'max' => 25,
                    'options' => null,
                ],
                [
                    'name' => 'b',
                    'label' => 'Desplazamiento b',
                    'type' => 'number',
                    'required' => true,
                    'defaultValue' => 8,
                    'placeholder' => '8',
                    'helpText' => 'Se normaliza módulo 26.',
                    'min' => 0,
                    'max' => 25,
                    'options' => null,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, int $a, int $b, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector;

        if ($includeSteps) {
            $collector->add(
                'Flujo Afín',
                $encrypting ? 'Se aplica a*x+b módulo 26.' : 'Se aplica el inverso modular de a.',
                ['Entrada', 'a', 'b', 'Salida'],
                [['A', (string) CipherSupport::mod($a), (string) CipherSupport::mod($b), $this->transform('A', $a, $b, $encrypting)]],
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'a='.CipherSupport::mod($a).', b='.CipherSupport::mod($b),
            'explanation' => 'El valor a debe tener inverso modular módulo 26.',
            'steps' => $collector->all(),
        ];
    }

    private function transform(string $text, int $a, int $b, bool $encrypting): string
    {
        $a = CipherSupport::mod($a);
        $b = CipherSupport::mod($b);
        $inverse = $encrypting ? null : CipherSupport::modularInverse($a);

        return CipherSupport::transformLetters($text, static function (int $index) use ($a, $b, $encrypting, $inverse): int {
            return $encrypting
                ? $a * $index + $b
                : $inverse * ($index - $b);
        });
    }

    private function gcd(int $left, int $right): int
    {
        while ($right !== 0) {
            [$left, $right] = [$right, $left % $right];
        }

        return abs($left);
    }
}
