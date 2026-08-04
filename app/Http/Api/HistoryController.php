<?php

namespace App\Http\Api;

use App\Models\User;
use App\Services\CipherOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HistoryController
{
    public function clear(Request $request, CipherOperationService $operationService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('api');
        $operationService->clearForUser($user);

        return response()->json(['message' => 'Historial eliminado correctamente.']);
    }

    public function index(Request $request, CipherOperationService $operationService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('api');
        $operations = $operationService->paginateForUser($user, [
            'operation' => $request->string('operation')->toString() ?: null,
            'algorithm' => $request->string('algorithm')->toString() ?: null,
            'dateRange' => $request->string('dateRange')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
            'perPage' => $request->integer('perPage', 15),
        ]);

        return response()->json($operations);
    }
}
