@extends('layouts.app')

@section('header_title', 'Genealogía de Lote 360°')

@section('content')
<div class="min-h-[80vh] flex flex-col items-center justify-center -translate-y-12">
    <!-- Premium Branding -->
    <div class="text-center mb-12 animate-fade-in">
        <div class="inline-flex items-center justify-center p-4 bg-blue-50 rounded-3xl mb-6 shadow-inner ring-4 ring-blue-50/50">
            <svg class="w-16 h-16 text-aurofarma-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <h1 class="text-5xl font-black text-slate-800 tracking-tighter sm:text-6xl">
            Trazabilidad <span class="text-aurofarma-blue italic">360°</span>
        </h1>
        <p class="mt-4 text-slate-500 font-bold text-lg max-w-lg mx-auto leading-relaxed">
            Consulte el historial forense, suministros y firmas electrónicas de cualquier lote de producción.
        </p>
    </div>

    <!-- Search Box -->
    @if(session('error'))
    <div class="w-full max-w-2xl bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-6 shadow-sm flex items-center animate-fade-in ring-4 ring-red-50/50">
        <svg class="w-6 h-6 mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-bold text-lg">{{ session('error') }}</span>
    </div>
    @endif

    <div class="w-full max-w-2xl bg-white p-8 rounded-[2.5rem] shadow-2xl border-2 border-slate-100 ring-8 ring-slate-50/50 transition-all hover:ring-blue-50/80 group">
        <form action="#" onsubmit="event.preventDefault(); goToGenealogy();" id="search-form" class="relative">
            <input type="text" id="lote-input" required
                   class="w-full pl-6 pr-44 py-8 rounded-3xl border-2 border-slate-100 bg-slate-50/50 text-2xl font-black text-slate-800 placeholder-slate-300 focus:border-aurofarma-blue focus:bg-white focus:ring-0 transition-all shadow-inner"
                   placeholder="Escriba el # de Lote (ej: 604MT02)...">
            
            <button type="submit" 
                    class="absolute right-3 top-3 bottom-3 px-10 bg-slate-900 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-xl shadow-slate-200 hover:bg-aurofarma-blue hover:shadow-blue-200 active:scale-[0.97] transition-all flex items-center gap-2">
                <span>Buscar</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </form>
    </div>

    <!-- Quick Stats Tags -->
    <div class="mt-12 flex flex-wrap justify-center gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <span class="px-4 py-2 bg-slate-100 rounded-full border border-slate-200">Pesaje</span>
        <span class="px-4 py-2 bg-slate-100 rounded-full border border-slate-200">Manufactura</span>
        <span class="px-4 py-2 bg-slate-100 rounded-full border border-slate-200">Envase</span>
        <span class="px-4 py-2 bg-slate-100 rounded-full border border-slate-200">Libre 21 CFR Part 11</span>
    </div>
</div>

<script>
    function goToGenealogy() {
        const lote = document.getElementById('lote-input').value.trim();
        if (lote) {
            window.location.href = `/genealogia/${lote}`;
        }
    }
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 0.8s ease-out forwards; }
</style>
@endsection
