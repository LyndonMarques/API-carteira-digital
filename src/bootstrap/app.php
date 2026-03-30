<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Captura Violações de Regra de Negócio (ex: Saldo insuficiente)
        $exceptions->render(function (\DomainException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 422);
            }
        });

        // Captura Entradas Inválidas que passaram pelo Request mas falharam no DTO/Service
        $exceptions->render(function (\InvalidArgumentException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 400);
            }
        });

        // Captura o bloqueio do Rate Limiter (Redis/Throttle)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Limite de requisições excedido. Aguarde um momento e tente novamente.'
                ], 429); // 429 é o status code HTTP oficial para Too Many Requests
            }
        });
        

    })->create();
