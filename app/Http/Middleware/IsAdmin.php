<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
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
