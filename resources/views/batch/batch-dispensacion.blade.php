@extends('layouts.app')

@section('header_title', 'Formatos - Dispensación')

@push('styles')
/* Swal injected globally in app.blade.php */
@endpush

@section('content')
<div class="max-w-7xl mx-auto py-8">
    
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
    @if ($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-bold text-red-800">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- HEADING: QUALITY DOCUMENT FOR THE RECORD -->
    <div class="bg-white border-2 border-gray-900 overflow-hidden mb-6 shadow-xl">
        <div class="border-b-2 border-gray-900 p-4 text-center bg-gray-50 flex items-center justify-between">
            <img src="{{ asset('img/logo.png') }}" alt="Aurofarma Logo" class="h-10 ml-4 mix-blend-multiply opacity-80 filter grayscale">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-wider">FORMATO DE REGISTRO DE VERIFICACIÓN DE DISPENSACIÓN</h1>
                <p class="text-sm font-bold text-gray-500 tracking-widest mt-1">CÓDIGO: F-PR-009 | VERSIÓN: 01</p>
            </div>
            <div class="w-32 mr-4"></div>
        </div>

        <!-- Document Metadata Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 bg-white text-sm">
            <div class="border-r border-b border-gray-900 p-3">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Producto</p>
                <p class="text-base font-black text-slate-800 uppercase">{{ $op->product->name }}</p>
            </div>
            <div class="border-r border-b border-gray-900 p-3 bg-gray-50">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Lote</p>
                <p class="text-base font-black text-slate-800 uppercase">{{ $op->lote }}</p>
            </div>
            <div class="border-r border-b border-gray-900 p-3">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Orden de Producción</p>
                <p class="text-base font-black text-aurofarma-blue rounded">{{ $op->op_number }}</p>
            </div>
            <div class="border-b border-gray-900 p-3 bg-gray-50">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Fecha de Vencimiento</p>
                <p class="text-base font-black text-slate-800 uppercase">{{ \Carbon\Carbon::parse($op->expiration_date)->format('m-Y') }}</p>
            </div>

            <div class="col-span-2 md:col-span-1 border-r border-b md:border-b-0 border-gray-900 p-3">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Tamaño Lote Granel Total</p>
                <p class="text-base font-black text-slate-800 uppercase">{{ number_format($op->bulk_size_kg, 2, '.', '') }} KG</p>
            </div>
            <div class="col-span-2 md:col-span-3 p-3">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Presentaciones a Envasar</p>
                <div class="text-sm font-bold text-slate-700 flex flex-wrap gap-x-4 gap-y-1">
                    @foreach($op->opPresentations as $pres)
                        <span class="inline-flex items-center"><span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-2"></span>{{ $pres->units_to_produce }} UNIDADES de {{ $pres->presentation->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CORE TABLE -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-xl overflow-hidden mb-6">
        <div class="bg-slate-900 p-3 text-white">
            <h3 class="text-sm font-black tracking-widest uppercase">Registro de Materias Primas</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-black">
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center w-24">FECHA</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-left">MATERIA PRIMA DISPENSADA</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center w-28">LOTE</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center">CANTIDAD TEÓRICA</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center">CANTIDAD REAL</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center">H. INICIO</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center">H. FINAL</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-center w-32">REALIZÓ</th>
                        <th class="border-b-2 border-slate-400 p-3 text-center w-36">ENTRADA DATOS</th>
                    </tr>
                </thead>
                <tbody id="dispensing-table-body">
                    @foreach($op->product->ingredients as $ingredient)
                        @php
                            // Calculate theoretical amount: (bulk_size * percentage) / 100
                            $theoreticalQty = round(($op->bulk_size_kg * $ingredient->percentage) / 100, 2);
                            $minTolerancia = round($theoreticalQty * 0.99, 2);
                            $maxTolerancia = round($theoreticalQty * 1.01, 2);
                            
                            // Check if this ingredient has already been completely weighed
                            $dispensedDetail = collect($dispensingDetails)->firstWhere('formula_ingredient_id', $ingredient->id);
                            
                            $isCompleted = !is_null($dispensedDetail);
                        @endphp
                        
                        <tr class="hover:bg-slate-50 ingredient-row {{ $isCompleted ? 'bg-green-50/50' : '' }}" data-ingredient-id="{{ $ingredient->id }}" data-theoretical="{{ $theoreticalQty }}" data-min="{{ $minTolerancia }}" data-max="{{ $maxTolerancia }}">
                            <td class="border-b border-r border-slate-300 p-2 text-center text-xs font-bold text-slate-500">
                                {{ $isCompleted ? \Carbon\Carbon::parse($dispensedDetail->fecha)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d') }}
                            </td>
                            
                            <td class="border-b border-r border-slate-300 p-2">
                                <div class="font-bold text-slate-800" style="font-size: 12px;">{{ $ingredient->material_name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $ingredient->material_code }}</div>
                            </td>

                            {{-- LOTE DE MATERIA PRIMA --}}
                            <td class="border-b border-r border-slate-300 p-2 text-center align-middle w-28">
                                @if($isCompleted)
                                    <span class="text-[11px] font-black text-gray-800 uppercase tracking-wide">
                                        {{ $dispensedDetail->lote_mp ?? '---' }}
                                    </span>
                                @else
                                    <input type="text" maxlength="100"
                                           class="lote-mp-input w-full border border-gray-200 rounded px-2 py-1.5 text-[11px] font-bold text-center text-gray-500 bg-gray-100 cursor-not-allowed outline-none"
                                           value="{{ $reconciledBatches[$ingredient->material_name . ' (' . $ingredient->material_code . ')'] ?? '' }}"
                                           readonly
                                           placeholder="---">
                                @endif
                            </td>

                            <!-- Theoretical QTY -->
                            <td class="border-b border-r border-slate-300 p-2 text-center">
                                <span class="bg-gray-100 text-slate-700 px-3 py-1 rounded font-black border border-gray-300 block w-full">
                                    {{ number_format($theoreticalQty, 2, '.', '') }} <span class="text-[10px]">{{ $ingredient->unit }}</span>
                                </span>
                            </td>
                            
                            <!-- Actual QTY -->
                            <td class="border-b border-r border-slate-300 p-2 text-center align-middle">
                                @if($isCompleted)
                                    <span class="bg-white text-aurofarma-blue px-3 py-1 rounded font-black border-2 border-blue-200 block w-full">
                                        {{ number_format($dispensedDetail->cantidad_real, 2, '.', '') }} <span class="text-[10px]">{{ $ingredient->unit }}</span>
                                    </span>
                                @else
                                    <div class="relative w-full">
                                        <input type="number" step="0.01" min="{{ $minTolerancia }}" max="{{ $maxTolerancia }}" class="qty-real-input mask-decimal focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-1.5 px-3 sm:text-sm border-gray-300 rounded font-bold text-gray-900 border text-center shadow-inner pt-2 pb-2" placeholder="0.00">
                                        <span class="absolute right-2 top-1.5 text-xs font-bold text-gray-400">{{ $ingredient->unit }}</span>
                                    </div>
                                @endif
                            </td>
                            
                            <td class="border-b border-r border-slate-300 text-center font-bold text-gray-700 p-2 time-start">
                                {{ $isCompleted ? substr($dispensedDetail->hora_inicio, 0, 5) : '--:--' }}
                            </td>
                            
                            <td class="border-b border-r border-slate-300 text-center font-bold text-gray-700 p-2 time-end">
                                {{ $isCompleted ? substr($dispensedDetail->hora_final, 0, 5) : '--:--' }}
                            </td>
                            
                            <td class="border-b border-r border-slate-300 text-center align-middle p-2 user-stamp">
                                @if($isCompleted)
                                    <span class="text-xs font-black text-aurofarma-blue bg-blue-50 px-2 py-1 rounded inline-block">{{ $dispensedDetail->realizadoPor->name }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            
                            <!-- Action Button -->
                            <td class="border-b border-slate-300 text-center align-middle p-2 action-cell">
                                @if($isCompleted)
                                    <span class="text-green-600 bg-green-100 px-2 py-1 rounded shadow-sm flex items-center justify-center font-bold text-[10px] uppercase">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> COMPLETO
                                    </span>
                                @else
                                    <button type="button" class="btn-action w-full bg-green-500 hover:bg-green-600 text-white transition rounded p-2 text-xs font-black shadow flex items-center justify-center">
                                        INICIAR
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- CIERRE DE DOCUMENTO -->
    <form action="{{ route('batch.dispensacion.cerrar', $op) }}" method="POST" id="form-cerrar-dispensacion">
        @csrf
        <input type="hidden" name="qa_user_id" id="qa_user_id_final">
        <input type="hidden" name="on_behalf_of_id" id="on_behalf_of_id_final">
        <input type="hidden" name="password" id="password_final">

        
        <div class="bg-white border-2 border-gray-900 mb-6 shadow-md rounded">
            <div class="bg-gray-100 border-b border-gray-300 p-2 px-4 font-bold text-slate-700 uppercase tracking-widest text-xs">Observaciones Adicionales / Novedades</div>
            <textarea name="observaciones" rows="3" class="w-full border-none focus:ring-0 p-4 font-medium text-sm text-gray-800" placeholder="Escriba aquí cualquier novedad presentada durante la dispensación..."></textarea>
        </div>
        
        <!-- FIRMAS -->
        <div class="grid grid-cols-2 bg-white border-2 border-gray-900">
            <!-- REALIZADO POR -->
            <div class="p-6 border-r-2 border-gray-900 flex flex-col justify-between items-center text-center relative overflow-hidden bg-slate-50">
                <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: repeating-linear-gradient(45deg, #000 25%, transparent 25%, transparent 75%, #000 75%, #000), repeating-linear-gradient(45deg, #000 25%, #fff 25%, #fff 75%, #000 75%, #000); background-position: 0 0, 10px 10px; background-size: 20px 20px;"></div>
                <div class="flex-grow flex items-center justify-center w-full py-8 text-aurofarma-blue">
                    <x-cfr21-signature-flow 
                        :initialSigned="$dispensing->status === 'COMPLETADO' && $dispensing->realizadoPor ? true : false"
                        :initialName="$dispensing->realizadoPor->name ?? ''"
                        :initialDate="$dispensing->fecha_realizado ? \Carbon\Carbon::parse($dispensing->fecha_realizado)->format('Y-m-d') : ''"
                        :initialHour="$dispensing->fecha_realizado ? \Carbon\Carbon::parse($dispensing->fecha_realizado)->format('H:i:s') : ''"
                        buttonText="FIRMAR DISPENSACIÓN"
                        buttonClass="'hidden'"
                        onSignature="null"
                    />
                </div>
                <p class="mt-4 text-xs font-black text-slate-800 tracking-widest uppercase">Realizado Por</p>
                <p class="text-[10px] text-slate-500 uppercase mt-1">CFR 21 Parte 11 - Firma Electrónica</p>
            </div>
            
            <!-- VERIFICADO POR -->
            <div class="p-6 flex flex-col justify-between items-center text-center bg-white relative">
                <div class="flex-grow flex flex-col items-center justify-center w-full py-8 font-black text-xl italic border-b-2 border-dotted border-gray-400">
                    <span class="text-gray-300">ESPACIO PARA VERIFICADOR</span>
                </div>
                <p class="mt-4 text-xs font-black text-slate-800 tracking-widest uppercase">Verificado Por (Aseguramiento)</p>
            </div>
        </div>

        <!-- Acciones Finales -->
        <div class="flex justify-end mt-8 pb-12 space-x-4">
            <button type="submit" class="px-8 py-4 bg-slate-900 rounded shadow-2xl text-white font-black hover:bg-black transition-all transform active:scale-95 flex items-center tracking-widest">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                GUARDAR Y CERRAR DISPENSACIÓN
            </button>
        </div>
    </form>
</div>

<!-- Modal QA Verification para Dispensación -->
<div id="qaVerificationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
            <!-- Header Modal -->
            <div class="bg-gradient-to-r from-aurofarma-blue to-blue-700 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-black text-white flex items-center" id="modal-title">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Doble Verificación de Calidad (Cierre)
                </h3>
                <button type="button" onclick="closeQaModal()" class="text-blue-100 hover:text-white transition-colors cursor-pointer z-50 relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Paso 1: Autenticación QA -->
            <div id="step-1-auth" class="px-8 py-8 relative">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-50 mb-4 shadow-inner ring-4 ring-blue-50">
                        <svg class="h-8 w-8 text-aurofarma-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-black text-slate-800">Autenticación Requerida</h4>
                    <p class="text-sm text-slate-500 mt-2">Un responsable de Calidad debe validar el pesaje antes de cerrar el módulo.</p>
                </div>
                
                <div id="qa-auth-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm font-bold text-center"></div>

                <form id="qa-auth-form" onsubmit="event.preventDefault(); handleQaAuth();" class="space-y-5">
                    <!-- Responsable selector (QA) -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 uppercase tracking-widest mb-1">Responsable de Calidad</label>
                        <select id="on_behalf_of_id_modal" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 font-bold text-slate-800 focus:border-aurofarma-blue focus:ring-0 transition-colors">
                            @foreach($calidad as $qa_user)
                                <option value="{{ $qa_user->id }}" {{ Auth::id() == $qa_user->id ? 'selected' : '' }}>{{ $qa_user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Su Contraseña</label>
                        <input type="password" id="qa-password" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-aurofarma-blue focus:border-aurofarma-blue bg-gray-50 text-gray-900 font-medium transition-all" placeholder="••••••••">
                    </div>
                    <div class="pt-4">
                        <button type="submit" id="btn-qa-auth" class="w-full py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-black text-white bg-aurofarma-blue hover:opacity-90 active:scale-[0.98] transition-all flex justify-center items-center">
                            VALIDAR CREDENCIALES
                        </button>
                    </div>
                </form>
            </div>

            <!-- Paso 2: Checklist de Verificación -->
            <div id="step-2-checklist" class="hidden">
                <div class="bg-blue-50 px-8 py-5 border-b border-blue-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-black text-slate-800">Verificando: <span id="qa-user-name-display" class="text-aurofarma-blue underline"></span></h4>
                        <p class="text-xs text-slate-500 font-medium mt-1">Confirme los pesos reales de cada insumo.</p>
                    </div>
                    <div class="bg-green-100 text-green-700 p-2 rounded-full border border-green-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                </div>

                <div class="p-8">
                    <div class="bg-slate-50 rounded-xl p-5 border border-slate-200 mb-6 max-h-60 overflow-y-auto">
                        <h5 class="text-xs font-black uppercase text-slate-500 mb-3 tracking-wider">Materias Primas Pensadas:</h5>
                        <!-- Contenedor dinámico INYECTADO POR JS -->
                        <ul id="qa-dynamic-checklist" class="space-y-3 text-sm text-slate-700">
                        </ul>
                    </div>
                    
                    <div class="bg-slate-900 p-5 rounded-xl border border-slate-800 text-white mb-6 relative overflow-hidden">
                        <div class="absolute right-0 top-0 opacity-10 p-2">
                             <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        </div>
                        <h4 class="text-xs font-black text-aurofarma-teal uppercase tracking-widest mb-1">Firma Electrónica (QA)</h4>
                        <p class="text-xs text-slate-300">
                            Al hacer clic en Guardar, usted está firmando la verificación de todas las pesadas mostradas.
                        </p>
                    </div>

                    <div id="qa-save-error" class="hidden mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm font-bold text-center"></div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeQaModal()" class="px-6 py-3 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" id="btn-qa-save" onclick="handleQaVerificationSave()" class="px-6 py-3 border border-transparent rounded-xl shadow-lg shadow-teal-200 text-sm font-black text-white bg-aurofarma-teal hover:opacity-90 transition-all flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            GUARDAR VERIFICACIÓN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const currentUserId = {{ Auth::id() }};
    let mainForm = null;
    let qaVerifiedUserId = null;

    document.addEventListener('DOMContentLoaded', () => {
        mainForm = document.getElementById('form-cerrar-dispensacion');
        
        if (mainForm) {
            mainForm.addEventListener('submit', function(e) {
                e.preventDefault();
                openQaModal();
            });
        }

        // Global listener for decimal precision
        document.body.addEventListener('blur', function(e) {
            if (e.target && e.target.classList.contains('mask-decimal') && e.target.value) {
                e.target.value = parseFloat(e.target.value).toFixed(2);
            }
        }, true);

        const _token = document.querySelector('input[name="_token"]').value;
        const submitUrl = "{{ route('batch.dispensacion.detalle', $op) }}";
        
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const row = this.closest('tr');
                const ingredientId = row.dataset.ingredientId;
                const theoreticalQty = row.dataset.theoretical;
                const inputReal = row.querySelector('.qty-real-input');
                const timeStartCell = row.querySelector('.time-start');
                const timeEndCell = row.querySelector('.time-end');
                const userStampCell = row.querySelector('.user-stamp');
                const actionCell = row.querySelector('.action-cell');
                
                // ESTADO INICIAR
                if (this.innerText.includes('INICIAR')) {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const mins = String(now.getMinutes()).padStart(2, '0');
                    timeStartCell.innerText = `${hours}:${mins}`;
                    
                    this.innerHTML = 'FINALIZAR';
                    this.className = "btn-action w-full bg-red-500 hover:bg-red-600 text-white transition rounded p-2 text-xs font-black shadow flex items-center justify-center animate-pulse";
                    inputReal.focus();
                    return;
                }
                
                // ESTADO FINALIZAR
                if (this.innerText.includes('FINALIZAR')) {
                    try {
                        console.log("Iniciando validación de tolerancia...");
                        const realValue = parseFloat(inputReal.value);
                        const minTolerancia = parseFloat(row.dataset.min);
                        const maxTolerancia = parseFloat(row.dataset.max);
                        const materiaPrima = row.querySelector('.font-bold.text-slate-800').innerText;
                        const loteInput = row.querySelector('.lote-mp-input');
                        const loteValue = loteInput ? loteInput.value.trim().toUpperCase() : '';

                        console.log(`Valores capturados -> Real: ${realValue}, Min: ${minTolerancia}, Max: ${maxTolerancia}, Lote: ${loteValue}`);

                        if (!loteValue) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Lote Requerido',
                                text: 'Debe ingresar el número de LOTE de la materia prima antes de finalizar.',
                                heightAuto: false
                            });
                            loteInput.focus();
                            return;
                        }

                    if (isNaN(realValue) || realValue <= 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Entrada Inválida',
                            text: 'Debe digitar una Cantidad Real válida mayor a cero antes de finalizar.',
                            confirmButtonColor: '#3085d6',
                            heightAuto: false
                        });
                        return;
                    }

                    if (realValue < minTolerancia || realValue > maxTolerancia) {
                        Swal.fire({
                            title: '⚠️ ALERTA DE CALIDAD',
                            html: `<div style="text-align: left;">
                                     <p>La cantidad pesada está fuera del rango de tolerancia permitido (± 1%).</p>
                                     <hr>
                                     <b>Cantidad Teórica:</b> ${theoreticalQty} <br>
                                     <b>Rango Permitido:</b> ${minTolerancia} a ${maxTolerancia} <br><br>
                                     <span style="color: #d33;">Verifique la balanza o comuníquese con el supervisor.</span>
                                   </div>`,
                            icon: 'warning',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#3085d6',
                            background: '#ffffff',
                            heightAuto: false,
                            customClass: {
                                popup: 'border-radius-15'
                            }
                        });
                        inputReal.value = '';
                        inputReal.focus();
                        return;
                    }

                    Swal.fire({
                        title: '¿Confirmar Pesaje?',
                        text: `¿Está seguro de que la cantidad de ${realValue} kg es correcta para ${materiaPrima}? Una vez guardado, el registro no se podrá modificar.`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, Guardar y Firmar',
                        cancelButtonText: 'Cancelar',
                        heightAuto: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Capturar hora fin
                            const now = new Date();
                            const hours = String(now.getHours()).padStart(2, '0');
                            const mins = String(now.getMinutes()).padStart(2, '0');
                            timeEndCell.innerText = `${hours}:${mins}`;

                            // Block UI
                            this.disabled = true;
                            this.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                            inputReal.readOnly = true;

                            // Enviar Fetch API POST
                            fetch(submitUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': _token,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    formula_ingredient_id: ingredientId,
                                    lote_mp: loteValue,
                                    cantidad_teorica: theoreticalQty,
                                    cantidad_real: realValue,
                                    hora_inicio: timeStartCell.innerText,
                                    hora_final: timeEndCell.innerText
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Pesaje Registrado',
                                        text: 'La materia prima ha sido dispensada correctamente.',
                                        timer: 2000,
                                        showConfirmButton: false,
                                        heightAuto: false
                                    });

                                    // UI Update to Success state
                                    userStampCell.innerHTML = `<span class="text-xs font-black text-aurofarma-blue bg-blue-50 border border-blue-200 shadow-sm px-2 py-1 rounded inline-block">${data.realizado_por_name}</span>`;
                                    actionCell.innerHTML = `<span class="text-green-600 bg-green-100 border border-green-200 px-2 py-1 rounded shadow-sm flex items-center justify-center font-bold text-[10px] uppercase"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg> COMPLETO</span>`;
                                    row.classList.add('bg-green-50');
                                    
                                    // Freeze lote input
                                    if (loteInput) {
                                        const loteTd = loteInput.closest('td');
                                        loteTd.innerHTML = `<span class="text-[11px] font-black text-gray-800 uppercase tracking-wide">${loteValue}</span>`;
                                    }

                                    // Reemplazar input con formato bloqueado
                                    const tdReal = inputReal.closest('td');
                                    const unit = inputReal.nextElementSibling.innerText;
                                    tdReal.innerHTML = `<span class="bg-white text-aurofarma-blue px-3 py-1 rounded font-black border-2 border-blue-200 block w-full shadow-inner">${parseFloat(realValue).toFixed(2)} <span class="text-[10px]">${unit}</span></span>`;
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Error al guardar el registro en la base de datos: ' + data.message,
                                        icon: 'error',
                                        heightAuto: false
                                    });
                                    this.disabled = false;
                                    this.innerText = 'FINALIZAR';
                                    inputReal.readOnly = false;
                                }
                            })
                            .catch(error => {
                                console.error('Error de red:', error);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Ocurrió un error en la comunicación con el servidor.',
                                    icon: 'error',
                                    heightAuto: false
                                });
                                this.disabled = false;
                                this.innerText = 'FINALIZAR';
                                inputReal.readOnly = false;
                            });
                        }
                    });
                    
                    } catch (e) {
                        console.error("Error catastrofico en el script JS:", e);
                        alert("Error interno en JavaScript. Revisa la consola (F12). Detalle: " + e.message);
                    }
                }
            });
        });
    });

    // --- Modal y QA JS ---
    function openQaModal() {
        document.getElementById('qaVerificationModal').classList.remove('hidden');
        document.getElementById('step-1-auth').classList.remove('hidden');
        document.getElementById('step-2-checklist').classList.add('hidden');
        document.getElementById('qa-auth-form').reset();
        document.getElementById('qa-auth-error').classList.add('hidden');
        document.getElementById('qa-save-error').classList.add('hidden');
        
        // Cargar lista de pesadas
        const listContainer = document.getElementById('qa-dynamic-checklist');
        listContainer.innerHTML = '';
        
        const rows = document.querySelectorAll('.ingredient-row');
        let weightCount = 0;
        
        rows.forEach(row => {
            const actionText = row.querySelector('.action-cell').innerText;
            if(actionText.includes('COMPLETO')) {
                const materialName = row.querySelector('.font-bold.text-slate-800').innerText;
                const loteSpan = row.querySelector('span.text-\\[11px\\]'); 
                const qtySpan = row.querySelector('.bg-white.text-aurofarma-blue');

                const lote = loteSpan ? loteSpan.innerText : 'S/L';
                const qtyStr = qtySpan ? qtySpan.innerText.trim() : '0';

                const li = document.createElement('li');
                li.className = 'flex items-start bg-white p-3 border border-slate-200 rounded shadow-sm';
                li.innerHTML = `
                    <div class="flex-shrink-0 mt-0.5 mr-3">
                        <input type="checkbox" required class="qa-checkbox h-4 w-4 text-aurofarma-teal focus:ring-aurofarma-teal border-gray-300 rounded cursor-pointer">
                    </div>
                    <span class="font-medium leading-snug">Confirmar peso de <b class="text-slate-800">${qtyStr}</b> para el insumo <b class="text-blue-700">${materialName}</b> (Lote: ${lote})</span>
                `;
                listContainer.appendChild(li);
                weightCount++;
            }
        });

        if (weightCount === 0) {
            listContainer.innerHTML = '<li class="text-red-500 font-bold italic">No se ha registrado ninguna pesada para verificar.</li>';
        }

        setTimeout(() => {
            document.getElementById('qa-password').focus();
        }, 100);
    }

    function closeQaModal() {
        document.getElementById('qaVerificationModal').classList.add('hidden');
    }


    function handleQaAuth() {
        let psw = document.getElementById('qa-password').value;
        let onBehalfOfId = document.getElementById('on_behalf_of_id_modal').value;
        let btn = document.getElementById('btn-qa-auth');
        let errBox = document.getElementById('qa-auth-error');
        
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> VALIDANDO...';
        btn.disabled = true;
        errBox.classList.add('hidden');

        axios.post('{{ route("batch.qa.credentials", $op) }}', {
            password: psw,
            on_behalf_of_id: onBehalfOfId
        })
        .then(res => {
            if (res.data.success) {
                qaVerifiedUserId = res.data.user_id;

                // Capturamos los datos para el envío final
                qaFinalOnBehalfOfId = onBehalfOfId;
                qaFinalPassword = psw;

                document.getElementById('qa-user-name-display').innerText = res.data.user_name;
                
                document.getElementById('step-1-auth').classList.add('hidden');
                document.getElementById('step-2-checklist').classList.remove('hidden');
                
                setTimeout(() => {
                    const firstCb = document.querySelector('.qa-checkbox');
                    if(firstCb) firstCb.focus();
                }, 100);
            }
        })
        .catch(err => {
            let msg = err.response && err.response.data && err.response.data.message 
                ? err.response.data.message 
                : 'Error de servidor. Intente nuevamente.';
            errBox.innerText = msg;
            errBox.classList.remove('hidden');
        })
        .finally(() => {
            btn.innerHTML = 'VALIDAR CREDENCIALES';
            btn.disabled = false;
        });
    }

    function handleQaVerificationSave() {
        let checkboxes = document.querySelectorAll('.qa-checkbox');
        
        if (checkboxes.length === 0) {
            let errBox = document.getElementById('qa-save-error');
            errBox.innerText = "No hay pesadas registradas para verificar.";
            errBox.classList.remove('hidden');
            return;
        }

        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });

        let errBox = document.getElementById('qa-save-error');
        if (!allChecked) {
            errBox.innerText = "Debe confirmar todas las pesadas marcando las casillas correspondientes.";
            errBox.classList.remove('hidden');
            return;
        }

        errBox.classList.add('hidden');
        let btn = document.getElementById('btn-qa-save');
        let originalText = btn.innerHTML;
        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> ENVIANDO...';
        btn.disabled = true;

        // Populate hidden fields and submit natively
        document.getElementById('qa_user_id_final').value = qaVerifiedUserId;
        document.getElementById('on_behalf_of_id_final').value = qaFinalOnBehalfOfId;
        document.getElementById('password_final').value = qaFinalPassword;
        mainForm.submit();
    }
</script>
@endpush
