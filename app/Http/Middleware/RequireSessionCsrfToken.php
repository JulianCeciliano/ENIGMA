<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

final class RequireSessionCsrfToken extends PreventRequestForgery
{
    public function handle($request, Closure $next): mixed
    {
        if ($request->user('web') === null && $request->user('api') !== null) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
