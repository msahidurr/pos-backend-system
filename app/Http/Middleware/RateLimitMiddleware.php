<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'api:' . auth()->id() ?? $request->ip();

        if (RateLimiter::tooManyAttempts($key, 100, 1)) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 1);

        return $next($request);
    }
}
