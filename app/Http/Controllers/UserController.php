<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withTrashed()->get();
        // Cargamos los audit trails relacionados con usuarios para la pestaña de auditoría
        $query = AuditLog::query();

        if (request()->filled('q')) {
            $term = request()->q;
            $query->where(function($q) use ($term) {
                $q->where('reason', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%")
                  ->orWhere('new_values', 'like', "%{$term}%")
                  ->orWhere('old_values', 'like', "%{$term}%")
                  ->orWhereHas('user', function($u) use ($term) {
                      $u->where('name', 'like', "%{$term}%");
                  });
            });
        }

        if (request()->filled('alert')) {
            $query->where(function($q) {
                $q->where('reason', 'like', '%ALERTA%')
                  ->orWhere('action', 'like', '%ALERTA%')
                  ->orWhere('reason', 'like', '%BLOQUEO%')
                  ->orWhere('reason', 'like', '%DESVIACIÓN%')
                  ->orWhere('action', 'like', '%FALLID%');
            });
        }

        $audits = $query->orderBy('created_at', 'desc')->take(300)->get();
        $roles = \App\Models\Role::with('permissions')->get();
        $permissions = \App\Models\Permission::all();
        
        return view('users.index', compact('users', 'audits', 'roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
        ]);

        $role = \App\Models\Role::findOrFail($request->role);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role->name,
        ]);

        return redirect()->back()->with('success', 'Usuario creado exitosamente con el rol oficial: ' . $role->name);
    }

    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'required',
        ]);

        $role = \App\Models\Role::findOrFail($request->role);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $role->name,
        ]);

        return redirect()->back()->with('success', 'Usuario actualizado exitosamente al rol: ' . $role->name);
    }

    public function toggleStatus($id)
    {
        // Using withTrashed to find it even if it's currently SoftDeleted
        $user = User::withTrashed()->findOrFail($id);
        
        if ($user->trashed()) {
            $user->restore();
            return redirect()->back()->with('success', 'Usuario activado exitosamente.');
        } else {
            $user->delete();
            return redirect()->back()->with('success', 'Usuario desactivado exitosamente.');
        }
    }

    public function syncRolePermissions(Request $request)
    {
        $permissionsData = $request->input('permissions', []);
        
        $roles = \App\Models\Role::all();
        foreach ($roles as $role) {
            // $permissionsData[$role->id] will be an array of permission IDs if any box is checked
            $rolePermissions = $permissionsData[$role->id] ?? [];
            $role->permissions()->sync($rolePermissions);
        }
        
        return redirect()->back()->with('success', 'Matriz de permisos (CFR 21) sincronizada exitosamente.');
    }
}
