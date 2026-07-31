<?php

/* =========================================================
   ⚠️ FEATURE LOCKED ⚠️ - DO NOT MODIFY - VER 1.2
   CENTRALIZED CFR 21 SIGNATURE SERVICE (AuroTrace Core)
   Single Source of Truth for Electronic Identity Verification.
   ========================================================= */

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Cfr21SignatureService
{
    /**
     * Valida las credenciales de un usuario bajo el protocolo CFR 21 Part 11.
     * Implementa búsqueda inteligente por Identidad Maestra (email o name).
     * 
     * @param string $username (Email o Nombre de Usuario, ej: 'ADMIN')
     * @param string $password
     * @return \App\Models\User|false
     */
    public function validateSignature($username, $password)
    {
        // Protocolo de Identidad Maestra (Johann v3.2)
        // Se busca al usuario por email o por name
        $user = User::where('email', $username)
                    ->orWhere('name', $username)
                    ->first();

        if ($user && Hash::check($password, $user->password)) {
            // Autenticación exitosa bajo protocolo CFR 21
            // FASE 2 FIX: Se elimina Auth::login() para no secuestrar la sesión principal
            return $user;
        }

        // Fallback para fallos de búsqueda o contraseña incorrecta
        return false;
    }

    /**
     * Genera el HTML universal estandarizado para firmas electrónicas AuroTrace.
     * Garantiza inmutabilidad visual desde el servidor.
     */
    public function renderSignatureHtml($userName, $timestamp = null, $compact = false)
    {
        if ($timestamp instanceof \Carbon\Carbon) {
            $dt = $timestamp;
        } elseif ($timestamp) {
            $dt = \Carbon\Carbon::parse($timestamp);
        } else {
            $dt = now();
        }

        $date = $dt->format('Y-m-d');
        $time = $dt->format('H:i:s');

        $timeBlock = $compact ? '' : '<span style="color: #64748b; font-size: 10px; border-left: 1px solid #cbd5e1; padding-left: 8px;">' . $date . ' <span style="margin: 0 3px; color: #cbd5e1;">|</span> ' . $time . '</span>';

        return <<<HTML
<div class="auro-signature-mini" style="display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #048ABF; border-radius: 4px; padding: 4px 10px; font-family: 'Inter', sans-serif; font-size: 11px; color: #0A2540; width: 100%; box-sizing: border-box; height: 32px; justify-content: flex-start; text-align: left;">
    <i class="fas fa-check-circle" style="color: #048ABF; font-size: 14px; flex-shrink: 0;"></i>
    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
        <span style="font-weight: 800; color: #048ABF; text-transform: uppercase;">$userName</span>
        $timeBlock
    </div>
</div>
HTML;
    }
}
