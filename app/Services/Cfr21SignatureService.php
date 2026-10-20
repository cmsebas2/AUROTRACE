<?php

/* =========================================================
   ⚠️ FEATURE LOCKED ⚠️ - DO NOT MODIFY - VER 1.2
   CENTRALIZED CFR 21 SIGNATURE SERVICE (AuroTrace Core)
   Single Source of Truth for Electronic Identity Verification.
   ========================================================= */

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * ============================================================================
 * SERVICIO CENTRAL DE FIRMA ELECTRÓNICA (21 CFR Part 11) - Cfr21SignatureService
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * En la industria farmacéutica (norma FDA 21 CFR Part 11), un simple clic no basta
 * para firmar un documento crítico: la persona debe identificarse con su usuario/correo
 * y su contraseña secreta.
 *
 * ¿Qué hace este servicio?
 * 1. `validateSignature($username, $password)`:
 *    Busca al usuario sin importar si escribió su nombre o correo en mayúsculas o minúsculas,
 *    y comprueba su contraseña sin cerrar ni cambiar la sesión del operario que está en la pantalla.
 * 2. `renderSignatureHtml(...)`:
 *    Genera el sello visual azul con el nombre del firmante, fecha y hora exacta.
 * ============================================================================
 */
class Cfr21SignatureService
{
    /**
     * Valida las credenciales de un usuario bajo el protocolo CFR 21 Part 11.
     * Implementa búsqueda inteligente e insensible a mayúsculas/minúsculas por email o name.
     *
     * @param string $username (Email o Nombre de Usuario, ej: 'ADMIN' o 'calidad@aurofarma.com')
     * @param string $password Contraseña en texto plano a verificar
     * @return \App\Models\User|false Retorna el modelo User si es válido, o false si falla
     */
    public function validateSignature($username, $password)
    {
        $normalized = mb_strtolower(trim((string) $username), 'UTF-8');
        if ($normalized === '' || empty($password)) {
            return false;
        }

        // Búsqueda agrupada e insensible a mayúsculas/minúsculas (compatible con PostgreSQL, MySQL y SQLite)
        $user = User::where(function ($query) use ($username, $normalized) {
            $query->where('email', $username)
                  ->orWhere('name', $username)
                  ->orWhereRaw('LOWER(email) = ?', [$normalized])
                  ->orWhereRaw('LOWER(name) = ?', [$normalized]);
        })->first();

        if ($user && Hash::check($password, $user->password)) {
            // Autenticación exitosa bajo protocolo CFR 21 sin secuestrar la sesión activa de la terminal
            return $user;
        }

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
        $safeUserName = htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8');

        $timeBlock = $compact ? '' : '<span style="color: #64748b; font-size: 10px; border-left: 1px solid #cbd5e1; padding-left: 8px;">' . $date . ' <span style="margin: 0 3px; color: #cbd5e1;">|</span> ' . $time . '</span>';

        return <<<HTML
<div class="auro-signature-mini" style="display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #048ABF; border-radius: 4px; padding: 4px 10px; font-family: 'Inter', sans-serif; font-size: 11px; color: #0A2540; width: 100%; box-sizing: border-box; height: 32px; justify-content: flex-start; text-align: left;">
    <i class="fas fa-check-circle" style="color: #048ABF; font-size: 14px; flex-shrink: 0;"></i>
    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 8px;">
        <span style="font-weight: 800; color: #048ABF; text-transform: uppercase;">$safeUserName</span>
        $timeBlock
    </div>
</div>
HTML;
    }
}
