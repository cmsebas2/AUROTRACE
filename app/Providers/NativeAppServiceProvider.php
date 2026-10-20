<?php

namespace App\Providers;

/**
 * ============================================================================
 * PROVEEDOR DE APLICACIÓN DE ESCRITORIO (NativePHP) - NativeAppServiceProvider
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Configuración opcional para ejecutar AUROTRACE como ventana de escritorio nativa
 * mediante NativePHP. Protegido con `class_exists` para no generar errores si
 * el paquete `nativephp/laravel` no está instalado en el entorno web.
 * ============================================================================
 */
class NativeAppServiceProvider
{
    /**
     * Se ejecuta cuando la aplicación nativa de escritorio arranca.
     */
    public function boot(): void
    {
        if (class_exists(\Native\Laravel\Facades\Window::class)) {
            \Native\Laravel\Facades\Window::open()
                ->width(1280)
                ->height(720)
                ->title('Aurofarma ERP - Sistema de Manufactura')
                ->showDevTools(false);
        }
    }

    /**
     * Directivas php.ini personalizadas para el entorno de escritorio.
     */
    public function phpIni(): array
    {
        return [];
    }
}
