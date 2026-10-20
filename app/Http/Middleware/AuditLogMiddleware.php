<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Auth;

/**
 * ============================================================================
 * MIDDLEWARE DE AUDITORÍA AUTOMÁTICA (21 CFR Part 11) - AuditLogMiddleware
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Piensa en esta clase como una "cámara de seguridad" instalada en las puertas
 * más importantes del sistema.
 *
 * ¿Cuándo graba un registro en la tabla `audit_logs`?
 * - Cuando la acción fue exitosa (sin errores HTTP ni errores de validación).
 * - Y cuando la ruta corresponde a una firma, verificación de calidad (QA),
 *   cierre de etapa o guardado crítico del EBR.
 *
 * Seguridad:
 * - Nunca guarda contraseñas (`password`, `signature_password`, etc.) ni tokens CSRF.
 * ============================================================================
 */
class AuditLogMiddleware
{
    /**
     * Intercepta la petición HTTP y registra la auditoría tras completarse con éxito.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo auditamos métodos que modifican datos (POST, PUT, PATCH, DELETE)
        if ($request->isMethodSafe()) {
            return $response;
        }

        // Verificar que la respuesta haya sido verdaderamente exitosa (sin redirecciones por error ni JSON fallido)
        if ($this->isSuccessfulResponse($response) && ($request->has('qa_user_id') || $this->isCriticalRoute($request))) {
            $userId = $request->input('qa_user_id') ?? $request->input('on_behalf_of_id') ?? Auth::id();
            $action = $this->getMeaning($request);

            // Excluir SIEMPRE cualquier campo de contraseña o token de seguridad antes de guardar en BD
            $safePayload = $request->except([
                'password',
                'password_confirmation',
                'signature_password',
                'qa_password',
                'current_password',
                '_token',
                '_method',
            ]);

            try {
                AuditLog::create([
                    'user_id'    => $userId,
                    'action'     => $action,
                    'model_type' => $this->getModelType($request),
                    'model_id'   => $this->getModelId($request),
                    'new_values' => json_encode($safePayload),
                    'ip_address' => $request->ip(),
                    'reason'     => $request->input('observations') ?? $request->input('reason'),
                ]);
            } catch (\Throwable $e) {
                // Evita interrumpir la respuesta al usuario si ocurre un error no crítico de serialización
            }
        }

        return $response;
    }

    /**
     * Comprueba si la respuesta HTTP fue exitosa y no contiene errores de validación o credenciales.
     */
    private function isSuccessfulResponse(Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Si es una redirección (302) pero la sesión lleva errores de validación, la acción falló
        if ($response->isRedirection() && (session()->has('errors') || session()->has('error'))) {
            return false;
        }

        // Si es una respuesta JSON con {"success": false}, la firma o acción fue rechazada
        $content = $response->getContent();
        if (is_string($content) && str_starts_with(trim($content), '{')) {
            $decoded = json_decode($content, true);
            if (is_array($decoded) && array_key_exists('success', $decoded) && $decoded['success'] === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determina si el nombre de la ruta actual corresponde a una operación crítica auditable.
     */
    private function isCriticalRoute(Request $request): bool
    {
        $criticalPatterns = [
            'verify',
            'sign',
            'cerrar',
            'finish',
            'store',
        ];

        $routeName = $request->route()?->getName() ?? '';

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($routeName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Traduce el nombre técnico de la ruta a un "Significado de Firma" legible por humanos
     * (Cumplimiento Artículo 3.3 de GEMINI.md - 21 CFR Part 11).
     */
    private function getMeaning(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? 'Acción Desconocida';

        $meanings = [
            'batch.fabricacion.verify.dynamic' => 'Firma de Verificación QA - Paso de Fabricación',
            'batch.fabricacion.store.dynamic'  => 'Firma de Operario - Paso de Fabricación',
            'batch.conciliacion.sign'          => 'Firma de Conciliación de Materiales',
            'batch.despeje.store'              => 'Firma de Despeje de Línea',
            'batch.qa.verification'            => 'Firma de Verificación QA - Despeje de Línea',
            'batch.dispensacion.cerrar'        => 'Firma de Cierre de Dispensación',
            'batch.envase.verify'              => 'Firma de Verificación QA - Envase',
        ];

        return $meanings[$routeName] ?? "Ejecución: $routeName";
    }

    /**
     * Identifica el modelo principal afectado según los parámetros de la ruta.
     */
    private function getModelType(Request $request): ?string
    {
        if ($request->route('batch') || $request->route('op') || $request->route('lote')) {
            return ProductionOrder::class;
        }
        return null;
    }

    /**
     * Obtiene el ID numérico de la Orden de Producción (OP) asociada a la ruta.
     */
    private function getModelId(Request $request): ?int
    {
        $param = $request->route('batch') ?? $request->route('op') ?? $request->route('lote');

        if (is_object($param) && isset($param->id)) {
            return (int) $param->id;
        }
        if (is_numeric($param)) {
            return (int) $param;
        }
        if (is_string($param) && $param !== '') {
            return ProductionOrder::where('lote', $param)->value('id');
        }
        return null;
    }
}
