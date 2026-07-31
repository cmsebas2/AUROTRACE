@extends('layouts.app')

@section('header_title', 'Formatos - Envase (A3PPR0010)')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .tabla-auro { width: 100%; border-collapse: collapse; border: 2px solid black; }
    .tabla-auro td, .tabla-auro th { border: 2px solid black; padding: 8px; color: black !important; font-size: 13px; }
    .bg-auro-header { background-color: #f8fafc; }
    .fw-bold { font-weight: 700 !important; }
    .btn-outline-sign {
        border: 2px solid black; background: transparent; color: black; font-weight: 800; padding: 6px 20px; text-transform: uppercase; border-radius: 4px; transition: all 0.2s;
    }
    .btn-outline-sign:hover { background: black; color: white; }
    .readonly-bg { background-color: #e5e7eb !important; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4">
    
    <!-- Progress Indicator -->
    @include('batch.partials.ebr-navigation')

    @php
        $res = $op->packagingResult;
        $isSigned = $res && $res->signed_at;
        $isQaSigned = $res && $res->qa_verified_at;
        $readonly = $isSampleSigned ?? $isSigned ? 'readonly' : '';
        $disabled = $isSigned ? 'disabled' : '';
        $bgClass = $isSigned ? 'readonly-bg' : 'bg-white';
        
        // Peso Declarado (Basado en la primera presentación para el cálculo de límites)
        $opPresentation = $op->opPresentations->first();
        $theoreticalWeight = $opPresentation->presentation->theoretical_weight ?? 0;
        $pesoDeclarado = $theoreticalWeight;
        
        // Límites Sugeridos (Ejemplo: +/- 1%)
        $optimo = $pesoDeclarado;
        $superior = $optimo * 1.02;
        $inferior = $optimo * 0.98;

        $yieldFinal = $op->final_yield_percentage ?? 0;
    @endphp

    <div class="bg-white p-8 shadow-2xl border-2 border-black min-h-screen font-sans text-gray-900 mb-10" id="envase-container">
        
        <!-- ENCABEZADO NORMATIVO -->
        <table class="tabla-auro mb-6">
            <tbody>
                <tr>
                    <td style="width: 20%;" class="text-center font-bold">A3PPR0010</td>
                    <td rowspan="2" style="width: 60%;" class="text-center text-xl font-black uppercase">CONTROL DE ENVASE Y PESOS</td>
                    <td rowspan="2" style="width: 20%;" class="text-center">
                        <img src="{{ asset('img/logo.png') }}" alt="AUROFARMA" style="max-height: 50px;" class="mx-auto grayscale opacity-80">
                    </td>
                </tr>
                <tr>
                    <td class="text-center text-[10px]">VERSIÓN: 01</td>
                </tr>
            </tbody>
        </table>

        <!-- PUNTO 2: INFORMACIÓN GENERAL -->
        <div class="border-2 border-black mb-6">
            <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">2. INFORMACIÓN DEL LOTE</div>
            <div class="grid grid-cols-2 p-4 gap-y-4">
                <div class="flex flex-col">
                    <span class="text-[10px] items-center font-bold uppercase text-gray-500">Producto:</span>
                    <span class="text-lg font-black text-blue-900">{{ $op->product->name }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Lote:</span>
                    <span class="text-lg font-black">{{ $op->lote }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Tamaño del Lote:</span>
                    <span class="text-lg font-black">{{ number_format($op->bulk_size_kg, 2) }} {{ $op->unit }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Cantidad a Envasar:</span>
                    <span class="text-lg font-black text-green-700">
                        {{ number_format($op->opPresentations->sum('units_to_produce'), 0) }} UNIDADES
                    </span>
                </div>
                <div class="flex flex-col border-t pt-2">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Peso Declarado (g):</span>
                    <span id="peso-declarado-val" class="text-lg font-black">{{ $pesoDeclarado }} g</span>
                </div>
                <div class="flex flex-col border-t pt-2">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Peso Promedio Real (g):</span>
                    <span id="peso-promedio-header" class="text-2xl font-black text-red-600">
                        {{ number_format($res->average_weight ?? 0, 2) }} g
                    </span>
                </div>
                <div class="flex flex-col border-t pt-2">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Estado del Lote:</span>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full uppercase tracking-wider
                        @if($op->status == 'ACONDICIONAMIENTO') bg-blue-100 text-blue-800
                        @elseif($op->status == 'PESAJE') bg-emerald-100 text-emerald-800
                        @elseif($op->status == 'MANUFACTURA') bg-amber-100 text-amber-800
                        @elseif($op->status == 'CUARENTENA') bg-red-100 text-red-800
                        @else bg-green-100 text-green-800 @endif
                    ">
                        {{ $op->status }}
                    </span>
                </div>
                <div class="flex flex-col border-t pt-2">
                    <span class="text-[10px] font-bold uppercase text-gray-500">Rendimiento Final:</span>
                    <span id="yield-display-header" class="text-2xl font-black {{ $yieldFinal < 90 || $yieldFinal > 110 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($yieldFinal, 2) }}%
                    </span>
                </div>
            </div>
            <div class="flex justify-between border-t-2 border-black px-4 py-2 bg-gray-50 font-bold text-[11px]">
                <span>INICIO: {{ $res->start_time ? $res->start_time->format('d/m/Y H:i') : '---' }}</span>
                <span>FINAL: <span id="envase-end-time-display">{{ $res->end_time ? $res->end_time->format('d/m/Y H:i') : '---' }}</span></span>
            </div>
        </div>

        <!-- PUNTO 3: CARACTERÍSTICAS FÍSICAS (CALIDAD) -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="border-2 border-black">
                <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">3.1 CARACTERÍSTICAS SENSORIALES</div>
                <div class="p-4 space-y-4">
                    @foreach(['color' => 'Color conforme', 'odor' => 'Olor característico', 'texture' => 'Textura uniforme', 'particles' => 'Libre de partículas'] as $key => $label)
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[12px] uppercase">{{ $label }}</span>
                            <select id="check-{{ $key }}" {{ $disabled }} class="border-2 border-black p-1 text-xs font-bold {{ $bgClass }}">
                                @php $field = $key . ($key == 'particles' ? '_free' : '_conforme') @endphp
                                <option value="1" {{ ($res && $res->$field) ? 'selected' : '' }}>SÍ</option>
                                <option value="0" {{ ($res && !$res->$field) ? 'selected' : '' }}>NO</option>
                            </select>
                        </div>
                    @endforeach
                    
                    <div class="pt-4 border-t-2 border-black">
                        <span class="font-bold text-[12px] uppercase block mb-2">Total Unidades Obtenidas</span>
                        <input type="number" id="units_obtained" value="{{ $res->units_obtained ?? '' }}" {{ $readonly }}
                               oninput="calculateYieldInRealTime()"
                               class="w-full border-2 border-black p-2 text-lg font-black text-center {{ $bgClass }}" placeholder="Ingrese total de unidades">
                        
                        <div id="yield-alert" class="hidden mt-2 p-2 bg-red-600 text-white text-[10px] font-bold uppercase text-center animate-pulse">
                            ALERTA: Rendimiento Crítico (<span id="yield-alert-val">0</span>%). Se requiere investigación de desviación por Dirección Técnica.
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESO PROMEDIO (PUNTO 3) -->
            <div class="border-2 border-black">
                <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">3.2 CÁLCULO DE PESO PROMEDIO (n=10)</div>
                <div class="p-4">
                    <div class="grid grid-cols-5 gap-2 mb-4">
                        @for($i = 1; $i <= 10; $i++)
                            <div class="flex flex-col">
                                <span class="text-[9px] font-bold text-center">#{{ $i }}</span>
                                <input type="number" step="0.1" id="weight-{{ $i }}" value="{{ $res->{'weight_'.$i} ?? '' }}" {{ $readonly }}
                                       class="weight-avg-input border border-black p-1 text-center text-xs font-bold {{ $bgClass }}">
                            </div>
                        @endfor
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 p-2 text-center rounded">
                        <span class="text-[10px] font-bold text-yellow-800 uppercase block">Promedio Calculado</span>
                        <span id="peso-promedio-calc" class="text-xl font-black">{{ number_format($res->average_weight ?? 0, 2) }}</span> <span class="font-bold">g</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- PUNTO 4: CONTROL DE PESOS EN PROCESO (Gráfico) -->
        <div class="border-2 border-black mb-6">
            <div class="bg-black text-white p-1 text-[10px] font-black uppercase tracking-widest pl-2">4. REGISTRO DE PESOS EN PROCESO (SPC)</div>
            <div class="grid grid-cols-3 gap-0 h-[350px]">
                <div class="col-span-1 border-r-2 border-black overflow-y-auto p-2">
                    <table class="w-full text-center text-[10px]">
                        <thead>
                            <tr class="bg-gray-100 font-bold uppercase border-b border-black">
                                <th class="p-1">Hora</th>
                                <th class="p-1">Peso (g)</th>
                                <th class="p-1"></th>
                            </tr>
                        </thead>
                        <tbody id="periodic-weights-body">
                            @foreach($op->packagingWeightControls as $c)
                                <tr class="border-b border-gray-200">
                                    <td class="p-1 font-mono">{{ $c->controlled_at->format('H:i') }}</td>
                                    <td class="p-1 font-bold">{{ number_format($c->weight, 1) }} g</td>
                                    <td class="p-1">✓</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(!$isSigned)
                    <div class="mt-4 flex gap-1">
                        <input type="number" step="0.1" id="new-weight-val" class="flex-1 border-2 border-black p-1 text-xs font-bold" placeholder="0.0">
                        <button onclick="addPeriodicWeight()" class="bg-black text-white px-3 py-1 text-[10px] font-black uppercase">Añadir</button>
                    </div>
                    @endif
                </div>
                <!-- CHART CONTAINER -->
                <div class="col-span-2 p-4 bg-gray-50 flex flex-col items-center justify-center">
                    <canvas id="weightChart" style="width: 100%; height: 100%; max-height: 280px;"></canvas>
                    <div class="flex gap-4 mt-2 text-[8px] font-bold uppercase">
                        <div class="flex items-center gap-1"><span class="w-2 h-2 bg-red-500 rounded-full"></span> L. Superior ({{ number_format($superior,2) }})</div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-full"></span> L. Óptimo ({{ number_format($optimo,2) }})</div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 bg-red-500 rounded-full"></span> L. Inferior ({{ number_format($inferior,2) }})</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FIRMAS -->
        <div class="grid grid-cols-2 gap-4 mt-8">
            <!-- REALIZÓ -->
            <div class="border-2 border-black p-4 text-center">
                <span class="block text-[10px] font-black uppercase mb-4 text-gray-500">Realizado por:</span>
                <x-cfr21-signature-flow 
                    :initialSigned="$isSigned"
                    :initialName="$res->user->name ?? ''"
                    :initialDate="$res->signed_at ? $res->signed_at->format('Y-m-d') : ''"
                    :initialHour="$res->signed_at ? $res->signed_at->format('H:i:s') : ''"
                    buttonText="Firmar Cierre"
                    buttonClass="'btn-outline-sign'"
                    onSignature="signPackaging"
                />
            </div>
            <!-- VERIFICÓ -->
            <div class="border-2 border-black p-4 text-center">
                <span class="block text-[10px] font-black uppercase mb-4 text-gray-500">Verificado por:</span>
                @if(!$isSigned)
                    <span class="text-[10px] italic text-gray-400">Esperando firma de operario...</span>
                @else
                    <x-cfr21-signature-flow 
                        :initialSigned="$isQaSigned"
                        :initialName="$res->qaUser->name ?? ''"
                        :initialDate="$res->qa_verified_at ? $res->qa_verified_at->format('Y-m-d') : ''"
                        :initialHour="$res->qa_verified_at ? $res->qa_verified_at->format('H:i:s') : ''"
                        buttonText="Verificar Calidad"
                        buttonClass="'btn-outline-sign border-blue-600 text-blue-600 hover:bg-blue-600'"
                        onSignature="qaVerifyEnvase"
                    />
                @endif
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('dashboard') }}" class="text-xs font-bold text-gray-500 hover:text-black transition-all">← Volver al Dashboard</a>
        </div>
    </div>
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
                    Firma Electrónica: Cierre de Envase
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

                    <div class="pt-4 text-center">
                        <button type="submit" id="btn-op-sig" class="w-full py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-green-100 text-sm font-black text-white bg-green-600 hover:bg-green-700 active:scale-[0.98] transition-all flex justify-center items-center uppercase tracking-widest">
                            FINALIZAR Y FIRMAR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal QA Verification Reuse -->
@include('batch.modals.qa-verification')

@endsection

@push('scripts')
<script>
    const WEIGHT_DECLARADO = {{ $pesoDeclarado }};
    const OPTIMO = {{ $optimo }};
    const SUPERIOR = {{ $superior }};
    const INFERIOR = {{ $inferior }};
    
    // CHART LOGIC
    let chart;
    const initialLabels = @json($op->packagingWeightControls->map(fn($c) => $c->controlled_at->format('H:i')));
    const initialData = @json($op->packagingWeightControls->pluck('weight'));

    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('weightChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: initialLabels,
                datasets: [
                    {
                        label: 'Peso Real',
                        data: initialData,
                        borderColor: '#2563eb', // Blue
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: '#2563eb',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Superior',
                        data: Array(initialLabels.length || 1).fill(SUPERIOR),
                        borderColor: '#ef4444',
                        borderWidth: 1.5,
                        orderDash: [5, 5],
                        pointRadius: 0,
                        fill: false
                    },
                    {
                        label: 'Óptimo',
                        data: Array(initialLabels.length || 1).fill(OPTIMO),
                        borderColor: '#22c55e',
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false
                    },
                    {
                        label: 'Inferior',
                        data: Array(initialLabels.length || 1).fill(INFERIOR),
                        borderColor: '#ef4444',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        suggestedMin: INFERIOR - 2,
                        suggestedMax: SUPERIOR + 2,
                        ticks: { font: { size: 9 } }
                    },
                    x: { ticks: { font: { size: 8 } } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // AVERAGE LOGIC
        const weightInputs = document.querySelectorAll('.weight-avg-input');
        weightInputs.forEach(input => {
            input.addEventListener('input', calculateAverage);
        });
    });

    function calculateAverage() {
        const inputs = document.querySelectorAll('.weight-avg-input');
        let sum = 0;
        let count = 0;
        inputs.forEach(i => {
            const val = parseFloat(i.value);
            if (!isNaN(val)) { sum += val; count++; }
        });
        const avg = count > 0 ? (sum / count) : 0;
        const avgFormatted = avg.toFixed(2);
        
        document.getElementById('peso-promedio-calc').innerText = avgFormatted;
        document.getElementById('peso-promedio-header').innerText = avgFormatted + ' g';
        
        // Visual warning if out of bounds
        if (avg > SUPERIOR || avg < INFERIOR) {
            document.getElementById('peso-promedio-header').className = 'text-2xl font-black text-red-600 animate-pulse';
        } else {
            document.getElementById('peso-promedio-header').className = 'text-2xl font-black text-green-600';
        }
    }

    function addPeriodicWeight() {
        const valInput = document.getElementById('new-weight-val');
        const weight = parseFloat(valInput.value);
        if (isNaN(weight)) return;

        fetch("{{ route('batch.envase.weight.store', $op) }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ weight: weight })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update Table
                const tbody = document.getElementById('periodic-weights-body');
                const row = `<tr class="border-b border-gray-200">
                                <td class="p-1 font-mono">${data.time}</td>
                                <td class="p-1 font-bold">${data.weight} g</td>
                                <td class="p-1">✓</td>
                             </tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
                
                // Update Chart
                chart.data.labels.push(data.time);
                chart.data.datasets[0].data.push(data.weight);
                // Update limit lines length
                chart.data.datasets[1].data.push(SUPERIOR);
                chart.data.datasets[2].data.push(OPTIMO);
                chart.data.datasets[3].data.push(INFERIOR);
                
                chart.update();
                valInput.value = '';
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    }

    let pendingSignatureData = null;

    function openOperarioModal() {
        document.getElementById('operarioSignatureModal').classList.remove('hidden');
        document.getElementById('operario-sig-form').reset();
        document.getElementById('op_password').focus();
    }

    function closeOperarioModal() {
        document.getElementById('operarioSignatureModal').classList.add('hidden');
    }


    function signPackaging() {
        const data = {
            color_conforme: document.getElementById('check-color').value,
            odor_conforme: document.getElementById('check-odor').value,
            texture_conforme: document.getElementById('check-texture').value,
            particles_free: document.getElementById('check-particles').value,
            average_weight: document.getElementById('peso-promedio-calc').innerText,
            units_obtained: document.getElementById('units_obtained').value
        };

        const currentYield = calculateYieldValue();
        if (currentYield < 90 || currentYield > 110) {
             Swal.fire({
                title: 'BLOQUEO DE CALIDAD',
                text: 'El rendimiento está fuera de rango (90-110%). El proceso no puede cerrarse.',
                icon: 'warning'
            });
            return;
        }
        
        for(let i=1; i<=10; i++) {
            data['weight_' + i] = document.getElementById('weight-' + i).value;
        }

        pendingSignatureData = data;
        openOperarioModal();
    }

    function submitOperarioSignature() {
        const password = document.getElementById('op_password').value;
        const onBehalfOfId = document.getElementById('op_on_behalf_of_id').value;
        const btn = document.getElementById('btn-op-sig');

        if (!password) return;

        btn.disabled = true;
        btn.innerHTML = 'FIRMANDO...';

        const payload = {
            ...pendingSignatureData,
            password: password,
            on_behalf_of_id: onBehalfOfId
        };

        fetch("{{ route('batch.envase.store', $op) }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'FINALIZAR Y FIRMAR';
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Error de red', 'error');
            btn.disabled = false;
            btn.innerHTML = 'FINALIZAR Y FIRMAR';
        });
    }

    function qaVerifyEnvase() {
        openQaModal('Verificación de Envase y Pesos');
        
        // Override global submitQaVerification for this view specifics
        window.submitQaVerification = function() {
            const password = document.getElementById('qa_password').value;
            const onBehalfOfId = document.getElementById('qa_on_behalf_of_id').value;
            const btn = document.getElementById('btn-qa-submit');

            btn.disabled = true;
            btn.innerHTML = 'VALIDANDO...';

            // 1. Verify credentials and get user_id
            fetch("{{ route('batch.qa.credentials', $op) }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ password: password, on_behalf_of_id: onBehalfOfId })
            })
            .then(res => res.json())
            .then(auth => {
                if(auth.success) {
                    // 2. Perform final verification
                    fetch("{{ route('batch.envase.verify', $op) }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ 
                            qa_user_id: auth.user_id,
                            on_behalf_of_id: onBehalfOfId,
                            password: password
                        })
                    })
                    .then(r => r.json())
                    .then(v => {
                        if(v.success) { location.reload(); }
                        else { Swal.fire('Error', v.message, 'error'); btn.disabled = false; btn.innerHTML = 'VERIFICAR'; }
                    });
                } else {
                    Swal.fire('Error', auth.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'VERIFICAR';
                }
            });
        };
    }


    function calculateYieldValue() {
        const units = parseInt(document.getElementById('units_obtained').value) || 0;
        const bulkSize = {{ $op->bulk_size_kg }};
        const theoreticalWeight = {{ $pesoDeclarado }};
        
        if (bulkSize <= 0 || theoreticalWeight <= 0) return 0;

        const totalObtainedKg = (units * theoreticalWeight) / 1000;
        return (totalObtainedKg / bulkSize) * 100;
    }

    function calculateYieldInRealTime() {
        const yield = calculateYieldValue();
        const yieldFormatted = yield.toFixed(2);
        
        const header = document.getElementById('yield-display-header');
        if (header) {
            header.innerText = yieldFormatted + '%';
            header.className = (yield < 90 || yield > 110) ? 'text-2xl font-black text-red-600' : 'text-2xl font-black text-green-600';
        }

        const btn = document.querySelector('button[onclick="signPackaging()"]');
        const alert = document.getElementById('yield-alert');
        const alertVal = document.getElementById('yield-alert-val');

        if (yield < 90 || yield > 110) {
            if (btn) btn.disabled = true;
            if (btn) btn.classList.add('opacity-50', 'cursor-not-allowed');
            if (alert) alert.classList.remove('hidden');
            if (alertVal) alertVal.innerText = yieldFormatted;
        } else {
            if (btn) btn.disabled = false;
            if (btn) btn.classList.remove('opacity-50', 'cursor-not-allowed');
            if (alert) alert.classList.add('hidden');
        }
    }
</script>
@endpush
