<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

trait SignatureTrait
{
    /**
     * Protocolo Universal de Firma Electrónica (CFR 21)
     * Valida credenciales contra cualquier usuario autorizado y registra el log de auditoría.
     */
    public function validateAndLogSignature(string $username, string $password, string $reason)
    {
        // 1. Uso del Servicio Universal de Firma (Johann v3.2)
        $signingUser = app(\App\Services\Cfr21SignatureService::class)->validateSignature($username, $password);

        if (!$signingUser) {
            AuditLog::create([
                'action' => 'FALLO FIRMA ELECTRÓNICA',
                'model_type' => get_class($this),
                'model_id' => $this->id ?? null,
                'reason' => "Credenciales inválidas para ($username) en: $reason",
                'ip_address' => \Request::ip(),
            ]);
            throw new \Exception('Firma Electrónica Inválida. Verifique su usuario y contraseña.');
        }

        $signingUser = \Auth::user();

        // 3. Registrar firma exitosa en auditoría
        AuditLog::create([
            'user_id' => $signingUser->id,
            'action' => 'FIRMA ELECTRÓNICA APLICADA',
            'model_type' => get_class($this),
            'model_id' => $this->id ?? null,
            'new_values' => json_encode([
                'signer_name' => $signingUser->name,
                'role' => $signingUser->role,
                'action_context' => $reason,
                'timestamp' => now()->toDateTimeString()
            ]),
            'reason' => $reason,
            'ip_address' => \Request::ip(),
        ]);

        return $signingUser;
    }

    /**
     * Verifies the electronic signature and logs it (Legacy compatible).
     */
    public function verifyElectronicSignature(string $password, string $reason)
    {
        $user = Auth::user();

        if (!Hash::check($password, $user->password)) {
            // Log failed attempt if needed
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'FALLO FIRMA ELECTRÓNICA',
                'model_type' => get_class($this),
                'model_id' => $this->id ?? null,
                'reason' => "Contraseña incorrecta para: $reason",
                'ip_address' => Request::ip(),
            ]);

            throw new \Exception('La firma electrónica ha fallado: contraseña incorrecta.');
        }

        // Log successful signature
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'FIRMA ELECTRÓNICA APLICADA',
            'model_type' => get_class($this),
            'model_id' => $this->id ?? null,
            'reason' => $reason,
            'ip_address' => Request::ip(),
        ]);

        return true;
    }
}
