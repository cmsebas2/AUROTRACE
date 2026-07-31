@extends('layouts.app')

@section('header_title', 'Centro de Monitoreo en Tiempo Real')

@section('content')
<div class="space-y-10 animate-fade-in">
    
    <!-- 1. Panel de Indicadores Maestros (KPIs de Solo Lectura) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Planta en Marcha -->
        <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 flex items-center gap-6 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                <svg class="w-32 h-32 text-slate-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C7.512 4.142 9.49 3.5 12 3.5a1 1 0 110 2c-2.01 0-3.49.5-4.42 1.328a4.012 4.012 0 00-1.274 1.804 1 1 0 11-1.974-.605z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="p-5 rounded-3xl bg-slate-900 text-white shadow-2xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Planta en Marcha</p>
                <div class="text-4xl font-black text-slate-800 tracking-tighter">{{ $plantaEnMarcha }} <span class="text-lg font-bold text-slate-400">OPs</span></div>
            </div>
        </div>

        <!-- Fase de Codificado -->
        <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 flex items-center gap-6 relative overflow-hidden group">
            <div class="p-5 rounded-3xl bg-blue-500 text-white shadow-2xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M10 7h.01M10 11h.01M10 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">En Codificación</p>
                <div class="text-4xl font-black text-slate-800 tracking-tighter">{{ $codificadoCount }}</div>
            </div>
        </div>

        <!-- Control de Calidad -->
        <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 flex items-center gap-6 relative overflow-hidden group">
            <div class="p-5 rounded-3xl bg-emerald-500 text-white shadow-2xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Revisión Calidad</p>
                <div class="text-4xl font-black text-slate-800 tracking-tighter">{{ $calidadCount }}</div>
            </div>
        </div>

        <!-- Producto Terminado -->
        <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 flex items-center gap-6 relative overflow-hidden group">
            <div class="p-5 rounded-3xl bg-amber-500 text-white shadow-2xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            </div>
            <div>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Liberados Hoy</p>
                <div class="text-4xl font-black text-slate-800 tracking-tighter">{{ $liberadoHoy }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- 2. Monitor de Línea de Producción (Tabla Informativa) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-black text-slate-800 tracking-tighter flex items-center gap-3">
                    <span class="w-4 h-8 bg-aurofarma-blue rounded-full"></span>
                    Monitor de Línea Activa
                </h3>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] animate-pulse">Actualizando en Vivo...</span>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">OP / Lote</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest">Producto</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-center">Progreso</th>
                            <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-right">Ubicación / Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($activeOrders as $op)
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-lg font-black text-slate-800 tracking-tighter">{{ $op->lote }}</span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">OP: {{ $op->op_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-bold text-slate-600 leading-tight block max-w-[200px]">{{ $op->product->name }}</span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col gap-2 min-w-[140px]">
                                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                            <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all duration-1000 shadow-lg" 
                                                 style="width: {{ $op->progress_percentage }}%"></div>
                                        </div>
                                        <span class="text-[9px] font-black text-slate-400 text-center uppercase tracking-widest">{{ $op->progress_percentage }}% Completado</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    @php
                                        $badgeStyles = [
                                            'Emisión' => 'bg-slate-100 text-slate-500',
                                            'Ajuste DT' => 'bg-indigo-50 text-indigo-600',
                                            'Codificación' => 'bg-blue-50 text-blue-600',
                                            'Revisión COAs' => 'bg-emerald-50 text-emerald-600',
                                            'Despeje Línea' => 'bg-purple-50 text-purple-600',
                                            'Dispensación' => 'bg-green-50 text-green-600',
                                            'Manufactura' => 'bg-amber-50 text-amber-600',
                                            'Empaque' => 'bg-pink-50 text-pink-600',
                                        ];
                                        $style = $badgeStyles[$op->current_step_label] ?? 'bg-slate-100 text-slate-600';
                                    @endphp
                                    <span class="px-4 py-2 rounded-2xl {{ $style }} text-[10px] font-black uppercase tracking-widest border border-current opacity-80 group-hover:opacity-100 transition-opacity">
                                        {{ $op->current_step_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-20">
                                        <svg class="w-20 h-20 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        <p class="mt-4 text-sm font-black uppercase tracking-widest">Sin actividad en planta</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3 & 4: Sidebar Informativa -->
        <div class="space-y-10">
            <!-- Feed Forense CFR 21 -->
            <div class="space-y-6">
                <h3 class="text-2xl font-black text-slate-800 tracking-tighter flex items-center gap-3">
                    <svg class="w-8 h-8 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Firmas Recientes
                </h3>
                <div class="bg-slate-900 rounded-[2.5rem] p-8 shadow-2xl border border-slate-800">
                    <div class="space-y-6">
                        @foreach($auditLogs as $log)
                            <div class="flex gap-4 items-start border-l-2 border-slate-700 pl-6 pb-2 relative">
                                <div class="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50"></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[9px] font-black text-blue-400 uppercase tracking-widest">{{ $log->action }}</span>
                                        <span class="text-[8px] font-bold text-slate-500">{{ $log->created_at->format('H:i') }}</span>
                                    </div>
                                    <p class="text-[11px] font-bold text-white leading-tight">
                                        {{ $log->user->name ?? 'SISTEMA' }} 
                                        <span class="text-slate-500 font-normal">validó en lote</span>
                                        <span class="text-blue-300">{{ $log->model_id }}</span>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8 pt-6 border-t border-slate-800 flex justify-between items-center opacity-50">
                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-[0.3em]">CFR 21 Part 11 Audit Trail</span>
                        <svg class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.9L9.03 9.069a2.107 2.107 0 002.125 0l6.863-4.17A2.107 2.107 0 0015.894 1.5H4.106a2.107 2.107 0 00-1.94 3.4zM2.166 15.1A2.107 2.107 0 004.106 18.5h11.788a2.107 2.107 0 001.94-3.4l-6.863-4.17a2.107 2.107 0 00-2.125 0l-6.864 4.17z" clip-rule="evenodd"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Trazabilidad 360° (Modo Mapa) -->
            <div class="bg-gradient-to-br from-aurofarma-blue to-indigo-900 rounded-[2.5rem] p-8 shadow-2xl text-white relative overflow-hidden group">
                <div class="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <h4 class="text-lg font-black tracking-tighter mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Eficiencia Planta
                </h4>
                <div class="space-y-4">
                    <div class="flex justify-between text-[10px] font-black uppercase tracking-widest opacity-60">
                        <span>Meta Diaria</span>
                        <span>85%</span>
                    </div>
                    <div class="w-full h-4 bg-white/10 rounded-full overflow-hidden p-1">
                        <div class="h-full bg-white rounded-full transition-all duration-1000" style="width: 72%"></div>
                    </div>
                    <p class="text-[9px] font-bold text-indigo-200 mt-4 leading-relaxed italic">
                        "El sistema de monitoreo 360° garantiza la integridad de los datos en tiempo real bajo estándares internacionales."
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fade-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    
    .shadow-3xl { shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3); }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
@endsection

