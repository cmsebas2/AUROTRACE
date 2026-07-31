<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Cfr21Signature implements ValidationRule
{
    /**
     * Run the validation rule.
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

        $authenticated = false;
        if (Auth::once(['email' => $username, 'password' => $value])) {
            $authenticated = true;
        } elseif (Auth::once(['name' => $username, 'password' => $value])) {
            $authenticated = true;
        }

        if (!$authenticated) {
            $fail('Firma Electrónica Inválida. La contraseña no coincide con los registros del sistema.');
        }
    }
}
