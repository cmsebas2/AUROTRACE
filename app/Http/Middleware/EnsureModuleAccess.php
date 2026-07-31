<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Restricción para Analista de Producción
        if ($user->hasRole('Analista de Producción')) {
            // Permitir logout siempre
            if ($request->is('logout')) {
                return $next($request);
            }

            // Redirigir Dashboard principal al de Reacondicionamiento
            if ($request->is('dashboard')) {
                return redirect()->route('reconditioning.dashboard');
            }

            // Bloquear cualquier cosa que no sea Reacondicionamiento o su Dashboard
            if (!$request->is('reacondicionamiento*')) {
                abort(403, 'Acceso Denegado: Su perfil está restringido exclusivamente al área de Reacondicionamiento.');
            }
        }

        return $next($request);
    }
}
