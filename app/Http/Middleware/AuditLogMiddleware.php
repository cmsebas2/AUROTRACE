<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo logueamos si la petición fue exitosa y es una acción de firma/verificación
        // o si contiene un qa_user_id (firma de calidad)
        if ($response->getStatusCode() < 400 && ($request->has('qa_user_id') || $this->isCriticalRoute($request))) {
            
            $userId = $request->input('qa_user_id') ?? Auth::id();
            $action = $this->getMeaning($request);
            
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => $this->getModelType($request),
                'model_id' => $this->getModelId($request),
                'new_values' => json_encode($request->except(['password', 'password_confirmation', '_token'])),
                'ip_address' => $request->ip(),
                'reason' => $request->input('observations') ?? $request->input('reason'),
            ]);
        }

        return $response;
    }

    private function isCriticalRoute(Request $request): bool
    {
        $criticalPatterns = [
            'verify',
            'sign',
            'cerrar',
            'finish',
            'store'
        ];

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($request->route()?->getName() ?? '', $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getMeaning(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? 'Acción Desconocida';
        
        $meanings = [
            'batch.fabricacion.verify.dynamic' => 'Firma de Verificación QA - Paso de Fabricación',
            'batch.fabricacion.store.dynamic' => 'Firma de Operario - Paso de Fabricación',
            'batch.conciliacion.sign' => 'Firma de Conciliación de Materiales',
            'batch.despeje.store' => 'Firma de Despeje de Línea',
            'batch.qa.verification' => 'Firma de Verificación QA - Despeje de Línea',
            'batch.dispensacion.cerrar' => 'Firma de Cierre de Dispensación',
            'batch.envase.verify' => 'Firma de Verificación QA - Envase',
        ];

        return $meanings[$routeName] ?? "Ejecución: $routeName";
    }

    private function getModelType(Request $request): ?string
    {
        if ($request->route('batch')) {
            return 'App\Models\ProductionOrder';
        }
        return null;
    }

    private function getModelId(Request $request): ?int
    {
        $batch = $request->route('batch');
        if (is_object($batch)) {
            return $batch->id;
        }
        if (is_numeric($batch)) {
            return (int) $batch;
        }
        return null;
    }
}
