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
     * Scopes legados refactorizados a dinámica de permisos (RBAC)
     */
    public function scopeOperarios($query)
    {
        return $this->scopeWithPermission($query, 'registrar_manufactura_y_pesaje');
    }

    public function scopeCalidad($query)
    {
        // Personal con capacidad de auditoría o verificación crítica
        return $this->scopeWithPermission($query, 'verificacion_controles_en_proceso');
    }

    /**
     * Helper para verificar roles (Enum-based).
     */
    public function hasRole($roles)
    {
        if (is_array($roles)) {
            $upperRoles = array_map('mb_strtoupper', $roles);
            return in_array(mb_strtoupper($this->role), $upperRoles);
        }
        return mb_strtoupper($this->role) === mb_strtoupper($roles);
    }

    /**
     * Check if user's role has a specific permission
     */
    public function hasPermission($permissionName)
    {
        $role = \App\Models\Role::with('permissions')->where('name', $this->role)->first();
        if (!$role) return false;
        
        return $role->permissions->contains('name', $permissionName);
    }
}
