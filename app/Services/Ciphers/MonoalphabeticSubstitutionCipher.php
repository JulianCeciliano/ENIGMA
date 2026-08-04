<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class MonoalphabeticSubstitutionCipher implements ClassicCipher
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function breakCipher(string $cipherText): array
    {
        $analyzer = new LanguageAnalyzer();
        $mapping = $this->frequencyMapping($cipherText, $analyzer);
        $plainText = $this->decodeWithMapping($cipherText, $mapping);
        $analysis = $analyzer->analyze($plainText);

        return [
            'plainText' => $plainText,
            'recoveredKey' => $this->mappingToKey($mapping),
            ...$analysis,
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeSubstitutionKey($parameters['key'] ?? null);
        $inverse = array_fill(0, 26, 0);

        foreach (str_split($key) as $plainIndex => $cipherLetter) {
            $inverse[ord($cipherLetter) - ord('A')] = $plainIndex;
        }

        $result = CipherSupport::transformLetters($text, static fn (int $index): int => $inverse[$index]);

        return $this->payload($result, $key, false, $includeSteps);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeSubstitutionKey($parameters['key'] ?? null);
        $result = CipherSupport::transformLetters($text, static fn (int $index): int => ord($key[$index]) - ord('A'));

        return $this->payload($result, $key, true, $includeSteps);
    }

    public function id(): string
    {
        return 'substitution';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Sustitución monoalfabética',
            'description' => 'Reemplaza cada letra por la letra correspondiente de un alfabeto permutado.',
            'formula' => 'E(x) = key[x]',
            'parameters' => [[
                'name' => 'key',
                'label' => 'Alfabeto clave',
                'type' => 'text',
                'required' => true,
                'defaultValue' => 'QWERTYUIOPASDFGHJKLZXCVBNM',
                'placeholder' => '26 letras sin repetir',
                'helpText' => 'Debe ser una permutación completa de A-Z.',
                'min' => 26,
                'max' => 26,
                'options' => null,
            ]],
        ];
    }

    /**
     * @param array<int, int> $mapping
     */
    private function decodeWithMapping(string $text, array $mapping): string
    {
        return CipherSupport::transformLetters($text, static fn (int $index): int => $mapping[$index]);
    }

    /**
     * @return array<int, int>
     */
    private function frequencyMapping(string $text, LanguageAnalyzer $analyzer): array
    {
        $letters = $analyzer->lettersOnly($text);
        $counts = array_fill(0, 26, 0);

        foreach (str_split($letters) as $letter) {
            $counts[ord($letter) - ord('A')]++;
        }

        arsort($counts);
        $cipherOrder = array_keys($counts);
        $language = $analyzer->analyze($text)['idioma'];
        $frequencies = $language === 'Espanol'
            ? [4, 0, 0, 3, 4, 0, 0, 0, 8, 0, 0, 11, 12, 13, 14, 15, 0, 17, 18, 19, 20, 0, 0, 0, 0, 0]
            : [4, 19, 0, 3, 4, 0, 6, 7, 8, 0, 0, 11, 12, 13, 14, 15, 0, 17, 18, 19, 20, 0, 0, 0, 24, 0];
        arsort($frequencies);
        $plainOrder = array_keys($frequencies);
        $mapping = array_fill(0, 26, 0);

        foreach ($cipherOrder as $position => $cipherIndex) {
            $mapping[$cipherIndex] = $plainOrder[$position] ?? $position;
        }

        return $mapping;
    }

    /**
     * @param array<int, int> $mapping
     */
    private function mappingToKey(array $mapping): string
    {
        $key = array_fill(0, 26, 'A');

        foreach ($mapping as $cipherIndex => $plainIndex) {
            $key[$plainIndex] = chr(ord('A') + $cipherIndex);
        }

        return implode('', $key);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, string $key, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector();

        if ($includeSteps) {
            $collector->add(
                'Tabla de sustitución',
                $encrypting ? 'La clave define la letra cifrada para cada letra plana.' : 'Se utiliza la tabla inversa de la clave.',
                ['Plano', 'Cifrado'],
                array_map(
                    static fn (int $index): array => [self::ALPHABET[$index], $key[$index]],
                    range(0, 25),
                ),
                true,
            );
        }

        return [
            'result' => $result,
            'keyUsed' => $key,
            'explanation' => 'Los caracteres fuera de A-Z y a-z se conservan sin cambios.',
            'steps' => $collector->all(),
        ];
    }
}
