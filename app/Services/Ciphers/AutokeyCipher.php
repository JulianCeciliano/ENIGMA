<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class AutokeyCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $letters = (new LanguageAnalyzer())->lettersOnly($cipherText);

        if ($letters === '') {
            return [
                'plainText' => $cipherText,
                'recoveredKey' => '',
                'score' => -1_000_000.0,
                'idioma' => 'Espanol',
            ];
        }

        $analyzer = new LanguageAnalyzer();
        $best = null;
        $maximumSeedLength = min(12, strlen($letters));

        for ($length = 1; $length <= $maximumSeedLength; $length++) {
            foreach (['Espanol', 'Ingles'] as $language) {
                $seed = $this->initialSeed($letters, $length, $language);
                $plainText = $this->transform($cipherText, $seed, false);
                $score = $analyzer->score($plainText, $language);

                for ($pass = 0; $pass < 2; $pass++) {
                    $improved = false;

                    for ($position = 0; $position < $length; $position++) {
                        $bestCharacter = $seed[$position];
                        $bestScore = $score;

                        for ($candidate = 0; $candidate < 26; $candidate++) {
                            $candidateSeed = substr_replace($seed, chr(ord('A') + $candidate), $position, 1);
                            $candidatePlain = $this->transform($cipherText, $candidateSeed, false);
                            $candidateScore = $analyzer->score($candidatePlain, $language);

                            if ($candidateScore > $bestScore) {
                                $bestCharacter = chr(ord('A') + $candidate);
                                $bestScore = $candidateScore;
                            }
                        }

                        if ($bestCharacter !== $seed[$position]) {
                            $seed = substr_replace($seed, $bestCharacter, $position, 1);
                            $score = $bestScore;
                            $improved = true;
                        }
                    }

                    if (! $improved) {
                        break;
                    }
                }

                $plainText = $this->transform($cipherText, $seed, false);

                if ($best === null || $score > $best['score']) {
                    $best = [
                        'plainText' => $plainText,
                        'recoveredKey' => $seed,
                        'score' => $score,
                        'idioma' => $language,
                    ];
                }
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
        $key = CipherSupport::normalizeLettersKey($parameters['key'] ?? null);

        return $this->payload($this->transform($text, $key, false), $key, false, $includeSteps);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeLettersKey($parameters['key'] ?? null);

        return $this->payload($this->transform($text, $key, true), $key, true, $includeSteps);
    }

    public function id(): string
    {
        return 'autokey';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Autokey (autoclave)',
            'description' => 'Usa una semilla y continúa con el texto plano recuperado.',
            'formula' => 'E(P_i) = (P_i + K_i) mod 26',
            'parameters' => [[
                'name' => 'key',
                'label' => 'Semilla',
                'type' => 'text',
                'required' => true,
                'defaultValue' => 'QUEEN',
                'placeholder' => 'Una palabra de A a Z',
                'helpText' => 'Después de la semilla se usa el texto plano.',
                'min' => null,
                'max' => null,
                'options' => null,
            ]],
        ];
    }

    private function initialSeed(string $letters, int $length, string $language): string
    {
        $expected = $language === 'Espanol' ? 'EAOSRN' : 'ETAOIN';
        $seed = '';

        for ($position = 0; $position < $length; $position++) {
            $cipherIndex = ord($letters[$position] ?? 'A') - ord('A');
            $plainIndex = ord($expected[$position % strlen($expected)]) - ord('A');
            $seed .= chr(ord('A') + CipherSupport::mod($cipherIndex - $plainIndex));
        }

        return $seed;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, string $key, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector();

        if ($includeSteps) {
            $collector->add(
                'Flujo Autokey',
                'La semilla se consume primero y después se usa el texto plano.',
                ['Posición', 'Entrada', 'Clave efectiva', 'Salida'],
                [['1', 'A', $key[0], $this->transform('A', $key, $encrypting)]],
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'Semilla: '.$key,
            'explanation' => 'Los caracteres que no son letras ASCII no consumen posiciones de la clave efectiva.',
            'steps' => $collector->all(),
        ];
    }

    private function transform(string $text, string $seed, bool $encrypting): string
    {
        $output = '';
        $plainLetters = [];
        $position = 0;
        $seedLength = strlen($seed);

        foreach (str_split($text) as $character) {
            $index = CipherSupport::letterIndex($character);

            if ($index === null) {
                $output .= $character;
                continue;
            }

            $keyIndex = $position < $seedLength
                ? ord($seed[$position]) - ord('A')
                : ($encrypting ? $plainLetters[$position - $seedLength] : $plainLetters[$position - $seedLength]);
            $plainIndex = $encrypting
                ? CipherSupport::mod($index + $keyIndex)
                : CipherSupport::mod($index - $keyIndex);
            $plainLetters[] = $encrypting ? $index : $plainIndex;
            $output .= CipherSupport::characterFromIndex($plainIndex, $character);
            $position++;
        }

        return $output;
    }
}
