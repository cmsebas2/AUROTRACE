@extends('layouts.app')

@section('header_title', 'Gobierno de Identidad, Roles & Permisos (IAM · 21 CFR Part 11)')

@section('content')
@php
    // Preparar datos enriquecidos para Alpine.js (Reactividad instantánea en Modales, Configurador Modular y Simulador)
    $usersJson = $users->map(function ($u) use ($auditStatsByUser) {
        $meta = $u->getProfileBadgeMeta();
        $custom = $u->getCustomPermissionArrays();
        $rolePerms = $u->getRolePermissionNames();
        $effectivePerms = $u->getEffectivePermissionNames();
        $stats = $auditStatsByUser[$u->id] ?? ['events' => 0, 'signatures' => 0];
        $shortUser = explode('@', $u->email)[0] ?? $u->email;

        return [
            'id'                    => $u->id,
            'name'                  => $u->name,
            'email'                 => $u->email,
            'short_user'            => $shortUser,
            'role'                  => $u->role,
            'area'                  => $meta['area'],
            'raw_area'              => $u->area ?? '',
            'signature_level'       => $meta['level'],
            'raw_signature_level'   => $u->signature_level ?? '',
            'badge_bg'              => $meta['badge_bg'],
            'avatar_bg'             => $meta['avatar_bg'],
            'icon'                  => $meta['icon'],
            'is_active'             => !$u->trashed(),
            'is_admin'              => $u->isAdmin(),
            'failed_login_attempts' => (int) ($u->failed_login_attempts ?? 0),
            'last_login'            => $u->last_login_at ? $u->last_login_at->format('Y-m-d H:i') : 'Nunca',
            'password_changed'      => $u->password_changed_at ? $u->password_changed_at->format('Y-m-d') : 'Estándar',
            'role_perms'            => array_values($rolePerms),
            'custom_grant'          => array_values($custom['grant']),
            'custom_revoke'         => array_values($custom['revoke']),
            'effective_perms'       => array_values($effectivePerms),
            'events_count'          => $stats['events'],
            'signatures_count'      => $stats['signatures'],
        ];
    })->values();

    $rolesJson = $roles->map(function ($r) use ($users) {
        $permNames = $r->permissions->pluck('name')->values()->all();
        $upperName = mb_strtoupper(trim($r->name));
        if (in_array($upperName, ['ADMIN', 'ADMINISTRADOR', 'SUPERADMIN', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA', 'DIRECTOR TECNICO Y DE PRODUCCION'])) {
            $permNames = array_keys(\App\Models\User::flatPermissionCatalog());
        }
        $userCount = $users->filter(fn($u) => mb_strtoupper(trim($u->role)) === $upperName)->count();

        return [
            'id'          => $r->id,
            'name'        => $r->name,
            'description' => $r->description ?? 'Perfil operativo regulado bajo protocolo 21 CFR Part 11.',
            'perm_names'  => $permNames,
            'users_count' => $userCount,
            'is_protected'=> in_array($upperName, ['ADMIN', 'OPERARIO', 'CALIDAD', 'DIRECTOR TECNICO Y DE PRODUCCION']),
        ];
    })->values();

    $initialRoleId = request('role_id') ?: ($roles->first()->id ?? null);
@endphp

<div class="w-full space-y-6 pb-16" x-data="iamGovernanceApp()">

    <!-- Notificaciones de Éxito / Error -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-sm">
                    <i class="fas fa-check text-sm"></i>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Operación IAM Registrada en Audit Trail</p>
                    <p class="text-sm font-semibold">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm">
            <div class="flex items-center space-x-2 mb-1">
                <i class="fas fa-triangle-exclamation text-red-600"></i>
                <span class="text-xs font-black uppercase tracking-wider">Alerta de Validación IAM</span>
            </div>
            <ul class="list-disc pl-6 text-xs font-semibold space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 1. HERO HEADER 3D (Gobierno de Identidad & Seguridad 21 CFR Part 11) -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white p-6 sm:p-8 shadow-2xl border border-slate-800">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2.5">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 font-mono text-[10px] font-black uppercase tracking-widest border border-cyan-500/30">
                        <i class="fas fa-fingerprint mr-1.5"></i>21 CFR Part 11 · IAM Core
                    </span>
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold border border-emerald-500/30 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                        Segregación Maker / Checker Activa
                    </span>
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 text-[10px] font-bold border border-slate-700">
                        9 Módulos · {{ $kpis['total_permissions'] }} Permisos Granulares
                    </span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Centro de Control de Usuarios, Roles y Permisos
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 font-medium max-w-3xl leading-relaxed">
                    Administre las credenciales del personal de planta, configure permisos granulares por rol o de forma individual por usuario para cada apartado de <strong>AUROTRACE</strong>, supervise firmas electrónicas y audite la bitácora inmutable.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button"
                        @click="openCreateRoleModal()"
                        class="px-4 py-2.5 rounded-2xl text-xs font-extrabold uppercase tracking-wider text-cyan-300 bg-slate-800/90 hover:bg-slate-800 border border-cyan-500/30 transition-all flex items-center space-x-2 shadow-sm">
                    <i class="fas fa-shield-halved text-xs"></i>
                    <span>+ Nuevo Rol BPM</span>
                </button>

                <button type="button"
                        @click="openCreateUserModal()"
                        class="px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-slate-900 bg-gradient-to-r from-cyan-400 to-cyan-300 hover:from-cyan-300 hover:to-cyan-200 shadow-3d-cyan transition-all flex items-center space-x-2">
                    <i class="fas fa-user-plus text-sm"></i>
                    <span>+ Nuevo Usuario Autorizado</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. TARJETAS KPIs EJECUTIVOS IAM -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- KPI 1: Usuarios Activos -->
        <div class="card-3d p-5 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Personal Autorizado</span>
                <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                    <i class="fas fa-users text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-display text-3xl font-black text-slate-800">{{ $kpis['active_users'] }}</span>
                <span class="text-xs font-bold text-emerald-600">Activos</span>
                @if($kpis['inactive_users'] > 0)
                    <span class="text-[11px] font-semibold text-slate-400">/ {{ $kpis['inactive_users'] }} Inactivos</span>
                @endif
            </div>
            <p class="mt-2 text-[11px] text-slate-500 font-medium">De {{ $kpis['total_users'] }} cuentas totales en BD</p>
        </div>

        <!-- KPI 2: Roles BPM -->
        <div class="card-3d p-5 relative overflow-hidden cursor-pointer" @click="tab = 'matrix'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Roles BPM Oficiales</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fas fa-user-tag text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-display text-3xl font-black text-slate-800">{{ $kpis['total_roles'] }}</span>
                <span class="text-xs font-bold text-indigo-600">Perfiles</span>
            </div>
            <p class="mt-2 text-[11px] text-slate-500 font-medium">Maker · Checker QA · DT · Admin</p>
        </div>

        <!-- KPI 3: Permisos Granulares -->
        <div class="card-3d p-5 relative overflow-hidden cursor-pointer" @click="tab = 'matrix'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Permisos del ERP</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fas fa-key text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-display text-3xl font-black text-slate-800">{{ $kpis['total_permissions'] }}</span>
                <span class="text-xs font-bold text-emerald-600">En 9 Módulos</span>
            </div>
            <p class="mt-2 text-[11px] text-slate-500 font-medium">Por Rol + Permisos Individuales</p>
        </div>

        <!-- KPI 4: Firmas Electrónicas CFR 21 -->
        <div class="card-3d p-5 relative overflow-hidden cursor-pointer" @click="tab = 'audit'">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Firmas CFR 21</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-file-signature text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-display text-3xl font-black text-slate-800">{{ number_format($kpis['total_signatures']) }}</span>
                <span class="text-xs font-bold text-purple-600">Sellos</span>
            </div>
            <p class="mt-2 text-[11px] text-slate-500 font-medium">{{ number_format($kpis['total_audits']) }} eventos en Audit Trail</p>
        </div>

        <!-- KPI 5: Estado de Seguridad / Bloqueos -->
        <div class="card-3d p-5 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Seguridad de Acceso</span>
                <div class="w-9 h-9 rounded-xl {{ $kpis['locked_users'] > 0 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center">
                    <i class="fas {{ $kpis['locked_users'] > 0 ? 'fa-lock' : 'fa-shield-check' }} text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-display text-3xl font-black {{ $kpis['locked_users'] > 0 ? 'text-red-600' : 'text-slate-800' }}">{{ $kpis['locked_users'] }}</span>
                <span class="text-xs font-bold {{ $kpis['locked_users'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $kpis['locked_users'] > 0 ? 'Con Intentos Fallidos' : 'Sin Bloqueos' }}
                </span>
            </div>
            <p class="mt-2 text-[11px] text-slate-500 font-medium">Monitoreo de credenciales activo</p>
        </div>
    </div>

    <!-- 3. NAVEGACIÓN POR PESTAÑAS PRINCIPALES -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="tab = 'users'"
                    :class="tab === 'users' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-2">
                <i class="fas fa-id-badge text-xs"></i>
                <span>1. Directorio de Usuarios</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black"
                      :class="tab === 'users' ? 'bg-cyan-400 text-slate-900' : 'bg-slate-100 text-slate-700'">
                    {{ $kpis['total_users'] }}
                </span>
            </button>

            <button type="button"
                    @click="tab = 'matrix'"
                    :class="tab === 'matrix' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-2">
                <i class="fas fa-sliders text-xs"></i>
                <span>2. Roles & Permisos por Módulo</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black"
                      :class="tab === 'matrix' ? 'bg-cyan-400 text-slate-900' : 'bg-slate-100 text-slate-700'">
                    {{ $kpis['total_permissions'] }}
                </span>
            </button>

            <button type="button"
                    @click="tab = 'simulator'"
                    :class="tab === 'simulator' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-2">
                <i class="fas fa-sitemap text-xs"></i>
                <span>3. Simulador & Mapa de Accesos</span>
            </button>

            <button type="button"
                    @click="tab = 'audit'"
                    :class="tab === 'audit' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all flex items-center space-x-2">
                <i class="fas fa-fingerprint text-xs"></i>
                <span>4. Audit Trail Forense (CFR 21)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black"
                      :class="tab === 'audit' ? 'bg-cyan-400 text-slate-900' : 'bg-slate-100 text-slate-700'">
                    {{ $audits->count() }}
                </span>
            </button>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- PESTAÑA 1: DIRECTORIO DE USUARIOS, CREDENCIALES Y PERMISOS INDIVIDUALES -->
    <!-- ======================================================================= -->
    <div x-show="tab === 'users'" x-cloak class="space-y-4">

        <!-- Barra de Búsqueda y Filtros Instantáneos -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Buscador -->
                <div class="relative min-w-[260px] flex-1 max-w-md">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text"
                           x-model="userFilters.search"
                           placeholder="Buscar por nombre, correo, usuario corto o rol..."
                           class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>

                <!-- Filtro por Rol -->
                <select x-model="userFilters.role"
                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">Todos los Roles BPM</option>
                    <template x-for="r in rolesList" :key="r.id">
                        <option :value="r.name" x-text="r.name"></option>
                    </template>
                </select>

                <!-- Filtro por Estado -->
                <select x-model="userFilters.status"
                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="all">Todos los Estados</option>
                    <option value="active">Solo Activos</option>
                    <option value="inactive">Solo Inactivos (Baja Lógica)</option>
                    <option value="locked">Con Intentos Fallidos</option>
                </select>
            </div>

            <div class="flex items-center space-x-2 text-xs font-bold text-slate-500">
                <span>Mostrando <strong class="text-slate-900" x-text="filteredUsers.length"></strong> de {{ $users->count() }} usuarios</span>
                <template x-if="userFilters.search || userFilters.role || userFilters.status !== 'all'">
                    <button type="button"
                            @click="userFilters = {search: '', role: '', status: 'all'}"
                            class="text-cyan-600 hover:text-cyan-800 underline ml-2">
                        Limpiar filtros
                    </button>
                </template>
            </div>
        </div>

        <!-- Tabla Avanzada de Usuarios -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-[10px] font-black uppercase tracking-wider">Identidad & Login</th>
                            <th class="px-5 py-3.5 text-left text-[10px] font-black uppercase tracking-wider">Rol BPM & Nivel CFR 21</th>
                            <th class="px-5 py-3.5 text-left text-[10px] font-black uppercase tracking-wider">Permisos Asignados</th>
                            <th class="px-5 py-3.5 text-left text-[10px] font-black uppercase tracking-wider">Actividad & Firmas</th>
                            <th class="px-5 py-3.5 text-left text-[10px] font-black uppercase tracking-wider">Seguridad & Estado</th>
                            <th class="px-5 py-3.5 text-right text-[10px] font-black uppercase tracking-wider">Gobierno & Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="u in filteredUsers" :key="u.id">
                            <tr class="hover:bg-slate-50/90 transition-colors" :class="!u.is_active ? 'opacity-65 bg-slate-50/50' : ''">
                                <!-- 1. Identidad & Login -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br text-white font-display font-black text-xs flex items-center justify-center shadow-sm flex-shrink-0"
                                             :class="u.avatar_bg">
                                            <span x-text="u.name.substring(0, 2).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm font-extrabold text-slate-900" x-text="u.name"></span>
                                                <span class="text-[10px] font-mono text-slate-400" x-text="'#' + u.id"></span>
                                            </div>
                                            <div class="flex items-center space-x-2 mt-0.5">
                                                <span class="text-xs text-slate-500 font-medium" x-text="u.email"></span>
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[10px] font-bold border border-slate-200"
                                                      title="Usuario corto para Login y Firma Electrónica">
                                                    <i class="fas fa-terminal text-[8px] mr-1 text-cyan-600"></i><span x-text="u.short_user"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Rol BPM & Nivel CFR 21 -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col items-start space-y-1">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border"
                                              :class="u.badge_bg">
                                            <i class="fas mr-1" :class="u.icon"></i>
                                            <span x-text="u.role"></span>
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tight" x-text="u.signature_level"></span>
                                        <span class="text-[10px] text-slate-400 font-medium" x-text="u.area"></span>
                                    </div>
                                </td>

                                <!-- 3. Permisos Efectivos -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs font-extrabold text-slate-800" x-text="u.effective_perms.length + ' / ' + totalPermissionsCount + ' permisos'"></span>
                                            <template x-if="u.custom_grant.length > 0">
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase"
                                                      title="Permisos adicionales otorgados individualmente">
                                                    +<span x-text="u.custom_grant.length"></span> Especial
                                                </span>
                                            </template>
                                            <template x-if="u.custom_revoke.length > 0">
                                                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[9px] font-black uppercase"
                                                      title="Permisos del rol restringidos individualmente">
                                                    -<span x-text="u.custom_revoke.length"></span> Restringido
                                                </span>
                                            </template>
                                        </div>
                                        <div class="w-36 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-cyan-500 to-blue-600 rounded-full"
                                                 :style="`width: ${Math.min(100, Math.round((u.effective_perms.length / totalPermissionsCount) * 100))}%`"></div>
                                        </div>
                                        <div class="flex items-center space-x-2 pt-0.5">
                                            <button type="button"
                                                    @click="openUserPermissionsModal(u)"
                                                    class="text-[11px] font-extrabold text-cyan-600 hover:text-cyan-800 flex items-center space-x-1">
                                                <i class="fas fa-key text-[10px]"></i>
                                                <span>Dar / Quitar Permisos</span>
                                            </button>
                                            <span class="text-slate-300">|</span>
                                            <button type="button"
                                                    @click="simulatorUserId = u.id; tab = 'simulator'"
                                                    class="text-[11px] font-bold text-slate-500 hover:text-slate-800"
                                                    title="Simular qué ve este usuario en la página">
                                                Simular vista
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- 4. Actividad & Firmas CFR 21 -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-center px-2.5 py-1 rounded-xl bg-purple-50 border border-purple-100">
                                            <div class="text-xs font-black text-purple-700" x-text="u.signatures_count"></div>
                                            <div class="text-[9px] font-bold uppercase text-purple-500">Firmas</div>
                                        </div>
                                        <div class="text-center px-2.5 py-1 rounded-xl bg-slate-50 border border-slate-200">
                                            <div class="text-xs font-black text-slate-700" x-text="u.events_count"></div>
                                            <div class="text-[9px] font-bold uppercase text-slate-400">Eventos</div>
                                        </div>
                                    </div>
                                    <div class="mt-1.5 text-[10px] text-slate-500">
                                        Últ. acceso: <strong class="font-mono text-slate-700" x-text="u.last_login"></strong>
                                    </div>
                                </td>

                                <!-- 5. Seguridad & Estado -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex flex-col items-start space-y-1">
                                        <template x-if="u.is_active">
                                            <span class="px-2.5 py-0.5 inline-flex items-center text-[10px] font-black uppercase rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>Activo
                                            </span>
                                        </template>
                                        <template x-if="!u.is_active">
                                            <span class="px-2.5 py-0.5 inline-flex items-center text-[10px] font-black uppercase rounded-full bg-red-100 text-red-800 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>Inactivo (SoftDelete)
                                            </span>
                                        </template>

                                        <template x-if="u.failed_login_attempts > 0">
                                            <span class="px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold">
                                                <i class="fas fa-triangle-exclamation mr-1"></i><span x-text="u.failed_login_attempts"></span> intento(s) fallido(s)
                                            </span>
                                        </template>
                                        <template x-if="u.failed_login_attempts === 0">
                                            <span class="text-[10px] text-slate-400 font-medium">
                                                <i class="fas fa-check-circle text-emerald-500 mr-1"></i>Firma CFR 21 Habilitada
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                <!-- 6. Acciones -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <!-- Configurar Permisos Individuales -->
                                        <button type="button"
                                                @click="openUserPermissionsModal(u)"
                                                class="p-2 rounded-xl bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200 transition"
                                                title="Configurar Permisos de este Usuario">
                                            <i class="fas fa-user-lock text-xs"></i>
                                        </button>

                                        <!-- Editar Perfil -->
                                        <button type="button"
                                                @click="openEditUserModal(u)"
                                                class="p-2 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition"
                                                title="Editar Perfil, Rol y Área">
                                            <i class="fas fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Reset Contraseña / PIN / Desbloquear -->
                                        <button type="button"
                                                @click="openSecurityModal(u)"
                                                class="p-2 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition"
                                                title="Cambiar Contraseña, PIN Firma CFR 21 o Desbloquear">
                                            <i class="fas fa-key text-xs"></i>
                                        </button>

                                        <!-- Ver Auditoría del Usuario -->
                                        <a :href="'{{ route('users.index') }}?tab=audit&audit_user=' + u.id"
                                           class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 transition"
                                           title="Ver Historial de Auditoría y Firmas de este Usuario">
                                            <i class="fas fa-clock-rotate-left text-xs"></i>
                                        </a>

                                        <!-- Activar / Inactivar -->
                                        <form :action="'/users/' + u.id + '/toggle'" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('¿Confirma cambiar el estado de acceso de este usuario? Su historial de firmas en lotes se conservará intacto (21 CFR Part 11).')"
                                                    class="p-2 rounded-xl border transition text-xs font-bold"
                                                    :class="u.is_active ? 'bg-orange-50 text-orange-600 hover:bg-orange-100 border-orange-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-200'"
                                                    :title="u.is_active ? 'Inactivar Cuenta (SoftDelete)' : 'Reactivar Cuenta'">
                                                <i class="fas" :class="u.is_active ? 'fa-user-slash' : 'fa-user-check'"></i>
                                            </button>
                                        </form>

                                        <!-- Eliminar Definitivamente -->
                                        <form :action="'/users/' + u.id + '/delete'" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('⚠️ ¿Está seguro de ELIMINAR DEFINITIVAMENTE este usuario? Se borrará por completo de la base de datos y ya no aparecerá en el directorio.')"
                                                    class="p-2 rounded-xl bg-red-600 text-white hover:bg-red-700 border border-red-700 transition text-xs font-bold shadow-xs"
                                                    title="Eliminar Usuario Definitivamente">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredUsers.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-bold text-xs uppercase tracking-wider">
                                No se encontraron usuarios con los criterios de búsqueda seleccionados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- PESTAÑA 2: ROLES BPM & MATRIZ DE PERMISOS POR MÓDULO (MAKER / CHECKER)  -->
    <!-- ======================================================================= -->
    <div x-show="tab === 'matrix'" x-cloak style="display: none;" class="space-y-6">

        <!-- Selector de Modo de Visualización -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-slate-900">Arquitectura de Permisos Basada en Roles (RBAC · 21 CFR Part 11)</h2>
                <p class="text-xs text-slate-500 font-medium">Configure qué opciones, pantallas y firmas electrónicas puede ejecutar cada Rol en los 9 módulos de AUROTRACE.</p>
            </div>

            <div class="flex items-center space-x-2 bg-slate-100 p-1 rounded-xl border border-slate-200">
                <button type="button"
                        @click="matrixViewMode = 'modular'"
                        :class="matrixViewMode === 'modular' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition">
                    <i class="fas fa-grip mr-1.5"></i>Configurador por Rol y Módulo
                </button>
                <button type="button"
                        @click="matrixViewMode = 'table'"
                        :class="matrixViewMode === 'table' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition">
                    <i class="fas fa-table-cells mr-1.5"></i>Matriz Global Cruzada
                </button>
            </div>
        </div>

        <!-- MODO 2A: CONFIGURADOR MODULAR POR ROL (TARJETAS DE LOS 9 MÓDULOS) -->
        <div x-show="matrixViewMode === 'modular'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Columna Izquierda: Lista de Roles BPM -->
            <div class="lg:col-span-4 space-y-3">
                <div class="bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-700">Perfiles / Roles BPM</span>
                        <button type="button"
                                @click="openCreateRoleModal()"
                                class="text-xs font-extrabold text-cyan-600 hover:text-cyan-800">
                            + Crear Rol
                        </button>
                    </div>

                    <div class="space-y-2 max-h-[620px] overflow-y-auto pr-1">
                        <template x-for="r in rolesList" :key="r.id">
                            <div @click="selectRoleForModular(r.id)"
                                 class="p-3.5 rounded-2xl border transition-all cursor-pointer flex items-center justify-between"
                                 :class="selectedRoleId === r.id ? 'bg-slate-900 text-white border-slate-900 shadow-md' : 'bg-slate-50/70 hover:bg-slate-100 text-slate-800 border-slate-200'">
                                <div class="min-w-0 pr-2">
                                    <div class="text-xs font-black uppercase tracking-wide truncate" x-text="r.name"></div>
                                    <div class="text-[10px] mt-1 flex items-center space-x-2"
                                         :class="selectedRoleId === r.id ? 'text-cyan-300' : 'text-slate-500'">
                                        <span><i class="fas fa-key mr-1"></i><span x-text="r.perm_names.length"></span> permisos</span>
                                        <span>•</span>
                                        <span><i class="fas fa-user mr-1"></i><span x-text="r.users_count"></span> usuario(s)</span>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs"
                                   :class="selectedRoleId === r.id ? 'text-cyan-400' : 'text-slate-300'"></i>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Los 9 Módulos del Rol Seleccionado -->
            <div class="lg:col-span-8">
                <template x-if="currentSelectedRole">
                    <form :action="'/users/roles/' + currentSelectedRole.id + '/sync-single'" method="POST" class="space-y-5">
                        @csrf

                        <!-- Cabecera del Rol Seleccionado + Plantillas Rápidas -->
                        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-cyan-50 text-cyan-700 border border-cyan-200">
                                            Editando Rol BPM
                                        </span>
                                        <span class="text-xs font-bold text-slate-400" x-text="selectedRolePerms.length + ' de ' + totalPermissionsCount + ' permisos activos'"></span>
                                    </div>
                                    <h3 class="font-display text-xl font-black text-slate-900 mt-1" x-text="currentSelectedRole.name"></h3>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <button type="submit"
                                            class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-[#005889] to-[#06B6D4] hover:opacity-95 text-white text-xs font-black uppercase tracking-wider shadow-3d-button transition flex items-center space-x-2">
                                        <i class="fas fa-floppy-disk"></i>
                                        <span>Guardar Permisos del Rol</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Preajustes / Plantillas Recomendadas -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1">Cargar Plantilla Rápida:</span>
                                <button type="button" @click="applyPresetToRole('ALL')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    Acceso Total (36/36)
                                </button>
                                <button type="button" @click="applyPresetToRole('QA_DIRECTOR')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition">
                                    Director / Coord. Calidad QA
                                </button>
                                <button type="button" @click="applyPresetToRole('QA_INSPECTOR')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 transition">
                                    Inspector de Calidad
                                </button>
                                <button type="button" @click="applyPresetToRole('ANALISTA_PROD')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 transition">
                                    Analista de Producción / Maquilas
                                </button>
                                <button type="button" @click="applyPresetToRole('OPERARIO_EBR')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition">
                                    Operario EBR (Maker)
                                </button>
                                <button type="button" @click="applyPresetToRole('NONE')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-red-50 hover:bg-red-100 text-red-600 transition">
                                    Desmarcar Todo
                                </button>
                            </div>
                        </div>

                        <!-- Tarjetas de los 9 Módulos del Sistema -->
                        <div class="space-y-4">
                            @foreach($permissionCatalog as $modKey => $module)
                                @php
                                    $modPermKeys = array_keys($module['permissions']);
                                @endphp
                                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-slate-900 text-cyan-400 flex items-center justify-center text-xs">
                                                <i class="fas {{ $module['icon'] }}"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">{{ $module['title'] }}</h4>
                                                    <span class="px-2 py-0.5 rounded bg-slate-200/70 text-slate-600 font-mono text-[10px] font-bold">{{ $module['route_label'] }}</span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 font-medium">{{ $module['description'] }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-2">
                                            <button type="button"
                                                    @click="toggleModuleInRole({{ json_encode($modPermKeys) }}, true)"
                                                    class="text-[10px] font-extrabold text-cyan-700 hover:underline">
                                                Marcar módulo
                                            </button>
                                            <span class="text-slate-300">|</span>
                                            <button type="button"
                                                    @click="toggleModuleInRole({{ json_encode($modPermKeys) }}, false)"
                                                    class="text-[10px] font-bold text-slate-400 hover:text-slate-600">
                                                Quitar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($module['permissions'] as $permKey => $permMeta)
                                            <label class="flex items-start space-x-3 p-3 rounded-2xl border transition cursor-pointer"
                                                   :class="selectedRolePerms.includes('{{ $permKey }}') ? 'bg-cyan-50/40 border-cyan-300 shadow-sm' : 'bg-white hover:bg-slate-50 border-slate-200'">
                                                <input type="checkbox"
                                                       name="permission_names[]"
                                                       value="{{ $permKey }}"
                                                       x-model="selectedRolePerms"
                                                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="text-xs font-extrabold text-slate-800 leading-snug">{{ $permMeta['label'] }}</span>
                                                        <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase flex-shrink-0
                                                            {{ $permMeta['type'] === 'MAKER' ? 'bg-blue-100 text-blue-800' : '' }}
                                                            {{ $permMeta['type'] === 'CHECKER' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                                            {{ $permMeta['type'] === 'FIRMA_CFR21' ? 'bg-purple-100 text-purple-800' : '' }}
                                                            {{ $permMeta['type'] === 'ADMIN' ? 'bg-rose-100 text-rose-800' : '' }}
                                                            {{ $permMeta['type'] === 'LECTURA' ? 'bg-slate-100 text-slate-700' : '' }}">
                                                            {{ $permMeta['type'] }}
                                                        </span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">{{ $permMeta['desc'] }}</p>
                                                    <span class="inline-block mt-1 font-mono text-[9px] text-slate-400">{{ $permKey }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODO 2B: MATRIZ GLOBAL CRUZADA (TABLA COMPARATIVA TODOS LOS ROLES) -->
        <div x-show="matrixViewMode === 'table'" style="display: none;">
            <form action="{{ route('users.roles.sync') }}" method="POST" class="space-y-4">
                @csrf
                <div class="bg-white rounded-3xl shadow-sm overflow-x-auto border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-900 text-white">
                            <tr>
                                <th class="px-4 py-3.5 text-left font-black uppercase tracking-wider sticky left-0 bg-slate-900 z-10 min-w-[280px]">
                                    Módulo / Permiso del Sistema
                                </th>
                                @foreach($roles as $role)
                                    <th class="px-3 py-3.5 text-center font-black uppercase tracking-wider text-cyan-300 min-w-[110px]">
                                        {{ $role->name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($permissionCatalog as $modKey => $module)
                                <tr class="bg-slate-100/90">
                                    <td colspan="{{ $roles->count() + 1 }}" class="px-4 py-2 font-black text-[11px] uppercase tracking-wider text-slate-800">
                                        <i class="fas {{ $module['icon'] }} text-cyan-600 mr-2"></i>{{ $module['title'] }}
                                        <span class="text-slate-400 font-mono ml-2">({{ $module['route_label'] }})</span>
                                    </td>
                                </tr>
                                @foreach($module['permissions'] as $permKey => $permMeta)
                                    @php
                                        $permModel = $permissions[$permKey] ?? null;
                                    @endphp
                                    @if($permModel)
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-4 py-3 sticky left-0 bg-white z-10 border-r border-slate-100">
                                                <div class="font-bold text-slate-800">{{ $permMeta['label'] }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">{{ $permKey }}</div>
                                            </td>
                                            @foreach($roles as $role)
                                                <td class="px-3 py-3 text-center">
                                                    <input type="checkbox"
                                                           name="permissions[{{ $role->id }}][]"
                                                           value="{{ $permModel->id }}"
                                                           class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-slate-300 rounded cursor-pointer"
                                                           {{ $role->permissions->contains('id', $permModel->id) ? 'checked' : '' }}>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black uppercase tracking-wider shadow-lg transition flex items-center space-x-2">
                        <i class="fas fa-check-double"></i>
                        <span>Sincronizar Matriz Global (21 CFR Part 11)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- PESTAÑA 3: SIMULADOR & MAPA DE ACCESOS DE LA PÁGINA (AUROTRACE)         -->
    <!-- ======================================================================= -->
    <div x-show="tab === 'simulator'" x-cloak style="display: none;" class="space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <span class="px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 text-[10px] font-black uppercase tracking-wider border border-cyan-200">
                        Diagnóstico de Permisos en Vivo
                    </span>
                    <h2 class="font-display text-xl font-black text-slate-900 mt-1">
                        Simulador de Acceso por Usuario en AUROTRACE
                    </h2>
                    <p class="text-xs text-slate-500">
                        Seleccione cualquier usuario para inspeccionar qué apartados del menú lateral y qué acciones del flujo EBR tiene habilitadas.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <label class="text-xs font-black uppercase text-slate-500">Inspeccionar Usuario:</label>
                    <select x-model.number="simulatorUserId"
                            class="rounded-2xl border border-slate-300 px-4 py-2.5 text-xs font-extrabold text-slate-800 bg-slate-50 focus:ring-cyan-500">
                        <template x-for="u in usersList" :key="u.id">
                            <option :value="u.id" x-text="u.name + ' (' + u.role + ')'"></option>
                        </template>
                    </select>
                    <template x-if="simulatedUser">
                        <button type="button"
                                @click="openUserPermissionsModal(simulatedUser)"
                                class="px-4 py-2.5 rounded-2xl bg-cyan-500 hover:bg-cyan-600 text-white text-xs font-black uppercase tracking-wider shadow-sm transition">
                            <i class="fas fa-sliders mr-1"></i>Modificar sus Permisos
                        </button>
                    </template>
                </div>
            </div>

            <template x-if="simulatedUser">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Vista Previa del Menú Lateral (Sidebar) de ese Usuario -->
                    <div class="lg:col-span-4 bg-slate-900 text-white rounded-3xl p-5 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-cyan-400">Simulación Menú Lateral</div>
                                <div class="text-sm font-black mt-0.5" x-text="simulatedUser.name"></div>
                                <div class="text-[10px] text-slate-400 font-mono" x-text="simulatedUser.role"></div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black"
                                  :class="simulatedUser.is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300'"
                                  x-text="simulatedUser.is_active ? 'CUENTA ACTIVA' : 'CUENTA INACTIVA'"></span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <template x-for="nav in simulatedSidebarItems" :key="nav.label">
                                <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border transition"
                                     :class="nav.allowed ? 'bg-slate-800/90 border-cyan-500/30 text-white' : 'bg-slate-900/40 border-slate-800 text-slate-500 line-through'">
                                    <div class="flex items-center space-x-2.5">
                                        <i class="fas w-4 text-center" :class="[nav.icon, nav.allowed ? 'text-cyan-400' : 'text-slate-600']"></i>
                                        <div>
                                            <div class="font-bold text-xs" x-text="nav.label"></div>
                                            <div class="text-[10px] font-mono text-slate-400" x-text="nav.route"></div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase"
                                          :class="nav.allowed ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-500'"
                                          x-text="nav.allowed ? 'VISIBLE' : 'OCULTO'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Desglose de Capacidades en los 9 Módulos -->
                    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($permissionCatalog as $modKey => $module)
                            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas {{ $module['icon'] }} text-cyan-600 text-xs"></i>
                                        <span class="text-xs font-black uppercase text-slate-800">{{ $module['title'] }}</span>
                                    </div>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $module['route_label'] }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach($module['permissions'] as $permKey => $permMeta)
                                        <div class="flex items-center justify-between px-2.5 py-1.5 rounded-xl bg-white border border-slate-200/80 text-[11px]">
                                            <span class="font-semibold text-slate-700 truncate pr-2">{{ $permMeta['label'] }}</span>
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase flex-shrink-0"
                                                  :class="simulatedUser.effective_perms.includes('{{ $permKey }}') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-400'"
                                                  x-text="simulatedUser.effective_perms.includes('{{ $permKey }}') ? 'AUTORIZADO' : 'BLOQUEADO'">
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- PESTAÑA 4: AUDIT TRAIL FORENSE & FIRMAS ELECTRÓNICAS (21 CFR PART 11)   -->
    <!-- ======================================================================= -->
    <div x-show="tab === 'audit'" x-cloak style="display: none;" class="space-y-4">

        <!-- Barra de Filtros Forenses -->
        <form method="GET" action="{{ route('users.index') }}" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap gap-4 items-end">
            <input type="hidden" name="tab" value="audit">

            <div class="flex-1 min-w-[220px]">
                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Búsqueda Forense (Lote, Acción, IP, Motivo)</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Ej. 604MT02, FIRMA, LIBERACION..."
                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold focus:ring-cyan-500 focus:border-cyan-500">
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Filtrar por Usuario</label>
                <select name="audit_user" class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700">
                    <option value="">Todos los Usuarios</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ (string) request('audit_user') === (string) $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-4 h-9">
                <label class="flex items-center space-x-2 text-xs font-extrabold text-purple-700 cursor-pointer">
                    <input type="checkbox" name="only_signatures" value="1" {{ request('only_signatures') ? 'checked' : '' }} class="rounded border-purple-300 text-purple-600 focus:ring-purple-500">
                    <span>Solo Firmas CFR 21</span>
                </label>

                <label class="flex items-center space-x-2 text-xs font-extrabold text-red-700 cursor-pointer">
                    <input type="checkbox" name="alert" value="1" {{ request('alert') ? 'checked' : '' }} class="rounded border-red-300 text-red-600 focus:ring-red-500">
                    <span>Solo Alertas y Desviaciones</span>
                </label>
            </div>

            <div class="ml-auto flex gap-2">
                @if(request('q') || request('alert') || request('audit_user') || request('only_signatures'))
                    <a href="{{ route('users.index', ['tab' => 'audit']) }}" class="bg-slate-100 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-200">
                        Limpiar
                    </a>
                @endif
                <button type="submit" class="bg-slate-900 text-white px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider hover:bg-slate-800 transition">
                    Filtrar Bitácora
                </button>
            </div>
        </form>

        <!-- Tabla Rígida de Auditoría Inmutable -->
        <div class="bg-white shadow-sm rounded-3xl border border-slate-200 overflow-hidden relative">
            <table class="tabla-rigida-audit">
                <thead class="bg-slate-900 sticky top-0 z-10 text-white">
                    <tr>
                        <th class="w-1/5 text-left px-4 py-3.5 text-[10px] font-black uppercase tracking-wider">Módulo / Acción</th>
                        <th class="w-1/6 text-left px-4 py-3.5 text-[10px] font-black uppercase tracking-wider">Fecha y Sello Hora</th>
                        <th class="w-1/6 text-left px-4 py-3.5 text-[10px] font-black uppercase tracking-wider">Ejecutor & IP</th>
                        <th class="w-1/2 text-left px-4 py-3.5 text-[10px] font-black uppercase tracking-wider">Narrativa Forense del Evento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($audits as $audit)
                        @php
                            $style = $audit->action_style;
                            $badge = $audit->module_badge;
                            $human = $audit->human_description;
                            $meta = $audit->metadata;
                        @endphp
                        <tr class="hover:bg-slate-50 transition cursor-pointer group"
                            @click="activeAudit = {{ json_encode([
                                'id' => $audit->id,
                                'date' => $audit->created_at ? $audit->created_at->format('d/m/Y H:i:s') : '',
                                'user' => $audit->user->name ?? 'Sistema',
                                'ip' => $audit->ip_address,
                                'module' => $badge,
                                'action' => mb_strtoupper($audit->action),
                                'style' => $style,
                                'description' => $human,
                                'old' => json_decode($audit->old_values, true),
                                'new' => json_decode($audit->clean_new_values, true),
                                'meta' => $meta,
                                'reason' => $audit->reason
                            ]) }}; openDrawer = true;">

                            <td class="p-4">
                                <div class="flex items-center space-x-2 mb-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider {{ $badge['color'] }}">
                                        {{ $badge['name'] }}
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-400">#{{ $audit->id }}</span>
                                </div>
                                <div class="flex items-center space-x-1.5" style="color: {{ $style['color'] }}">
                                    <span class="text-sm font-bold">{{ $style['icon'] }}</span>
                                    <span class="text-xs font-black uppercase tracking-tight">{{ $audit->action }}</span>
                                </div>
                            </td>

                            <td class="p-4 align-top">
                                <div class="text-xs font-bold text-slate-800">{{ $audit->created_at ? $audit->created_at->format('d M Y') : '-' }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $audit->created_at ? $audit->created_at->format('H:i:s') : '' }}</div>
                            </td>

                            <td class="p-4 align-top">
                                <div class="text-xs font-extrabold text-slate-900 truncate">{{ $audit->user->name ?? 'Sistema Automático' }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $audit->ip_address }}</div>
                            </td>

                            <td class="p-4 align-top">
                                <div class="text-xs sm:text-sm text-slate-700 font-medium leading-snug pr-4 group-hover:text-slate-900">
                                    {{ $human }}
                                </div>
                                <div class="mt-1.5 flex gap-2">
                                    <span class="text-[10px] text-cyan-600 font-extrabold opacity-0 group-hover:opacity-100 transition">Inspeccionar evidencia JSON &rarr;</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center p-12 text-slate-400 font-bold uppercase tracking-widest text-xs">
                                No se encontraron registros en el Audit Trail para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL 1: CREAR / EDITAR PERFIL DE USUARIO                               -->
    <!-- ======================================================================= -->
    <div x-show="showUserModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showUserModal" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showUserModal = false"></div>

            <div x-show="showUserModal"
                 x-transition
                 class="relative bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-200 z-10">
                <form :action="editMode ? '/users/' + userForm.id : '{{ route('users.store') }}'" method="POST">
                    @csrf
                    <template x-if="editMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="px-6 py-5 bg-slate-900 text-white flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-widest text-cyan-400">Credencial Electrónica 21 CFR Part 11</span>
                            <h3 class="text-lg font-black mt-0.5" x-text="editMode ? 'Editar Perfil de Usuario Autorizado' : 'Registrar Nuevo Usuario en Planta'"></h3>
                        </div>
                        <button type="button" @click="showUserModal = false" class="text-slate-400 hover:text-white">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Nombre Completo del Funcionario *</label>
                                <input type="text" name="name" x-model="userForm.name" required placeholder="Ej. Dra. Constanza Pérez / Juan Operario"
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold focus:ring-cyan-500 focus:border-cyan-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Correo o Identificador de Acceso (Login) *</label>
                                <input type="text" name="email" x-model="userForm.email" required placeholder="Ej. analista@aurofarma.com o simplemente: analista"
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold focus:ring-cyan-500 focus:border-cyan-500">
                                <p class="text-[11px] text-slate-400 mt-1">Si escribe solo el usuario sin <code>@</code>, el sistema asignará automáticamente <code>@aurofarma.com</code>.</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Rol Oficial BPM (Segregación Maker / Checker) *</label>
                                <select name="role" x-model="userForm.role" required
                                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-bold text-slate-800 focus:ring-cyan-500 focus:border-cyan-500">
                                    <option value="" disabled>Seleccione el Rol Regulatorio...</option>
                                    <template x-for="r in rolesList" :key="r.id">
                                        <option :value="r.name" x-text="r.name + ' (' + r.perm_names.length + ' permisos base)'"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Área Operativa</label>
                                <select name="area" x-model="userForm.area"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                    <option value="">Automática según Rol</option>
                                    <option value="PLANTA DE MANUFACTURA EBR">PLANTA DE MANUFACTURA EBR</option>
                                    <option value="ASEGURAMIENTO DE CALIDAD (QA)">ASEGURAMIENTO DE CALIDAD (QA)</option>
                                    <option value="DIRECCIÓN TÉCNICA & PLANTA">DIRECCIÓN TÉCNICA & PLANTA</option>
                                    <option value="PLANEACIÓN & MAQUILAS">PLANEACIÓN & MAQUILAS</option>
                                    <option value="CUSTODIA ARCHIVO FÍSICO 3D">CUSTODIA ARCHIVO FÍSICO 3D</option>
                                    <option value="SISTEMAS & GOBIERNO IAM">SISTEMAS & GOBIERNO IAM</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Nivel de Firma CFR 21</label>
                                <select name="signature_level" x-model="userForm.signature_level"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                    <option value="">Automático según Rol</option>
                                    <option value="MAKER / EJECUTOR EN PLANTA">MAKER / EJECUTOR EN PLANTA</option>
                                    <option value="CHECKER / VERIFICADOR QA">CHECKER / VERIFICADOR QA</option>
                                    <option value="LIBERADOR / APROBADOR DT">LIBERADOR / APROBADOR DT</option>
                                    <option value="SUPERUSUARIO / CUSTODIO CFR 21">SUPERUSUARIO / CUSTODIO CFR 21</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1"
                                       x-text="editMode ? 'Nueva Contraseña (Opcional)' : 'Contraseña de Acceso *'"></label>
                                <input type="password" name="password" :required="!editMode" minlength="6"
                                       placeholder="Mínimo 6 caracteres"
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm focus:ring-cyan-500">
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">PIN / Clave Firma CFR 21 (Opcional)</label>
                                <input type="password" name="pin_firma" minlength="4"
                                       placeholder="Vacío = misma contraseña"
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm focus:ring-cyan-500">
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3">
                        <button type="button" @click="showUserModal = false" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black uppercase tracking-wider shadow-md">
                            <i class="fas fa-save mr-1.5"></i>Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL 2: CONFIGURADOR DE PERMISOS INDIVIDUALES POR USUARIO (OVERRIDES)  -->
    <!-- ======================================================================= -->
    <div x-show="showUserPermsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showUserPermsModal" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showUserPermsModal = false"></div>

            <div x-show="showUserPermsModal"
                 x-transition
                 class="relative bg-white rounded-3xl shadow-2xl max-w-5xl w-full overflow-hidden border border-slate-200 z-10 max-h-[90vh] flex flex-col">
                <template x-if="permModalUser">
                    <form :action="'/users/' + permModalUser.id + '/permissions'" method="POST" class="flex flex-col max-h-[90vh]">
                        @csrf

                        <!-- Header -->
                        <div class="px-6 py-4 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-4 flex-shrink-0">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-mono text-[10px] font-black uppercase">
                                        Permisos Granulares por Usuario
                                    </span>
                                    <span class="text-xs text-slate-300 font-bold" x-text="permModalSelected.length + ' de ' + totalPermissionsCount + ' permisos activos'"></span>
                                </div>
                                <h3 class="text-lg font-black mt-0.5">
                                    Permisos de: <span class="text-cyan-400" x-text="permModalUser.name"></span>
                                </h3>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button" @click="permModalSelected = [...allPermissionKeys]" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-[11px] font-bold text-cyan-300">
                                    Habilitar Todos
                                </button>
                                <button type="button" @click="resetUserPermsToRole()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-[11px] font-bold text-slate-300">
                                    Restaurar a su Rol Base
                                </button>
                                <button type="button" @click="showUserPermsModal = false" class="text-slate-400 hover:text-white ml-2">
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Selector de Rol Base dentro del Modal -->
                        <div class="px-6 py-3 bg-slate-100 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 flex-shrink-0">
                            <div class="flex items-center space-x-3">
                                <label class="text-xs font-black uppercase text-slate-700">Rol Base del Usuario:</label>
                                <select name="role" x-model="permModalUser.role" @change="onPermModalRoleChange()"
                                        class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-extrabold text-slate-900 bg-white">
                                    <template x-for="r in rolesList" :key="r.id">
                                        <option :value="r.name" x-text="r.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="text-[11px] text-slate-600 font-medium">
                                Puede marcar o desmarcar cualquier permiso específico para este usuario sin alterar a los demás usuarios del mismo rol.
                            </div>
                        </div>

                        <!-- Cuerpo Scrollable con los 9 Módulos -->
                        <div class="p-6 overflow-y-auto space-y-4 flex-1">
                            @foreach($permissionCatalog as $modKey => $module)
                                @php
                                    $modPermKeys = array_keys($module['permissions']);
                                @endphp
                                <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas {{ $module['icon'] }} text-cyan-600 text-xs"></i>
                                            <span class="text-xs font-black uppercase text-slate-800">{{ $module['title'] }}</span>
                                            <span class="text-[10px] font-mono text-slate-400">{{ $module['route_label'] }}</span>
                                        </div>
                                        <div class="space-x-2">
                                            <button type="button" @click="toggleModuleInUserModal({{ json_encode($modPermKeys) }}, true)" class="text-[10px] font-extrabold text-cyan-700 hover:underline">Activar módulo</button>
                                            <span class="text-slate-300">|</span>
                                            <button type="button" @click="toggleModuleInUserModal({{ json_encode($modPermKeys) }}, false)" class="text-[10px] font-bold text-slate-400 hover:text-slate-600">Quitar módulo</button>
                                        </div>
                                    </div>
                                    <div class="p-3.5 grid grid-cols-1 md:grid-cols-2 gap-2.5 bg-white">
                                        @foreach($module['permissions'] as $permKey => $permMeta)
                                            <label class="flex items-start space-x-2.5 p-2.5 rounded-xl border transition cursor-pointer"
                                                   :class="permModalSelected.includes('{{ $permKey }}') ? 'bg-cyan-50/50 border-cyan-300' : 'border-slate-200 hover:bg-slate-50'">
                                                <input type="checkbox"
                                                       name="effective_permissions[]"
                                                       value="{{ $permKey }}"
                                                       x-model="permModalSelected"
                                                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-xs font-bold text-slate-800">{{ $permMeta['label'] }}</span>
                                                        <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $permMeta['type'] }}</span>
                                                    </div>
                                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $permMeta['desc'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 flex-shrink-0">
                            <input type="text" name="reason" placeholder="Justificación de auditoría (Opcional - 21 CFR Part 11)..."
                                   class="flex-1 min-w-[240px] rounded-xl border border-slate-300 px-3 py-2 text-xs">
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="showUserPermsModal = false" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#005889] to-[#06B6D4] text-white text-xs font-black uppercase tracking-wider shadow-md">
                                    <i class="fas fa-check mr-1.5"></i>Guardar Permisos del Usuario
                                </button>
                            </div>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL 3: DESBLOQUEO DE CUENTA & RESET DE CONTRASEÑA / FIRMA CFR 21      -->
    <!-- ======================================================================= -->
    <div x-show="showSecurityModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showSecurityModal" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showSecurityModal = false"></div>

            <div x-show="showSecurityModal"
                 x-transition
                 class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 z-10">
                <template x-if="securityUser">
                    <form :action="'/users/' + securityUser.id + '/unlock'" method="POST">
                        @csrf
                        <div class="px-6 py-4 bg-amber-500 text-slate-900 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest">Custodia de Seguridad CFR 21</span>
                                <h3 class="text-base font-black">Credenciales & Desbloqueo</h3>
                            </div>
                            <button type="button" @click="showSecurityModal = false" class="text-slate-900/70 hover:text-slate-900">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                                <div class="font-black text-slate-800" x-text="securityUser.name"></div>
                                <div class="text-slate-500 font-mono" x-text="securityUser.email"></div>
                                <div class="mt-1 text-[11px] font-bold text-amber-700">
                                    Intentos fallidos actuales: <span x-text="securityUser.failed_login_attempts"></span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Nueva Contraseña de Acceso (Opcional)</label>
                                <input type="password" name="new_password" minlength="6" placeholder="Dejar vacío para solo desbloquear intentos"
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Nuevo PIN de Firma Electrónica CFR 21 (Opcional)</label>
                                <input type="password" name="new_pin" minlength="4" placeholder="Dejar vacío para sincronizar con contraseña"
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                            </div>

                            <template x-if="!securityUser.is_active">
                                <label class="flex items-center space-x-2 text-xs font-bold text-emerald-700 cursor-pointer">
                                    <input type="checkbox" name="restore_account" value="1" checked class="rounded text-emerald-600">
                                    <span>Reactivar también esta cuenta inactiva</span>
                                </label>
                            </template>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 mb-1">Motivo para el Audit Trail</label>
                                <input type="text" name="reason" placeholder="Ej. Restablecimiento solicitado por el operario..."
                                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs">
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end space-x-2">
                            <button type="button" @click="showSecurityModal = false" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black uppercase">
                                Aplicar y Auditar
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL 4: CREAR NUEVO ROL BPM                                            -->
    <!-- ======================================================================= -->
    <div x-show="showRoleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="showRoleModal" x-transition.opacity class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showRoleModal = false"></div>

            <div x-show="showRoleModal"
                 x-transition
                 class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 z-10">
                <form action="{{ route('users.roles.store') }}" method="POST">
                    @csrf
                    <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-mono uppercase tracking-widest text-cyan-400">Arquitectura RBAC</span>
                            <h3 class="text-base font-black">Crear Nuevo Rol BPM</h3>
                        </div>
                        <button type="button" @click="showRoleModal = false" class="text-slate-400 hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-700 mb-1">Nombre del Rol / Cargo en Planta *</label>
                            <input type="text" name="name" required placeholder="Ej. SUPERVISOR DE ENVASE / JEFE DE ALMACÉN"
                                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-bold uppercase">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-slate-700 mb-1">Descripción Operativa</label>
                            <input type="text" name="description" placeholder="Ej. Responsable de línea de acondicionamiento y codificado"
                                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-slate-700 mb-1">Clonar Permisos Iniciales Desde (Opcional)</label>
                            <select name="clone_from" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">
                                <option value="">Iniciar con 0 permisos (Configurar luego)</option>
                                <template x-for="r in rolesList" :key="r.id">
                                    <option :value="r.id" x-text="r.name + ' (' + r.perm_names.length + ' permisos)'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end space-x-2">
                        <button type="button" @click="showRoleModal = false" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-600 text-white text-xs font-black uppercase">
                            Crear Rol Oficial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- DRAWER LATERAL DE AUDITORÍA FORENSE (DETALLE DEL EVENTO)                -->
    <!-- ======================================================================= -->
    <div x-show="openDrawer" x-cloak class="fixed inset-0 overflow-hidden z-50" style="display: none;">
        <div class="absolute inset-0 overflow-hidden">
            <div x-show="openDrawer" x-transition.opacity class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" @click="openDrawer = false"></div>
            <div class="fixed inset-y-0 right-0 max-w-full flex">
                <div x-show="openDrawer"
                     x-transition:enter="transform transition ease-in-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-md">
                    <div class="h-full flex flex-col bg-white shadow-2xl overflow-y-scroll">
                        <template x-if="activeAudit">
                            <div>
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between" :style="`background-color: ${activeAudit.style.bg}`">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-2xl" :style="`color: ${activeAudit.style.color}`" x-text="activeAudit.style.icon"></span>
                                        <h2 class="text-base font-black text-slate-800 uppercase tracking-tight" x-text="activeAudit.action"></h2>
                                    </div>
                                    <button @click="openDrawer = false" class="text-slate-400 hover:text-slate-600">
                                        <i class="fas fa-times text-lg"></i>
                                    </button>
                                </div>

                                <div class="p-6 space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Módulo</div>
                                            <span class="px-2 py-1 rounded text-xs font-bold" :class="activeAudit.module.color" x-text="activeAudit.module.name"></span>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Sello de Tiempo</div>
                                            <div class="text-xs font-bold text-slate-800 font-mono" x-text="activeAudit.date"></div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Firma / Ejecutor</div>
                                            <div class="text-xs font-bold text-slate-800" x-text="activeAudit.user"></div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">IP de Terminal</div>
                                            <div class="text-xs font-bold text-slate-800 font-mono" x-text="activeAudit.ip"></div>
                                        </div>
                                    </div>

                                    <hr class="border-slate-100">

                                    <div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Narrativa del Evento</div>
                                        <div class="bg-slate-50 rounded-2xl p-4 text-xs sm:text-sm text-slate-700 leading-relaxed border border-slate-200/80" x-text="activeAudit.description"></div>
                                        <template x-if="activeAudit.reason">
                                            <div class="mt-2 text-xs italic text-slate-500 border-l-2 border-cyan-500 pl-2">Justificación: <span x-text="activeAudit.reason"></span></div>
                                        </template>
                                    </div>

                                    <template x-if="activeAudit.old && Object.keys(activeAudit.old).length > 0">
                                        <div>
                                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Comparativa Antes / Después</div>
                                            <div class="space-y-2.5">
                                                <template x-for="(val, key) in activeAudit.new" :key="key">
                                                    <template x-if="activeAudit.old[key] !== undefined && activeAudit.old[key] !== val">
                                                        <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-xs">
                                                            <div class="text-xs font-bold text-slate-800 mb-1.5" x-text="key"></div>
                                                            <div class="flex items-center space-x-2 text-xs font-mono">
                                                                <div class="flex-1 bg-red-50 text-red-700 p-2 rounded line-through overflow-hidden text-ellipsis" x-text="typeof activeAudit.old[key] === 'object' ? JSON.stringify(activeAudit.old[key]) : activeAudit.old[key]"></div>
                                                                <div class="text-slate-400">&rarr;</div>
                                                                <div class="flex-1 bg-emerald-50 text-emerald-700 p-2 rounded overflow-hidden text-ellipsis" x-text="typeof val === 'object' ? JSON.stringify(val) : val"></div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <div x-data="{ showRaw: false }" class="border border-slate-200 rounded-2xl overflow-hidden">
                                        <button type="button" @click="showRaw = !showRaw" class="w-full bg-slate-50 px-4 py-3 flex justify-between items-center hover:bg-slate-100 transition">
                                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Evidencia Técnica (JSON)</span>
                                            <span class="text-slate-400" x-text="showRaw ? '▲' : '▼'"></span>
                                        </button>
                                        <div x-show="showRaw" class="p-4 bg-slate-900 text-emerald-400 font-mono text-[10px] overflow-x-auto">
                                            <pre x-text="JSON.stringify(activeAudit.new, null, 2)"></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<style>
    .tabla-rigida-audit { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .tabla-rigida-audit th, .tabla-rigida-audit td { border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    pre { white-space: pre-wrap; word-wrap: break-word; }
</style>

<script>
    function iamGovernanceApp() {
        const usersData = @json($usersJson);
        const rolesData = @json($rolesJson);
        const allPermKeys = @json(array_keys($flatCatalog));

        return {
            tab: '{{ request('tab', 'users') }}',
            matrixViewMode: '{{ request('view_mode', 'modular') }}',
            usersList: usersData,
            rolesList: rolesData,
            allPermissionKeys: allPermKeys,
            totalPermissionsCount: allPermKeys.length,

            // Filtros del Directorio de Usuarios
            userFilters: {
                search: '',
                role: '',
                status: 'all'
            },

            // Configurador Modular por Rol
            selectedRoleId: {{ $initialRoleId ? (int) $initialRoleId : 'null' }},
            selectedRolePerms: [],

            // Simulador de Accesos
            simulatorUserId: usersData.length > 0 ? usersData[0].id : null,

            // Modales
            showUserModal: false,
            editMode: false,
            userForm: { id: null, name: '', email: '', role: '', area: '', signature_level: '' },

            showUserPermsModal: false,
            permModalUser: null,
            permModalSelected: [],

            showSecurityModal: false,
            securityUser: null,

            showRoleModal: false,

            // Audit Drawer
            activeAudit: null,
            openDrawer: false,

            init() {
                if (this.selectedRoleId) {
                    this.selectRoleForModular(this.selectedRoleId);
                } else if (this.rolesList.length > 0) {
                    this.selectRoleForModular(this.rolesList[0].id);
                }
            },

            get filteredUsers() {
                return this.usersList.filter(u => {
                    const q = this.userFilters.search.toLowerCase().trim();
                    const matchesSearch = !q ||
                        u.name.toLowerCase().includes(q) ||
                        u.email.toLowerCase().includes(q) ||
                        u.short_user.toLowerCase().includes(q) ||
                        u.role.toLowerCase().includes(q) ||
                        u.area.toLowerCase().includes(q);

                    const matchesRole = !this.userFilters.role ||
                        u.role.toUpperCase() === this.userFilters.role.toUpperCase();

                    let matchesStatus = true;
                    if (this.userFilters.status === 'active') matchesStatus = u.is_active;
                    if (this.userFilters.status === 'inactive') matchesStatus = !u.is_active;
                    if (this.userFilters.status === 'locked') matchesStatus = u.failed_login_attempts > 0;

                    return matchesSearch && matchesRole && matchesStatus;
                });
            },

            get currentSelectedRole() {
                return this.rolesList.find(r => r.id === this.selectedRoleId) || null;
            },

            get simulatedUser() {
                return this.usersList.find(u => u.id === this.simulatorUserId) || null;
            },

            get simulatedSidebarItems() {
                const u = this.simulatedUser;
                if (!u) return [];
                const p = u.effective_perms;
                return [
                    { label: 'Centro de Monitoreo', route: '/dashboard', icon: 'fa-chart-pie', allowed: p.includes('ver_dashboard') },
                    { label: 'Catálogo & Fórmulas', route: '/productos', icon: 'fa-flask', allowed: p.includes('ver_productos') },
                    { label: 'OPs Activas Planta', route: '/ops/activas', icon: 'fa-clipboard-list', allowed: p.includes('ver_monitoreo_ops') },
                    { label: 'Ejecución de Lotes EBR', route: '/ops/ejecucion', icon: 'fa-industry', allowed: p.includes('ejecutar_manufactura') },
                    { label: 'Expedientes Batch Record', route: '/batch-records', icon: 'fa-file-pdf', allowed: p.includes('ver_expedientes_batch_records') },
                    { label: 'Dictamen Calidad (QA)', route: '/calidad', icon: 'fa-shield-halved', allowed: p.includes('ver_aseguramiento_calidad') },
                    { label: 'Consultas BR (Archivo 3D)', route: '/consultas-br', icon: 'fa-boxes-packing', allowed: p.includes('ver_modulo_archivo_3d') },
                    { label: 'Genealogía 360° & Liberación', route: '/genealogia', icon: 'fa-bolt', allowed: p.includes('ver_genealogia') },
                    { label: 'Maquilas Externas', route: '/maquilas', icon: 'fa-truck-ramp-box', allowed: p.includes('acceso_maquilas_externas') },
                    { label: 'Usuarios & Permisos IAM', route: '/users', icon: 'fa-user-lock', allowed: p.includes('gestionar_usuarios_roles') || p.includes('gestionar_ajustes_sistema') },
                ];
            },

            selectRoleForModular(roleId) {
                this.selectedRoleId = roleId;
                const found = this.rolesList.find(r => r.id === roleId);
                this.selectedRolePerms = found ? [...found.perm_names] : [];
            },

            toggleModuleInRole(keys, enable) {
                if (enable) {
                    this.selectedRolePerms = Array.from(new Set([...this.selectedRolePerms, ...keys]));
                } else {
                    this.selectedRolePerms = this.selectedRolePerms.filter(k => !keys.includes(k));
                }
            },

            applyPresetToRole(preset) {
                const presets = {
                    'ALL': [...this.allPermissionKeys],
                    'NONE': [],
                    'QA_DIRECTOR': [
                        'ver_dashboard', 'ver_productos', 'imprimir_ficha_tecnica', 'aprobar_op',
                        'ver_monitoreo_ops', 'verificar_ajuste_activos', 'aprobar_codificado',
                        'verificacion_controles_en_proceso', 'realizar_despeje_y_muestreo',
                        'ver_aseguramiento_calidad', 'registrar_coas', 'aprobar_coas',
                        'dictaminar_calidad_qa', 'ver_expedientes_batch_records', 'ver_genealogia',
                        'liberacion_final_lote', 'firmar_liberacion', 'ver_modulo_archivo_3d',
                        'gestionar_archivo_fisico_3d', 'acceso_maquilas_externas',
                        'revision_calidad_maquila', 'ver_audit_trail', 'ver_auditoria'
                    ],
                    'QA_INSPECTOR': [
                        'ver_productos', 'ver_monitoreo_ops', 'verificar_ajuste_activos',
                        'aprobar_codificado', 'realizar_despeje_y_muestreo',
                        'verificacion_controles_en_proceso', 'ver_aseguramiento_calidad',
                        'registrar_coas', 'dictaminar_calidad_qa', 'ver_expedientes_batch_records',
                        'ver_modulo_archivo_3d', 'gestionar_archivo_fisico_3d',
                        'acceso_maquilas_externas', 'revision_calidad_maquila'
                    ],
                    'ANALISTA_PROD': [
                        'ver_dashboard', 'ver_productos', 'gestionar_productos_formulas',
                        'configurar_instructivos_ebr', 'imprimir_ficha_tecnica', 'crear_op',
                        'ver_monitoreo_ops', 'registrar_ajuste_activos', 'solicitar_aprobacion_codificado',
                        'ejecutar_manufactura', 'ver_expedientes_batch_records', 'ver_modulo_archivo_3d',
                        'gestionar_archivo_fisico_3d', 'acceso_maquilas_externas',
                        'gestionar_ordenes_maquila', 'registrar_recepcion_maquila',
                        'acceder_reacondicionamiento', 'gestionar_inventario'
                    ],
                    'OPERARIO_EBR': [
                        'ver_dashboard', 'ver_productos', 'ejecutar_manufactura',
                        'registrar_conciliacion_materiales', 'realizar_despeje_y_muestreo',
                        'registrar_manufactura_y_pesaje', 'registrar_envasado_acondicionamiento',
                        'registrar_ajuste_activos', 'solicitar_aprobacion_codificado'
                    ]
                };
                this.selectedRolePerms = presets[preset] || [];
            },

            openCreateUserModal() {
                this.editMode = false;
                this.userForm = {
                    id: null,
                    name: '',
                    email: '',
                    role: this.rolesList.length > 0 ? this.rolesList[0].name : 'OPERARIO',
                    area: '',
                    signature_level: ''
                };
                this.showUserModal = true;
            },

            openEditUserModal(u) {
                this.editMode = true;
                this.userForm = {
                    id: u.id,
                    name: u.name,
                    email: u.email,
                    role: u.role,
                    area: u.raw_area || '',
                    signature_level: u.raw_signature_level || ''
                };
                this.showUserModal = true;
            },

            openUserPermissionsModal(u) {
                this.permModalUser = JSON.parse(JSON.stringify(u));
                this.permModalSelected = [...u.effective_perms];
                this.showUserPermsModal = true;
            },

            onPermModalRoleChange() {
                if (!this.permModalUser) return;
                const roleObj = this.rolesList.find(r => r.name.toUpperCase() === this.permModalUser.role.toUpperCase());
                if (roleObj) {
                    this.permModalSelected = [...roleObj.perm_names];
                }
            },

            resetUserPermsToRole() {
                this.onPermModalRoleChange();
            },

            toggleModuleInUserModal(keys, enable) {
                if (enable) {
                    this.permModalSelected = Array.from(new Set([...this.permModalSelected, ...keys]));
                } else {
                    this.permModalSelected = this.permModalSelected.filter(k => !keys.includes(k));
                }
            },

            openSecurityModal(u) {
                this.securityUser = u;
                this.showSecurityModal = true;
            },

            openCreateRoleModal() {
                this.showRoleModal = true;
            }
        };
    }
</script>
@endpush
@endsection
