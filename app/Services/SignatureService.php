<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * ============================================================================
 * SERVICIO DE FIRMA OPERATIVA EBR - SignatureService
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Este servicio apoya a `BatchController` y `GenealogyController` durante la
 * ejecución en planta (pesaje, despeje de línea, pasos de manufactura y liberación):
 *
 * 1. `verify($userId, $password)`:
 *    Comprueba que el usuario seleccionado exista y que la contraseña ingresada
 *    le pertenezca. Si falla, lanza un error de validación amigable.
 * 2. `logSignature(...)`:
 *    Deja constancia inmutable en `audit_logs` de quién ejecutó la firma, sobre
 *    qué lote o registro y con qué datos técnicos.
 * ============================================================================
 */
class SignatureService
{
    /**
     * Valida una firma electrónica bajo el estándar 21 CFR Part 11
     * verificando la contraseña del usuario seleccionado.
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
     * Registra la firma en el Audit Trail con soporte para delegación operativa.
     *
     * @param string $action Significado legible de la acción firmada
     * @param string|null $modelType Clase del modelo afectado (ej: ProductionOrder::class)
     * @param int|null $modelId ID del registro afectado
     * @param array $payload Datos técnicos respaldados por la firma
     * @param int|null $onBehalfOfId ID del usuario verificador/ejecutor si difiere de la sesión
     */
    public function logSignature(string $action, ?string $modelType, ?int $modelId, array $payload, ?int $onBehalfOfId = null)
    {
        $signerId = Auth::id() ?? $onBehalfOfId;
        $effectiveUserId = $onBehalfOfId ?? $signerId;

        AuditLog::create([
            'user_id'         => $effectiveUserId,
            'signer_id'       => $signerId,
            'on_behalf_of_id' => $effectiveUserId,
            'action'          => $action,
            'model_type'      => $modelType,
            'model_id'        => $modelId,
            'new_values'      => json_encode($payload),
            'ip_address'      => request()->ip(),
            'justification'   => $payload['reason'] ?? $payload['observations'] ?? null,
        ]);
    }
}
