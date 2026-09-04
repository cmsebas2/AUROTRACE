<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AuroTrace') }} | @yield('header_title', 'EBR / MES')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Outfit (Referencia Aurofarma Oficial) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 (Asíncrono para eliminar bloqueo de renderizado) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>

    <!-- Tailwind CSS (Using CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Axios (Defer) -->
    <script defer src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- SweetAlert2 (Defer) -->
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js (Defer) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['"Outfit"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        aurofarma: {
                            DEFAULT: '#005889',     // Azul Corporativo
                            dark: '#003B5C',        // Azul Profundo
                            navy: '#0A2540',        // Navy Industrial
                            blue: '#048ABF',        // Azul Aurofarma
                            cyan: '#06B6D4',        // Cyan Tecnológico
                            teal: '#0891B2',        // Teal
                            green: '#028838',       // Verde BPM / Calidad
                            red: '#DE2021',         // Rojo Alerta / Desvíos
                            orange: '#F28E13',      // Naranja
                            gold: '#FBBF24',        // Amarillo Vitaminas
                            amber: '#f59e0b',
                            slate: '#0F172A',       // Base Dark
                            surface: '#F8FAFC',     // Fondo paneles
                        }
                    },
                    boxShadow: {
                        '3d-card': '0 12px 30px -10px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.95)',
                        '3d-card-hover': '0 25px 45px -12px rgba(6, 182, 212, 0.18), 0 10px 20px -5px rgba(0, 88, 137, 0.12), inset 0 1px 0 rgba(255, 255, 255, 1)',
                        '3d-badge': '0 2px 6px -1px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.4)',
                        '3d-button': '0 10px 20px -5px rgba(0, 88, 137, 0.4), 0 4px 6px -2px rgba(6, 182, 212, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.3)',
                        '3d-cyan': '0 12px 25px -8px rgba(6, 182, 212, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.35)',
                        '3d-dark': '0 20px 40px -15px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.08)',
                        'inner-glow': 'inset 0 1px 2px rgba(255, 255, 255, 0.2), inset 0 -1px 2px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Aurofarma Custom 3D & Utility Tokens */
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .font-display {
            font-family: 'Outfit', sans-serif;
        }
        
        /* 3D Glass Layer */
        .glass-dark {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.97) 0%, rgba(10, 17, 34, 0.98) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-right: 1px solid rgba(255, 255, 255, 0.07);
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .card-3d {
            background: #FFFFFF;
            border-radius: 1.25rem;
            box-shadow: 0 12px 30px -10px rgba(15, 23, 42, 0.06), 0 4px 10px -2px rgba(15, 23, 42, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-3d:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -12px rgba(0, 88, 137, 0.12), 0 8px 16px -4px rgba(6, 182, 212, 0.08), inset 0 1px 0 rgba(255, 255, 255, 1);
            border-color: rgba(6, 182, 212, 0.3);
        }

        /* 4-Color Aurofarma Shimmer Bar */
        .aurofarma-bar {
            height: 4px;
            width: 100%;
            display: flex;
            background: linear-gradient(90deg, #06B6D4 0%, #005889 25%, #DE2021 50%, #F28E13 75%, #028838 100%);
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.4);
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        .dark-scroll::-webkit-scrollbar-thumb {
            background: #334155;
        }
        .dark-scroll::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof axios !== 'undefined') {
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                let token = document.head.querySelector('meta[name="csrf-token"]');
                if (token) {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
                }
            }
        });
    </script>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 h-full overflow-hidden flex">

    @auth
        <!-- 3D Dark Glass Industrial Sidebar (Aurofarma MES Cockpit) -->
        <aside class="w-72 h-screen glass-dark text-white flex flex-col justify-between hidden md:flex shadow-2xl relative z-30 flex-shrink-0">
            <!-- Ambient 3D Glow Corner -->
            <div class="absolute top-0 left-0 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col flex-1 min-h-0">
                <!-- Brand Header -->
                <div class="p-6 border-b border-slate-800/80 flex flex-col items-center justify-center relative">
                    <div class="flex items-center justify-center bg-white/5 p-3 rounded-2xl border border-white/10 shadow-inner w-full">
                        <img src="{{ asset('img/logo.png') }}" alt="Aurofarma Logo" class="h-10 object-contain drop-shadow-[0_4px_8px_rgba(0,0,0,0.4)]">
                    </div>
                    
                    <div class="mt-3 flex items-center justify-between w-full px-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-display text-base font-black tracking-wider text-white">AUROTRACE</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-3d-cyan uppercase tracking-widest">EBR 4.0</span>
                        </div>
                        <div class="flex items-center space-x-1.5" title="Sistema Activo en Línea">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">MES</span>
                        </div>
                    </div>
                </div>
                
                <!-- Main Navigation -->
                <nav class="flex-1 mt-4 px-3 pb-6 space-y-1.5 overflow-y-auto dark-scroll">
                    
                    <!-- 1. DASHBOARD -->
                    @if(auth()->user()->hasPermission('ver_dashboard') && !auth()->user()->hasRole('Analista de Producción'))
                    <a href="{{ route('dashboard') }}" 
                       class="group relative flex items-center px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('dashboard') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-cyan-400 group-hover:bg-slate-700' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        </div>
                        <span class="flex-1 tracking-tight">Centro de Monitoreo</span>
                        @if(request()->routeIs('dashboard'))
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 shadow-[0_0_8px_#06B6D4]"></span>
                        @endif
                    </a>
                    @endif

                    <!-- 2. PRODUCCIÓN EN PLANTA (EBR) -->
                    @if(!auth()->user()->hasRole('Analista de Producción'))
                    <div class="pt-2">
                        <p class="px-4 pb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500">Operaciones EBR</p>
                        
                        <!-- Catálogo de Fórmulas Maestras -->
                        @if(auth()->user()->hasPermission('ver_productos'))
                        <a href="{{ route('productos.index') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('productos.*') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('productos.*') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-cyan-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">Catálogo & Fórmulas</span>
                        </a>
                        @endif

                        <!-- Órdenes de Producción Activas -->
                        @if(auth()->user()->hasPermission('ver_monitoreo_ops'))
                        <a href="{{ route('op.activas') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('op.activas') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('op.activas') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-amber-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 022 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">OPs Activas Planta</span>
                        </a>
                        @endif

                        <!-- Ejecución de Manufactura -->
                        @if(auth()->user()->hasPermission('ejecutar_manufactura'))
                        <a href="{{ route('op.ejecucion') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('op.ejecucion') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('op.ejecucion') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-emerald-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">Ejecución de Lotes</span>
                        </a>
                        @endif
                    </div>
                    @endif

                    <!-- 3. CALIDAD & REGULATORIO -->
                    <div class="pt-2">
                        <p class="px-4 pb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500">Calidad & Cumplimiento</p>

                        <!-- Batch Records -->
                        <a href="{{ route('batch-records.index') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('batch-records.*') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('batch-records.*') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-amber-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">Expedientes Batch Record</span>
                        </a>

                        <!-- Consultas BR (Archivo Físico 3D) -->
                        <a href="{{ route('consultas.br') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('consultas.br') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('consultas.br') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-cyan-400 group-hover:bg-slate-700' }}">
                                <i class="fas fa-cubes text-sm"></i>
                            </div>
                            <span class="flex-1 tracking-tight">Consultas BR (Archivo 3D)</span>
                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">3D</span>
                        </a>

                        <!-- Aseguramiento de Calidad / COAs -->
                        @if(auth()->user()->hasPermission('ver_aseguramiento_calidad'))
                        <a href="{{ route('op.calidad') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('op.calidad') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('op.calidad') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-emerald-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">QA / Liberación de Lotes</span>
                        </a>
                        @endif

                        <!-- Genealogía & Trazabilidad Total -->
                        @if((auth()->user()->hasPermission('ver_genealogia') || auth()->user()->hasRole(['admin', 'ADMIN', 'Administrador'])) && !auth()->user()->hasRole('Analista de Producción'))
                        <a href="{{ route('genealogia.index') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('genealogia.*') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('genealogia.*') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-cyan-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">Genealogía 360° & Audits</span>
                        </a>
                        @endif
                    </div>

                    <!-- 4. MAQUILAS EXTERNAS -->
                    @if(!auth()->user()->hasRole('Analista de Producción'))
                    <div class="pt-2">
                        <p class="px-4 pb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500">Cadena de Suministro</p>
                        <a href="{{ route('maquila.index') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('maquila.*') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('maquila.*') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-cyan-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">Maquilas Externas</span>
                        </a>
                    </div>
                    @endif

                    <!-- 5. CONFIGURACIÓN & IAM -->
                    @if((auth()->user()->hasPermission('gestionar_usuarios_roles') || auth()->user()->hasPermission('gestionar_ajustes_sistema')) && !auth()->user()->hasRole('Analista de Producción'))
                    <div class="pt-2">
                        <p class="px-4 pb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500">Administración</p>
                        @if(auth()->user()->hasPermission('gestionar_usuarios_roles'))
                        <a href="{{ route('users.index') }}" 
                           class="group relative flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border-l-4 border-cyan-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 transition-colors {{ request()->routeIs('users.*') ? 'bg-cyan-500/30 text-cyan-300' : 'bg-slate-800/80 text-slate-400 group-hover:text-cyan-400 group-hover:bg-slate-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <span class="flex-1 tracking-tight">Usuarios & Permisos</span>
                        </a>
                        @endif
                    </div>
                    @endif

                </nav>
            </div>
            
            <!-- Bottom Compliance Card (CFR 21 Part 11) -->
            <div class="p-4 m-3 rounded-2xl bg-slate-800/70 border border-slate-700/60 shadow-inner flex flex-col space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black tracking-widest text-slate-300 uppercase">Cumplimiento</span>
                    <span class="px-2 py-0.5 rounded text-[8px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">VALIDADO</span>
                </div>
                <div class="flex items-center space-x-2 text-[11px] text-slate-300">
                    <svg class="w-3.5 h-3.5 text-cyan-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="font-medium text-[10px]">21 CFR Part 11 · Res. ICA</span>
                </div>
                <p class="text-[9px] text-slate-400 leading-tight">Laboratorios Aurofarma S.A.S.</p>
            </div>
        </aside>

        <!-- Main Workplace Shell -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-100/70">
            <!-- Aurofarma 4-Color Gradient Topline -->
            <div class="aurofarma-bar"></div>

            <!-- 3D Floating Glass Topbar -->
            <header class="h-16 glass-header px-6 flex items-center justify-between z-20 flex-shrink-0 shadow-sm">
                <div class="flex items-center space-x-4">
                    <button class="md:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    
                    <div class="flex items-center space-x-3">
                        <span class="w-2.5 h-6 bg-gradient-to-b from-cyan-500 to-aurofarma rounded-full shadow-3d-cyan hidden sm:block"></span>
                        <h1 class="font-display text-xl font-black text-slate-800 tracking-tight">
                            @yield('header_title', 'Dashboard')
                        </h1>
                    </div>
                </div>

                <!-- Status Pill & User Card -->
                <div class="flex items-center space-x-5">
                    <!-- Live Plant Heartbeat -->
                    <div class="hidden lg:flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-slate-900 text-white shadow-3d-badge border border-slate-700/80">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                        </span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-300">PLANTA EN LÍNEA</span>
                    </div>

                    <!-- User Profile & Action -->
                    <div class="flex items-center pl-3 border-l border-slate-200">
                        <div class="text-right hidden sm:block mr-3">
                            <p class="text-sm font-extrabold text-slate-800 leading-none">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] font-bold text-cyan-700 uppercase tracking-wider mt-1">{{ Auth::user()->role }}</p>
                        </div>

                        <!-- 3D User Avatar Pill -->
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-aurofarma text-white font-display font-black flex items-center justify-center shadow-3d-button text-sm mr-3">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        
                        <!-- Logout Form -->
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                        <button onclick="document.getElementById('logout-form').submit();" 
                                class="px-3.5 py-2 rounded-xl text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 border border-red-200 transition-all shadow-sm flex items-center space-x-1.5"
                                title="Cerrar Sesión Segura">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span class="hidden sm:inline">Salir</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area with Modern 3D Canvas Background -->
            <main class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50 relative">
                <!-- Background Ambient Glow -->
                <div class="absolute top-10 right-10 w-96 h-96 bg-cyan-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute bottom-10 left-10 w-96 h-96 bg-blue-600/5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    @else
        <!-- Guest View (Terminal de Login 3D Glassmorphic) -->
        <main class="w-full min-h-screen flex items-center justify-center bg-slate-900 relative overflow-hidden">
            <!-- Background 3D Mesh Gradients -->
            <div class="absolute -top-40 -left-40 w-[550px] h-[550px] bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-40 -right-40 w-[550px] h-[550px] bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] bg-slate-800/40 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 w-full flex items-center justify-center p-4">
                @yield('content')
            </div>
        </main>
    @endauth

    @stack('scripts')
</body>
</html>
