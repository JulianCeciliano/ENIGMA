<?php

namespace App\Services;

use App\Models\CipherOperation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class CipherOperationService
{
    /**
     * @param array<string, mixed> $requestData
     * @param array<string, mixed> $result
     */
    public function recordSuccess(User $user, array $requestData, array $result, int $durationMs): CipherOperation
    {
        $input = (string) $requestData['text'];
        $output = (string) ($result['result'] ?? $result['plainText'] ?? '');

        return CipherOperation::query()->create([
            'user_id' => $user->id,
            'operation' => $result['operation'] ?? 'break',
            'algorithm' => $result['algorithm'],
            'status' => 'success',
            'input_text' => $input,
            'output_text' => $output,
            'parameters' => $requestData['parameters'] ?? [],
            'input_hash' => hash('sha256', $input),
            'output_hash' => hash('sha256', $output),
            'recovered_key' => $result['recoveredKey'] ?? null,
            'score' => $result['score'] ?? null,
            'idioma' => $result['idioma'] ?? null,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param array<string, mixed> $requestData
     */
    public function recordFailure(User $user, array $requestData, string $algorithm, string $operation, string $message, int $durationMs): CipherOperation
    {
        $input = (string) ($requestData['text'] ?? $requestData['cipherText'] ?? '');

        return CipherOperation::query()->create([
            'user_id' => $user->id,
            'operation' => $operation,
            'algorithm' => $algorithm,
            'status' => 'failed',
            'input_text' => $input,
            'output_text' => null,
            'parameters' => $requestData['parameters'] ?? [],
            'input_hash' => hash('sha256', $input),
            'output_hash' => null,
            'recovered_key' => null,
            'score' => null,
            'idioma' => null,
            'duration_ms' => $durationMs,
            'error_message' => $message,
        ]);
    }

    public function clearForUser(User $user): void
    {
        CipherOperation::query()->whereBelongsTo($user)->delete();
    }

    /**
     * @param array{operation?: ?string, algorithm?: ?string, dateRange?: ?string, search?: ?string, perPage?: int} $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForUser(User $user, array $filters): LengthAwarePaginator
    {
        $query = CipherOperation::query()->whereBelongsTo($user);

        $this->applyFilters($query, $filters);

        $perPage = min(50, max(1, (int) ($filters['perPage'] ?? 15)));

        return $query->latest()->paginate($perPage)->through(
            fn (CipherOperation $operation): array => $this->toArray($operation),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(CipherOperation $operation): array
    {
        $input = (string) $operation->input_text;
        $output = $operation->output_text === null ? null : (string) $operation->output_text;

        return [
            'id' => $operation->id,
            'timestamp' => $operation->created_at?->toISOString(),
            'operation' => strtoupper((string) $operation->operation),
            'algorithm' => $operation->algorithm,
            'status' => strtoupper((string) $operation->status),
            'payloadPreview' => mb_substr($output ?? $input, 0, 96),
            'inputHash' => $operation->input_hash,
            'outputHash' => $operation->output_hash,
            'inputText' => $input,
            'outputText' => $output,
            'parameters' => $operation->parameters,
            'recoveredKey' => $operation->recovered_key,
            'score' => $operation->score,
            'idioma' => $operation->idioma,
            'durationMs' => $operation->duration_ms,
            'errorMessage' => $operation->error_message,
        ];
    }

    /**
     * @param Builder<CipherOperation> $query
     * @param array{operation?: ?string, algorithm?: ?string, dateRange?: ?string, search?: ?string} $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (($filters['operation'] ?? null) !== null && $filters['operation'] !== 'all') {
            $query->where('operation', strtolower((string) $filters['operation']));
        }

        if (($filters['algorithm'] ?? null) !== null && $filters['algorithm'] !== 'all') {
            $query->where('algorithm', strtolower((string) $filters['algorithm']));
        }

        $dateRange = $filters['dateRange'] ?? null;

        if (in_array($dateRange, ['24h', '7d', '30d'], true)) {
            $since = match ($dateRange) {
                '24h' => Carbon::now()->subHours(24),
                '7d' => Carbon::now()->subDays(7),
                default => Carbon::now()->subDays(30),
            };

            $query->where('created_at', '>=', $since);
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('input_hash', 'like', $search.'%')
                    ->orWhere('output_hash', 'like', $search.'%');
            });
        }
    }
}
