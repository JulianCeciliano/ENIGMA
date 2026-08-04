<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CipherOperationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HistoryPageController extends Controller
{
    public function index(Request $request, CipherOperationService $operationService): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('history/index', [
            'operations' => $operationService->paginateForUser($user, [
                'operation' => null,
                'algorithm' => null,
                'dateRange' => null,
                'search' => null,
                'perPage' => 15,
            ]),
        ]);
    }
}
