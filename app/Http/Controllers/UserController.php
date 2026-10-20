<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * ============================================================================
 * CONTROLADOR DE GESTIÓN DE USUARIOS Y PERMISOS (IAM / CFR 21) - UserController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Administra quién puede usar AUROTRACE y qué puede hacer cada rol:
 * 1. `index()`: Lista todos los usuarios (activos e inactivos), la matriz de
 *    roles/permisos y el visor forense de auditoría (`audit_logs`).
 * 2. `store()` y `update()`: Crean o actualizan usuarios asignándoles su rol oficial.
 * 3. `toggleStatus()`: Activa o desactiva un usuario sin borrar su historial (SoftDelete).
 * 4. `syncRolePermissions()`: Guarda los checkboxes de la matriz de permisos RBAC.
 * ============================================================================
 */
class UserController extends Controller
{
    /**
     * Muestra el panel de administración de usuarios, roles y auditoría forense.
     */
    public function index()
    {
        $users = User::withTrashed()->get();
        $query = AuditLog::with('user');

        if (request()->filled('q')) {
            $term = request()->q;
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%")
                  ->orWhere('new_values', 'like', "%{$term}%")
                  ->orWhere('old_values', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($u) use ($term) {
                      $u->where('name', 'like', "%{$term}%");
                  });
            });
        }

        if (request()->filled('alert')) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%ALERTA%')
                  ->orWhere('action', 'like', '%ALERTA%')
                  ->orWhere('reason', 'like', '%BLOQUEO%')
                  ->orWhere('reason', 'like', '%DESVIACIÓN%')
                  ->orWhere('action', 'like', '%FALLID%');
            });
        }

        $audits = $query->orderBy('created_at', 'desc')->take(300)->get();
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('users.index', compact('users', 'audits', 'roles', 'permissions'));
    }

    /**
     * Crea un nuevo usuario con su rol oficial.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required',
        ]);

        $role = Role::findOrFail($request->role);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $role->name,
        ]);

        return redirect()->back()->with('success', 'Usuario creado exitosamente con el rol oficial: ' . $role->name);
    }

    /**
     * Actualiza los datos y el rol de un usuario existente.
     */
    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'     => 'required',
            'password' => 'nullable|min:6',
        ]);

        $role = Role::findOrFail($request->role);

        $updateData = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $role->name,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
            $updateData['password_changed_at'] = now();
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'Usuario actualizado exitosamente al rol: ' . $role->name);
    }

    /**
     * Activa o desactiva lógicamente (SoftDelete) la cuenta de un usuario.
     */
    public function toggleStatus($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
            return redirect()->back()->with('success', 'Usuario activado exitosamente.');
        }

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Por seguridad no puede desactivar su propia cuenta en sesión.');
        }

        $user->delete();
        return redirect()->back()->with('success', 'Usuario desactivado exitosamente.');
    }

    /**
     * Sincroniza la matriz de permisos por rol y deja trazabilidad en el Audit Log.
     */
    public function syncRolePermissions(Request $request)
    {
        $permissionsData = $request->input('permissions', []);

        DB::transaction(function () use ($permissionsData, $request) {
            $roles = Role::all();
            foreach ($roles as $role) {
                $rolePermissions = $permissionsData[$role->id] ?? [];
                $role->permissions()->sync($rolePermissions);
            }

            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'ACTUALIZACIÓN MATRIZ RBAC',
                'model_type' => Role::class,
                'model_id'   => null,
                'new_values' => json_encode($permissionsData),
                'ip_address' => $request->ip(),
                'reason'     => 'Sincronización de matriz de permisos (21 CFR Part 11).',
            ]);
        });

        return redirect()->back()->with('success', 'Matriz de permisos (CFR 21) sincronizada exitosamente.');
    }
}
