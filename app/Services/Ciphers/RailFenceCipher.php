<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class RailFenceCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $analyzer = new LanguageAnalyzer;
        $characters = CipherSupport::textCharacters($cipherText);
        $maximumRails = min(20, max(2, count($characters)));
        $best = null;

        for ($rails = 2; $rails <= $maximumRails; $rails++) {
            $plainText = $this->decryptText($cipherText, $rails);
            $analysis = $analyzer->analyze($plainText);

            if ($best === null || $analysis['score'] > $best['score']) {
                $best = [
                    'plainText' => $plainText,
                    'recoveredKey' => (string) $rails,
                    ...$analysis,
                ];
            }
        }

        return $best;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $rails = $this->rails($parameters['rails'] ?? null);

        return $this->payload($this->decryptText($text, $rails), $rails, false, $includeSteps);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $rails = $this->rails($parameters['rails'] ?? null);

        return $this->payload($this->encryptText($text, $rails), $rails, true, $includeSteps);
    }

    public function id(): string
    {
        return 'rail-fence';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Rail Fence',
            'description' => 'Escribe el texto en zigzag sobre varios rieles y lee cada riel completo.',
            'formula' => 'rail = 0, 1, ..., rails - 1, ..., 1, 0',
            'parameters' => [[
                'name' => 'rails',
                'label' => 'Número de rieles',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 3,
                'placeholder' => '3',
                'helpText' => 'Debe estar entre 2 y 100.',
                'min' => 2,
                'max' => 100,
                'options' => null,
            ]],
        ];
    }

    private function decryptText(string $text, int $rails): string
    {
        $characters = CipherSupport::textCharacters($text);
        $pattern = $this->pattern(count($characters), $rails);
        $counts = array_count_values($pattern);
        $railCharacters = array_fill(0, $rails, []);
        $offset = 0;

        for ($rail = 0; $rail < $rails; $rail++) {
            $railCharacters[$rail] = array_slice($characters, $offset, $counts[$rail] ?? 0);
            $offset += $counts[$rail] ?? 0;
        }

        $positions = array_fill(0, $rails, 0);
        $plainText = '';

        foreach ($pattern as $rail) {
            $plainText .= $railCharacters[$rail][$positions[$rail]++] ?? '';
        }

        return $plainText;
    }

    private function encryptText(string $text, int $rails): string
    {
        $characters = CipherSupport::textCharacters($text);
        $railCharacters = array_fill(0, $rails, []);

        foreach ($this->pattern(count($characters), $rails) as $position => $rail) {
            $railCharacters[$rail][] = $characters[$position];
        }

        return implode('', array_merge(...$railCharacters));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, int $rails, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector;

        if ($includeSteps) {
            $length = min(count(CipherSupport::textCharacters($result)), 160);
            $pattern = $this->pattern($length, $rails);

            $collector->add(
                'Patrón Rail Fence',
                $encrypting ? 'Los caracteres se distribuyen siguiendo un patrón zigzag.' : 'Se reconstruyen los rieles y se recorre el zigzag original.',
                ['Posición', 'Riel'],
                array_map(
                    static fn (int $position, int $rail): array => [(string) ($position + 1), (string) $rail],
                    array_keys($pattern),
                    $pattern,
                ),
            );
        }

        return [
            'result' => $result,
            'keyUsed' => 'Rieles: '.$rails,
            'explanation' => 'Rail Fence mueve todos los caracteres y no añade relleno.',
            'steps' => $collector->all(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function pattern(int $length, int $rails): array
    {
        if ($length === 0) {
            return [];
        }

        $pattern = [];
        $rail = 0;
        $direction = 1;

        for ($position = 0; $position < $length; $position++) {
            $pattern[] = $rail;

            if ($rail === 0) {
                $direction = 1;
            } elseif ($rail === $rails - 1) {
                $direction = -1;
            }

            $rail += $direction;
        }

        return $pattern;
    }

    private function rails(mixed $value): int
    {
        $rails = CipherSupport::integerParameter($value, 'rails');

        if ($rails < 2 || $rails > 100) {
            throw new CipherValidationException('El número de rieles debe estar entre 2 y 100.', 'rails');
        }

        return $rails;
    }
}
