<?php

namespace App\Services\Ciphers;

use App\Services\Cryptanalysis\LanguageAnalyzer;

final class ColumnarTranspositionCipher implements ClassicCipher
{
    public function breakCipher(string $cipherText): array
    {
        $characters = CipherSupport::textCharacters($cipherText);
        $maximumColumns = min(8, max(2, count($characters)));
        $analyzer = new LanguageAnalyzer();
        $best = null;

        for ($columns = 2; $columns <= $maximumColumns; $columns++) {
            foreach ($this->permutations(range(0, $columns - 1)) as $order) {
                $plainText = $this->decryptWithOrder($characters, $columns, $order);
                $analysis = $analyzer->analyze($plainText);

                if ($best === null || $analysis['score'] > $best['score']) {
                    $best = [
                        'plainText' => $plainText,
                        'recoveredKey' => 'columnas='.$columns.'; orden='.implode(', ', array_map(static fn (int $index): int => $index + 1, $order)),
                        ...$analysis,
                    ];
                }
            }
        }

        return $best ?? [
            'plainText' => $cipherText,
            'recoveredKey' => 'columnas=2; orden=1, 2',
            'score' => -1_000_000.0,
            'idioma' => 'Espanol',
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function decrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeColumnKey($parameters['key'] ?? null);

        return $this->payload($this->decryptWithKey($text, $key), $key, false, $includeSteps);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function encrypt(string $text, array $parameters, bool $includeSteps = true): array
    {
        $key = CipherSupport::normalizeColumnKey($parameters['key'] ?? null);

        return $this->payload($this->encryptWithKey($text, $key), $key, true, $includeSteps);
    }

    public function id(): string
    {
        return 'columnar';
    }

    public function metadata(): array
    {
        return [
            'id' => $this->id(),
            'name' => 'Transposición Columnar',
            'description' => 'Escribe el texto por filas y lee las columnas según el orden de la clave.',
            'formula' => 'columnOrder(key) y lectura por columnas sin relleno',
            'parameters' => [[
                'name' => 'key',
                'label' => 'Clave de columnas',
                'type' => 'text',
                'required' => true,
                'defaultValue' => 'ZEBRAS',
                'placeholder' => 'A-Z o dígitos',
                'helpText' => 'Los empates se resuelven de izquierda a derecha.',
                'min' => 2,
                'max' => null,
                'options' => null,
            ]],
        ];
    }

    private function decryptWithKey(string $text, string $key): string
    {
        return $this->decryptWithOrder(CipherSupport::textCharacters($text), strlen($key), CipherSupport::columnOrder($key));
    }

    /**
     * @param array<int, string> $characters
     * @param array<int, int> $order
     */
    private function decryptWithOrder(array $characters, int $columns, array $order): string
    {
        $length = count($characters);
        $rowCount = (int) ceil($length / $columns);
        $remainder = $length % $columns;
        $columnData = array_fill(0, $columns, []);
        $offset = 0;

        foreach ($order as $column) {
            $columnLength = $remainder === 0 || $column < $remainder ? $rowCount : $rowCount - 1;
            $columnData[$column] = array_slice($characters, $offset, $columnLength);
            $offset += $columnLength;
        }

        $plainText = '';

        for ($row = 0; $row < $rowCount; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                if (isset($columnData[$column][$row])) {
                    $plainText .= $columnData[$column][$row];
                }
            }
        }

        return $plainText;
    }

    private function encryptWithKey(string $text, string $key): string
    {
        $characters = CipherSupport::textCharacters($text);
        $order = CipherSupport::columnOrder($key);
        $output = '';
        $columns = strlen($key);

        foreach ($order as $column) {
            for ($position = $column; $position < count($characters); $position += $columns) {
                $output .= $characters[$position];
            }
        }

        return $output;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $result, string $key, bool $encrypting, bool $includeSteps): array
    {
        $collector = new StepCollector();

        if ($includeSteps) {
            $order = CipherSupport::columnOrder($key);
            $collector->add(
                'Orden de columnas',
                'La clave se ordena alfabéticamente y los empates conservan su posición original.',
                ['Columna original', 'Clave', 'Orden de lectura'],
                array_map(
                    static fn (int $index, int $position): array => [(string) ($index + 1), $key[$index], (string) ($position + 1)],
                    $order,
                    array_keys($order),
                ),
            );
        }

        return [
            'result' => $result,
            'keyUsed' => $key,
            'explanation' => 'La transposición mueve todos los caracteres y conserva la longitud sin añadir relleno.',
            'steps' => $collector->all(),
        ];
    }

    /**
     * @param array<int, int> $items
     * @return array<int, array<int, int>>
     */
    private function permutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $permutations = [];

        foreach ($items as $index => $item) {
            $remaining = $items;
            unset($remaining[$index]);

            foreach ($this->permutations(array_values($remaining)) as $permutation) {
                $permutations[] = [$item, ...$permutation];
            }
        }

        return $permutations;
    }
}
