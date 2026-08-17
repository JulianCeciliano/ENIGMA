<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\PolyalphabeticCryptanalysis;

final class BeaufortCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $result = (new PolyalphabeticCryptanalysis)->break($cipherText, true);

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

        return $this->payload($this->transform($text, $key), $key, $includeSteps);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeLettersKey($parameters['key'] ?? null);

        return $this->payload($this->transform($text, $key), $key, $includeSteps);
    }

    public function id(): string
    {
        return 'beaufort';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Beaufort',
            'description' => 'Calcula la diferencia entre la clave repetida y cada letra del texto.',
            'formula' => 'C = (K - P) mod 26',
            'parameters' => [[
                'name' => 'key',
                'label' => 'Palabra clave',
                'type' => 'text',
                'required' => true,
                'defaultValue' => 'FUERTE',
                'placeholder' => 'Una palabra de A a Z',
                'helpText' => 'La misma operación cifra y descifra.',
                'min' => null,
                'max' => null,
                'options' => null,
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, string $key, bool $includeSteps): array
    {
        $collector = new StepCollector;

        if ($includeSteps) {
            $collector->add(
                'Flujo de Beaufort',
                'La salida se calcula como clave menos entrada módulo 26.',
                ['Posición', 'Entrada', 'Clave', 'Salida'],
                [['1', 'A', $key[0], $this->transform('A', $key)]],
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'Palabra clave: '.$key,
            'explanation' => 'Beaufort es involutivo y los caracteres no ASCII no avanzan la clave.',
            'steps' => $collector->all(),
        ];
    }

    private function transform(string $text, string $key): string
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
            $output .= CipherSupport::characterFromIndex($keyIndex - $index, $character);
            $position++;
        }

        return $output;
    }
}
