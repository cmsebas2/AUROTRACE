@extends('layouts.app')

@section('header_title', 'Gestión de Usuarios y Roles (IAM)')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg flex items-center justify-between" role="alert">
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ tab: '{{ request('tab', 'users') }}', showModal: false, editMode: false, form: {}, filters: {fecha: '', hora: '', ejecutor: '', ip: '', accion: '', contexto: ''}, activeAudit: null, openDrawer: false }">
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="tab = 'users'" :class="{'border-aurofarma-teal text-aurofarma-teal': tab === 'users', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'users'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition font-bold">
                    Directorio de Usuarios
                </button>
                <button @click="tab = 'audit'" :class="{'border-aurofarma-teal text-aurofarma-teal': tab === 'audit', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'audit'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition font-bold">
                    Audit Trail (CFR 21)
                </button>
                <button @click="tab = 'matrix'" :class="{'border-aurofarma-teal text-aurofarma-teal': tab === 'matrix', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'matrix'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition font-bold">
                    Matriz de Permisos
                </button>
            </nav>
        </div>

        <!-- Tab Content: Users -->
        <div x-show="tab === 'users'" x-cloak>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-slate-800">Personas Autorizadas</h2>
                <button @click="showModal = true; editMode = false; form = {role: ''}" class="bg-aurofarma-teal hover:bg-teal-600 text-white font-bold py-2 px-4 rounded shadow transition">
                    + Nuevo Usuario
                </button>
            </div>

            <div class="bg-white shadow rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-800 text-white">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Correo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Rol (BPM)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Último Acceso</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $u)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $u->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $u->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $u->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 uppercase">
                                    {{ str_replace('_', ' ', $u->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $u->last_login_at ? $u->last_login_at->format('Y-m-d H:i') : 'Nunca' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($u->trashed())
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button @click="showModal = true; editMode = true; form = {id: '{{ $u->id }}', name: '{{ $u->name }}', email: '{{ $u->email }}', role: '{{ $u->role }}'}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</button>
                                
                                <form action="{{ route('users.toggle', $u->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $u->trashed() ? 'text-green-600 hover:text-green-900 font-bold' : 'text-red-500 hover:text-red-700' }}" onclick="return confirm('¿Confirmas el cambio de estado biológico para firmas CFR 21?')">
                                        {{ $u->trashed() ? 'Activar' : 'Desactivar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Audit Trail -->
        <div x-show="tab === 'audit'" x-cloak style="display: none;">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-slate-800">Audit Trail (CFR 21 Part 11)</h2>
            </div>
            
            <form method="GET" action="{{ route('users.index') }}" class="mb-4 flex flex-wrap gap-4 items-end bg-slate-50 p-4 rounded border border-slate-200">
                <input type="hidden" name="tab" value="audit">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Búsqueda General (Lote, Usuario, Razón...)</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Ej. 604MT02" class="border border-slate-300 rounded px-3 py-2 text-sm focus:ring-aurofarma-teal focus:border-aurofarma-teal w-64 shadow-sm">
                </div>
                <div class="flex items-center h-9">
                    <label class="flex items-center space-x-2 text-sm font-bold text-red-700 cursor-pointer">
                        <input type="checkbox" name="alert" value="1" {{ request('alert') ? 'checked' : '' }} class="rounded border-red-300 text-red-600 focus:ring-red-500">
                        <span>Solo Alertas y Desviaciones</span>
                    </label>
                </div>
                <div class="ml-auto flex gap-2">
                    @if(request('q') || request('alert'))
                        <a href="{{ route('users.index', ['tab' => 'audit']) }}" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded text-sm font-bold shadow-sm hover:bg-slate-50">Limpiar</a>
                    @endif
                    <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded text-sm font-bold shadow-sm hover:bg-slate-900 transition">Filtrar Historial</button>
                </div>
            </form>
            
            <div class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden relative">
                <table class="tabla-rigida-audit">
                    <thead class="bg-slate-800 sticky top-0 z-10 text-white">
                        <tr>
                            <th class="w-1/6 text-left px-4 py-3 text-xs font-bold uppercase tracking-wider">Módulo / Acción</th>
                            <th class="w-1/6 text-left px-4 py-3 text-xs font-bold uppercase tracking-wider">Fecha y Hora</th>
                            <th class="w-1/6 text-left px-4 py-3 text-xs font-bold uppercase tracking-wider">Ejecutor</th>
                            <th class="w-1/2 text-left px-4 py-3 text-xs font-bold uppercase tracking-wider">Descripción del Evento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
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
                                'date' => $audit->created_at->format('d/m/Y H:i:s'),
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
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider {{ $badge['color'] }}">
                                        {{ $badge['name'] }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-1.5" style="color: {{ $style['color'] }}">
                                    <span class="text-sm font-bold">{{ $style['icon'] }}</span>
                                    <span class="text-xs font-black uppercase tracking-tight">{{ $audit->action }}</span>
                                </div>
                            </td>
                            
                            <td class="p-4 align-top">
                                <div class="text-xs font-bold text-slate-800">{{ $audit->created_at->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $audit->created_at->format('H:i:s') }}</div>
                            </td>

                            <td class="p-4 align-top">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ current(explode(' ', $audit->user->name ?? 'Sistema')) }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $audit->ip_address }}</div>
                            </td>

                            <td class="p-4 align-top">
                                <div class="text-sm text-slate-700 font-medium leading-snug pr-4 group-hover:text-slate-900">
                                    {{ $human }}
                                </div>
                                <div class="mt-2 flex gap-2">
                                    <span class="text-[10px] text-aurofarma-teal font-bold opacity-0 group-hover:opacity-100 transition">Ver detalles &rarr;</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center p-10 text-slate-400 font-bold uppercase tracking-widest italic">Cero registros encontrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Drawer Lateral (Detalle del Audit) -->
            <div x-show="openDrawer" class="fixed inset-0 overflow-hidden z-50" style="display: none;">
                <div class="absolute inset-0 overflow-hidden">
                    <div x-show="openDrawer" x-transition.opacity class="absolute inset-0 bg-slate-900 bg-opacity-25 transition-opacity" @click="openDrawer = false"></div>
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
                                        <!-- Header del Drawer -->
                                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between" :style="`background-color: ${activeAudit.style.bg}`">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-2xl" :style="`color: ${activeAudit.style.color}`" x-text="activeAudit.style.icon"></span>
                                                <h2 class="text-lg font-bold text-slate-800 uppercase tracking-tight" x-text="activeAudit.action"></h2>
                                            </div>
                                            <button @click="openDrawer = false" class="text-slate-400 hover:text-slate-600">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>

                                        <!-- Contenido -->
                                        <div class="p-6 space-y-6">
                                            
                                            <!-- Metadatos Principales -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Módulo</div>
                                                    <span class="px-2 py-1 rounded text-xs font-bold" :class="activeAudit.module.color" x-text="activeAudit.module.name"></span>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Fecha de Registro</div>
                                                    <div class="text-sm font-bold text-slate-800 font-mono" x-text="activeAudit.date"></div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Firma / Ejecutor</div>
                                                    <div class="text-sm font-bold text-slate-800" x-text="activeAudit.user"></div>
                                                </div>
                                                <div>
                                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">IP de Origen</div>
                                                    <div class="text-sm font-bold text-slate-800 font-mono" x-text="activeAudit.ip"></div>
                                                </div>
                                            </div>

                                            <hr class="border-slate-100">

                                            <!-- Descripción del Evento -->
                                            <div>
                                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Descripción del Evento</div>
                                                <div class="bg-slate-50 rounded p-4 text-sm text-slate-700 leading-relaxed border border-slate-100 shadow-inner" x-text="activeAudit.description"></div>
                                                <template x-if="activeAudit.reason">
                                                    <div class="mt-2 text-xs italic text-slate-500 border-l-2 border-slate-300 pl-2">Justificación técnica: <span x-text="activeAudit.reason"></span></div>
                                                </template>
                                            </div>

                                            <!-- Cambios Registrados (Antes / Después) -->
                                            <template x-if="activeAudit.old && Object.keys(activeAudit.old).length > 0">
                                                <div>
                                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Modificaciones Detectadas</div>
                                                    <div class="space-y-3">
                                                        <template x-for="(val, key) in activeAudit.new" :key="key">
                                                            <template x-if="activeAudit.old[key] !== undefined && activeAudit.old[key] !== val">
                                                                <div class="bg-white border border-slate-200 rounded p-3 shadow-sm">
                                                                    <div class="text-xs font-bold text-slate-800 mb-2" x-text="key"></div>
                                                                    <div class="flex items-center space-x-2 text-sm font-mono">
                                                                        <div class="flex-1 bg-red-50 text-red-700 p-2 rounded line-through overflow-hidden text-ellipsis" x-text="typeof activeAudit.old[key] === 'object' ? JSON.stringify(activeAudit.old[key]) : activeAudit.old[key]"></div>
                                                                        <div class="text-slate-400">&rarr;</div>
                                                                        <div class="flex-1 bg-green-50 text-green-700 p-2 rounded overflow-hidden text-ellipsis" x-text="typeof val === 'object' ? JSON.stringify(val) : val"></div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Raw JSON Data (Colapsable) -->
                                            <div x-data="{ showRaw: false }" class="mt-8 border border-slate-200 rounded overflow-hidden">
                                                <button @click="showRaw = !showRaw" class="w-full bg-slate-50 px-4 py-3 flex justify-between items-center hover:bg-slate-100 transition">
                                                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Datos Técnicos (JSON)</span>
                                                    <span class="text-slate-400" x-text="showRaw ? '▲' : '▼'"></span>
                                                </button>
                                                <div x-show="showRaw" class="p-4 bg-slate-900 text-green-400 font-mono text-[10px] overflow-x-auto">
                                                    <template x-if="activeAudit.meta">
                                                        <div class="mb-4">
                                                            <div class="text-yellow-400 mb-1">// Metadata Inyectada</div>
                                                            <pre x-text="JSON.stringify(activeAudit.meta, null, 2)"></pre>
                                                        </div>
                                                    </template>
                                                    <template x-if="activeAudit.new">
                                                        <div>
                                                            <div class="text-blue-400 mb-1">// Nuevos Valores / Payload</div>
                                                            <pre x-text="JSON.stringify(activeAudit.new, null, 2)"></pre>
                                                        </div>
                                                    </template>
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

        <!-- Tab Content: Matrix -->
        <div x-show="tab === 'matrix'" x-cloak style="display: none;">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-slate-800">Matriz de Permisos (Maker/Checker)</h2>
            </div>
            
            <form action="{{ route('users.roles.sync') }}" method="POST">
                @csrf
                <div class="bg-white shadow rounded-lg overflow-x-auto border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Permisos</th>
                                @foreach($roles as $role)
                                <th class="px-6 py-3 text-center text-xs font-bold text-aurofarma-teal uppercase tracking-wider">{{ str_replace('_', ' ', $role->name) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($permissions as $permission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 uppercase">
                                    {{ str_replace('_', ' ', $permission->name) }}
                                </td>
                                @foreach($roles as $role)
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <input type="checkbox" 
                                           name="permissions[{{ $role->id }}][]" 
                                           value="{{ $permission->id }}" 
                                           class="h-4 w-4 text-aurofarma-teal focus:ring-aurofarma-teal border-gray-300 rounded"
                                           {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-6 rounded shadow-lg transition">
                        Guardar Matriz
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal Formulario Usuario -->
        <div x-show="showModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition.opacity class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showModal = false">
                    <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    
                    <form :action="editMode ? '/users/' + form.id : '{{ route('users.store') }}'" method="POST">
                        @csrf
                        <template x-if="editMode">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4 border-b pb-2" x-text="editMode ? 'Editar Perfil de Usuario' : 'Registrar Nuevo Perfil (IAM)'"></h3>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                                <input type="text" name="name" x-model="form.name" required class="w-full rounded border-gray-300 border focus:ring-aurofarma-teal focus:border-aurofarma-teal px-3 py-2 text-sm shadow-sm">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Identificador de Acceso (Email)</label>
                                <input type="email" name="email" x-model="form.email" required class="w-full rounded border-gray-300 border focus:ring-aurofarma-teal focus:border-aurofarma-teal px-3 py-2 text-sm shadow-sm">
                            </div>

                            <div class="mb-4" x-show="!editMode">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña Segura (Mín 6 caracteres)</label>
                                <input type="password" name="password" :required="!editMode" class="w-full rounded border-gray-300 border focus:ring-aurofarma-teal focus:border-aurofarma-teal px-3 py-2 text-sm shadow-sm">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Segregación de Rol (Maker / Checker)</label>
                                 <select name="role" x-model="form.role" required class="w-full rounded border-gray-300 border focus:ring-aurofarma-teal focus:border-aurofarma-teal px-3 py-2 text-sm shadow-sm">
                                    <option value="" disabled>Seleccionar Rol Regulatorio</option>
                                     @foreach($roles as $role)
                                        <option value="{{ $role->id }}" :selected="form.role == '{{ $role->name }}' || form.role == '{{ $role->id }}'">{{ str_replace('_', ' ', $role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-100 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-aurofarma-teal text-base font-medium text-white hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar Perfil
                            </button>
                            <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 text-sm focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto">
                                Cancelar Operación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    [x-cloak] { display: none !important; }
    
    /* ESTILO TABLA RÍGIDA AUDIT - GLOBAL IAM */
    .tabla-rigida-audit { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #e2e8f0; }
    .tabla-rigida-audit th, .tabla-rigida-audit td { border: 1px solid #e2e8f0; padding: 12px; vertical-align: top; }
    .bg-navy-industrial { background-color: #0A2540 !important; color: white !important; text-align: center; }
    .txt-centro { text-align: center !important; }
    pre { white-space: pre-wrap; word-wrap: break-word; }
</style>
@endpush
@endsection
