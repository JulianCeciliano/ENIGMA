<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\PolyalphabeticCryptanalysis;

final class VigenereCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $result = (new PolyalphabeticCryptanalysis)->break($cipherText);

        return [
            'plainText' => $result['plainText'],
            'recoveredKey' => $result['key'],
            'score' => $result['score'],
            'idioma' => $result['idioma'],
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeLettersKey($parameters['key'] ?? null);

        return $this->payload($text, $this->transform($text, $key, false), $key, false, $includeSteps);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeLettersKey($parameters['key'] ?? null);

        return $this->payload($text, $this->transform($text, $key, true), $key, true, $includeSteps);
    }

    public function id(): string
    {
        return 'vigenere';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Vigenère',
            'description' => 'Aplica desplazamientos diferentes siguiendo una palabra clave repetida.',
            'formula' => 'E(P_i, K_i) = (P_i + K_i) mod 26',
            'parameters' => [[
                'name' => 'key',
                'label' => 'Palabra clave',
                'type' => 'text',
                'required' => true,
                'defaultValue' => 'LEMON',
                'placeholder' => 'Una palabra de A a Z',
                'helpText' => 'La clave se repite únicamente sobre las letras del texto.',
                'min' => null,
                'max' => null,
                'options' => null,
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $input, string $result, string $key, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector;

        if ($includeSteps) {
            $collector->add(
                'Flujo de Vigenère',
                $encrypting ? 'Cada letra suma el valor de la letra correspondiente de la clave.' : 'Cada letra resta el valor de la letra correspondiente de la clave.',
                ['Posición', 'Entrada', 'Clave', 'Salida'],
                $this->stepRows($input, $key, $encrypting),
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'Palabra clave: '.$key,
            'explanation' => 'Los espacios y signos no consumen posiciones de la clave.',
            'steps' => $collector->all(),
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function stepRows(string $text, string $key, bool $encrypting): array
    {
        $rows = [];
        $position = 0;

        foreach (CipherSupport::textCharacters($text) as $index => $character) {
            if (count($rows) >= 160) {
                break;
            }

            if (CipherSupport::letterIndex($character) === null) {
                $rows[] = [(string) ($index + 1), $character, '-', $character];

                continue;
            }

            $keyCharacter = $key[$position % strlen($key)];
            $rows[] = [
                (string) ($index + 1),
                $character,
                $keyCharacter,
                $this->transformCharacterAtPosition($character, $key, $position, $encrypting),
            ];
            $position++;
        }

        return $rows;
    }

    private function transform(string $text, string $key, bool $encrypting): string
    {
        $output = '';
        $position = 0;
        $keyLength = strlen($key);

        foreach (CipherSupport::textCharacters($text) as $character) {
            $index = CipherSupport::letterIndex($character);

            if ($index === null) {
                $output .= $character;

                continue;
            }

            $keyIndex = ord($key[$position % $keyLength]) - ord('A');
            $output .= CipherSupport::characterFromIndex(
                $encrypting ? $index + $keyIndex : $index - $keyIndex,
                $character,
            );
            $position++;
        }

        return $output;
    }

    private function transformCharacterAtPosition(
        string $character,
        string $key,
        int $position,
        bool $encrypting,
    ): string {
        $index = CipherSupport::letterIndex($character);
        $keyIndex = ord($key[$position % strlen($key)]) - ord('A');

        return CipherSupport::characterFromIndex(
            $encrypting ? $index + $keyIndex : $index - $keyIndex,
            $character,
        );
    }
}
