@extends('layouts.app')

@section('header_title', 'Formatos - Fabricación')

@push('styles')
/* Swal injected globally in app.blade.php */
@endpush

@section('content')
<div class="max-w-7xl mx-auto py-8">
    
    @php
        $dispensacionCompletada = $op->dispensing && $op->dispensing->status === 'COMPLETADO';
        // Alternativamente, si no hay record de dispensacion, checar line clearance
        if (!$dispensacionCompletada) {
            $lcDispensacion = $op->lineClearances->where('area', 'Dispensación')->first();
            $dispensacionCompletada = $lcDispensacion && !empty($lcDispensacion->hora_fin);
        }
    @endphp

    <!-- Progress Indicator -->
    @include('batch.partials.ebr-navigation')

    <!-- ERROR AND SUCCESS STATES -->
    @if (session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 font-medium text-green-800">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    <!-- EXCEL STYLE DOCUMENT WRAPPER (EXECUTION) -->
    @if($plan)
    <div class="bg-white p-8 shadow-2xl border-2 border-slate-900 min-h-screen font-sans text-gray-900 mb-10">
        
        <!-- 1. ENCABEZADO NORMATIVO (REDISEÑO DE ALTA PRECISIÓN IF-304-1) -->
        <style>
            .tabla-auro {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid black;
                margin-bottom: 0;
            }
            .tabla-auro td, .tabla-auro th {
                border: 2px solid black;
                color: black !important;
            }
            .tabla-interna td {
                border: none;
            }
            .fw-bold { font-weight: 700 !important; }
            .fw-normal { font-weight: 400 !important; }
            .content-normal {
                font-style: normal !important;
                font-weight: 400 !important;
            }

            /* Botones y Acciones */
            .btn-play-ebr {
                background-color: #d1fae5 !important;
                color: #065f46 !important;
                border: 1px solid #10b981 !important;
                transition: all 0.2s;
            }
            .btn-play-ebr:hover { background-color: #10b981 !important; color: white !important; }
            
            .btn-stop-ebr {
                background-color: #fee2e2 !important;
                color: #991b1b !important;
                border: 1px solid #ef4444 !important;
                transition: all 0.2s;
            }
            .btn-stop-ebr:hover { background-color: #ef4444 !important; color: white !important; }
            .btn-stop-ebr:disabled { opacity: 0.5; cursor: not-allowed; }
            
            /* .btn-signed-ebr removed — sign state now shown as text div like ingredients */

            .btn-outline-sign {
                background-color: transparent !important;
                color: #000000 !important;
                border: 1.5px solid #000000 !important;
                padding: 4px 14px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                border-radius: 4px !important;
                text-transform: uppercase !important;
                transition: all 0.2s;
                white-space: nowrap !important;
                display: inline-block !important;
            }
            .btn-outline-sign:hover {
                background-color: #000000 !important;
                color: #ffffff !important;
            }
            .btn-outline-sign:disabled {
                opacity: 0.3;
                cursor: not-allowed;
            }
            @keyframes pulse-green {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.7; }
                100% { transform: scale(1); opacity: 1; }
            }
            .pulse-green {
                animation: pulse-green 2s infinite;
            }
        </style>

        <table class="tabla-auro tabla-ebr align-middle mb-6">
            <tbody>
                <tr>
                    <td class="text-start p-1 uppercase" style="width: 25%; font-size: 12px;">
                        <span>CÓDIGO:</span> <span>{{ $plan->master_code_header ?? '---' }}</span>
                    </td>
                    <td rowspan="4" class="text-center fw-bold uppercase tracking-tight bg-gray-50" style="width: 50%; font-size: 18px;">
                        ORDEN DE PRODUCCIÓN / FABRICACIÓN
                    </td>
                    <td rowspan="4" class="text-center p-2" style="width: 25%;">
                        <img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 60px;" class="mx-auto grayscale opacity-80 mix-blend-multiply">
                    </td>
                </tr>
                <tr>
                    <td class="text-start p-1 uppercase" style="font-size: 12px;">
                        <span>VERSIÓN:</span> <span>{{ $plan->version ?? '01' }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="text-start p-1" style="font-size: 12px;">
                        <span>FECHA DE EMISIÓN:</span> 
                        <span>{{ \Carbon\Carbon::parse($plan->issue_date ?? now())->format('Y-m-d') }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="text-start p-1 text-center uppercase" style="font-size: 12px;">
                        <span>PÁGINA</span> <span>1 de 1</span>
                    </td>
                </tr>

                <tr>
                    <td class="p-0 align-middle" style="width: 22%;">
                        <table class="tabla-interna" style="width: 100%; border-collapse: collapse; margin: 0;">
                            <tr>
                                <td class="p-2 text-start" style="border-bottom: 2px solid black;">
                                    <span class="fw-bold" style="font-size: 15px;">Registro ICA:</span> 
                                    <span class="fw-normal ms-1" style="font-size: 15px;">{{ $plan->ica_registry ?? $op->product->ica_license ?? 'NO REGISTRA' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 text-start">
                                    <span class="fw-bold" style="font-size: 15px;">Fórmula maestra:</span> 
                                    <span class="fw-normal ms-1" style="font-size: 15px;">{{ $op->product->formula_maestra ?? 'SIN ASIGNAR' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="text-center align-middle p-2" style="width: 58%;">
                        <div class="fw-bold text-gray-600" style="font-size: 12px; text-transform: uppercase;">NOMBRE DE PRODUCTO:</div>
                        <div class="fw-bold text-blue-900 text-uppercase mt-1" style="font-size: 20px; line-height: 1;">
                            {{ $op->product->name }}
                        </div>
                        
                        <div class="d-flex justify-content-center align-items-center mt-2">
                            <span class="fw-bold me-2 uppercase text-gray-600" style="font-size: 15px;">TAMAÑO DE LOTE:</span>
                            <span class="fw-bold text-slate-800" style="font-size: 15px;">{{ number_format($op->bulk_size_kg, 2, '.', '') }}</span>
                            <span class="fw-bold ms-2" style="font-size: 15px;">KG</span>
                        </div>
                    </td>

                    <td class="text-center align-middle p-2" style="width: 20%;">
                        <div class="fw-bold text-gray-600" style="font-size: 15px;">CÓDIGO:</div>
                        <div class="fw-bold text-blue-800" style="font-size: 15px;">{{ $plan->master_code_header }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 2. CUERPO DEL DOCUMENTO -->
        <div class="mt-6 space-y-4">
            
            <!-- 1. OBJETIVO -->
            <div class="border-2 border-black">
                <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">1. OBJETIVO</div>
                <div class="p-3 content-normal" style="font-size: 12px;">{{ $plan->objective }}</div>
            </div>

            <!-- 2. REQUERIMIENTOS PREVIOS -->
            <div class="border-2 border-black">
                <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">2. REQUERIMIENTOS PREVIOS</div>
                <div class="p-3 leading-relaxed whitespace-pre-line content-normal" style="font-size: 12px;">{{ $plan->requirements }}</div>
            </div>

            <!-- 3. EQUIPOS UTILIZADOS -->
            <div class="border-2 border-black">
                <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">3. EQUIPOS UTILIZADOS</div>
                <div class="p-3 content-normal" style="font-size: 12px;">{{ $plan->equipment }}</div>
            </div>

            <!-- 4. FABRICACIÓN -->
            <div class="border-2 border-black overflow-hidden">
                <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">4. FABRICACIÓN</div>
                
                <div class="p-4 bg-white">
                    {{-- Bloque de Instrucciones Generales (Modo Lectura) --}}
                    <div class="mb-4">
                        <label class="fw-bold block mb-1" style="font-size: 15px;">Instrucciones de Fabricación:</label>
                        <div class="p-3 border border-gray-100 bg-gray-50/30 rounded text-[15px] italic text-gray-700">
                            {{ $plan->manufacturing_instructions ?? 'Seguir estrictamente los pasos descritos en este instructivo.' }}
                        </div>
                    </div>

                    {{-- Tabla de Datos Técnicos (Sincronizada con Maestro) --}}
                    <table class="tabla-auro" style="width: 100%; border-collapse: collapse; border: 2px solid black;">
                        <tbody>
                            <tr>
                                <td class="p-2 fw-bold" style="width: 30%; background-color: #f2f2f2; font-size: 15px;">Tamaño de Lote:</td>
                                <td class="p-2 fw-normal" style="width: 70%; font-size: 15px;">
                                    <span>{{ number_format($op->bulk_size_kg, 2, '.', '') }} KG</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 fw-bold" style="background-color: #f2f2f2; font-size: 15px;">Fecha de fabricación:</td>
                                <td class="p-2 fw-normal" style="font-size: 15px;">
                                    {{ \Carbon\Carbon::parse($op->manufacturing_date)->format('Y-m-d') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 fw-bold" style="background-color: #f2f2f2; font-size: 15px;">Lote:</td>
                                <td class="p-2 fw-normal" style="font-size: 15px;">
                                    {{ $op->lote ?? '---' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 fw-bold" style="background-color: #f2f2f2; font-size: 15px;">Fecha de vencimiento:</td>
                                <td class="p-2 fw-normal" style="font-size: 15px;">
                                    {{ \Carbon\Carbon::parse($op->expiration_date)->format('Y-m') }}
                                </td>
                            </tr>
                            {{-- Fila oculta o adicional para Destrucción si se requiere mostrar en el documento final, 
                               el requerimiento pide que se recalcule, aquí lo aseguramos visualmente --}}
                            <tr>
                                <td class="p-2 fw-bold" style="background-color: #f2f2f2; font-size: 15px;">Laboratorio maquilador:</td>
                                <td class="p-2 fw-normal" style="font-size: 15px;">
                                    {{ $op->laboratorio ?? 'AUROFARMA S.A.S.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. FABRICACIÓN / ACTIVIDADES (DINÁMICO) -->
        <table class="tabla-auro mt-4 w-full border-collapse text-center" style="border: 2px solid black; table-layout: fixed;">
            <thead class="bg-gray-100 fw-bold" style="border-bottom: 2px solid black; font-size: 12px;">
                <tr>
                    <th rowspan="2" class="p-2 border-2 border-black" style="width: 35%;">ACTIVIDAD / INSTRUCCIÓN</th>
                    <th colspan="3" class="p-1 border-2 border-black" style="width: 25%;">CANTIDAD</th>
                    <th rowspan="2" class="p-2 border-2 border-black text-center" style="width: 10%;">Fecha</th>
                    <th colspan="2" class="p-1 border-2 border-black" style="width: 10%;">HORA</th>
                    <th colspan="2" class="p-1 border-2 border-black" style="width: 20%;">RESPONSABLE</th>
                </tr>
                <tr>
                    <th class="p-1 border-2 border-black" style="width: 5%;">Unidad</th>
                    <th class="p-1 border-2 border-black" style="width: 10%;">Teórica</th>
                    <th class="p-1 border-2 border-black" style="width: 10%;">Utilizada</th>
                    <th class="p-1 border-2 border-black" style="width: 5%;">Inicial</th>
                    <th class="p-1 border-2 border-black" style="width: 5%;">Final</th>
                    <th class="p-1 border-2 border-black" style="width: 10%;">Realizó</th>
                    <th class="p-1 border-2 border-black" style="width: 10%;">Verificó</th>
                </tr>
            </thead>
            <tbody>
                @php $planSteps = ($plan && $plan->steps) ? $plan->steps : [] @endphp
                
                @forelse($planSteps as $stepIndex => $step)
                    {{-- LÓGICA DE INMUTABILIDAD BPM PARA PASO --}}
                    @php
                        $stepExec = $executions->where('plan_step_id', $step->id)->whereNull('plan_step_ingredient_id')->first();
                        $isStepSigned = $stepExec && $stepExec->signed_at;
                        $stepReadonly = $isStepSigned ? 'readonly' : '';
                        $stepBg = $isStepSigned ? 'bg-gray-200 cursor-not-allowed' : 'bg-white';
                        $stepLockedEv = $isStepSigned ? 'onkeydown="return false;"' : '';
                    @endphp

                    {{-- FILA PRINCIPAL DEL PASO --}}
                    <tr class="step-card-wrapper border-b-2 border-black bg-white group relative @if($stepIndex > 0) opacity-50 pointer-events-none bg-gray-50 @endif" id="step-container-{{ $step->id }}">
                        <!-- ACTIVIDAD (35%) -->
                        <td class="border-2 border-black p-4 align-top text-start" style="width: 35%;">
                            <div class="flex flex-col gap-2">
                                <div class="text-[12px] font-normal leading-tight uppercase text-black">{{ $step->description }}</div>
                                
                                <div class="text-[8px] text-gray-400 font-bold uppercase tracking-widest mt-1">PASO #{{ $stepIndex + 1 }}</div>

                                @if(in_array($step->type, ['MEZCLA', 'TAMIZADO']))
                                    <div class="mt-2 border-t border-gray-100 pt-2">
                                        <div class="flex flex-col gap-2">
                                            {{-- Parámetro Teórico (Solo lectura) --}}
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black uppercase text-blue-800">{{ $step->type === 'MEZCLA' ? 'RPM' : 'MALLA' }} TEÓRICO:</span>
                                                <span class="text-[13px] font-black">{{ $step->target_rpm ?: $step->mesh_size ?: '---' }}</span>
                                            </div>
                                            @if($step->type === 'MEZCLA' && $step->theoretical_time_minutes)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black uppercase text-blue-800">TIEMPO TEÓRICO:</span>
                                                <span class="text-[13px] font-black">{{ $step->theoretical_time_minutes }} min</span>
                                            </div>
                                            @endif
                                            {{-- Campos Reales (Editables con Bloqueo BPM) --}}
                                            <div class="flex items-center gap-2 bg-green-50/50 p-1 border border-green-100 rounded">
                                                <span class="text-[11px] font-bold uppercase text-gray-600">RPM REAL:</span>
                                                <input type="number" step="0.01" id="step{{ $step->id }}-rpm"
                                                       {{ $stepReadonly }} {!! $stepLockedEv !!}
                                                       class="w-24 {{ $stepBg }} border border-green-300 p-0.5 text-[12px] text-center focus:ring-0 font-bold"
                                                       placeholder="0.00" value="{{ $stepExec->rpm ?? '' }}">
                                            </div>
                                            <div class="flex items-center gap-2 bg-orange-50/50 p-1 border border-orange-100 rounded">
                                                <span class="text-[11px] font-bold uppercase text-gray-600">MIN REAL:</span>
                                                <input type="number" step="0.01" min="0" id="step{{ $step->id }}-minutes"
                                                       {{ $stepReadonly }} {!! $stepLockedEv !!}
                                                       class="w-24 {{ $stepBg }} border border-orange-300 p-0.5 text-[12px] text-center focus:ring-0 font-bold"
                                                       placeholder="0.00" value="{{ $stepExec->elapsed_minutes ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                @if($step->type === 'CONTROL_PROCESO')
                                    <div class="mt-2 bg-blue-50/30 p-2 border border-blue-100 rounded space-y-2">
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <span class="font-black uppercase text-blue-900">PRUEBA:</span>
                                            <span class="uppercase">{{ $step->ipc_test_type }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <span class="font-black uppercase text-gray-500">ESP:</span>
                                            <span class="uppercase">{{ $step->ipc_specification }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-black uppercase text-blue-900">REAL:</span>
                                            <input type="text" name="steps[{{ $step->id }}][ipc_result]" id="step{{ $step->id }}-ipc_result"
                                                   {{ $stepReadonly }} {!! $stepLockedEv !!}
                                                   class="flex-1 {{ $stepBg }} border-b-2 border-blue-200 p-0.5 text-[12px] text-black focus:ring-0 font-bold" 
                                                   placeholder="..." value="{{ $stepExec->ipc_result ?? '' }}">
                                        </div>
                                    </div>
                                @endif
 
                                @if($step->type === 'TEXTO')
                                    <div class="mt-2">
                                        <div class="text-[9px] font-bold text-gray-400 uppercase italic">Instrucción Confirmada en Firma</div>
                                    </div>
                                @endif
                            </div>
                        </td>
 
                        <!-- COLUMNAS DE REGISTRO (15px Normal, Padding p-4) -->
                        <td class="border-2 border-black p-4 text-center align-middle font-normal" style="font-size: 15px; width: 5%;">---</td>
                        <td class="border-2 border-black p-4 text-center align-middle font-normal" style="font-size: 15px; width: 10%;">---</td>
                        <td class="border-2 border-black p-4 text-center align-middle font-normal" style="font-size: 15px; width: 10%;">---</td>
                        
                        <!-- 5. FECHA -->
                        <td class="border-2 border-black p-4 text-center align-middle relative bg-gray-50/30" style="width: 10%;">
                            <span id="timer-date-display-step-{{ $step->id }}" class="text-[12px] tracking-tighter">---</span>
                        </td>
 
                        <!-- 6. HORA INICIO | 7. HORA FIN -->
                        <td class="border-2 border-black p-4 text-center align-middle" style="width: 5%;">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <div id="timer-start-display-step-{{ $step->id }}" class="text-[12px] font-black hidden">--:--</div>
                                <button type="button" 
                                        @if($stepIndex > 0) disabled @endif
                                        onclick="captureEBRTime('start', {{ $step->id }}, 'step')" 
                                        id="btn-start-step-{{ $step->id }}" 
                                        class="btn-play-ebr p-1.5 rounded-full shadow-sm @if($stepIndex > 0) opacity-50 cursor-not-allowed @endif"
                                        @if($stepIndex > 0) title="Finalice el paso anterior primero" @endif>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                            <input type="hidden" name="steps[{{ $step->id }}][start_time]" id="input-start-step-{{ $step->id }}" class="step-start-input" data-step-id="{{ $step->id }}" data-qa-signed="{{ ($step->manufacturingExecutions ?? collect())->where('qa_user_id', '!=', null)->count() > 0 ? 'true' : 'false' }}">
                        </td>
                        <td class="border-2 border-black p-4 text-center align-middle relative" style="width: 5%;">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <div id="status-pulse-step-{{ $step->id }}" class="absolute -top-1 -right-1 hidden flex items-center gap-1 bg-green-100 px-1 rounded border border-green-500 z-10 pulse-green">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    </span>
                                    <span class="text-[7px] font-bold text-black uppercase">T-ON</span>
                                </div>
                                <div id="timer-end-display-step-{{ $step->id }}" class="text-[12px] font-black hidden">--:--</div>
                                <button type="button" disabled onclick="captureEBRTime('end', {{ $step->id }}, 'step')" id="btn-end-step-{{ $step->id }}" 
                                        class="btn-stop-ebr p-1.5 rounded-full shadow-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                            <input type="hidden" name="steps[{{ $step->id }}][end_time]" id="input-end-step-{{ $step->id }}" class="step-end-input" data-step-id="{{ $step->id }}" data-qa-signed="{{ ($step->manufacturingExecutions ?? collect())->where('qa_user_id', '!=', null)->count() > 0 ? 'true' : 'false' }}">
                        </td>
 
                         <!-- 8. REALIZÓ -->
                          <td class="border-2 border-black p-2 text-center align-middle" style="width: 10%;">
                              <div class="flex flex-col items-center justify-center min-h-[40px]" id="step-sign-container-{{ $step->id }}">
                                  @if($isStepSigned)
                                      <div class="flex flex-col items-center p-1 bg-green-50 rounded border border-green-200 w-full text-center leading-tight">
                                          <div class="text-[8px] font-black uppercase text-green-600 tracking-widest mb-0.5">✓ FIRMADO</div>
                                          <div class="text-[9px] font-black text-gray-900 uppercase">{{ $stepExec->user->name }}</div>
                                          <div class="text-[8px] text-gray-500 font-mono">
                                              FECHA: {{ $stepExec->signed_at->format('Y-m-d') }} | HORA: {{ $stepExec->signed_at->format('H:i:s') }}
                                          </div>
                                      </div>
                                  @else
                                      <button type="button" id="btn-sign-{{ $step->id }}" onclick="saveGenericStep({{ $step->id }}, '{{ $step->type }}')" 
                                              class="btn-outline-sign @if(($stepIndex > 0 && !isset($planSteps[$stepIndex-1])) || ($step->type === 'CARGA' && count($step->ingredients) > 0)) opacity-50 pointer-events-none cursor-not-allowed @endif">
                                         Firmar
                                      </button>
                                  @endif
                              </div>
                         </td>
 
                         <!-- 9. VERIFICÓ -->
                         <td class="border-2 border-black p-4 text-center align-middle" style="width: 10%;">
                              <div class="flex flex-col items-center justify-center min-h-[40px] {{ $isStepSigned ? '' : 'hidden' }}" id="step-qa-container-{{ $step->id }}">
                                  @if($stepExec && $stepExec->qa_user_id)
                                      <div class="flex flex-col items-center p-1 bg-blue-50 rounded border border-blue-200 w-full text-center leading-tight">
                                          <div class="text-[8px] font-black uppercase text-blue-600 tracking-widest mb-0.5">✓ VERIFICADO</div>
                                          <div class="text-[9px] font-black text-gray-900 uppercase">{{ $stepExec->qaUser->name }}</div>
                                          <div class="text-[8px] text-gray-500 font-mono">
                                              FECHA: {{ $stepExec->qa_verified_at->format('Y-m-d') }} | HORA: {{ $stepExec->qa_verified_at->format('H:i:s') }}
                                          </div>
                                      </div>
                                  @elseif($isStepSigned)
                                      @if(auth()->user()->hasPermission('verificacion_controles_en_proceso'))
                                      <button type="button" id="btn-qa-{{ $step->id }}" onclick="qaVerifyGenericStep({{ $step->id }}, {{ $stepExec->id }})" 
                                              class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] py-1 px-3 rounded font-bold uppercase transition-all shadow-sm">
                                         VERIFICAR
                                      </button>
                                              @endif
                                      @endif
                                  @endif
                              </div>
                         </td>
                    </tr>

                    {{-- FILAS DE MATERIALES (REDISEÑO RADICAL MAESTRO) --}}
                    @if($step->type === 'CARGA' && count($step->ingredients) > 0)
                         @foreach($step->ingredients as $s_ing)
                            {{-- LÓGICA DE INMUTABILIDAD BPM PARA INGREDIENTE --}}
                            @php
                                $ingExec = $executions->where('plan_step_ingredient_id', $s_ing->id)->first();
                                $isIngSigned = $ingExec && $ingExec->signed_at;
                                $ingReadonly = $isIngSigned ? 'readonly' : '';
                                $ingBg = $isIngSigned ? 'bg-gray-200 cursor-not-allowed' : 'bg-white';
                                $ingLockedEv = $isIngSigned ? 'onkeydown="return false;"' : '';

                                $materialPercentage = $s_ing->formulaIngredient->percentage ?? 0;
                                $calculatedTheoretical = round(($op->bulk_size_kg * $materialPercentage) / 100, 4);
                                $theoreticalDisplay = rtrim(rtrim(number_format($calculatedTheoretical, 4, '.', ''), '0'), '.');
                            @endphp
                            <tr class="step-material-row border-b-2 border-black bg-white group/mat parent-step-{{ $step->id }} opacity-50 pointer-events-none bg-gray-50" id="ing-row-{{ $s_ing->id }}">
                                <!-- MATERIAL (35%) -->
                                <td class="border-2 border-black p-3 pl-4 text-start align-middle relative" style="width: 35%;">
                                    <span class="font-bold text-gray-900 uppercase block truncate" style="font-size: 12px;">
                                        • {{ $s_ing->formulaIngredient->material_name }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 font-mono">{{ $s_ing->formulaIngredient->material_code ?? '' }}</span>
                                </td>

                                <!-- UNIDAD (5%) -->
                                <td class="border-2 border-black p-2 text-center align-middle font-bold" style="font-size: 12px; width: 5%;">
                                    {{ $s_ing->formulaIngredient->unit ?? 'KG' }}
                                </td>

                                <!-- TEÓRICA (10%) — BLOQUEADA, SOLO LECTURA -->
                                <td class="border-2 border-black p-2 text-center align-middle bg-gray-50" style="width: 10%;">
                                    <span class="font-black text-gray-700" style="font-size: 12px;">{{ $theoreticalDisplay }}</span>
                                </td>

                                 <td class="border-2 border-black p-2 text-center align-middle" style="width: 10%;">
                                     <input type="number" step="0.0001" min="0"
                                            {{ $ingReadonly }} {!! $ingLockedEv !!}
                                            class="ing-real-qty w-full {{ $ingBg }} border-2 border-blue-300 rounded p-1 text-center focus:ring-blue-400 focus:border-blue-500 text-[12px] font-black"
                                            placeholder="0.00" value="{{ $ingExec->yield_kg ?? '0.00' }}"
                                            data-theoretical="{{ $calculatedTheoretical }}">
                                 </td>

                                <!-- FECHA (10%) -->
                                <td class="border-2 border-black p-2 text-center align-middle bg-gray-50/30" style="width: 10%;">
                                    <span id="timer-date-display-ing-{{ $s_ing->id }}" class="text-[12px]">---</span>
                                </td>

                                <!-- INICIAL (5%) -->
                                <td class="border-2 border-black p-1 text-center align-middle relative font-normal" style="font-size: 15px; width: 5%;">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <div id="timer-start-display-ing-{{ $s_ing->id }}" class="text-[11px] font-bold hidden">--:--</div>
                                        <button type="button" disabled onclick="captureEBRTime('start', {{ $s_ing->id }}, 'ingredient')" id="btn-start-ing-{{ $s_ing->id }}" 
                                                class="btn-play-ebr p-1 rounded-full shadow-sm opacity-50 cursor-not-allowed">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                        </button>
                                    </div>
                                    <input type="hidden" name="ingredients[{{ $s_ing->id }}][start_time]" id="input-start-ing-{{ $s_ing->id }}">
                                </td>

                                <!-- FINAL (5%) -->
                                <td class="border-2 border-black p-1 text-center align-middle relative font-normal" style="font-size: 15px; width: 5%;">
                                    <div class="flex flex-col items-center justify-center gap-1">
                                        <div id="status-pulse-ing-{{ $s_ing->id }}" class="absolute -top-1 -right-1 hidden flex items-center gap-1 bg-green-100 px-1 rounded border border-green-500 z-10 pulse-green">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-green-500"></span>
                                            </span>
                                            <span class="text-[6px] font-bold text-black uppercase">T-ON</span>
                                        </div>
                                        <div id="timer-end-display-ing-{{ $s_ing->id }}" class="text-[11px] font-bold hidden">--:--</div>
                                        <button type="button" disabled onclick="captureEBRTime('end', {{ $s_ing->id }}, 'ingredient')" id="btn-end-ing-{{ $s_ing->id }}" 
                                                class="btn-stop-ebr p-1 rounded-full shadow-sm">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"></path></svg>
                                        </button>
                                    </div>
                                    <input type="hidden" name="ingredients[{{ $s_ing->id }}][end_time]" id="input-end-ing-{{ $s_ing->id }}">
                                </td>

                                <!-- REALIZÓ (10%) - Firma Independiente de Ingrediente -->
                                <td class="border-2 border-black p-4 text-center align-middle" style="width: 10%;">
                                    <div class="flex flex-col items-center justify-center min-h-[40px]" id="ing-sign-container-{{ $s_ing->id }}">
                                        @if($isIngSigned)
                                            <div class="flex flex-col items-center p-1 bg-green-50 rounded border border-green-200 w-full text-center leading-tight">
                                                <div class="text-[8px] font-black uppercase text-green-600 tracking-widest mb-0.5">✓ FIRMADO</div>
                                                <div class="text-[9px] font-black text-gray-900 uppercase">{{ $ingExec->user->name }}</div>
                                                <div class="text-[8px] text-gray-500 font-mono">
                                                    FECHA: {{ $ingExec->signed_at->format('Y-m-d') }} | HORA: {{ $ingExec->signed_at->format('H:i:s') }}
                                                </div>
                                            </div>
                                        @else
                                            <button type="button" id="btn-sign-ing-{{ $s_ing->id }}" 
                                                    onclick="saveIngredientSignature({{ $s_ing->id }}, {{ $step->id }})" 
                                                    class="btn-outline-sign">
                                               Firmar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <!-- VERIFICÓ (10%) -->
                                <td class="border-2 border-black p-4 text-center align-middle" style="width: 10%;">
                                     <div class="flex flex-col items-center justify-center min-h-[40px] {{ $isIngSigned ? '' : 'hidden' }}" id="ing-qa-container-{{ $s_ing->id }}">
                                         @if($ingExec && $ingExec->qa_user_id)
                                             <div class="flex flex-col items-center p-1 bg-blue-50 rounded border border-blue-200 w-full text-center leading-tight">
                                                 <div class="text-[8px] font-black uppercase text-blue-600 tracking-widest mb-0.5">✓ VERIFICADO</div>
                                                 <div class="text-[9px] font-black text-gray-900 uppercase">{{ $ingExec->qaUser->name }}</div>
                                                 <div class="text-[8px] text-gray-500 font-mono">
                                                     FECHA: {{ $ingExec->qa_verified_at->format('Y-m-d') }} | HORA: {{ $ingExec->qa_verified_at->format('H:i:s') }}
                                                 </div>
                                             </div>
                                         @elseif($isIngSigned)
                                             @if(auth()->user()->hasPermission('verificacion_controles_en_proceso'))
                                             <button type="button" id="btn-qa-ing-{{ $s_ing->id }}" onclick="qaVerifyGenericStep({{ $s_ing->id }}, {{ $ingExec->id }}, true, {{ $step->id }})" 
                                                     class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] py-1 px-3 rounded font-bold uppercase transition-all shadow-sm">
                                                VERIFICAR
                                             </button>
                                              @endif
                                         @endif
                                     </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="p-10 text-center text-red-600 uppercase tracking-widest text-lg">
                            FALLA CRÍTICA: NO HAY ACTIVIDADES CARGADAS
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- 4. PIE DE PÁGINA (EXECUTION) -->
         <div class="mt-8 grid grid-cols-2 gap-4">
              <div class="border-2 border-black p-3 bg-yellow-50/10">
                  <span class="block text-[8px] uppercase mb-1 leading-none text-black">NOTA / LÓGICA DE AJUSTE (REFERENCIA):</span>
                 <p class="text-[10px] content-normal leading-relaxed">{{ $plan->potency_adjustment_logic }}</p>
              </div>
              <div class="border-2 border-black p-3 bg-red-50/10">
                  <span class="block text-[8px] uppercase mb-1 leading-none text-black">OBSERVACIONES DE MANUFACTURA:</span>
                 <textarea id="manufacture-observations-final" class="w-full border-none p-0 bg-transparent text-[10px] content-normal focus:ring-0" rows="3" placeholder="Ninguna novedad reportada..."></textarea>
              </div>
         </div>
 
         @if(!empty($plan->sterilization_method))
         <div class="mt-4 border-2 border-black p-3 bg-gray-50/10">
              <span class="block text-[8px] uppercase mb-1 leading-none text-black">MÉTODO DE ESTERILIZACIÓN (REFERENCIA):</span>
             <p class="text-[10px] content-normal leading-relaxed whitespace-pre-line">{{ $plan->sterilization_method }}</p>
         </div>
         @endif

        <div class="mt-10 border-t-4 border-black pt-8" x-data="{ 
            handleFinish(detail) {
                let form = this.$refs.finishForm;
                let userField = document.createElement('input');
                userField.type = 'hidden'; userField.name = 'signature_user_id'; userField.value = detail.user_id;
                form.appendChild(userField);
                form.submit();
            }
        }">
            <form action="{{ route('batch.fabricacion.cerrar', $op) }}" method="POST" x-ref="finishForm">
                @csrf
                <x-cfr21-signature-flow 
                    module="PRODUCCION" 
                    action="Finalización de Manufactura" 
                    role="ADMIN"
                    buttonText="Finalizar Fabricación y Continuar (CFR 21)"
                    buttonClass="'bg-black text-white font-black px-8 py-4 rounded-xl text-sm uppercase tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl w-full flex items-center justify-center gap-3'"
                    @signature-verified="handleFinish($event.detail)"
                />
            </form>
        </div>
    </div>
    @else
        <div class="bg-red-50 border-4 border-red-200 p-12 text-center rounded-2xl shadow-2xl mb-6">
            <svg class="mx-auto h-20 w-20 text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h3 class="text-2xl font-black text-red-900 uppercase tracking-tighter">¡ALERTA DE CALIDAD!</h3>
            <p class="text-lg text-red-700 mt-2 font-bold italic">No hay un Instructivo de Fabricación vigente configurado para este producto.</p>
            <div class="mt-8">
                <a href="{{ route('productos.instructivo.edit', $op->product) }}" class="bg-red-900 text-white font-black px-6 py-3 rounded hover:bg-red-800 transition-colors">
                    IR AL CONFIGURADOR EBR
                </a>
            </div>
        </div>
    @endif
</div>



<!-- Modal Firma Operario (Realizó) -->
<div id="operarioSignatureModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border-t-4 border-green-600">
            <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-black text-gray-900 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Firma Electrónica: Realizó
                </h3>
                <button type="button" onclick="closeOperarioModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="px-8 py-6">
                <form id="operario-sig-form" onsubmit="event.preventDefault(); submitOperarioSignature();" class="space-y-5">
                    <!-- Responsable selector -->
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Operario Responsable</label>
                        <select id="op_on_behalf_of_id" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 font-bold text-gray-800 focus:border-green-600 focus:ring-0 transition-colors">
                            @foreach($operarios as $op_user)
                                <option value="{{ $op_user->id }}" {{ Auth::id() == $op_user->id ? 'selected' : '' }}>{{ $op_user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Confirmar con su Contraseña</label>
                        <input type="password" id="op_password" required class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 font-bold text-gray-800 focus:border-green-600 focus:ring-0 transition-colors" placeholder="••••••••">
                    </div>

                    <div class="pt-4">
                        <button type="submit" id="btn-op-sig" class="w-full py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-green-100 text-sm font-black text-white bg-green-600 hover:bg-green-700 active:scale-[0.98] transition-all flex justify-center items-center uppercase tracking-widest">
                            VALIDAR Y FIRMAR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const savedExecutions = @json($executions);
    let pendingOpPayload = null;
    let pendingQaPayload = null;
    let pendingQaContext = {};
    let currentQaContainer = null;
    
    document.addEventListener('DOMContentLoaded', () => {
        if (savedExecutions && savedExecutions.length > 0) {
            savedExecutions.forEach(exec => {
                const isIngredient = exec.step_type === 'INGREDIENTE';
                const id = isIngredient ? exec.plan_step_ingredient_id : exec.plan_step_id;
                
                if (!id) return;

                const typeStr = isIngredient ? 'ing' : 'step';
                
                // Set start time
                if (exec.start_time) {
                    const startInput = document.getElementById(`input-start-${typeStr}-${id}`);
                    if (startInput) startInput.value = exec.start_time;
                    const startDisplay = document.getElementById(`timer-start-display-${typeStr}-${id}`);
                    if (startDisplay) {
                        startDisplay.innerText = exec.start_time.substring(0, 5);
                        startDisplay.classList.remove('hidden');
                    }
                    const startBtn = document.getElementById(`btn-start-${typeStr}-${id}`);
                    if (startBtn) {
                        startBtn.classList.add('hidden');
                        startBtn.disabled = true;
                    }
                }
                
                // Set end time
                if (exec.end_time) {
                    const endInput = document.getElementById(`input-end-${typeStr}-${id}`);
                    if (endInput) endInput.value = exec.end_time;
                    const endDisplay = document.getElementById(`timer-end-display-${typeStr}-${id}`);
                    if (endDisplay) {
                        endDisplay.innerText = exec.end_time.substring(0, 5);
                        endDisplay.classList.remove('hidden');
                    }
                    const endBtn = document.getElementById(`btn-end-${typeStr}-${id}`);
                    if (endBtn) {
                        endBtn.classList.add('hidden');
                        endBtn.disabled = true;
                    }
                }
                
                // Disable inputs if completed
                if (exec.signed_at) {
                    if (isIngredient) {
                        const row = document.getElementById(`btn-sign-ing-${id}`)?.closest('tr');
                        if (row) {
                            row.querySelectorAll('input, button').forEach(i => i.disabled = true);
                            const usedQtyInput = row.querySelector('.ing-real-qty');
                            if (usedQtyInput && exec.yield_kg) usedQtyInput.value = exec.yield_kg;
                            const check = row.querySelector('input[type="checkbox"]');
                            if (check) check.checked = true;
                        }
                    } else {
                        const row = document.getElementById(`step-container-${id}`);
                        if (row) {
                            // populate rpm and minutes
                            if (exec.rpm) {
                                const rpmInput = document.getElementById(`step${id}-rpm`);
                                if (rpmInput) { rpmInput.value = exec.rpm; rpmInput.disabled = true; }
                            }
                            if (exec.elapsed_minutes) {
                                const minInput = document.getElementById(`step${id}-minutes`);
                                if (minInput) { minInput.value = exec.elapsed_minutes; minInput.disabled = true; }
                            }
                            // checkboxes for text/carga
                            // const checks = document.querySelectorAll(`.step${id}-check`);
                            // checks.forEach(c => { c.checked = true; c.disabled = true; });

                            // disable step buttons
                            const stBtn = document.getElementById(`btn-start-step-${id}`);
                            if (stBtn) stBtn.disabled = true;
                            const ndBtn = document.getElementById(`btn-end-step-${id}`);
                            if (ndBtn) ndBtn.disabled = true;
                        }
                    }

                    // 1. If step is in progress, unlock its ingredients
                    if (exec.start_time && !exec.end_time) {
                        // Already handled by captureEBRTime logic and window.load
                    }
                }
            });

            // UNLOCK CASCADE ESTRICTO PARA HIDRATACIÓN INICIAL (RECARGAS)
            
            // 1. Restaurar las filas principales (Main Steps)
            const verifiedSteps = savedExecutions.filter(e => e.qa_user_id && e.step_type !== 'INGREDIENTE');
            if (verifiedSteps.length > 0) {
                const lastVerified = verifiedSteps[verifiedSteps.length - 1]; 
                const stepInputs = Array.from(document.querySelectorAll('input.step-start-input'));
                const currentIndex = stepInputs.findIndex(inp => inp.dataset.stepId == lastVerified.plan_step_id);
                
                savedExecutions.filter(e => e.step_type !== 'INGREDIENTE').forEach(ex => {
                    const row = document.getElementById(`step-container-${ex.plan_step_id}`);
                    if (row) row.classList.remove('opacity-50', 'bg-gray-50', 'pointer-events-none');
                });

                if (currentIndex !== -1 && currentIndex < stepInputs.length - 1) {
                    const nextStepId = stepInputs[currentIndex + 1].dataset.stepId;
                    const nextRow = document.getElementById(`step-container-${nextStepId}`);
                    if (nextRow) {
                        nextRow.classList.remove('opacity-50', 'bg-gray-50', 'pointer-events-none');
                        const nextBtnStart = document.getElementById(`btn-start-step-${nextStepId}`);
                        if (nextBtnStart) {
                            nextBtnStart.disabled = false;
                            nextBtnStart.classList.remove('opacity-50', 'cursor-not-allowed');
                            nextBtnStart.title = "";
                        }
                    }
                }
            } else {
                if (savedExecutions.length > 0) {
                    const firstEx = savedExecutions.find(e => e.step_type !== 'INGREDIENTE');
                    if (firstEx) {
                        const row = document.getElementById(`step-container-${firstEx.plan_step_id}`);
                        if (row) row.classList.remove('opacity-50', 'bg-gray-50', 'pointer-events-none');
                    }
                }
            }

            // 2. Restaurar filas de Ingredientes a nivel sub-paso
            const parentGroups = {};
            savedExecutions.filter(e => e.step_type === 'INGREDIENTE').forEach(ex => {
                if (!parentGroups[ex.plan_step_id]) parentGroups[ex.plan_step_id] = [];
                parentGroups[ex.plan_step_id].push(ex);
            });

            for (const parentId in parentGroups) {
                const exIngs = parentGroups[parentId];
                // Desbloquear la opacidad de los que ya están registrados
                exIngs.forEach(ex => {
                    const row = document.getElementById(`ing-row-${ex.plan_step_ingredient_id}`);
                    if (row) row.classList.remove('opacity-50', 'bg-gray-50', 'pointer-events-none');
                });

                // Encontrar el último que QA verificó
                const lastVerifiedIng = exIngs.slice().reverse().find(e => e.qa_user_id);
                if (lastVerifiedIng) {
                    const allIngRows = Array.from(document.querySelectorAll(`.parent-step-${parentId}`));
                    const currentIngRow = document.getElementById(`ing-row-${lastVerifiedIng.plan_step_ingredient_id}`);
                    const currentIndex = allIngRows.indexOf(currentIngRow);

                    if (currentIndex !== -1 && currentIndex < allIngRows.length - 1) {
                        const nextIngRow = allIngRows[currentIndex + 1];
                        if (nextIngRow) {
                            nextIngRow.classList.remove('opacity-50', 'pointer-events-none', 'bg-gray-50');
                            const btnStart = nextIngRow.querySelector('button[id^="btn-start-ing-"]');
                            if (btnStart) {
                                btnStart.disabled = false;
                                btnStart.classList.remove('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    } else if (currentIndex === allIngRows.length - 1) {
                        // Era el último, desencriptar firma del main step
                        const btnSignMain = document.getElementById(`btn-sign-${parentId}`);
                        if (btnSignMain) {
                            btnSignMain.classList.remove('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
                        }
                    }
                }
            }
        }
    });

    function captureEBRTime(type, id, level) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
        const displayTime = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit' });
        
        // Date Capture: YYYY-MM-DD
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();
        const displayDate = `${year}-${month}-${day}`;

        const prefix = level === 'step' ? 'step-' : 'ing-';
        const displayEl = document.getElementById(`timer-${type}-display-${prefix}${id}`);
        const dateEl = document.getElementById(`timer-date-display-${prefix}${id}`);
        const inputEl = document.getElementById(`input-${type}-${prefix}${id}`);
        const btnStart = document.getElementById(`btn-start-${prefix}${id}`);
        const btnEnd = document.getElementById(`btn-end-${prefix}${id}`);
        const pulseEl = document.getElementById(`status-pulse-${prefix}${id}`);

        if (type === 'start') {
            inputEl.value = timeStr;
            displayEl.innerText = displayTime;
            displayEl.classList.remove('hidden');
            if (dateEl) dateEl.innerText = displayDate;
            
            btnStart.classList.add('hidden');
            btnEnd.disabled = false;
            btnEnd.classList.remove('opacity-50', 'cursor-not-allowed');

            // Sequential Logic: Enable ONLY the FIRST ingredient when parent step starts
            if (level === 'step') {
                const firstIngredientRow = document.querySelector(`.parent-step-${id}`);
                if (firstIngredientRow) {
                    firstIngredientRow.classList.remove('opacity-50', 'pointer-events-none', 'bg-gray-50');
                    const btnStart = firstIngredientRow.querySelector('button[id^="btn-start-ing-"]');
                    if (btnStart) {
                        btnStart.disabled = false;
                        btnStart.classList.remove('opacity-50', 'cursor-not-allowed');
                        btnStart.title = "";
                    }
                }
            }

            if (pulseEl) pulseEl.classList.remove('hidden');
        } else {
            // STOP Logic
            // Dependency Check: Step stop depends on all ingredients being finished
            if (level === 'step') {
                const pendingIngEnds = Array.from(document.querySelectorAll(`.parent-step-${id} button[id^="btn-end-ing-"]`))
                                            .filter(btn => !btn.classList.contains('hidden'));
                
                if (pendingIngEnds.length > 0) {
                    Swal.fire({
                        title: 'Secuencia de Fabricación',
                        text: 'Debe finalizar todos los ingredientes antes de cerrar la actividad general.',
                        icon: 'warning',
                        heightAuto: false
                    });
                    return;
                }
            }

            inputEl.value = timeStr;
            displayEl.innerText = displayTime;
            displayEl.classList.remove('hidden');
            btnEnd.classList.add('hidden');
            if (pulseEl) pulseEl.classList.add('hidden');
            
            // NOTE: Next step UNLOCK is now handled in QA verification callback, not here.
        }
    }

    // Timer Persistence & Sequential Logic
    window.addEventListener('load', function() {
        const stepInputs = Array.from(document.querySelectorAll('input.step-start-input'));
        const stepEnds = Array.from(document.querySelectorAll('input.step-end-input'));
        
        let lastFinishedIndex = -1;

        stepInputs.forEach((input, index) => {
            const stepId = input.dataset.stepId;
            const endInput = stepEnds[index];
            const isQaSigned = input.dataset.qaSigned === 'true';

            // 1. If step is in progress, unlock its ingredients
            if (input.value && !endInput.value) {
                const ingredientButtons = document.querySelectorAll(`.parent-step-${stepId} button[id^="btn-start-ing-"]`);
                ingredientButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.title = "";
                });
            }

            // 2. Track the last FULLY VERIFIED step to enable the next one
            if (endInput.value && isQaSigned) {
                lastFinishedIndex = index;
            }
        });

        // 3. Unlock the next available step (timers & sign button)
        const nextToUnlock = lastFinishedIndex + 1;
        if (nextToUnlock < stepInputs.length) {
            const nextStepId = stepInputs[nextToUnlock].dataset.stepId;
            const nextStartBtn = document.getElementById(`btn-start-step-${nextStepId}`);
            const nextEndBtn = document.getElementById(`btn-end-step-${nextStepId}`);
            const nextSignBtn = document.getElementById(`btn-sign-${nextStepId}`);
            
            if (nextStartBtn) {
                nextStartBtn.disabled = false;
                nextStartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                nextStartBtn.title = "";
            }
            // Ensure end button is enabled if start was already pressed but not ended
            const nextInputVal = stepInputs[nextToUnlock].value;
            const nextEndVal = stepEnds[nextToUnlock].value;
            if (nextInputVal && !nextEndVal && nextEndBtn) {
                nextEndBtn.disabled = false;
                nextEndBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            if (nextSignBtn && nextInputVal && nextEndVal) {
                nextSignBtn.disabled = false;
                nextSignBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    });

    function saveGenericStep(stepId, stepType) {
        const hInicio = document.getElementById(`input-start-step-${stepId}`).value;
        const hFinal = document.getElementById(`input-end-step-${stepId}`).value;
        let rpm = null;

        if (!hInicio || !hFinal) {
            Swal.fire({
                title: 'Integridad de Datos',
                text: 'Debe capturar los tiempos reales (Iniciar/Finalizar) para firmar la actividad.',
                icon: 'warning',
                heightAuto: false
            });
            return;
        }

        if (stepType === 'CARGA') {
            const pendingIngSigns = document.querySelectorAll(`.parent-step-${stepId} button[id^="btn-sign-ing-"]:not(.hidden)`);
            if (pendingIngSigns.length > 0) {
                Swal.fire({
                    title: 'Secuencia de Firmas',
                    text: 'Debe firmar individualmente cada materia prima antes de realizar la firma general del paso.',
                    icon: 'warning',
                    heightAuto: false
                });
                return;
            }

/*
            const checks = document.querySelectorAll(`.step${stepId}-check`);
            const allChecked = Array.from(checks).every(c => c.checked);
            if (!allChecked && checks.length > 0) {
                Swal.fire({
                    title: 'Atención',
                    text: 'Debe confirmar físicamente la carga de TODAS las materias primas marcando las casillas.',
                    icon: 'warning',
                    heightAuto: false
                });
                return;
            }
*/
        }

        if (stepType === 'MEZCLA' || stepType === 'TAMIZADO') {
            const rpmInput = document.getElementById(`step${stepId}-rpm`);
            const minsInput = document.getElementById(`step${stepId}-minutes`);
            if (rpmInput) {
                rpm = rpmInput.value;
                if (!rpm) {
                    Swal.fire({
                        title: 'RPM Requerido',
                        text: 'Debe registrar el valor real de RPM / Velocidad antes de firmar.',
                        icon: 'warning',
                        heightAuto: false
                    });
                    return;
                }
            }
            if (minsInput && !minsInput.value) {
                Swal.fire({
                    title: 'Tiempo Requerido',
                    text: 'Debe registrar el tiempo real (minutos) de mezcla/tamizado antes de firmar.',
                    icon: 'warning',
                    heightAuto: false
                });
                return;
            }
        }

/*
        if (stepType === 'TEXTO') {
            const confirmCheck = document.querySelector(`.step${stepId}-check`);
            if (confirmCheck && !confirmCheck.checked) {
                Swal.fire({
                    title: 'Atención',
                    text: 'Debe confirmar que ha ejecutado la instrucción marcando la casilla.',
                    icon: 'warning',
                    heightAuto: false
                });
                return;
            }
        }
*/

        // Store context for the modal
        pendingOpPayload = {
            id: stepId,
            type: stepType,
            hInicio: hInicio,
            hFinal: hFinal,
            rpm: rpm,
            ipcResult: document.getElementById(`step${stepId}-ipc_result`)?.value,
            elapsedMinutes: document.getElementById(`step${stepId}-minutes`)?.value,
            isIngredient: false
        };

        openOperarioModal(`Paso #${stepId}`);
    }


    function openOperarioModal(label) {
        document.getElementById('operarioSignatureModal').classList.remove('hidden');
        document.getElementById('operario-sig-form').reset();
        document.getElementById('op_password').focus();
    }

    function closeOperarioModal() {
        document.getElementById('operarioSignatureModal').classList.add('hidden');
    }

    function submitOperarioSignature() {
        const btn = document.getElementById('btn-op-sig');
        try {
            const password = document.getElementById('op_password').value;
            const onBehalfOfId = document.getElementById('op_on_behalf_of_id').value;

            if (!password) {
                Swal.fire('Atención', 'Debe ingresar su contraseña.', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'FIRMANDO...';

            const formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}");
            formData.append('password', password);
            formData.append('on_behalf_of_id', onBehalfOfId);

            if (pendingOpPayload.isIngredient) {
                formData.append('plan_step_id', pendingOpPayload.stepId);
                formData.append('plan_step_ingredient_id', pendingOpPayload.id);
                formData.append('step_type', 'INGREDIENTE');
                formData.append('yield_kg', pendingOpPayload.yield_kg);
            } else {
                formData.append('plan_step_id', pendingOpPayload.id);
                formData.append('step_type', pendingOpPayload.type);
                if (pendingOpPayload.rpm) formData.append('rpm', pendingOpPayload.rpm);
                if (pendingOpPayload.ipcResult) formData.append('ipc_result', pendingOpPayload.ipcResult);
                if (pendingOpPayload.elapsedMinutes) formData.append('elapsed_minutes', pendingOpPayload.elapsedMinutes);
            }

            formData.append('start_time', pendingOpPayload.hInicio);
            formData.append('end_time', pendingOpPayload.hFinal);

            fetch("{{ route('batch.fabricacion.store.dynamic', $op) }}", {
                method: 'POST', 
                body: formData, 
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async res => {
                if (!res.ok) {
                    let msg = 'Error de conexión HTTP ' + res.status;
                    try { const errData = await res.json(); if (errData.message) msg = errData.message; } catch(e) {}
                    throw new Error(msg);
                }
                return res.json();
            })
            .then(data => {
            if (data.success) {
                closeOperarioModal();
                const typeSfx = pendingOpPayload.isIngredient ? 'ing-' : 'container-';
                const idSfx = pendingOpPayload.id;
                
                if (!pendingOpPayload.isIngredient) {
                    const stepRow = document.getElementById(`step-container-${idSfx}`);
                    if (stepRow) stepRow.querySelectorAll('.step-start-input, .step-end-input, button').forEach(i => {
                        if (!i.id.includes('btn-qa-')) i.disabled = true;
                    });
                    const rpmInput = document.getElementById(`step${idSfx}-rpm`);
                    if (rpmInput) rpmInput.disabled = true;
                    const minInput = document.getElementById(`step${idSfx}-minutes`);
                    if (minInput) minInput.disabled = true;
                    
                    const container = document.getElementById(`step-sign-container-${idSfx}`);
                    if (container) {
                        container.innerHTML = `
                            <div class="text-center leading-tight">
                                <div class="text-[8px] font-black uppercase text-green-600 tracking-widest mb-0.5">✓ FIRMADO</div>
                                <div class="text-[10px] font-black text-black uppercase">${data.user}</div>
                                <div class="text-[9px] font-mono text-gray-500 uppercase">FECHA/HORA: ${data.signed_at ?? ''}</div>
                            </div>`;
                        const qaBtnContainer = document.getElementById(`step-qa-container-${idSfx}`);
                        if (qaBtnContainer) {
                            qaBtnContainer.classList.remove('hidden');
                            qaVerifyGenericStep(idSfx, data.execution_id);
                        }
                    }
                } else {
                    const row = document.getElementById(`btn-sign-ing-${idSfx}`).closest('tr');
                    row.querySelectorAll('input, button').forEach(i => {
                        if (!i.id.includes('btn-qa-ing')) i.disabled = true;
                    });
                    const container = document.getElementById(`ing-sign-container-${idSfx}`);
                    if (container) {
                        container.innerHTML = `
                            <div class="text-center leading-tight">
                                <div class="text-[8px] font-black uppercase text-green-600 tracking-widest mb-0.5">✓ FIRMADO</div>
                                <div class="text-[10px] font-black text-black uppercase">${data.user}</div>
                                <div class="text-[9px] font-mono text-gray-500 uppercase">FECHA/HORA: ${data.signed_at ?? ''}</div>
                            </div>`;
                        const qaBtnContainer = document.getElementById(`ing-qa-container-${idSfx}`);
                        if (qaBtnContainer) {
                            qaBtnContainer.classList.remove('hidden');
                            qaVerifyGenericStep(idSfx, data.execution_id, true, pendingOpPayload.stepId);
                        }
                    }
                }
            } else {
                Swal.fire('Error de Firma', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'VALIDAR Y FIRMAR';
            }
        }).catch(err => {
            console.error('Op Save fetch Error:', err);
            Swal.fire('Error', 'Error de red al procesar la firma.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'VALIDAR Y FIRMAR';
        });
        } catch (error) {
            console.error('Op Save Pre-fetch Error:', error);
            Swal.fire('Error', 'Fallo interno al capturar datos de la firma.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'VALIDAR Y FIRMAR';
            }
        }
    }

    function saveIngredientSignature(ingId, stepId) {
        const hInicio = document.getElementById(`input-start-ing-${ingId}`).value;
        const hFinal = document.getElementById(`input-end-ing-${ingId}`).value;
        const row = document.getElementById(`btn-sign-ing-${ingId}`).closest('tr');
        const usedQtyInput = row.querySelector('.ing-real-qty');
        const usedQty = usedQtyInput ? usedQtyInput.value : '';

        if (!hInicio || !hFinal) {
            Swal.fire({ title: 'Tiempos Incompletos', text: 'Debe registrar la hora de INICIO y FIN antes de firmar.', icon: 'warning', heightAuto: false });
            return;
        }

        if (!usedQty || parseFloat(usedQty) <= 0) {
            Swal.fire({ title: 'Cantidad Incompleta', text: 'Debe ingresar la Cantidad Real utilizada (mayor que 0) antes de firmar.', icon: 'warning', heightAuto: false });
            return;
        }

        pendingOpPayload = {
            id: ingId,
            stepId: stepId,
            hInicio: hInicio,
            hFinal: hFinal,
            yield_kg: usedQty,
            isIngredient: true
        };

        openOperarioModal(`Materia Prima`);
    }


    // Modal QA Prep
    function qaVerifyGenericStep(stepId, executionId = null, isIngredient = false, parentStepId = null) {
        if (!executionId) return;
        
        pendingQaPayload = new FormData();
        pendingQaPayload.append('_token', "{{ csrf_token() }}");
        pendingQaPayload.append('execution_id', executionId);
        
        pendingQaContext = { isIngredient: isIngredient, id: stepId, parentStepId: parentStepId };
        currentQaContainer = document.getElementById(isIngredient ? `ing-qa-container-${stepId}` : `step-qa-container-${stepId}`);
        
        // CFR 21 Part 11: Abrir el modal unificado y pasarle el callback
        window.openCfr21ModalAjax((pwd, reason) => {
            pendingQaPayload.append('signature_password', pwd);
            pendingQaPayload.append('signature_reason', reason);
            finishQaVerificationSave();
        });
    }

    function closeQaModal() {
        window.closeCfr21ModalAjax();
        pendingQaPayload = null;
        pendingQaContext = {};
    }

    function finishQaVerificationSave() {
        fetch("{{ route('batch.fabricacion.verify.dynamic', $op) }}", {
            method: 'POST', 
            body: pendingQaPayload, 
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            if (!res.ok) {
                let msg = 'Error de validación.';
                try { 
                    const errData = await res.json(); 
                    if (errData.errors && errData.errors.signature_password) {
                        msg = errData.errors.signature_password[0];
                    } else if (errData.message) {
                        msg = errData.message;
                    }
                } catch(e) {}
                
                // CFR 21: Mostrar error dentro del modal y NO cerrarlo
                window.setCfr21ErrorAjax(msg);
                throw new Error(msg);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                const localContext = { ...pendingQaContext };
                closeQaModal();
                
                // Inyectar visualmente la firma
                if (currentQaContainer && data.qa_user_name) {
                    currentQaContainer.innerHTML = `
                        <div class="text-center leading-tight">
                            <div class="text-[8px] font-black uppercase text-blue-600 tracking-widest mb-0.5">✓ VERIFICADO</div>
                            <div class="text-[10px] font-black text-black uppercase">${data.qa_user_name}</div>
                            <div class="text-[9px] font-mono text-gray-500 uppercase">FECHA/HORA: ${data.qa_time ?? ''}</div>
                        </div>
                    `;
                }
                
                // EFECTO CASCADA ESTRICTO
                if (!localContext.isIngredient) {
                    const stepInputs = Array.from(document.querySelectorAll('input.step-start-input'));
                    const currentIndex = stepInputs.findIndex(inp => inp.dataset.stepId == localContext.id);
                    if (currentIndex !== -1 && currentIndex < stepInputs.length - 1) {
                        const nextStepId = stepInputs[currentIndex + 1].dataset.stepId;
                        
                        // 1. Identificar y desbloquear la fila del paso principal
                        const nextRow = document.getElementById(`step-container-${nextStepId}`);
                        if (nextRow) {
                            nextRow.classList.remove('opacity-50', 'bg-gray-50', 'pointer-events-none');
                            
                            // Desbloquear botón Start estrictamente
                            const nextBtnStart = document.getElementById(`btn-start-step-${nextStepId}`);
                            if (nextBtnStart) {
                                nextBtnStart.disabled = false;
                                nextBtnStart.classList.remove('opacity-50', 'cursor-not-allowed');
                                nextBtnStart.title = "";
                            }
                        }
                    }
                } else {
                    // Fue un Ingrediente. Desbloquear el siguiente Ingrediente OR la firma del Parent Step
                    const allIngRows = Array.from(document.querySelectorAll(`.parent-step-${localContext.parentStepId}`));
                    const currentIngRow = document.getElementById(`ing-row-${localContext.id}`);
                    const currentIndex = allIngRows.indexOf(currentIngRow);
                    
                    if (currentIndex !== -1 && currentIndex < allIngRows.length - 1) {
                        // Desbloquear el siguiente ingrediente
                        const nextIngRow = allIngRows[currentIndex + 1];
                        if (nextIngRow) {
                            nextIngRow.classList.remove('opacity-50', 'pointer-events-none', 'bg-gray-50');
                            const btnStart = nextIngRow.querySelector('button[id^="btn-start-ing-"]');
                            if (btnStart) {
                                btnStart.disabled = false;
                                btnStart.classList.remove('opacity-50', 'cursor-not-allowed');
                                btnStart.title = "";
                            }
                        }
                    } else if (currentIndex === allIngRows.length - 1) {
                        // Era el último ingrediente. Desbloquear el botón de firmar del Main Step
                        const btnSignMain = document.getElementById(`btn-sign-${localContext.parentStepId}`);
                        if (btnSignMain) {
                            btnSignMain.classList.remove('opacity-50', 'pointer-events-none', 'cursor-not-allowed');
                        }
                    }
                }
                
                Swal.fire({ icon: 'success', title: 'Verificación Exitosa', text: 'Secuencia guardada.', timer: 1500, showConfirmButton: false, heightAuto: false });
            } else {
                closeQaModal();
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al guardar.', heightAuto: false });
            }
        })
        .catch(error => {
            console.error('QA Final Save Error:', error);
            closeQaModal();
            Swal.fire('Error', 'Problema de conexión interna.', 'error');
            const btn = document.getElementById('btn-qa-auth');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'VERIFICAR';
            }
        });
    }


</script>
@endpush
