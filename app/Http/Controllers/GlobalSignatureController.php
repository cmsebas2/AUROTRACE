<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Cfr21SignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * ============================================================================
 * MOTOR UNIVERSAL DE FIRMAS ELECTRÓNICAS (21 CFR Part 11) - GlobalSignatureController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este controlador atiende las peticiones AJAX de los componentes visuales
 * `<x-cfr21-signature-flow>` y `<x-cfr21-signature-modal>`.
 *
 * Pasos que ejecuta:
 * 1. Verifica que exista una sesión activa en la terminal (`Auth::check()`, Artículo 3.2 GEMINI.md).
 * 2. Valida el usuario y contraseña del firmante mediante `Cfr21SignatureService`.
 * 3. Comprueba si el firmante tiene el rol exigido por el paso actual.
 * 4. Registra la evidencia forense en `audit_logs` y devuelve el sello HTML firmado.
 * ============================================================================
 */
class GlobalSignatureController extends Controller
{
    /**
     * Valida credenciales electrónicas y genera el sello visual CFR 21.
     */
    public function validateSignature(Request $request)
    {
        // Artículo 3.2 GEMINI.md: Asegurar que la sesión de la terminal sigue activa
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'La sesión de la terminal ha expirado. Inicie sesión nuevamente.',
            ], 401);
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'role'     => 'nullable|string',
            'reason'   => 'required|string',
            'context'  => 'nullable|string', // Ej: "PRODUCCION", "REACONDICIONAMIENTO"
        ]);

        $signatureService = app(Cfr21SignatureService::class);

        // Validación de Identidad sin secuestro de la sesión principal
        $user = $signatureService->validateSignature($request->username, $request->password);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas o usuario no encontrado.',
            ], 401);
        }

        // Verificación de Rol si el formulario exige un perfil específico
        if ($request->role && !$user->hasRole([$request->role, 'ADMIN', 'Administrador', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => "El usuario no tiene el rol requerido ({$request->role}) para esta firma.",
            ], 403);
        }

        $now = now();

        // Registro en Audit Trail (Evidencia inmutable de Firma Electrónica)
        DB::table('audit_logs')->insert([
            'user_id'    => $user->id,
            'action'     => 'FIRMA ELECTRONICA CFR 21',
            'model_type' => $request->context ?? 'GLOBAL',
            'model_id'   => null,
            'new_values' => json_encode([
                'signer_name' => $user->name,
                'reason'      => $request->reason,
                'timestamp'   => $now->toDateTimeString(),
                'ip'          => $request->ip(),
            ]),
            'reason'     => $request->reason,
            'ip_address' => $request->ip(),
            'created_at' => $now,
        ]);

        return response()->json([
            'success'        => true,
            'user_id'        => $user->id,
            'user_name'      => $user->name,
            'timestamp'      => $now->format('Y-m-d H:i:s'),
            'signature_html' => $signatureService->renderSignatureHtml($user->name, $now, $request->compact ?? false),
            'new_token'      => csrf_token(),
        ]);
    }
}
