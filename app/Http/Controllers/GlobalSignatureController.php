<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Cfr21SignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GlobalSignatureController extends Controller
{
    /**
     * Motor Universal de Validación de Firmas CFR 21.
     * Desacoplado de cualquier módulo específico.
     */
    public function validateSignature(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'role'     => 'nullable|string',
            'reason'   => 'required|string',
            'context'  => 'nullable|string', // Ej: "PRODUCCION", "REACONDICIONAMIENTO"
        ]);

        $signatureService = app(Cfr21SignatureService::class);
        
        // Validación de Identidad sin secuestro de sesión (Fase 2)
        $user = $signatureService->validateSignature($request->username, $request->password);

        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'Credenciales inválidas o usuario no encontrado.'
            ], 401);
        }

        // Verificación de Rol si se especifica
        if ($request->role && !$user->hasRole([$request->role, 'ADMIN', 'Administrador', 'admin'])) {
            return response()->json([
                'success' => false, 
                'message' => "El usuario no tiene el rol requerido ({$request->role}) para esta firma."
            ], 403);
        }

        $now = now();

        // Registro en Audit Trail (Evidencia de Firma)
        DB::table('audit_logs')->insert([
            'user_id'    => $user->id, // El firmante
            'action'     => 'FIRMA ELECTRONICA CFR 21',
            'model_type' => $request->context ?? 'GLOBAL',
            'model_id'   => null,
            'new_values' => json_encode([
                'signer_name' => $user->name,
                'reason' => $request->reason,
                'timestamp' => $now->toDateTimeString(),
                'ip' => $request->ip()
            ]),
            'reason'     => $request->reason,
            'ip_address' => $request->ip(),
            'created_at' => $now,
        ]);

        return response()->json([
            'success'   => true,
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'signature_html' => $signatureService->renderSignatureHtml($user->name, $now, $request->compact ?? false),
            'new_token' => csrf_token()
        ]);
    }
}
