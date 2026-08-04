<?php

namespace App\Http\Api;

use App\Http\Requests\BreakCipherRequest;
use App\Http\Requests\TransformCipherRequest;
use App\Models\User;
use App\Services\CipherOperationService;
use App\Services\CipherService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class CiphersController
{
    public function breakCipher(
        BreakCipherRequest $request,
        string $algorithm,
        CipherService $cipherService,
        CipherOperationService $operationService,
    ): JsonResponse {
        $startedAt = hrtime(true);
        $data = $request->validated();

        try {
            $result = $cipherService->breakCipher($algorithm, $data['text']);
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), 'algorithm');
        }

        /** @var User $user */
        $user = $request->user();
        $operationService->recordSuccess(
            $user,
            ['text' => $data['text'], 'parameters' => []],
            $result,
            $this->durationMs($startedAt),
        );

        return response()->json($result);
    }

    public function index(CipherService $cipherService): JsonResponse
    {
        return response()->json($cipherService->catalog());
    }

    public function transform(
        TransformCipherRequest $request,
        CipherService $cipherService,
        CipherOperationService $operationService,
    ): JsonResponse {
        $startedAt = hrtime(true);
        $data = $request->validated();
        $data['parameters'] ??= [];

        try {
            $result = $cipherService->transform(
                $data['algorithm'],
                $data['operation'],
                $data['text'],
                $data['parameters'],
                $data['includeSteps'],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), $this->errorField($exception->getMessage()));
        }

        /** @var User $user */
        $user = $request->user();
        $operationService->recordSuccess(
            $user,
            $data,
            $result,
            $this->durationMs($startedAt),
        );

        return response()->json($result);
    }

    private function durationMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }

    private function errorField(string $message): string
    {
        return match (true) {
            str_contains($message, 'multiplicador a') => 'a',
            str_contains($message, 'desplazamiento b') => 'b',
            str_contains($message, 'riel') => 'rails',
            str_contains($message, 'clave') => 'key',
            default => 'parameters',
        };
    }

    private function errorResponse(string $message, string $field): JsonResponse
    {
        return response()->json([
            'title' => 'No se pudo procesar la solicitud',
            'detail' => $message,
            'status' => 400,
            'errors' => [$field => [$message]],
        ], 400);
    }
}
