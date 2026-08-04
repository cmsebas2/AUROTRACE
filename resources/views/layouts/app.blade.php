<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AuroTrace') }} | @yield('header_title', 'Dashboard')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tailwind CSS (Using CDN temporarily as Vite/NPM is not installed) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        industrial: {
                            navy: '#0A2540',
                        },
                        aurofarma: {
                            blue: '#048ABF',
                            red: '#F23535',
                            orange: '#F28E13',
                            teal: '#04BFAD',
                        }
                    }
                }
            }
        }
    </script>
    <script>
        // Configurar Axios globalmente
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            let token = document.head.querySelector('meta[name="csrf-token"]');
            if (token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900 bg-gray-50 flex h-screen overflow-hidden">

    @auth
        <!-- Sidebar -->
        <div class="w-64 h-screen overflow-y-auto bg-slate-900 text-white flex flex-col justify-between hidden md:flex shadow-2xl">
            <div>
                <div class="py-6 flex flex-col items-center justify-center border-b border-gray-700/50 space-y-3">
                    <img src="{{ asset('img/logo.png') }}" alt="Aurofarma Logo" class="w-48 object-contain">
                    <span class="text-xl font-bold tracking-widest text-[#04BFAD] uppercase">AuroTrace</span>
                </div>
                
                <nav class="flex-1 mt-6 px-4 pb-24 space-y-2 overflow-y-auto">
                    @if(auth()->user()->hasPermission('ver_dashboard') && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('dashboard') }}" class="block py-3 px-4 rounded transition {{ request()->routeIs('dashboard') ? 'bg-white/10 border-l-4 border-aurofarma-teal text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        Inicio (Dashboard)
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('ver_productos') && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('productos.index') }}" class="block py-3 px-4 rounded transition flex items-center {{ request()->routeIs('productos.*') ? 'bg-white/10 border-l-4 border-aurofarma-teal text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Productos
                    </a>
                    @endif


                    <!-- Producción Menu -->

                    @if(auth()->user()->hasPermission('ejecutar_manufactura') && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('op.ejecucion') }}" class="block py-3 px-4 rounded transition flex items-center {{ request()->routeIs('op.ejecucion') ? 'bg-white/10 border-l-4 border-aurofarma-teal text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        <svg class="w-5 h-5 mr-3 text-aurofarma-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Órdenes de Producción
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('ver_monitoreo_ops') && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('op.activas') }}" class="block py-3 px-4 rounded transition flex items-center {{ request()->routeIs('op.activas') ? 'bg-white/10 border-l-4 border-aurofarma-teal text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        <svg class="w-5 h-5 mr-3 text-aurofarma-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        OPs Activas
                    </a>
                    @endif

                    <!-- Nuevo Módulo de Trazabilidad -->
                    @if((auth()->user()->hasPermission('ver_genealogia') || auth()->user()->hasRole(['admin', 'ADMIN', 'Administrador'])) && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('genealogia.index') }}" class="block py-3 px-4 rounded transition flex items-center {{ request()->routeIs('genealogia.*') ? 'bg-white/10 border-l-4 border-aurofarma-teal text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Genealogía & Audits
                    </a>
                    @endif

                    <!-- Módulo BATCH RECORDS (Expediente Maestro) -->
                    @if(!auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('batch-records.index') }}" class="block py-3 px-4 rounded transition flex items-center {{ request()->routeIs('batch-records.*') ? 'bg-white/10 border-l-4 border-aurofarma-teal text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        <svg class="w-5 h-5 mr-3 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Batch Records
                    </a>
                    @endif

                    <!-- Módulo Producción Maquilas -->
                    @if((auth()->user()->hasPermission('gestionar_maquilas') || auth()->user()->hasRole(['admin', 'ADMIN', 'Administrador'])) && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('maquila.index') }}" class="block py-3 px-4 rounded transition flex items-center {{ request()->routeIs('maquila.*') ? 'bg-white/10 border-l-4 border-emerald-400 text-white shadow-sm font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }}">
                        <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Producción Maquilas
                    </a>
                    @endif

                    <!-- Calidad Menu -->
                    @if((auth()->user()->hasPermission('ver_aseguramiento_calidad') || auth()->user()->hasPermission('ver_formulas_maestras')) && !auth()->user()->hasRole('Analista de Producción'))
                    <details class="group [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-800 hover:text-white rounded transition list-none">
                            <span>Calidad</span>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" class="w-4 h-4" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                        </summary>
                        <div class="mt-1 space-y-1 pl-4 border-l border-gray-700 ml-6">
                            @if(auth()->user()->hasPermission('ver_aseguramiento_calidad'))
                            <a href="{{ route('op.calidad') }}" class="block py-2 px-4 rounded transition hover:bg-gray-800 hover:text-white text-gray-300 text-sm">
                                Aseguramiento de Calidad
                            </a>
                            @endif
                        </div>
                    </details>
                    @endif

                    <!-- Configuración Menu -->
                    @if((auth()->user()->hasPermission('gestionar_usuarios_roles') || auth()->user()->hasPermission('gestionar_ajustes_sistema')) && !auth()->user()->hasRole('Analista de Producción'))
                    <details class="group [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-800 hover:text-white rounded transition list-none">
                            <span>Configuración</span>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" class="w-4 h-4" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                        </summary>
                        <div class="mt-1 space-y-1 pl-4 border-l border-gray-700 ml-6">
                            @if(auth()->user()->hasPermission('gestionar_usuarios_roles'))
                            <a href="{{ route('users.index') }}" class="block py-2 px-4 rounded transition {{ request()->routeIs('users.*') ? 'bg-gray-800 text-white font-medium' : 'hover:bg-gray-800 hover:text-white text-gray-300' }} text-sm">
                                Usuarios y Roles
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('gestionar_ajustes_sistema'))
                            <a href="#" class="block py-2 px-4 rounded transition hover:bg-gray-800 hover:text-white text-gray-300 text-sm">
                                Ajustes de Sistema
                            </a>
                            @endif
                        </div>
                    </details>
                    @endif
                </nav>
            </div>
            
            <div class="p-4 bg-gray-800 text-sm border-t border-gray-700">
                <p class="text-gray-400">Versión 1.0 (CFR 21 P11)</p>
            </div>
        </div>

        <!-- Layout Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar -->
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 w-full">
                <div class="flex items-center">
                    <button class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800 ml-4 md:ml-0">
                        @yield('header_title', 'Dashboard')
                    </h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">{{ Auth::user()->role }}</p>
                    </div>
                    
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-lg text-sm font-medium transition ml-2 border border-red-200">
                        Cerrar Sesión
                    </a>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                @yield('content')
            </main>
        </div>
    @else
        <!-- Guest View (Login) -->
        <main class="w-full flex items-center justify-center bg-gray-100">
            @yield('content')
        </main>
    @endauth

    @stack('scripts')
</body>
</html>
