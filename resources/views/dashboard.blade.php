@extends('layouts.app')

@section('header_title', 'Centro de Monitoreo en Tiempo Real')

@section('content')
<div class="space-y-8 animate-fade-in">
    
    <!-- 1. Panel de Indicadores Maestros (KPIs 3D con Gradientes e Iluminación Aurofarma) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- KPI 1: Planta en Marcha (Azul Corporativo / Cyan) -->
        <div class="card-3d p-6 relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
            
            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-black tracking-widest uppercase text-cyan-700 bg-cyan-50 border border-cyan-200/80 px-2.5 py-1 rounded-full shadow-sm">
                    Lotes Activos
                </span>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#005889] to-[#06B6D4] text-white flex items-center justify-center shadow-3d-cyan transform group-hover:rotate-6 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Planta en Marcha</p>
                <div class="font-display text-4xl font-black text-slate-800 tracking-tight mt-1 flex items-baseline gap-2">
                    {{ $plantaEnMarcha }}
                    <span class="text-xs font-extrabold text-cyan-600 uppercase tracking-widest">Órdenes (OP)</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-semibold">Líneas en operación</span>
                </span>
                <span class="font-bold text-slate-700">100% Capacidad</span>
            </div>
        </div>

        <!-- KPI 2: Fase de Codificado (Ámbar / Naranja) -->
        <div class="card-3d p-6 relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>

            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-black tracking-widest uppercase text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-full shadow-sm">
                    Fase Acondicionamiento
                </span>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F28E13] to-[#FBBF24] text-white flex items-center justify-center shadow-[0_10px_20px_-5px_rgba(242,142,19,0.4)] transform group-hover:rotate-6 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M10 7h.01M10 11h.01M10 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">En Codificación</p>
                <div class="font-display text-4xl font-black text-slate-800 tracking-tight mt-1 flex items-baseline gap-2">
                    {{ $codificadoCount }}
                    <span class="text-xs font-extrabold text-amber-600 uppercase tracking-widest">Envasando</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="font-semibold">Material empaque OK</span>
                </span>
                <span class="font-bold text-slate-700">Impresión activa</span>
            </div>
        </div>

        <!-- KPI 3: Control de Calidad (Verde BPM / QA) -->
        <div class="card-3d p-6 relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>

            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-black tracking-widest uppercase text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-full shadow-sm">
                    Cuarentena / COAs
                </span>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#028838] to-emerald-400 text-white flex items-center justify-center shadow-[0_10px_20px_-5px_rgba(2,136,56,0.4)] transform group-hover:rotate-6 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Revisión Calidad (QA)</p>
                <div class="font-display text-4xl font-black text-slate-800 tracking-tight mt-1 flex items-baseline gap-2">
                    {{ $calidadCount }}
                    <span class="text-xs font-extrabold text-emerald-600 uppercase tracking-widest">En análisis</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="font-semibold">Muestreo microbiológico</span>
                </span>
                <span class="font-bold text-slate-700">Conforme</span>
            </div>
        </div>

        <!-- KPI 4: Producto Terminado Liberado (Azul Corporativo / Cyan) -->
        <div class="card-3d p-6 relative overflow-hidden group">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>

            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-black tracking-widest uppercase text-blue-700 bg-blue-50 border border-blue-200/80 px-2.5 py-1 rounded-full shadow-sm">
                    Despacho Aprobado
                </span>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#003B5C] to-[#048ABF] text-white flex items-center justify-center shadow-[0_10px_20px_-5px_rgba(0,59,92,0.4)] transform group-hover:rotate-6 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Liberados Hoy</p>
                <div class="font-display text-4xl font-black text-slate-800 tracking-tight mt-1 flex items-baseline gap-2">
                    {{ $liberadoHoy }}
                    <span class="text-xs font-extrabold text-blue-600 uppercase tracking-widest">Lotes aptos</span>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    <span class="font-semibold">Firmas CFR 21 cerradas</span>
                </span>
                <span class="font-bold text-slate-700">Stock disponible</span>
            </div>
        </div>
    </div>

    <!-- 2. Monitor de Línea y Panel de Auditoría -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Monitor de Línea Activa (Tabla 3D Industrial) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-7 bg-gradient-to-b from-cyan-500 to-aurofarma rounded-full shadow-3d-cyan"></div>
                    <div>
                        <h3 class="font-display text-xl font-black text-slate-800 tracking-tight">Monitor de Línea Activa (EBR)</h3>
                        <p class="text-xs text-slate-500 font-medium">Seguimiento en vivo de las órdenes en manufactura</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-900 text-cyan-300 shadow-sm flex items-center space-x-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
                        <span>Flujo Continuo</span>
                    </span>
                </div>
            </div>

            <div class="card-3d overflow-hidden border border-slate-200/80">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white">
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-cyan-300">OP / Lote Físico</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-200">Producto Farmacéutico</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-center text-slate-200">Progreso EBR</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-right text-slate-200">Estado / Etapa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($activeOrders as $op)
                                <tr class="hover:bg-cyan-50/40 transition-colors group">
                                    <!-- Lote / OP Placa 3D -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-display text-base font-black text-slate-800 tracking-tight group-hover:text-cyan-600 transition-colors">
                                                {{ $op->lote }}
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">
                                                OP: <strong class="text-slate-600">{{ $op->op_number }}</strong>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Producto -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 font-black text-xs flex-shrink-0 shadow-inner">
                                                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                            </div>
                                            <div>
                                                <span class="text-sm font-bold text-slate-800 leading-tight block">
                                                    {{ $op->product->name ?? 'Producto no especificado' }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                                                    Forma: {{ $op->product->pharmaceutical_form ?? 'Oral / Inyectable' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Barra de Progreso 3D Cilíndrica -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1.5 min-w-[150px] max-w-[180px] mx-auto">
                                            <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden p-0.5 shadow-inner border border-slate-200/80">
                                                <div class="h-full bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400 rounded-full shadow-sm transition-all duration-700 relative overflow-hidden" 
                                                     style="width: {{ $op->progress_percentage }}%">
                                                    <div class="absolute inset-0 bg-white/25 animate-pulse"></div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between text-[9px] font-black text-slate-500 uppercase tracking-widest px-0.5">
                                                <span>Avance</span>
                                                <span class="text-cyan-700 font-extrabold">{{ $op->progress_percentage }}%</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Estado Pill con Colores Aurofarma -->
                                    <td class="px-6 py-4 text-right">
                                        @php
                                            $badgeStyles = [
                                                'Emisión' => 'bg-slate-100 text-slate-700 border-slate-300',
                                                'Ajuste DT' => 'bg-cyan-50 text-cyan-800 border-cyan-300',
                                                'Codificación' => 'bg-blue-50 text-blue-800 border-blue-300',
                                                'Revisión COAs' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                                                'Despeje Línea' => 'bg-purple-50 text-purple-800 border-purple-300',
                                                'Dispensación' => 'bg-teal-50 text-teal-800 border-teal-300',
                                                'Manufactura' => 'bg-amber-50 text-amber-800 border-amber-300',
                                                'Empaque' => 'bg-orange-50 text-orange-800 border-orange-300',
                                            ];
                                            $style = $badgeStyles[$op->current_step_label] ?? 'bg-slate-100 text-slate-700 border-slate-300';
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full {{ $style }} text-[10px] font-black uppercase tracking-wider border shadow-3d-badge">
                                            {{ $op->current_step_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-3 text-slate-300">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            </div>
                                            <p class="font-bold text-sm text-slate-600">No hay lotes en ejecución activa en este momento</p>
                                            <p class="text-xs text-slate-400 mt-1">Todas las órdenes han sido completadas o están en espera de liberación.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Columna Lateral: Firmas CFR 21 & Eficiencia 3D -->
        <div class="space-y-6">
            
            <!-- Feed Forense CFR 21 Part 11 (3D Dark Glass) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-black text-slate-800 tracking-tight flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Audit Trail en Vivo</span>
                    </h3>
                    <span class="text-[9px] font-black uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">CFR 21</span>
                </div>

                <div class="glass-dark rounded-3xl p-6 shadow-2xl border border-slate-700/60 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="space-y-4">
                        @forelse($auditLogs as $log)
                            <div class="flex gap-3.5 items-start border-l-2 border-slate-700 pl-4 pb-1 relative group">
                                <div class="absolute -left-[5px] top-1 w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_8px_#06B6D4] group-hover:scale-125 transition-transform"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-wider">{{ $log->action }}</span>
                                        <span class="text-[9px] font-bold text-slate-500">{{ $log->created_at->format('H:i') }}</span>
                                    </div>
                                    <p class="text-xs font-bold text-white leading-snug truncate">
                                        {{ $log->user->name ?? 'SISTEMA' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        Lote: <span class="text-cyan-300 font-mono font-bold">{{ $log->model_id }}</span>
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-4">No hay firmas registradas en el turno actual.</p>
                        @endforelse
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                        <span>Registro Criptográfico Inmutable</span>
                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    </div>
                </div>
            </div>

            <!-- OEE / Rendimiento Planta 3D Widget -->
            <div class="card-3d bg-gradient-to-br from-[#005889] via-[#003B5C] to-slate-900 p-6 rounded-3xl text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-2">
                        <div class="p-2 rounded-xl bg-white/10 text-cyan-300 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <h4 class="font-display text-base font-black tracking-tight">Rendimiento OEE</h4>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 uppercase tracking-widest">
                        Óptimo
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-semibold text-slate-300">Cumplimiento de Lotes</span>
                        <span class="font-display text-3xl font-black text-cyan-300">98.4%</span>
                    </div>

                    <!-- 3D Progress Bar with Sheen -->
                    <div class="w-full h-3.5 bg-black/30 rounded-full overflow-hidden p-0.5 shadow-inner border border-white/10">
                        <div class="h-full bg-gradient-to-r from-cyan-400 to-emerald-400 rounded-full shadow-sm" style="width: 98.4%"></div>
                    </div>

                    <p class="text-[10px] text-slate-300 leading-relaxed pt-1">
                        Control en tiempo real de rendimiento operativo (Rango 90% - 110% según Artículo 2 del protocolo E.T.A.P.A.).
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
