<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $attemptCredentials = [
            'email' => strtolower($credentials['username']) . '@temp.local',
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($attemptCredentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Log successful login (though CFR 21 Part 11 mainly applies to transactional DB actions,
            // system access can also be recorded).
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'login',
                'model_type' => 'App\Models\User',
                'model_id' => Auth::id(),
                'new_values' => json_encode([
                    'username' => $request->username,
                    '_metadata' => [
                        'tipo_cierre' => 'N/A',
                        'intento_numero' => session('login_attempts', 1)
                    ]
                ]),
                'ip_address' => $request->ip(),
                'reason' => 'User logged into MES system',
            ]);

            session()->forget('login_attempts');

            return redirect()->intended('dashboard');
        }

        $attempts = session('login_attempts', 0) + 1;
        session(['login_attempts' => $attempts]);

        $user = \App\Models\User::where('email', strtolower($credentials['username']) . '@temp.local')->first();
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login fallido',
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
                'new_values' => json_encode([
                    '_metadata' => [
                        'intento_numero' => $attempts
                    ]
                ]),
                'ip_address' => $request->ip(),
                'reason' => "Intento de login fallido ($attempts)",
            ]);
        }

        return back()->withErrors([
            'username' => 'Las credenciales proporcionadas son incorrectas o no están autorizadas.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'logout',
                'model_type' => 'App\Models\User',
                'model_id' => Auth::id(),
                'new_values' => json_encode([
                    '_metadata' => [
                        'tipo_cierre' => 'manual',
                    ]
                ]),
                'ip_address' => $request->ip(),
                'reason' => 'User logged out of MES system',
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
