<?php

namespace App\Services\Ciphers;

final class StepCollector
{
    private const MAX_COLUMNS = 80;

    private const MAX_ROWS = 160;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $steps = [];

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     */
    public function add(string $title, string $description, array $headers, array $rows, bool $preserveAllRows = false): void
    {
        $headers = array_slice($headers, 0, self::MAX_COLUMNS);
        $rows = $preserveAllRows ? $rows : array_slice($rows, 0, self::MAX_ROWS);
        $rows = array_map(
            static fn (array $row): array => array_slice($row, 0, self::MAX_COLUMNS),
            $rows,
        );

        $this->steps[] = [
            'title' => $title,
            'description' => $description,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->steps;
    }
}
