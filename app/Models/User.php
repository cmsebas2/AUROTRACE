<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\AuditableTrait;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ============================================================================
 * MODELO DE USUARIO E IDENTIDAD ELECTRÓNICA (IAM / CFR 21) - User
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Representa a cada persona autorizada en la planta (Operarios, Inspectores de
 * Calidad, Dirección Técnica, Administradores).
 *
 * Características clave:
 * - Usa `SoftDeletes`: Si un empleado se retira, su cuenta se desactiva (`deleted_at`),
 *   pero su historial de firmas en lotes antiguos jamás se pierde (Artículo 4 GEMINI.md).
 * - Control de Permisos (RBAC): El método `hasPermission()` consulta qué permisos
 *   tiene asignado el rol del usuario.
 * ============================================================================
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, AuditableTrait;

    /**
     * Campos asignables masivamente.
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
     * Campos ocultos al convertir el modelo a JSON o array.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión automática de tipos de datos (Fechas y Contraseña encriptada).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'last_login_at'       => 'datetime',
            'password_changed_at' => 'datetime',
            'password'            => 'hashed',
        ];
    }

    /**
     * Scope dinámico: Filtra usuarios cuyo rol posea un permiso específico.
     */
    public function scopeWithPermission($query, $permissionName)
    {
        $roles = Role::whereHas('permissions', function ($q) use ($permissionName) {
            $q->where('name', $permissionName);
        })->pluck('name');

        return $query->whereIn('role', $roles);
    }

    /**
     * Filtra usuarios habilitados para ejecutar pesaje y manufactura en planta.
     */
    public function scopeOperarios($query)
    {
        $roles = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'registrar_manufactura_y_pesaje');
        })->pluck('name')->toArray();

        $roles = array_unique(array_merge($roles, ['OPERARIO', 'Operario', 'operario', 'ADMIN', 'admin']));

        return $query->whereIn('role', $roles);
    }

    /**
     * Filtra usuarios habilitados para realizar verificaciones de Aseguramiento de Calidad (QA).
     */
    public function scopeCalidad($query)
    {
        $roles = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'verificacion_controles_en_proceso');
        })->pluck('name')->toArray();

        $roles = array_unique(array_merge($roles, [
            'CALIDAD', 'Calidad', 'calidad',
            'INSPECTOR DE CALIDAD', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
            'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'direccion_tecnica',
            'ADMIN', 'admin',
        ]));

        return $query->whereIn('role', $roles);
    }

    /**
     * Verifica si el usuario tiene uno o varios roles (insensible a mayúsculas/minúsculas).
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
     * Indica si el usuario tiene rol de Administrador del sistema.
     */
    public function isAdmin()
    {
        return $this->hasRole(['ADMIN', 'Administrador', 'admin', 'SUPERADMIN']);
    }

    /**
     * Verifica si el rol del usuario cuenta con un permiso específico (con caché en memoria por petición).
     */
    public function hasPermission($permissionName)
    {
        // Los roles administradores y Dirección Técnica tienen acceso maestro
        if ($this->hasRole(['ADMIN', 'Administrador', 'admin', 'SUPERADMIN', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA'])) {
            return true;
        }

        static $rolePermissionsCache = [];
        $roleKey = mb_strtolower(trim($this->role ?? ''));

        if (!isset($rolePermissionsCache[$roleKey])) {
            try {
                $role = Role::with('permissions')
                    ->whereRaw('LOWER(name) = ?', [$roleKey])
                    ->first();
                $rolePermissionsCache[$roleKey] = $role ? $role->permissions->pluck('name')->all() : [];
            } catch (\Throwable $e) {
                $rolePermissionsCache[$roleKey] = [];
            }
        }

        return in_array($permissionName, $rolePermissionsCache[$roleKey]);
    }

    /**
     * Identifica si el usuario pertenece al perfil exclusivo de Calidad (QA).
     */
    public function isQualityUser()
    {
        if ($this->hasRole(['ADMIN', 'admin', 'Administrador', 'SUPERADMIN', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'direccion_tecnica'])) {
            return false;
        }

        return $this->hasRole([
            'CALIDAD', 'calidad',
            'INSPECTOR DE CALIDAD', 'AUXILIAR DE CALIDAD',
            'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
            'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
        ]);
    }
}
