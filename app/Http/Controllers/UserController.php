<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\Rule;

/**
 * ============================================================================
 * CONTROLADOR DE GESTIÓN DE IDENTIDAD, ROLES Y PERMISOS (IAM / 21 CFR Part 11)
 * ============================================================================
 * Gobernanza integral de usuarios, segregación de funciones (Maker / Checker),
 * permisos granulares por módulo y auditoría forense para AUROTRACE EBR 4.0.
 * ============================================================================
 */
class UserController extends Controller
{
    /**
     * Garantiza que las columnas extendidas y los 36 permisos de los 9 módulos
     * existan en la base de datos (Auto-Healing para entornos Serverless / Vercel).
     */
    protected function ensureRbacCatalog(): void
    {
        try {
            if (Schema::hasTable('users')) {
                Schema::table('users', function (Blueprint $table) {
                    if (!Schema::hasColumn('users', 'area')) {
                        $table->string('area')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'signature_level')) {
                        $table->string('signature_level')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'custom_permissions')) {
                        $table->text('custom_permissions')->nullable();
                    }
                });
            }

            if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'description')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->string('description')->nullable();
                });
            }
        } catch (\Throwable $e) {
            // Continuar de forma segura si el motor de BD restringe DDL en caliente
        }

        try {
            $flatCatalog = User::flatPermissionCatalog();
            $existingPerms = Permission::pluck('id', 'name')->all();

            $missing = false;
            foreach (array_keys($flatCatalog) as $permName) {
                if (!isset($existingPerms[$permName])) {
                    Permission::firstOrCreate(['name' => $permName]);
                    $missing = true;
                }
            }

            // Asegurar roles oficiales base
            $officialRoles = [
                'ADMIN',
                'DIRECTOR TECNICO Y DE PRODUCCION',
                'ANALISTA DE PRODUCCION',
                'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
                'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
                'INSPECTOR DE CALIDAD',
                'AUXILIAR DE CALIDAD',
                'OPERARIO',
            ];

            foreach ($officialRoles as $rName) {
                Role::firstOrCreate(['name' => $rName]);
            }

            // Si se crearon permisos nuevos, ejecutar sincronización base de RolePermissionSeeder para ADMIN
            if ($missing) {
                $allIds = Permission::pluck('id');
                $adminRoles = Role::whereIn('name', ['ADMIN', 'admin', 'DIRECTOR TECNICO Y DE PRODUCCION', 'DIRECCION TECNICA'])->get();
                foreach ($adminRoles as $ar) {
                    $ar->permissions()->syncWithoutDetaching($allIds);
                }
            }
        } catch (\Throwable $e) {
            // Ignorar silenciosamente si aún no existen las tablas
        }
    }

    /**
     * Muestra el Centro de Gobierno de Usuarios, Roles, Permisos y Audit Trail Forense.
     */
    public function index(Request $request)
    {
        $this->ensureRbacCatalog();

        $users = User::withTrashed()->orderBy('id', 'asc')->get();

        // Conteo de actividad y firmas por usuario en el Audit Trail
        $auditStatsByUser = [];
        try {
            $stats = DB::table('audit_logs')
                ->select(
                    'user_id',
                    DB::raw('COUNT(*) as total_events'),
                    DB::raw("SUM(CASE WHEN UPPER(action) LIKE '%FIRMA%' OR UPPER(action) LIKE '%VERIFICA%' OR UPPER(action) LIKE '%LIBERA%' THEN 1 ELSE 0 END) as total_signatures")
                )
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->get();

            foreach ($stats as $st) {
                $auditStatsByUser[$st->user_id] = [
                    'events'     => (int) $st->total_events,
                    'signatures' => (int) $st->total_signatures,
                ];
            }
        } catch (\Throwable $e) {}

        // Filtrado de la Bitácora Forense (Audit Trail)
        $query = AuditLog::with('user');

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%")
                  ->orWhere('new_values', 'like', "%{$term}%")
                  ->orWhere('old_values', 'like', "%{$term}%")
                  ->orWhere('ip_address', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($u) use ($term) {
                      $u->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                  });
            });
        }

        if ($request->filled('audit_user')) {
            $query->where('user_id', $request->audit_user);
        }

        if ($request->filled('only_signatures')) {
            $query->where(function ($q) {
                $q->where('action', 'like', '%FIRMA%')
                  ->orWhere('action', 'like', '%Firma%')
                  ->orWhere('action', 'like', '%VERIFICA%')
                  ->orWhere('action', 'like', '%LIBERA%');
            });
        }

        if ($request->filled('alert')) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%ALERTA%')
                  ->orWhere('action', 'like', '%ALERTA%')
                  ->orWhere('reason', 'like', '%BLOQUEO%')
                  ->orWhere('reason', 'like', '%DESVIACIÓN%')
                  ->orWhere('action', 'like', '%FALLID%')
                  ->orWhere('action', 'like', '%RECHAZ%')
                  ->orWhere('action', 'like', '%ELIMINA%');
            });
        }

        $audits = $query->orderBy('created_at', 'desc')->take(300)->get();

        // Roles y Permisos estructurados
        $allRoles = Role::with('permissions')->orderBy('id', 'asc')->get();

        // Consolidar visualización de roles evitando duplicados por minúsculas/mayúsculas si no están en uso directo
        $usedRoleNames = $users->pluck('role')->map(fn($r) => trim((string) $r))->unique()->all();
        $seenUpper = [];
        $roles = $allRoles->filter(function ($role) use (&$seenUpper, $usedRoleNames) {
            $upper = mb_strtoupper(trim($role->name));
            // Priorizar versiones en MAYÚSCULAS o que estén asignadas a algún usuario
            if (!isset($seenUpper[$upper])) {
                $seenUpper[$upper] = $role->id;
                return true;
            }
            return in_array($role->name, $usedRoleNames, true);
        })->values();

        $permissions = Permission::all()->keyBy('name');
        $permissionCatalog = User::permissionCatalog();
        $flatCatalog = User::flatPermissionCatalog();

        // KPIs Ejecutivos IAM
        $kpis = [
            'total_users'       => $users->count(),
            'active_users'      => $users->whereNull('deleted_at')->count(),
            'inactive_users'    => $users->whereNotNull('deleted_at')->count(),
            'locked_users'      => $users->where('failed_login_attempts', '>', 0)->count(),
            'total_roles'       => $roles->count(),
            'total_permissions' => count($flatCatalog),
            'total_signatures'  => array_sum(array_column($auditStatsByUser, 'signatures')),
            'total_audits'      => AuditLog::count(),
        ];

        return view('users.index', compact(
            'users',
            'audits',
            'roles',
            'permissions',
            'permissionCatalog',
            'flatCatalog',
            'auditStatsByUser',
            'kpis'
        ));
    }

    /**
     * Crea un nuevo usuario con su rol oficial, credenciales CFR 21 y permisos opcionales.
     */
    public function store(Request $request)
    {
        $this->ensureRbacCatalog();

        $rawEmail = trim((string) $request->input('email'));
        if ($rawEmail !== '' && !str_contains($rawEmail, '@')) {
            $request->merge(['email' => strtolower($rawEmail) . '@aurofarma.com']);
        }

        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|min:6',
            'role'            => 'required',
            'area'            => 'nullable|string|max:120',
            'signature_level' => 'nullable|string|max:120',
            'pin_firma'       => 'nullable|string|min:4',
        ]);

        $role = is_numeric($request->role)
            ? Role::findOrFail($request->role)
            : Role::where('name', $request->role)->firstOrFail();

        $hashedPassword = Hash::make($request->password);
        $hashedPin = $request->filled('pin_firma') ? Hash::make($request->pin_firma) : $hashedPassword;

        $createData = [
            'name'                => trim($request->name),
            'email'               => strtolower(trim($request->email)),
            'password'            => $hashedPassword,
            'pin_firma'           => $hashedPin,
            'role'                => $role->name,
            'password_changed_at' => now(),
        ];

        if (Schema::hasColumn('users', 'area')) {
            $createData['area'] = $request->area;
        }
        if (Schema::hasColumn('users', 'signature_level')) {
            $createData['signature_level'] = $request->signature_level;
        }
        if (Schema::hasColumn('users', 'custom_permissions') && $request->has('custom_grant')) {
            $createData['custom_permissions'] = [
                'grant'  => array_values((array) $request->input('custom_grant', [])),
                'revoke' => [],
            ];
        }

        $newUser = User::create($createData);

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'CREACIÓN DE USUARIO IAM',
            'model_type' => User::class,
            'model_id'   => $newUser->id,
            'new_values' => json_encode([
                'name'  => $newUser->name,
                'email' => $newUser->email,
                'role'  => $newUser->role,
                'area'  => $request->area,
            ]),
            'ip_address' => $request->ip(),
            'reason'     => "Alta de usuario autorizado [{$newUser->name}] con perfil [{$role->name}].",
        ]);

        return redirect()->route('users.index', ['tab' => 'users'])
            ->with('success', "Usuario {$newUser->name} registrado exitosamente con el rol oficial: {$role->name}.");
    }

    /**
     * Actualiza los datos, credenciales y el rol de un usuario existente.
     */
    public function update(Request $request, $id)
    {
        $this->ensureRbacCatalog();

        $user = User::withTrashed()->findOrFail($id);

        $rawEmail = trim((string) $request->input('email'));
        if ($rawEmail !== '' && !str_contains($rawEmail, '@')) {
            $request->merge(['email' => strtolower($rawEmail) . '@aurofarma.com']);
        }

        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role'            => 'required',
            'password'        => 'nullable|min:6',
            'pin_firma'       => 'nullable|min:4',
            'area'            => 'nullable|string|max:120',
            'signature_level' => 'nullable|string|max:120',
        ]);

        $role = is_numeric($request->role)
            ? Role::findOrFail($request->role)
            : Role::where('name', $request->role)->firstOrFail();

        $oldSnapshot = [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];

        $updateData = [
            'name'  => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'role'  => $role->name,
        ];

        if (Schema::hasColumn('users', 'area')) {
            $updateData['area'] = $request->area;
        }
        if (Schema::hasColumn('users', 'signature_level')) {
            $updateData['signature_level'] = $request->signature_level;
        }

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
            $updateData['password_changed_at'] = now();
            $updateData['failed_login_attempts'] = 0;
            if (!$request->filled('pin_firma')) {
                $updateData['pin_firma'] = Hash::make($request->password);
            }
        }

        if ($request->filled('pin_firma')) {
            $updateData['pin_firma'] = Hash::make($request->pin_firma);
        }

        $user->update($updateData);

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'ACTUALIZACIÓN DE PERFIL IAM',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'old_values' => json_encode($oldSnapshot),
            'new_values' => json_encode([
                'name'             => $user->name,
                'email'            => $user->email,
                'role'             => $user->role,
                'password_updated' => $request->filled('password') ? 'SÍ' : 'NO',
            ]),
            'ip_address' => $request->ip(),
            'reason'     => "Actualización administrativa del perfil de {$user->name} (Rol: {$role->name}).",
        ]);

        return redirect()->route('users.index', ['tab' => 'users'])
            ->with('success', "Perfil de {$user->name} actualizado exitosamente al rol: {$role->name}.");
    }

    /**
     * Guarda los permisos individuales (Overrides: Grant / Revoke) y el rol de un usuario específico.
     */
    public function updateUserPermissions(Request $request, $id)
    {
        $this->ensureRbacCatalog();

        $user = User::withTrashed()->findOrFail($id);

        if ($request->filled('role')) {
            $role = is_numeric($request->role)
                ? Role::find($request->role)
                : Role::where('name', $request->role)->first();
            if ($role) {
                $user->role = $role->name;
            }
        }

        // Permisos seleccionados por el administrador como activos para este usuario
        $selectedPerms = array_values(array_unique((array) $request->input('effective_permissions', [])));
        $rolePerms = $user->getRolePermissionNames();

        // Calcular diferencias respecto al rol base:
        // - grant: está en $selectedPerms pero no en $rolePerms
        // - revoke: está en $rolePerms pero no en $selectedPerms
        $grant = array_values(array_diff($selectedPerms, $rolePerms));
        $revoke = array_values(array_diff($rolePerms, $selectedPerms));

        if (Schema::hasColumn('users', 'custom_permissions')) {
            $user->custom_permissions = [
                'grant'  => $grant,
                'revoke' => $revoke,
            ];
        }

        $user->save();

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'AJUSTE DE PERMISOS INDIVIDUALES',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'new_values' => json_encode([
                'usuario'            => $user->name,
                'rol_base'           => $user->role,
                'permisos_otorgados' => $grant,
                'permisos_revocados' => $revoke,
                'total_efectivos'    => count($selectedPerms),
            ]),
            'ip_address' => $request->ip(),
            'reason'     => $request->input('reason') ?: "Configuración granular de permisos para {$user->name} (21 CFR Part 11).",
        ]);

        return redirect()->route('users.index', ['tab' => 'users'])
            ->with('success', "Permisos individuales de {$user->name} actualizados correctamente (" . count($selectedPerms) . " permisos activos).");
    }

    /**
     * Desbloquea una cuenta con intentos fallidos y/o restablece su contraseña y PIN de Firma Electrónica.
     */
    public function unlockAndReset(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $request->validate([
            'new_password' => 'nullable|min:6',
            'new_pin'      => 'nullable|min:4',
        ]);

        $updateData = [
            'failed_login_attempts' => 0,
        ];

        $actionsTaken = ['Reinicio de contador de intentos fallidos'];

        if ($request->filled('new_password')) {
            $updateData['password'] = Hash::make($request->new_password);
            $updateData['password_changed_at'] = now();
            $updateData['pin_firma'] = Hash::make($request->filled('new_pin') ? $request->new_pin : $request->new_password);
            $actionsTaken[] = 'Restablecimiento de contraseña y Firma CFR 21';
        } elseif ($request->filled('new_pin')) {
            $updateData['pin_firma'] = Hash::make($request->new_pin);
            $actionsTaken[] = 'Actualización de PIN de Firma Electrónica';
        }

        if ($user->trashed() && $request->boolean('restore_account')) {
            $user->restore();
            $actionsTaken[] = 'Reactivación de cuenta';
        }

        $user->update($updateData);

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'DESBLOQUEO / RESET CREDENCIALES CFR 21',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'new_values' => json_encode([
                'usuario'  => $user->name,
                'acciones' => implode(', ', $actionsTaken),
            ]),
            'ip_address' => $request->ip(),
            'reason'     => $request->input('reason') ?: "Desbloqueo de seguridad y restablecimiento de credenciales para {$user->name}.",
        ]);

        return redirect()->route('users.index', ['tab' => 'users'])
            ->with('success', "Seguridad actualizada para {$user->name}: " . implode(' + ', $actionsTaken) . ".");
    }

    /**
     * Activa o desactiva lógicamente (SoftDelete) la cuenta de un usuario.
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
            $user->update(['failed_login_attempts' => 0]);

            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'REACTIVACIÓN DE USUARIO',
                'model_type' => User::class,
                'model_id'   => $user->id,
                'new_values' => json_encode(['usuario' => $user->name, 'estado' => 'ACTIVO']),
                'ip_address' => $request->ip(),
                'reason'     => "Reactivación de cuenta autorizada para {$user->name}.",
            ]);

            return redirect()->back()->with('success', "Cuenta de {$user->name} activada exitosamente.");
        }

        if ($user->id === auth()->id()) {
            return redirect()->back()->withErrors(['No puede inactivar su propia cuenta mientras tiene la sesión activa.']);
        }

        $user->delete();

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'INACTIVACIÓN DE USUARIO (SOFT DELETE)',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'new_values' => json_encode(['usuario' => $user->name, 'estado' => 'INACTIVO_CONSERVADO_CFR21']),
            'ip_address' => $request->ip(),
            'reason'     => "Baja lógica de usuario {$user->name} preservando historial de firmas electrónicas.",
        ]);

        return redirect()->back()->with('success', "Usuario {$user->name} inactivado exitosamente (trazabilidad histórica preservada).");
    }

    /**
     * Crea un nuevo Rol BPM en el sistema (opcionalmente clonando permisos de otro rol).
     */
    public function storeRole(Request $request)
    {
        $this->ensureRbacCatalog();

        $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'clone_from'  => 'nullable|integer',
        ]);

        $normalizedName = mb_strtoupper(trim($request->name));

        if (Role::whereRaw('UPPER(name) = ?', [$normalizedName])->exists()) {
            return redirect()->route('users.index', ['tab' => 'matrix'])
                ->withErrors(["El rol '{$normalizedName}' ya existe en el catálogo."]);
        }

        $roleData = ['name' => $normalizedName];
        if (Schema::hasColumn('roles', 'description')) {
            $roleData['description'] = $request->description;
        }

        $role = Role::create($roleData);

        if ($request->filled('clone_from')) {
            $sourceRole = Role::with('permissions')->find($request->clone_from);
            if ($sourceRole) {
                $role->permissions()->sync($sourceRole->permissions->pluck('id')->all());
            }
        }

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'CREACIÓN DE ROL BPM',
            'model_type' => Role::class,
            'model_id'   => $role->id,
            'new_values' => json_encode(['role' => $role->name, 'description' => $request->description]),
            'ip_address' => $request->ip(),
            'reason'     => "Creación del nuevo perfil regulatorio [{$role->name}].",
        ]);

        return redirect()->route('users.index', ['tab' => 'matrix', 'role_id' => $role->id])
            ->with('success', "Rol oficial '{$role->name}' creado exitosamente.");
    }

    /**
     * Elimina un rol personalizado si no tiene usuarios asignados.
     */
    public function destroyRole(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        $protectedRoles = ['ADMIN', 'OPERARIO', 'CALIDAD', 'DIRECTOR TECNICO Y DE PRODUCCION'];
        if (in_array(mb_strtoupper($role->name), $protectedRoles, true)) {
            return redirect()->route('users.index', ['tab' => 'matrix'])
                ->withErrors(["El rol estructural '{$role->name}' está protegido por el sistema."]);
        }

        $assignedCount = User::withTrashed()->whereRaw('UPPER(role) = ?', [mb_strtoupper($role->name)])->count();
        if ($assignedCount > 0) {
            return redirect()->route('users.index', ['tab' => 'matrix'])
                ->withErrors(["No se puede eliminar el rol '{$role->name}' porque tiene {$assignedCount} usuario(s) asignados."]);
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'ELIMINACIÓN DE ROL BPM',
            'model_type' => Role::class,
            'model_id'   => $roleId,
            'new_values' => json_encode(['deleted_role' => $roleName]),
            'ip_address' => $request->ip(),
            'reason'     => "Eliminación de rol sin usuarios asignados [{$roleName}].",
        ]);

        return redirect()->route('users.index', ['tab' => 'matrix'])
            ->with('success', "Rol '{$roleName}' eliminado correctamente.");
    }

    /**
     * Sincroniza los permisos de UN rol específico desde el configurador modular por tarjetas.
     */
    public function syncSingleRolePermissions(Request $request, $roleId)
    {
        $this->ensureRbacCatalog();

        $role = Role::findOrFail($roleId);
        $permNames = array_values(array_unique((array) $request->input('permission_names', [])));

        $permIds = Permission::whereIn('name', $permNames)->pluck('id')->all();
        $role->permissions()->sync($permIds);

        // Sincronizar también cualquier variante en minúscula del mismo nombre de rol
        $variants = Role::whereRaw('LOWER(name) = ?', [mb_strtolower($role->name)])
            ->where('id', '!=', $role->id)
            ->get();
        foreach ($variants as $variant) {
            $variant->permissions()->sync($permIds);
        }

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'ACTUALIZACIÓN PERMISOS DE ROL',
            'model_type' => Role::class,
            'model_id'   => $role->id,
            'new_values' => json_encode([
                'rol'              => $role->name,
                'total_permisos'   => count($permIds),
                'permisos_activos' => $permNames,
            ]),
            'ip_address' => $request->ip(),
            'reason'     => "Actualización de permisos del rol [{$role->name}] en configurador modular CFR 21.",
        ]);

        return redirect()->route('users.index', ['tab' => 'matrix', 'role_id' => $role->id])
            ->with('success', "Permisos del rol [{$role->name}] actualizados exitosamente (" . count($permIds) . " permisos habilitados).");
    }

    /**
     * Sincroniza la matriz global de permisos por rol y deja trazabilidad en el Audit Log.
     */
    public function syncRolePermissions(Request $request)
    {
        $this->ensureRbacCatalog();

        $permissionsData = $request->input('permissions', []);

        DB::transaction(function () use ($permissionsData, $request) {
            $roles = Role::all();
            foreach ($roles as $role) {
                if (array_key_exists($role->id, $permissionsData)) {
                    $rolePermissions = $permissionsData[$role->id] ?? [];
                    $role->permissions()->sync($rolePermissions);
                }
            }

            AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'ACTUALIZACIÓN MATRIZ RBAC',
                'model_type' => Role::class,
                'model_id'   => null,
                'new_values' => json_encode($permissionsData),
                'ip_address' => $request->ip(),
                'reason'     => 'Sincronización global de matriz de permisos (21 CFR Part 11).',
            ]);
        });

        return redirect()->route('users.index', ['tab' => 'matrix', 'view_mode' => 'table'])
            ->with('success', 'Matriz global de permisos (21 CFR Part 11) sincronizada exitosamente.');
    }
}
