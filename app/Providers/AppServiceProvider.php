<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

/**
 * ============================================================================
 * PROVEEDOR PRINCIPAL DE SERVICIOS - AppServiceProvider
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Configura comportamientos globales al iniciar la aplicación:
 * 1. Fuerza enlaces seguros `https://` cuando el sistema corre en producción o Vercel.
 * 2. Activa `ATTR_EMULATE_PREPARES` en la conexión PDO para garantizar compatibilidad
 *    con poolers de conexiones PostgreSQL en la nube (como Supabase / PgBouncer).
 * ============================================================================
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de inyección de dependencias.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa servicios globales de la aplicación.
     */
    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            if (config('app.env') === 'production'
                || request()->header('x-forwarded-proto') === 'https'
                || request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
                || str_contains(request()->getHost(), 'vercel.app')) {
                URL::forceScheme('https');
            }
        }

        // Compatibilidad con pooler de transacciones PostgreSQL (Supabase / PgBouncer)
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\ConnectionEstablished::class, function ($event) {
            try {
                $event->connection->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            } catch (\Throwable $e) {
                // Ignorar si el driver actual no soporta este atributo PDO
            }
        });
    }
}
