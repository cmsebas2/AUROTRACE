@extends('layouts.app')

@section('header_title', 'Formatos - Conciliación de Materiales')

@section('content')
<div class="max-w-7xl mx-auto py-8">
    
    <!-- Progress Indicator -->
    @include('batch.partials.ebr-navigation')

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

    <form action="{{ route('batch.conciliacion.sign', $op) }}" method="POST" id="form-conciliacion">
        @csrf
        <input type="hidden" name="on_behalf_of_id" id="final_on_behalf_of_id">
        <input type="hidden" name="password" id="final_password">

        
        <div class="bg-white border-2 border-gray-900 overflow-hidden mb-6 shadow-xl">
            <div class="border-b-2 border-gray-900 p-4 text-center bg-gray-50 flex items-center justify-between">
                <img src="{{ asset('img/logo.png') }}" alt="Aurofarma Logo" class="h-10 ml-4 mix-blend-multiply opacity-80 filter grayscale">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-wider">FORMATO DE CONCILIACIÓN DE MATERIALES</h1>
                    <p class="text-sm font-bold text-gray-500 tracking-widest mt-1">CÓDIGO: A2PPR0010 | VERSIÓN: 01</p>
                </div>
                <div class="w-32 mr-4"></div>
            </div>

            <!-- OP Data -->
            <div class="grid grid-cols-2 md:grid-cols-4 bg-white text-sm border-b-2 border-gray-900">
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
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Presentaciones</p>
                    <div class="text-xs font-bold text-slate-800">
                        @foreach($op->opPresentations as $pres)
                            {{ $pres->units_to_produce }} UND ({{ $pres->presentation->name }})<br>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. CONCILIACION DE MATERIAS PRIMAS -->
        <h2 class="text-lg font-black text-slate-800 mb-2 uppercase tracking-wide">1. Conciliación de Materias Primas</h2>
        <div class="bg-white rounded border-2 border-slate-900 shadow-md overflow-hidden mb-8">
            <table class="w-full border-collapse text-sm text-center">
                <thead>
                    <tr class="bg-slate-200 text-slate-700 font-black text-xs">
                        <th class="border-b-2 border-r border-slate-400 p-3 w-28">FECHA</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-left">DESCRIPCIÓN</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-40">LOTE</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-20">UND</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-32 bg-yellow-50">RECIBIDO</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-32">UTILIZADO</th>
                        <th class="border-b-2 border-slate-400 p-3 w-32">DEVUELTO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materiasPrimas as $mp)
                        <tr class="hover:bg-slate-50 border-b border-slate-300">
                            <td class="border-r border-slate-300 p-2 text-xs font-bold text-slate-500">
                                {{ \Carbon\Carbon::now()->format('Y-m-d') }}
                            </td>
                            <td class="border-r border-slate-300 p-2 text-left font-bold text-slate-800 text-xs">
                                {{ $mp->description }}
                            </td>
                            <td class="border-r border-slate-300 p-2">
                                <input type="text" name="items[{{ $mp->id }}][lote]" value="{{ $mp->lote }}" required {{ $firmado ? 'readonly' : '' }}
                                       class="w-full border border-gray-300 rounded px-2 py-1.5 text-[11px] font-black text-center uppercase focus:ring-aurofarma-blue {{ $firmado ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }}">
                            </td>
                            <td class="border-r border-slate-300 p-2 font-bold text-xs text-slate-600">
                                {{ $mp->unit }}
                            </td>
                            <td class="border-r border-slate-300 p-2 bg-yellow-50/30">
                                <input type="number" step="0.0001" name="items[{{ $mp->id }}][received_qty]" value="{{ $mp->received_qty }}" required {{ $firmado ? 'readonly' : '' }}
                                       class="w-full border border-yellow-300 rounded px-2 py-1.5 text-xs font-black text-center focus:ring-yellow-500 {{ $firmado ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }}">
                            </td>
                            <td class="border-r border-slate-300 p-2 bg-gray-100">
                                <input type="text" disabled placeholder="---" class="w-full border-none bg-transparent text-center text-xs">
                            </td>
                            <td class="border-slate-300 p-2 bg-gray-100">
                                <input type="text" disabled placeholder="---" class="w-full border-none bg-transparent text-center text-xs">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-slate-500 italic">No hay materias primas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 2. CONCILIACION DE MATERIAL DE ENVASE Y EMPAQUE -->
        <h2 class="text-lg font-black text-slate-800 mb-2 uppercase tracking-wide">2. Conciliación de Material de Envase y Empaque</h2>
        <div class="bg-white rounded border-2 border-slate-900 shadow-md overflow-hidden mb-8">
            <table class="w-full border-collapse text-sm text-center">
                <thead>
                    <tr class="bg-slate-200 text-slate-700 font-black text-xs">
                        <th class="border-b-2 border-r border-slate-400 p-3 w-28">FECHA</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 text-left">DESCRIPCIÓN</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-40">LOTE</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-20">UND</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-32 bg-yellow-50">RECIBIDO</th>
                        <th class="border-b-2 border-r border-slate-400 p-3 w-32">UTILIZADO</th>
                        <th class="border-b-2 border-slate-400 p-3 w-32">DEVUELTO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materialEmpaque as $me)
                        <tr class="hover:bg-slate-50 border-b border-slate-300">
                            <td class="border-r border-slate-300 p-2 text-xs font-bold text-slate-500">
                                {{ \Carbon\Carbon::now()->format('Y-m-d') }}
                            </td>
                            <td class="border-r border-slate-300 p-2 text-left font-bold text-slate-800 text-xs">
                                {{ $me->description }}
                            </td>
                            <td class="border-r border-slate-300 p-2">
                                <input type="text" name="items[{{ $me->id }}][lote]" value="{{ $me->lote }}" required {{ $firmado ? 'readonly' : '' }}
                                       class="w-full border border-gray-300 rounded px-2 py-1.5 text-[11px] font-black text-center uppercase focus:ring-aurofarma-blue {{ $firmado ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }}">
                            </td>
                            <td class="border-r border-slate-300 p-2 font-bold text-xs text-slate-600">
                                {{ $me->unit }}
                            </td>
                            <td class="border-r border-slate-300 p-2 bg-yellow-50/30">
                                <input type="number" step="0.01" name="items[{{ $me->id }}][received_qty]" value="{{ $me->received_qty }}" required {{ $firmado ? 'readonly' : '' }}
                                       class="w-full border border-yellow-300 rounded px-2 py-1.5 text-xs font-black text-center focus:ring-yellow-500 {{ $firmado ? 'bg-gray-100 cursor-not-allowed' : 'bg-white' }}">
                            </td>
                            <td class="border-r border-slate-300 p-2 bg-gray-100">
                                <input type="text" disabled placeholder="---" class="w-full border-none bg-transparent text-center text-xs">
                            </td>
                            <td class="border-slate-300 p-2 bg-gray-100">
                                <input type="text" disabled placeholder="---" class="w-full border-none bg-transparent text-center text-xs">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-slate-500 italic">No hay materiales de empaque registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- OBSERVACIONES -->
        <div class="bg-gray-100 border-2 border-gray-900 mb-6 shadow-md rounded">
            <div class="border-b border-gray-300 p-2 px-4 font-bold text-slate-700 uppercase tracking-widest text-xs">Observaciones</div>
            <textarea disabled rows="3" class="w-full border-none focus:ring-0 p-4 font-medium text-sm text-gray-500 bg-gray-100" placeholder="[Las observaciones se habilitarán al finalizar el lote]"></textarea>
        </div>
        
        <!-- FIRMAS -->
        <div class="grid grid-cols-2 bg-white border-2 border-gray-900 mb-6">
            <!-- REALIZADO POR -->
            <div class="p-6 border-r-2 border-gray-900 flex flex-col justify-between items-center text-center relative overflow-hidden bg-slate-50">
                <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: repeating-linear-gradient(45deg, #000 25%, transparent 25%, transparent 75%, #000 75%, #000), repeating-linear-gradient(45deg, #000 25%, #fff 25%, #fff 75%, #000 75%, #000); background-position: 0 0, 10px 10px; background-size: 20px 20px;"></div>
                
                <div class="flex-grow flex items-center justify-center w-full py-8 text-aurofarma-blue">
                    <x-cfr21-signature-flow 
                        :initialSigned="$firmado ? true : false"
                        :initialName="$userRealizo->name ?? ''"
                        :initialDate="$reconciliations->first() && $reconciliations->first()->signed_at ? \Carbon\Carbon::parse($reconciliations->first()->signed_at)->format('Y-m-d') : ''"
                        :initialHour="$reconciliations->first() && $reconciliations->first()->signed_at ? \Carbon\Carbon::parse($reconciliations->first()->signed_at)->format('H:i:s') : ''"
                        buttonText="FIRMAR Y CONTINUAR"
                        buttonClass="'hidden'"
                        onSignature="openSignatureModal"
                    />
                </div>
                <p class="mt-4 text-xs font-black text-slate-800 tracking-widest uppercase">Realizado Por</p>
                <p class="text-[10px] text-slate-500 uppercase mt-1">CFR 21 Parte 11 - Firma Electrónica</p>
            </div>
            
            <!-- VERIFICADO POR QA -->
            <div class="p-6 flex flex-col justify-between items-center text-center bg-white relative">
                <div class="flex-grow flex flex-col items-center justify-center w-full py-8 font-black text-xl italic border-b-2 border-dotted border-gray-400">
                    <span class="text-gray-300">ESPACIO PARA VERIFICADOR (AL CIERRE)</span>
                </div>
                <p class="mt-4 text-xs font-black text-slate-800 tracking-widest uppercase">Aprobó (Aseguramiento)</p>
            </div>
        </div>

        <!-- Acciones Finales -->
        <div class="flex justify-end mt-4 pb-12 space-x-4">
            @if(!$firmado)
                <button type="button" onclick="openSignatureModal()" class="px-8 py-4 bg-aurofarma-blue rounded shadow-xl text-white font-black hover:bg-blue-700 transition-all flex items-center tracking-widest">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    FIRMAR Y CONTINUAR A DISPENSACIÓN
                </button>
            @else
                <a href="{{ route('batch.despeje', $op) }}" class="px-8 py-4 bg-green-600 rounded shadow-xl text-white font-black hover:bg-green-700 transition-all flex items-center tracking-widest">
                    CONTINUAR A LA SIGUIENTE FASE
                    <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- MODAL DE FIRMA ELECTRÓNICA ENTERPRISE -->
<div id="signatureModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-900 opacity-80 backdrop-blur-sm"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border-t-4 border-aurofarma-blue">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-aurofarma-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04m14.506 0A11.955 11.955 0 0112 21.056a11.955 11.955 0 01-8.618-3.04m14.506 0a11.955 11.955 0 01-3.382 3.382m0-6.764L12 12m0 0l4 4m-4-4l-4 4" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-xl leading-6 font-black text-gray-900 uppercase tracking-tight" id="modal-title">Firma Electrónica Reforzada</h3>
                        <div class="mt-2 p-3 bg-blue-50 border-l-4 border-aurofarma-blue rounded shadow-sm">
                            <p class="text-xs font-bold text-blue-800 italic uppercase">CUMPLIMIENTO 21 CFR PART 11</p>
                            <p class="text-[11px] text-blue-700 mt-1">Usted está a punto de firmar un registro electrónico. Esta acción equivale legalmente a su firma manuscrita.</p>
                        </div>

                        <!-- Formulario de Firma -->
                        <div class="mt-6 space-y-5">
                            <!-- Responsable selector -->
                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Operario Responsable</label>
                                <select id="on_behalf_of_id_modal" class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 font-bold text-gray-800 focus:border-aurofarma-blue focus:ring-0 transition-colors">
                                    @foreach($operarios as $op_user)
                                        <option value="{{ $op_user->id }}" {{ Auth::id() == $op_user->id ? 'selected' : '' }}>{{ $op_user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Confirmar con su Contraseña</label>
                                <input type="password" id="password_modal" class="w-full border-2 border-gray-200 rounded-lg px-4 py-2.5 font-bold text-gray-800 focus:border-aurofarma-blue focus:ring-0 transition-colors" placeholder="••••••••">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse space-x-reverse space-x-3">
                <button type="button" onclick="submitSignature()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-lg px-6 py-2.5 bg-aurofarma-blue text-sm font-black text-white hover:bg-blue-700 focus:outline-none transition-all sm:ml-3 sm:w-auto uppercase tracking-tighter">
                    VALIDAR Y FIRMAR
                </button>
                <button type="button" onclick="closeSignatureModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border-2 border-gray-300 shadow-sm px-6 py-2.5 bg-white text-sm font-bold text-gray-700 hover:bg-gray-100 focus:outline-none sm:mt-0 sm:w-auto uppercase tracking-tighter">
                    CANCELAR
                </button>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    const currentUserId = {{ Auth::id() }};
    
    function openSignatureModal() {
        document.getElementById('signatureModal').classList.remove('hidden');
        document.getElementById('password_modal').focus();
    }

    function closeSignatureModal() {
        document.getElementById('signatureModal').classList.add('hidden');
    }


    function submitSignature() {
        const onBehalfOfId = document.getElementById('on_behalf_of_id_modal').value;
        const password = document.getElementById('password_modal').value;

        if (!password) {
            alert('Debe ingresar su contraseña para firmar.');
            return;
        }

        // Poblar campos ocultos y enviar form principal
        document.getElementById('final_on_behalf_of_id').value = onBehalfOfId;
        document.getElementById('final_password').value = password;
        
        document.getElementById('form-conciliacion').submit();
    }

    // Auto-save logic on input change to prevent data loss before signing
    const inputs = document.querySelectorAll('input[type="text"]:not([disabled]):not([readonly]), input[type="number"]:not([disabled]):not([readonly])');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            const formData = new FormData(document.getElementById('form-conciliacion'));
            
            fetch("{{ route('batch.conciliacion.store', $op) }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).catch(console.error);
        });
    });
</script>
@endpush
@endsection
