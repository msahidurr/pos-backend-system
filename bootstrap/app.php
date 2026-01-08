<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            \App\Http\Middleware\TenantMiddleware::class,
            \App\Http\Middleware\VerifyTenantAccess::class,
            \App\Http\Middleware\EnsureJsonResponse::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\RateLimitMiddleware::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'An error occurred',
                    'message' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        });
    })
    ->create();
