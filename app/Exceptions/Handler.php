<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * Exceptions that should not be reported.
     */
    protected $dontReport = [
        AuthenticationException::class,
    ];

    /**
     * Register exception handling callbacks.
     */
    public function register(): void
    {
        // Model not found
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Resource not found',
                ], 404);
            }
        });

        // Validation errors
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // HTTP exceptions (403, 404, 500, etc.)
        $this->renderable(function (HttpExceptionInterface $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage() ?: 'Server error',
                ], $e->getStatusCode());
            }
        });
    }
}
