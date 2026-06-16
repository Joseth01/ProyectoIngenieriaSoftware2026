<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API 100% stateless basada en tokens de Sanctum (Authorization: Bearer).
        // No se usa el flujo SPA con cookies/CSRF, por eso NO se registra
        // EnsureFrontendRequestsAreStateful: ese middleware trataba el origen
        // 'localhost' del WebView de Capacitor como dominio stateful y exigía
        // un token CSRF, rompiendo el login desde el APK ("CSRF token mismatch").
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();