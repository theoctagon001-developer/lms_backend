<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies for Render deployment
        $middleware->web(append: [
            \Illuminate\Http\Middleware\TrustProxies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return detailed error messages for API routes even in production
        $exceptions->render(function (Throwable $e, $request) {
            // Only return detailed errors for API routes
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = 500;
                // Try to get status code from exception
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                } elseif (method_exists($e, 'getCode')) {
                    $code = $e->getCode();
                    if ($code >= 400 && $code < 600) {
                        $statusCode = $code;
                    }
                }
                
                // Log the full error for debugging
                Log::error('API Error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], $statusCode);
            }
        });
    })->create();
