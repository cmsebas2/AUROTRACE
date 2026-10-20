<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================================
 * GUARDIÁN DE ADMINISTRACIÓN (IAM) - IsAdmin Middleware
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este guardia protege las páginas de Administración de Usuarios y Roles (/users).
 * Solo deja pasar al usuario si:
 * 1. Tiene sesión iniciada (auth()->check()), Y
 * 2. Posee el permiso 'gestionar_usuarios_roles' o 'gestionar_ajustes_sistema'.
 * Si no cumple, devuelve un error 403 (Acceso Denegado).
 * ============================================================================
 */
class IsAdmin
{
    /**
     * Verifica permisos administrativos antes de continuar con la petición.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (auth()->user()->hasPermission('gestionar_usuarios_roles') || auth()->user()->hasPermission('gestionar_ajustes_sistema'))) {
            return $next($request);
        }

        abort(403, 'Acceso Denegado (Restricción IAM). Nivel de Permiso Insuficiente.');
    }
}
