<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
         $middleware->alias([
        'role' => RoleMiddleware::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
            // ❌ Unauthenticated (Sanctum)
    $exceptions->render(function (
        \Illuminate\Auth\AuthenticationException $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
    });

    // 🚫 Unauthorized (Role / Permission)
    $exceptions->render(function (
        \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: insufficient permission'
            ], 403);
        }
    });

    // ❓ Model Not Found
    $exceptions->render(function (
        \Illuminate\Database\Eloquent\ModelNotFoundException $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }
    });

    // 🧪 Validation Errors
    $exceptions->render(function (
        \Illuminate\Validation\ValidationException $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    });

    // 💥 Fallback (500)
    $exceptions->render(function (
        \Throwable $e,
        $request
    ) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    });


    })->create();
