<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SignatureService
{
    /**
     * Valida una firma electrónica bajo el estándar 21 CFR Part 11.
     * En este modo simplificado, se valida la contraseña del usuario SELECCIONADO.
     * 
     * @param int $userId ID del usuario que firma (seleccionado en la lista)
     * @param string $password Contraseña del usuario seleccionado
     * @return bool
     * @throws ValidationException
     */
    public function verify(int $userId, string $password)
    {
        $selectedUser = User::find($userId);

        if (!$selectedUser) {
            throw ValidationException::withMessages([
                'signature' => ['El usuario seleccionado no existe en el sistema.'],
            ]);
        }

        // Validar credenciales del usuario seleccionado
        if (!Hash::check($password, $selectedUser->password)) {
            throw ValidationException::withMessages([
                'signature' => ['La contraseña ingresada para ' . $selectedUser->name . ' es incorrecta.'],
            ]);
        }

        return true;
    }

    /**
     * Registra la firma en el Audit Trail con soporte Enterprise.
     * 
     * @param string $action
     * @param string|null $modelType
     * @param int|null $modelId
     * @param array $payload
     * @param int|null $onBehalfOfId
     */
    public function logSignature(string $action, ?string $modelType, ?int $modelId, array $payload, ?int $onBehalfOfId = null)
    {
        $signer = Auth::user();

        AuditLog::create([
            'user_id' => $signer->id, // Compatibilidad con esquema previo
            'signer_id' => $signer->id,
            'on_behalf_of_id' => $onBehalfOfId ?? $signer->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'new_values' => json_encode($payload),
            'ip_address' => request()->ip(),
            'justification' => null, // Ya no se utiliza justificación por agilidad operativa
        ]);
    }
}
