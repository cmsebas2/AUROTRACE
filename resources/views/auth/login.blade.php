@extends('layouts.app')

@section('header_title', 'Acceso al Sistema MES / EBR')

@section('content')
<div class="w-full max-w-md bg-white/95 backdrop-blur-xl rounded-3xl shadow-[0_25px_60px_-15px_rgba(0,0,0,0.4),0_0_35px_rgba(6,182,212,0.15)] border border-white/40 overflow-hidden relative transition-all duration-300">
    
    <!-- 4-Color Aurofarma Signature Top Shimmer Bar -->
    <div class="h-2 w-full flex">
        <div class="h-full flex-1 bg-[#06B6D4] shadow-[0_0_10px_#06B6D4]"></div>
        <div class="h-full flex-1 bg-[#DE2021] shadow-[0_0_10px_#DE2021]"></div>
        <div class="h-full flex-1 bg-[#F28E13] shadow-[0_0_10px_#F28E13]"></div>
        <div class="h-full flex-1 bg-[#005889] shadow-[0_0_10px_#005889]"></div>
    </div>
    
    <!-- Header with 3D Depth -->
    <div class="bg-gradient-to-b from-slate-50 to-white pt-8 pb-6 px-8 text-center border-b border-slate-100 flex flex-col items-center relative">
        <!-- Brand Logo in 3D Container -->
        <div class="p-3 bg-white rounded-2xl shadow-[0_8px_20px_-4px_rgba(0,0,0,0.08),inset_0_1px_0_rgba(255,255,255,1)] border border-slate-100 mb-3 transform hover:scale-105 transition-transform duration-300">
            <img src="{{ asset('img/logo.png') }}" alt="Aurofarma Logo" class="h-12 object-contain">
        </div>

        <div class="flex items-center space-x-2 mt-1">
            <span class="font-display text-xl font-black tracking-wider text-slate-800">AUROTRACE</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-3d-cyan tracking-widest uppercase">EBR / MES</span>
        </div>

        <p class="text-slate-400 font-bold text-[11px] mt-1 tracking-widest uppercase">Terminal de Manufactura & Control de Lotes</p>

        <!-- Live Terminal Pill -->
        <div class="inline-flex items-center space-x-2 mt-3 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-[10px] font-black uppercase tracking-wider">Terminal Operativa · Planta Mosquera</span>
        </div>
    </div>

    <!-- Login Form -->
    <div class="p-8 space-y-6">
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50/90 border border-red-200 text-red-700 shadow-sm flex items-start space-x-3">
                <div class="p-1 rounded-lg bg-red-100 text-red-600 flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <p class="font-black text-xs uppercase tracking-wider text-red-800">Error de Autenticación</p>
                    <ul class="list-disc pl-4 mt-1 text-xs space-y-0.5 text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
            @csrf

            <!-- Username Field -->
            <div>
                <label for="username" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>Nombre de Usuario</span>
                    <span class="text-[10px] font-medium text-slate-400">ID de Operario / Analista</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                           class="w-full pl-11 pr-4 py-3 rounded-2xl bg-slate-50/80 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all text-slate-800 shadow-[inset_0_2px_4px_rgba(0,0,0,0.03)] text-sm font-medium placeholder-slate-400"
                           placeholder="Ej: admin, operario_1, analista_qa">
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5 flex items-center justify-between">
                    <span>Contraseña Electrónica</span>
                    <span class="text-[10px] font-medium text-slate-400">Clave 21 CFR Part 11</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input id="password" type="password" name="password" required
                           class="w-full pl-11 pr-12 py-3 rounded-2xl bg-slate-50/80 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all text-slate-800 shadow-[inset_0_2px_4px_rgba(0,0,0,0.03)] text-sm font-medium placeholder-slate-400"
                           placeholder="••••••••••••">
                    
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-3.5 flex items-center text-slate-400 hover:text-cyan-600 focus:outline-none transition-colors" title="Mostrar/Ocultar contraseña">
                        <svg id="eyeIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Keep Session -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center space-x-2.5 cursor-pointer">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                    <span class="text-xs font-semibold text-slate-600">Mantener estación conectada</span>
                </label>
            </div>

            <!-- 3D Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        class="w-full relative group overflow-hidden rounded-2xl p-[1px] shadow-3d-button hover:shadow-3d-cyan transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0">
                    <div class="w-full bg-gradient-to-r from-[#005889] via-[#06B6D4] to-[#005889] text-white py-3.5 px-6 rounded-2xl flex items-center justify-center space-x-2 font-display font-black text-sm tracking-widest uppercase">
                        <span>INGRESAR A PLANTA MES</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </div>
                </button>
            </div>
            
            <!-- Regulatory Security Footer -->
            <div class="pt-3 border-t border-slate-100 text-center flex flex-col items-center space-y-1.5">
                <div class="flex items-center space-x-1.5 text-slate-400">
                    <svg class="w-3.5 h-3.5 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Auditoría Electrónica Inmutable</span>
                </div>
                <p class="text-[10px] text-slate-400 leading-tight">
                    Acceso registrado bajo la norma internacional <strong class="text-slate-600">21 CFR Part 11</strong> y normatividad <strong class="text-slate-600">ICA Colombia</strong>.
                </p>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle icon color
        this.classList.toggle('text-cyan-600');
    });
</script>
@endpush
@endsection
