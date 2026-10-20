<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\User;

/**
 * ============================================================================
 * CONTROLADOR DE AUTENTICACIÓN Y SESIONES - AuthController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Gestiona la puerta de entrada y salida de los usuarios al sistema:
 * 1. `showLoginForm()`: Muestra la pantalla de inicio de sesión (si ya entró, lo lleva al Dashboard).
 * 2. `login()`: Permite iniciar sesión escribiendo el correo completo o solo el usuario
 *    (probando automáticamente `@temp.local` y `@aurofarma.com`), registra el ingreso en
 *    `audit_logs` (norma 21 CFR Part 11) y redirige a Calidad si el usuario es de QA.
 * 3. `logout()`: Cierra la sesión de forma segura y deja constancia en la bitácora.
 * ============================================================================
 */
class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Procesa las credenciales de acceso y registra el evento en el Audit Trail.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $rawUsername = trim($credentials['username']);
        $password = $credentials['password'];

        // Si el usuario no escribe '@', probamos automáticamente los dominios corporativos internos
        $emailsToTry = [];
        if (str_contains($rawUsername, '@')) {
            $emailsToTry[] = strtolower($rawUsername);
        } else {
            $base = strtolower($rawUsername);
            $emailsToTry[] = $base . '@temp.local';
            $emailsToTry[] = $base . '@aurofarma.com';
        }

        $authenticated = false;
        foreach ($emailsToTry as $emailAttempt) {
            if (Auth::attempt(['email' => $emailAttempt, 'password' => $password], $request->boolean('remember'))) {
                $authenticated = true;
                break;
            }
        }

        if ($authenticated) {
            $request->session()->regenerate();
            $currentAttempt = session('login_attempts', 0) + 1;

            // Actualizar metadatos de último acceso CFR 21 en el usuario autenticado
            try {
                Auth::user()->update([
                    'last_login_at'         => now(),
                    'failed_login_attempts' => 0,
                ]);
            } catch (\Throwable $e) {
                // Continuar aunque alguna columna opcional aún no esté migrada
            }

            // Registrar inicio de sesión exitoso en el Audit Trail (21 CFR Part 11)
            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'login',
                'model_type' => User::class,
                'model_id'   => Auth::id(),
                'new_values' => json_encode([
                    'username'  => $request->username,
                    '_metadata' => [
                        'tipo_cierre'    => 'N/A',
                        'intento_numero' => $currentAttempt,
                    ],
                ]),
                'ip_address' => $request->ip(),
                'reason'     => 'User logged into MES system',
            ]);

            session()->forget('login_attempts');

            // Redirección directa al portal de Calidad para usuarios con rol exclusivo de Calidad
            if (Auth::user()->hasRole(['calidad', 'CALIDAD', 'INSPECTOR DE CALIDAD', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD'])) {
                return redirect()->route('calidad.index');
            }

            return redirect()->intended('dashboard');
        }

        // Si falló la autenticación, incrementar contador de intentos y auditar
        $attempts = session('login_attempts', 0) + 1;
        session(['login_attempts' => $attempts]);

        $user = User::whereIn('email', $emailsToTry)->first();
        if ($user) {
            try {
                $user->increment('failed_login_attempts');
            } catch (\Throwable $e) {}

            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'login fallido',
                'model_type' => User::class,
                'model_id'   => $user->id,
                'new_values' => json_encode([
                    '_metadata' => [
                        'intento_numero' => $attempts,
                    ],
                ]),
                'ip_address' => $request->ip(),
                'reason'     => "Intento de login fallido ($attempts)",
            ]);
        }

        return back()->withErrors([
            'username' => 'Las credenciales proporcionadas son incorrectas o no están autorizadas.',
        ])->onlyInput('username');
    }

    /**
     * Cierra la sesión activa del usuario y registra el evento en el Audit Trail.
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'logout',
                'model_type' => User::class,
                'model_id'   => Auth::id(),
                'new_values' => json_encode([
                    '_metadata' => [
                        'tipo_cierre' => 'manual',
                    ],
                ]),
                'ip_address' => $request->ip(),
                'reason'     => 'User logged out of MES system',
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
