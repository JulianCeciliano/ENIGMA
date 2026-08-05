<?php

namespace App\Http\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SessionController
{
    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
