@extends('layouts.app')

@section('header_title', 'Genealogía de Lote 360°')

@section('content')
<div class="min-h-[75vh] flex flex-col items-center justify-center relative py-6">
    <!-- Ambient 3D Glows -->
    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Premium Branding Hero -->
    <div class="text-center mb-10 relative z-10">
        <div class="inline-flex items-center justify-center p-4 bg-white rounded-3xl mb-4 shadow-3d-card border border-slate-200/80 transform hover:scale-105 transition-transform">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#005889] to-[#06B6D4] flex items-center justify-center text-white shadow-3d-cyan">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
        </div>

        <div class="flex items-center justify-center space-x-2 mb-2">
            <span class="font-display text-xs font-black uppercase tracking-widest text-cyan-700 bg-cyan-50 px-3 py-1 rounded-full border border-cyan-200">
                Auditoría Forense Farmacéutica
            </span>
            <span class="font-display text-xs font-black uppercase tracking-widest text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                21 CFR Part 11
            </span>
        </div>

        <h1 class="font-display text-4xl sm:text-5xl font-black text-slate-900 tracking-tight">
            Trazabilidad <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#005889] via-[#06B6D4] to-[#005889]">Genealógica 360°</span>
        </h1>
        <p class="mt-3 text-slate-500 font-medium text-sm sm:text-base max-w-lg mx-auto leading-relaxed">
            Consulte el expediente acumulativo, materias primas, material de envase y firmas electrónicas de cualquier lote de manufactura.
        </p>
    </div>

    <!-- Search Box (3D Floating Card) -->
    @if(session('error'))
    <div class="w-full max-w-2xl bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-6 shadow-sm flex items-center">
        <svg class="w-5 h-5 mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-bold text-sm">{{ session('error') }}</span>
    </div>
    @endif

    <div class="w-full max-w-2xl card-3d p-4 rounded-3xl border border-slate-200/80 shadow-3d-card-hover relative z-10 bg-white">
        <form action="#" onsubmit="event.preventDefault(); goToGenealogy();" id="search-form" class="relative">
            <div class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                
                <input type="text" id="lote-input" required
                       class="w-full pl-14 pr-36 py-5 rounded-2xl border border-slate-200 bg-slate-50/50 text-xl font-display font-black text-slate-800 placeholder-slate-400 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-500/10 transition-all shadow-inner uppercase tracking-wider"
                       placeholder="EJ: 604MT02, LOTE-123...">
                
                <button type="submit" 
                        class="absolute right-2 top-2 bottom-2 px-6 bg-gradient-to-r from-[#005889] to-[#06B6D4] text-white font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-3d-button hover:shadow-3d-cyan active:scale-95 transition-all flex items-center space-x-1.5">
                    <span>Consultar</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Phase Tags 3D -->
    <div class="mt-8 flex flex-wrap justify-center gap-2.5 text-[10px] font-black uppercase tracking-wider text-slate-500 relative z-10">
        <span class="px-3.5 py-1.5 bg-white rounded-full border border-slate-200 shadow-3d-badge flex items-center space-x-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
            <span>Pesaje</span>
        </span>
        <span class="px-3.5 py-1.5 bg-white rounded-full border border-slate-200 shadow-3d-badge flex items-center space-x-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            <span>Manufactura</span>
        </span>
        <span class="px-3.5 py-1.5 bg-white rounded-full border border-slate-200 shadow-3d-badge flex items-center space-x-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            <span>Acondicionamiento</span>
        </span>
        <span class="px-3.5 py-1.5 bg-white rounded-full border border-slate-200 shadow-3d-badge flex items-center space-x-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>Liberación QA</span>
        </span>
        <span class="px-3.5 py-1.5 bg-slate-900 text-white rounded-full shadow-3d-badge flex items-center space-x-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-cyan-300">Auditoría Criptográfica</span>
        </span>
    </div>
</div>

<script>
    function goToGenealogy() {
        const lote = document.getElementById('lote-input').value.trim();
        if (lote) {
            window.location.href = `/genealogia/${encodeURIComponent(lote)}`;
        }
    }
</script>
@endsection
