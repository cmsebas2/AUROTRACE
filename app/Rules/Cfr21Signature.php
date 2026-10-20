<?php

namespace App\Rules;

use App\Services\Cfr21SignatureService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Support\Facades\Auth;

/**
 * ============================================================================
 * REGLA DE VALIDACIÓN DE FIRMA ELECTRÓNICA - Cfr21Signature
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Esta regla se usa dentro de `$request->validate(['signature_password' => ['required', new Cfr21Signature]])`.
 * Verifica que la contraseña ingresada coincida con el usuario que firma, usando
 * `Cfr21SignatureService` para NO alterar el usuario activo en la sesión (`Auth::user()`).
 * ============================================================================
 */
class Cfr21Signature implements ValidationRule
{
    /**
     * Ejecuta la validación de la contraseña de firma electrónica.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $username = request()->input('username') ?: (Auth::check() ? Auth::user()->email : null);

        if (!$username) {
            $fail('Firma Electrónica Inválida. No se pudo identificar al usuario.');
            return;
        }

        // Delegamos en Cfr21SignatureService para validar sin mutar Auth::user() en memoria
        $verifiedUser = app(Cfr21SignatureService::class)->validateSignature($username, (string) $value);

        if (!$verifiedUser) {
            $fail('Firma Electrónica Inválida. La contraseña no coincide con los registros del sistema.');
        }
    }
}
