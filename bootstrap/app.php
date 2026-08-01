<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\AuditLogMiddleware::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            echo "<h2>AUROTRACE Fatal Bootstrap Error</h2>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>";
            echo "<pre style='background:#f4f4f4;padding:15px;border-radius:5px;overflow:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        });
    })->create();

/* Vercel Serverless Storage Bypass */
if (isset($_ENV['APP_STORAGE'])) {
    $app->useStoragePath($_ENV['APP_STORAGE']);
    $app->useBootstrapPath($_ENV['APP_STORAGE'] . '/bootstrap');
}

return $app;
