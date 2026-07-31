@extends('layouts.app')

@section('header_title', 'Genealogía 360° - EBR Master')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-10 animate-fade-in" x-data="{ showReleaseWarning: {{ $hasAlerts ? 'true' : 'false' }}, activeAudit: null, openDrawer: false }">
    
    <!-- SECCIÓN A: Hero Banner (Estado del Lote) -->
    <div class="bg-white rounded-[2.5rem] shadow-[0_20px_40px_-12px_rgba(0,0,0,0.05)] border border-slate-100 overflow-hidden relative">
        <!-- Decoración de Fondo Sutil -->
        <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-slate-50 to-transparent opacity-60"></div>
        <div class="absolute -top-16 -left-16 w-48 h-48 bg-aurofarma-blue/5 rounded-full blur-3xl"></div>
        
        <div class="relative px-10 py-8 flex flex-col xl:flex-row justify-between items-stretch gap-8">
            
            <!-- Columna 1: Identidad Principal -->
            <div class="flex-1 flex flex-col justify-center">
                <div class="flex items-center gap-2 mb-6">
                    <span class="px-4 py-1 bg-slate-900 text-white text-[8px] font-black uppercase tracking-[0.2em] rounded-full shadow-md whitespace-nowrap">Electronic Batch Record</span>
                    <span class="px-4 py-1 bg-aurofarma-blue/10 text-aurofarma-blue text-[8px] font-black uppercase tracking-[0.2em] rounded-full border border-aurofarma-blue/20 whitespace-nowrap">OP: {{ $op->op_number }}</span>
                </div>
                
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter leading-[0.9] mb-4">
                    {{ $op->product->name }}<span class="text-aurofarma-blue">.</span>
                </h1>

                <div class="flex items-center gap-4">
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl px-6 py-3 flex items-center gap-3 shadow-sm">
                        <div class="w-1.5 h-8 bg-aurofarma-blue rounded-full"></div>
                        <div>
                            <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest">Identificador de Lote</span>
                            <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ $op->lote }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna 2: Especificaciones Técnicas (Card Interna) -->
            <div class="xl:w-80 bg-slate-50/50 backdrop-blur-sm rounded-[2rem] border border-slate-100 p-6 flex flex-col justify-center gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white shadow-sm border border-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Fórmula Maestra</span>
                        <span class="text-xs font-black text-slate-700 tracking-tight">{{ $op->product->formula_maestra ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white shadow-sm border border-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <div>
                        <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Forma Farmacéutica</span>
                        <span class="text-xs font-black text-slate-700 tracking-tight">{{ $op->product->pharmaceutical_form ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white shadow-sm border border-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <div>
                        <span class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ $op->opPresentations->count() > 1 ? 'Presentaciones Finales' : 'Presentación Final' }}</span>
                        <div class="flex flex-col gap-0.5">
                            @foreach($op->opPresentations as $p)
                                <span class="text-xs font-black text-slate-700 tracking-tight">
                                    {{ $op->product->name }} {{ $p->presentation->name }} <span class="text-aurofarma-blue">x {{ number_format($p->units_to_produce) }} UNDS</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna 3: Estatus y Control -->
            <div class="flex flex-col items-center justify-center gap-4">
                <div class="flex flex-col items-center p-6 bg-white rounded-[2.5rem] shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)] border border-slate-50 min-w-[220px]">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-[0.2em] mb-4">Estado Producción</span>
                    @php
                        $statusColors = [
                            'LIBERADO' => 'bg-emerald-500 text-white shadow-emerald-200',
                            'RECHAZADO' => 'bg-red-500 text-white shadow-red-200',
                            'CUARENTENA' => 'bg-amber-500 text-white shadow-amber-200',
                        ];
                        $statusColor = $statusColors[$op->status] ?? 'bg-slate-900 text-white shadow-slate-200';
                    @endphp
                    <div class="px-8 py-4 rounded-[1.5rem] {{ $statusColor }} text-xl font-black tracking-tighter uppercase shadow-xl whitespace-nowrap">
                        {{ $op->friendly_status }}
                    </div>
                    <p class="mt-6 text-[8px] font-black text-slate-300 uppercase tracking-[0.3em]">Protocolo AuroTrace 2026</p>
                </div>
            </div>
        </div>

        <!-- Fila Inferior: Métricas de Producción -->
        <div class="bg-slate-900 px-10 py-6 flex flex-wrap justify-between items-center gap-6">
            <div class="flex items-center gap-8">
                <div class="flex flex-col">
                    <span class="text-[8px] font-black uppercase text-slate-500 tracking-[0.2em] mb-0.5">Tamaño Lote</span>
                    <span class="text-xl font-black text-white tracking-tighter">{{ number_format($op->bulk_size_kg, 2) }} <span class="text-aurofarma-blue text-[10px] uppercase">{{ $op->unit }}</span></span>
                </div>
                <div class="w-px h-8 bg-slate-800"></div>
                <div class="flex flex-col">
                    <span class="text-[8px] font-black uppercase text-slate-500 tracking-[0.2em] mb-0.5">Rendimiento Final</span>
                    <span class="text-xl font-black tracking-tighter {{ $op->final_yield_percentage < 90 || $op->final_yield_percentage > 110 ? 'text-red-400' : 'text-emerald-400' }}">
                        {{ number_format($op->final_yield_percentage, 2) }}%
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-8">
                <div class="flex flex-col items-end">
                    <span class="text-[8px] font-black uppercase text-slate-500 tracking-[0.2em] mb-0.5">Fecha Fabricación</span>
                    <span class="text-xl font-black text-white tracking-tighter">{{ $op->manufacturing_date ? \Carbon\Carbon::parse($op->manufacturing_date)->format('Y-m') : '---' }}</span>
                </div>
                <div class="w-px h-8 bg-slate-800"></div>
                <div class="flex flex-col items-end">
                    <span class="text-[8px] font-black uppercase text-slate-500 tracking-[0.2em] mb-0.5">Fecha Vencimiento</span>
                    <span class="text-xl font-black text-white tracking-tighter">{{ $op->expiration_date ? \Carbon\Carbon::parse($op->expiration_date)->format('Y-m') : '---' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN B: Línea de Tiempo del Batch (El Mapa de Ruta) -->
    <div class="bg-slate-900 rounded-[3rem] p-12 shadow-3xl border border-slate-800 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-12 opacity-5">
            <svg class="w-64 h-64 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path></svg>
        </div>
        
        <div class="relative flex flex-col md:flex-row justify-between items-start gap-8 mt-4">
            @foreach($milestones as $index => $m)
                <div class="flex-1 flex flex-col items-center group relative w-full md:w-auto">
                    <!-- Line Connector -->
                    @if($index < count($milestones) - 1)
                        <div class="hidden md:block absolute left-1/2 w-full h-1 top-6 bg-slate-800 -z-0">
                            <div class="h-full bg-aurofarma-blue transition-all duration-1000" style="width: {{ $m['status'] === 'complete' ? '100%' : '0%' }}"></div>
                        </div>
                    @endif

                    <!-- Node Circle -->
                    <div class="z-10 w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500 ring-8 ring-slate-900
                        {{ $m['status'] === 'complete' ? 'bg-emerald-500 shadow-lg shadow-emerald-500/20' : 
                          ($m['status'] === 'current' ? 'bg-aurofarma-blue shadow-lg shadow-blue-500/40 animate-pulse' : 'bg-slate-800 text-slate-600') }}">
                        @if($m['status'] === 'complete')
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        @elseif($m['status'] === 'current')
                            <div class="w-3 h-3 bg-white rounded-full"></div>
                        @else
                            <span class="text-xs font-black">{{ $index + 1 }}</span>
                        @endif
                    </div>

                    <!-- Label -->
                    <div class="mt-4 text-center">
                        <p class="text-[10px] font-black uppercase tracking-widest {{ $m['status'] === 'pending' ? 'text-slate-600' : 'text-white' }}">
                            {{ $m['label'] }}
                        </p>
                        @if($m['status'] === 'complete')
                            <span class="text-[8px] font-bold text-emerald-400 uppercase tracking-tighter">Validado</span>
                        @elseif($m['status'] === 'current')
                            <span class="text-[8px] font-bold text-blue-400 uppercase tracking-tighter">En Curso</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- SECCIÓN C: La Bóveda de Documentos (Grid de Archivos) -->
        <div class="lg:col-span-1 space-y-6">
            <h3 class="text-2xl font-black text-slate-800 tracking-tighter flex items-center gap-3">
                <svg class="w-8 h-8 text-aurofarma-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                Bóveda de Evidencias
            </h3>
            
            <div class="grid grid-cols-1 gap-4">
                <!-- Batch Record Principal -->
                <a href="{{ route('genealogia.pdf', $op) }}" target="_blank"
                   class="group p-6 bg-slate-900 rounded-[2rem] border border-slate-800 hover:border-aurofarma-blue transition-all shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500/10 rounded-2xl">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <span class="text-[8px] font-black text-blue-400 uppercase tracking-widest border border-blue-400/30 px-2 py-1 rounded-lg">Master Record</span>
                    </div>
                    <h4 class="text-white font-black text-lg tracking-tight group-hover:text-aurofarma-blue transition-colors">Electronic Batch Record (BR)</h4>
                    <p class="text-slate-500 text-xs mt-1 font-bold">Consolidado final de firmas y trazabilidad.</p>
                </a>

                <!-- COAs Dinámicos -->
                <div class="p-6 bg-white rounded-[2rem] border border-slate-100 shadow-xl overflow-hidden relative">
                    <h4 class="text-slate-800 font-black text-lg tracking-tight mb-4 flex items-center justify-between">
                        Certificados (COAs)
                        <span class="text-[10px] bg-slate-100 px-2 py-1 rounded-lg text-slate-500">A3PPR0007</span>
                    </h4>
                    <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                        @php $coaCount = 0; @endphp
                        @foreach($op->opMaterialReconciliations as $rec)
                            @if($rec->coa_pdf_path)
                                @php $coaCount++; @endphp
                                <a href="{{ Storage::url($rec->coa_pdf_path) }}" target="_blank"
                                   class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50 transition-all">
                                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black text-slate-800 truncate">{{ $rec->description }}</p>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter">Lote: {{ $rec->lote }} | Anal.: {{ $rec->n_analisis }}</p>
                                    </div>
                                </a>
                            @endif
                        @endforeach
                        @if($coaCount === 0)
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <p class="text-xs font-bold text-slate-300 italic uppercase">Sin archivos adjuntos</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    <!-- SECCIÓN D: Radar de Desviaciones y Auditoría (CFR 21) -->
    <div class="lg:col-span-2 space-y-6">
        <h3 class="text-2xl font-black text-slate-800 tracking-tighter flex items-center gap-3">
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            Radar de Trazabilidad y Auditoría
        </h3>

        <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden relative">
            <div class="bg-slate-900 px-8 py-4 flex justify-between items-center">
                <span class="text-[10px] font-black text-white uppercase tracking-[0.2em] italic">Forensic Feed Compliance</span>
                <span class="text-[9px] font-bold text-slate-400">Total Entradas: {{ $auditLogs->count() }}</span>
            </div>
            
            <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto custom-scrollbar">
                @forelse($auditLogs as $log)
                    @php
                        $style = $log->action_style;
                        $badge = $log->module_badge;
                        $human = $log->human_description;
                        $meta = $log->metadata;
                    @endphp
                    <div class="p-6 transition-all hover:bg-slate-50 cursor-pointer group {{ $log->is_alert ? 'bg-red-50/60 border-l-4 border-red-500 hover:bg-red-100/50' : '' }}"
                         @click="activeAudit = {{ json_encode([
                            'id' => $log->id,
                            'date' => $log->created_at->format('d/m/Y H:i:s'),
                            'user' => $log->user->name ?? 'Sistema',
                            'ip' => $log->ip_address,
                            'module' => $badge,
                            'action' => mb_strtoupper($log->action),
                            'style' => $style,
                            'description' => $human,
                            'old' => json_decode($log->old_values, true),
                            'new' => json_decode($log->clean_new_values, true),
                            'meta' => $meta,
                            'reason' => $log->reason
                         ]) }}; openDrawer = true;">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[10px] font-black {{ $log->is_alert ? 'text-red-700' : 'text-slate-400' }} uppercase tracking-tighter">
                                        {{ $log->created_at->timezone('America/Bogota')->format('d/m/Y H:i:s') }} (GMT-5)
                                    </span>
                                    
                                    <!-- JERARQUÍA DE PROCESO (TRIPLE BADGE) -->
                                    <div class="flex items-center gap-1">
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[9px] font-black uppercase tracking-wider border border-slate-200">
                                            {{ $op->fase_visual['proceso'] }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[9px] font-black uppercase tracking-wider border border-slate-200">
                                            {{ $op->fase_visual['subproceso'] }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-aurofarma-blue/10 text-aurofarma-blue rounded text-[9px] font-black uppercase tracking-wider border border-aurofarma-blue/20">
                                            {{ $op->fase_visual['actividad'] }}
                                        </span>
                                    </div>

                                    @if($log->is_alert)
                                        <span class="animate-pulse flex h-2 w-2 rounded-full bg-red-600"></span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-1.5 mb-1" style="color: {{ $style['color'] }}">
                                    <span class="text-sm font-bold">{{ $style['icon'] }}</span>
                                    <span class="text-xs font-black uppercase tracking-tight">
                                        {{ str_replace('CREACION OP INTELIGENTE', 'CREACIÓN OP', $log->action) }}
                                    </span>
                                </div>
                                <h5 class="text-sm font-black text-slate-800 leading-tight">
                                    {{ $log->user->name ?? 'SISTEMA' }} 
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">({{ $log->user->role ?? 'SISTEMA' }})</span>
                                </h5>
                                <p class="mt-2 text-[11px] font-medium text-slate-600 leading-relaxed pr-4">
                                    <span class="font-black text-slate-400 uppercase tracking-tighter">Acción:</span> {{ $human }}
                                </p>
                            </div>
                            <div class="md:w-auto flex items-center justify-end">
                                <span class="text-[10px] text-aurofarma-blue font-bold opacity-0 group-hover:opacity-100 transition whitespace-nowrap">Ver detalles &rarr;</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <p class="text-slate-400 font-bold italic uppercase text-xs">No hay registros de auditoría para este lote.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Drawer Lateral (Detalle del Audit) -->
        <div x-show="openDrawer" class="fixed inset-0 overflow-hidden z-[100]" style="display: none;">
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
    </div>

    <!-- Panel Final de Liberación QA -->
    <div class="bg-white rounded-[3rem] shadow-3xl border border-slate-100 p-12 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-aurofarma-blue/5 to-transparent"></div>
        <div class="relative flex flex-col md:flex-row items-center justify-between gap-12">
            <div class="max-w-2xl">
                <h3 class="text-4xl font-black text-slate-800 tracking-tighter">Certificación y Liberación Master</h3>
                <p class="mt-4 text-slate-500 font-bold text-lg leading-relaxed">
                    Este paso representa el cierre absoluto del ciclo de vida del producto. Al firmar, usted certifica que ha revisado la <span class="text-aurofarma-blue">Genealogía 360°</span> y que el lote cumple con todas las especificaciones de calidad.
                </p>

                <div x-show="showReleaseWarning" class="mt-6 p-6 bg-red-50 border-2 border-red-200 rounded-3xl flex items-center gap-6 animate-pulse">
                    <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center shrink-0">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <span class="text-xs font-black text-red-700 uppercase tracking-[0.2em]">⚠️ Advertencia Crítica de Desviación</span>
                        <p class="text-sm font-bold text-red-600 leading-snug">Existen desviaciones o alertas en el historial de este lote que requieren revisión manual obligatoria antes de proceder con la firma electrónica de liberación.</p>
                    </div>
                </div>
            </div>

            <div class="shrink-0">
                @if($op->status === 'LIBERADO')
                    <div class="flex flex-col items-center gap-6">
                        <div class="w-32 h-32 bg-emerald-100 rounded-full flex items-center justify-center border-8 border-emerald-50 shadow-inner">
                            <svg class="w-16 h-16 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="text-center">
                            <h4 class="text-3xl font-black text-emerald-800 tracking-tighter uppercase">Lote Liberado</h4>
                            <p class="text-xs font-bold text-emerald-600 mt-1 uppercase tracking-widest">Protocolo 21 CFR Part 11 Cerrado</p>
                        </div>
                    </div>
                @else
                    @if(auth()->user()->hasPermission('liberacion_final_lote'))
                        <div x-data="{ 
                            handleRelease(detail) {
                                axios.defaults.headers.common['X-CSRF-TOKEN'] = detail.new_token;
                                document.querySelector('meta[name=csrf-token]').content = detail.new_token;
                                let form = this.$refs.finalReleaseForm;
                                form.submit();
                            }
                        }">
                            <form action="{{ route('genealogia.release', $op) }}" method="POST" x-ref="finalReleaseForm">
                                @csrf
                                <x-cfr21-signature-flow 
                                    module="CALIDAD" 
                                    action="Liberación Master de Lote" 
                                    role="DIRECCIÓN TÉCNICA"
                                    buttonText="Ejecutar Liberación"
                                    buttonClass="'px-16 py-8 bg-slate-900 text-white font-black text-xl rounded-[2.5rem] shadow-3xl shadow-slate-200 hover:bg-aurofarma-blue hover:shadow-blue-200 active:scale-95 transition-all flex items-center gap-4 group'"
                                    @signature-verified="handleRelease($event.detail)"
                                />
                            </form>
                        </div>
                    @else
                        <div class="p-8 bg-slate-100 rounded-[2.5rem] border border-slate-200 text-center">
                            <svg class="w-12 h-12 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Requiere Permisos de Dirección Técnica</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fade-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-bounce-subtle { animation: bounce-subtle 3s infinite; }
    @keyframes bounce-subtle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    
    .shadow-3xl { shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3); }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection

