<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ValidateTokenWithUserAgent;
use App\Http\Middleware\CookieToBearer;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\CookieToBearer::class);
        $middleware->alias([
            'agent' => ValidateTokenWithUserAgent::class,
            'ctb' => CookieToBearer::class,
            'cookie-to-bearer' => function ($request, $next) {
    Log::info('INLINE MIDDLEWARE WORKS!');
    return response()->json([
        'response_code' => 200,
        'status' => 'success',
        'message' => 'Inline Middleware: This is a test message from inline middleware.',
    ], 200);
},
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
