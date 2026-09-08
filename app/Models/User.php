<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\AuditableTrait;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'failed_login_attempts',
        'last_login_at',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Scope dinámico: Usuarios cuyo rol tiene un permiso específico
     */
    public function scopeWithPermission($query, $permissionName)
    {
        $roles = \App\Models\Role::whereHas('permissions', function($q) use ($permissionName) {
            $q->where('name', $permissionName);
        })->pluck('name');

        return $query->whereIn('role', $roles);
    }

    /**
     * Scopes legados refactorizados a dinámica de permisos (RBAC) con fallbacks seguros
     */
    public function scopeOperarios($query)
    {
        $roles = \App\Models\Role::whereHas('permissions', function($q) {
            $q->where('name', 'registrar_manufactura_y_pesaje');
        })->pluck('name')->toArray();

        $roles = array_unique(array_merge($roles, ['OPERARIO', 'Operario', 'operario', 'ADMIN', 'admin']));

        return $query->whereIn('role', $roles);
    }

    public function scopeCalidad($query)
    {
        $roles = \App\Models\Role::whereHas('permissions', function($q) {
            $q->where('name', 'verificacion_controles_en_proceso');
        })->pluck('name')->toArray();

        $roles = array_unique(array_merge($roles, [
            'CALIDAD', 'Calidad', 'calidad',
            'INSPECTOR DE CALIDAD', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
            'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'direccion_tecnica',
            'ADMIN', 'admin'
        ]));

        return $query->whereIn('role', $roles);
    }

    /**
     * Helper para verificar roles (Enum-based, insensible a mayúsculas/minúsculas).
     */
    public function hasRole($roles)
    {
        $userRole = mb_strtoupper(trim($this->role ?? ''));
        if (is_array($roles)) {
            $upperRoles = array_map(fn($r) => mb_strtoupper(trim($r)), $roles);
            return in_array($userRole, $upperRoles);
        }
        return $userRole === mb_strtoupper(trim($roles));
    }

    /**
     * Check if user's role has a specific permission (con bypass Admin y caché de request).
     */
    public function hasPermission($permissionName)
    {
        // Los roles administradores tienen acceso irrestricto
        if ($this->hasRole(['ADMIN', 'Administrador', 'admin', 'SUPERADMIN', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA'])) {
            return true;
        }

        static $rolePermissionsCache = [];
        $roleKey = mb_strtolower(trim($this->role ?? ''));

        if (!isset($rolePermissionsCache[$roleKey])) {
            try {
                $role = \App\Models\Role::with('permissions')
                    ->whereRaw('LOWER(name) = ?', [$roleKey])
                    ->first();
                $rolePermissionsCache[$roleKey] = $role ? $role->permissions->pluck('name')->all() : [];
            } catch (\Throwable $e) {
                $rolePermissionsCache[$roleKey] = [];
            }
        }

        return in_array($permissionName, $rolePermissionsCache[$roleKey]);
    }
}
