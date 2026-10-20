<?php

/**
 * ============================================================================
 * ARCHIVO DE ARRANQUE PRINCIPAL (BOOTSTRAP) - AUROTRACE ERP
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este archivo es el "interruptor de encendido" de toda la aplicación Laravel.
 * Cada vez que un usuario entra a una página o hace clic en un botón, la petición
 * pasa primero por aquí para configurar 4 cosas fundamentales:
 *
 * 1. RUTAS (withRouting): Le dice a Laravel dónde están las direcciones web (routes/web.php).
 * 2. MIDDLEWARES (withMiddleware): Son los "guardias de seguridad" que revisan cada petición:
 *    - AuditLogMiddleware: Registra automáticamente en la bitácora (AuditLog) las firmas y acciones críticas.
 *    - IsAdmin ('admin'): Bloquea el paso si un usuario sin permisos intenta entrar al panel de usuarios.
 * 3. EXCEPCIONES (withExceptions): Captura errores inesperados para mostrar un mensaje claro sin romper el flujo.
 * 4. ALMACENAMIENTO SERVERLESS (Vercel): Si el sistema corre en la nube (Vercel), redirige los archivos
 *    temporales a la carpeta permitida por el servidor.
 * ============================================================================
 */

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
        // Confiar en proxies inversos (necesario para obtener la IP real en despliegues como Vercel)
        $middleware->trustProxies(at: '*');

        // Agregar el middleware de auditoría 21 CFR Part 11 a todas las rutas web
        $middleware->web(append: [
            \App\Http\Middleware\AuditLogMiddleware::class,
        ]);

        // Alias de middlewares para proteger rutas específicas (ej: Route::middleware('admin'))
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Dejar que Laravel maneje de forma nativa los errores normales de flujo:
            // - Usuario no autenticado (redirige al login)
            // - Error de validación de formulario (regresa al formulario con los mensajes en rojo)
            // - Registro no encontrado (404), falta de permisos (403) o sesión expirada CSRF (419)
            if ($e instanceof \Illuminate\Auth\AuthenticationException ||
                $e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ||
                $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
                $e instanceof \Illuminate\Session\TokenMismatchException ||
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }

            // Si la petición viene de JavaScript (AJAX / Fetch / Axios), respondemos en formato JSON
            // para que la interfaz pueda mostrar la alerta sin romperse.
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage(),
                ], 500);
            }

            // Respuesta HTML diagnóstica para errores críticos inesperados
            $html = "<h2>AUROTRACE - Error del Sistema</h2>"
                  . "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>"
                  . "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . " (Línea " . $e->getLine() . ")</p>"
                  . "<pre style='background:#f4f4f4;padding:15px;border-radius:5px;overflow:auto;'>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";

            return response($html, 500)->header('Content-Type', 'text/html; charset=utf-8');
        });
    })->create();

/* Bypass de almacenamiento para entornos Serverless (Vercel) */
if (isset($_ENV['APP_STORAGE'])) {
    $app->useStoragePath($_ENV['APP_STORAGE']);
    $app->useBootstrapPath($_ENV['APP_STORAGE'] . '/bootstrap');
}

return $app;
