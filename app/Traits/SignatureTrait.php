<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

/**
 * ============================================================================
 * TRAIT DE FIRMA ELECTRÓNICA EN MODELOS (21 CFR Part 11) - SignatureTrait
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Permite que un modelo (como `ProductionOrder`) pueda validar directamente una
 * firma electrónica con usuario + contraseña y dejar el registro en `audit_logs`.
 * ============================================================================
 */
trait SignatureTrait
{
    /**
     * Protocolo Universal de Firma Electrónica (CFR 21)
     * Valida credenciales contra el usuario firmante y registra el evento en el Audit Log.
     */
    public function validateAndLogSignature(string $username, string $password, string $reason)
    {
        // 1. Validar credenciales reales del firmante mediante Cfr21SignatureService
        $signingUser = app(\App\Services\Cfr21SignatureService::class)->validateSignature($username, $password);

        if (!$signingUser) {
            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'FALLO FIRMA ELECTRÓNICA',
                'model_type' => get_class($this),
                'model_id'   => $this->id ?? null,
                'reason'     => "Credenciales inválidas para ($username) en: $reason",
                'ip_address' => Request::ip(),
            ]);
            throw new \Exception('Firma Electrónica Inválida. Verifique su usuario y contraseña.');
        }

        // 2. Registrar firma exitosa conservando la identidad del usuario que firmó ($signingUser)
        AuditLog::create([
            'user_id'    => $signingUser->id,
            'action'     => 'FIRMA ELECTRÓNICA APLICADA',
            'model_type' => get_class($this),
            'model_id'   => $this->id ?? null,
            'new_values' => json_encode([
                'signer_name'     => $signingUser->name,
                'role'            => $signingUser->role,
                'session_user_id' => Auth::id(),
                'action_context'  => $reason,
                'timestamp'       => now()->toDateTimeString(),
            ]),
            'reason'     => $reason,
            'ip_address' => Request::ip(),
        ]);

        return $signingUser;
    }

    /**
     * Verifica la firma electrónica del usuario actualmente en sesión (Modo Legacy).
     */
    public function verifyElectronicSignature(string $password, string $reason)
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('No hay una sesión activa para verificar la firma electrónica.');
        }

        if (!Hash::check($password, $user->password)) {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'FALLO FIRMA ELECTRÓNICA',
                'model_type' => get_class($this),
                'model_id'   => $this->id ?? null,
                'reason'     => "Contraseña incorrecta para: $reason",
                'ip_address' => Request::ip(),
            ]);

            throw new \Exception('La firma electrónica ha fallado: contraseña incorrecta.');
        }

        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'FIRMA ELECTRÓNICA APLICADA',
            'model_type' => get_class($this),
            'model_id'   => $this->id ?? null,
            'reason'     => $reason,
            'ip_address' => Request::ip(),
        ]);

        return true;
    }
}
