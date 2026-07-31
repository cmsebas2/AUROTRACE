@extends('layouts.app')

@section('header_title', 'Planificador Semanal - Reacondicionamiento')

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ 
    showCompleteModal: false, 
    selectedItemId: null, 
    usedLabels: 0, 
    usedBoxes: 0,
    showReleaseModal: false,
    destination: 'PT',
    rejectionReason: '',
    quantityToRelease: 0,
    maxQty: 0,
    selectedItemReqs: { label: 0, box: 0, others: '' },
    openFinishModal(item) {
        this.selectedItemId = item.id;
        this.usedLabels = item.req_label || 0;
        this.usedBoxes = item.req_box || 0;
        this.showCompleteModal = true;
    }
}">
    
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-[#0A2540] flex items-center">
            <i class="fas fa-tasks text-aurofarma-blue mr-3"></i>
            Lista de Prioridades (Sábado)
        </h2>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('reconditioning.planner') }}" method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Buscar (Código, Lote, Traslado)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded focus:ring-aurofarma-blue focus:border-aurofarma-blue text-sm" placeholder="Ej. Q-MUTIN, Lote...">
                </div>
            </div>
            <div class="w-full md:w-64">
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nivel de Riesgo</label>
                <select name="risk" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-aurofarma-blue focus:border-aurofarma-blue text-sm">
                    <option value="">Todos los Riesgos</option>
                    <option value="1" {{ request('risk') == '1' ? 'selected' : '' }}>Nivel 1 (Crítico)</option>
                    <option value="2" {{ request('risk') == '2' ? 'selected' : '' }}>Nivel 2 (Medio)</option>
                    <option value="3" {{ request('risk') == '3' ? 'selected' : '' }}>Nivel 3 (Normal)</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto bg-[#0A2540] hover:bg-slate-800 text-white font-medium py-2 px-6 rounded transition-colors text-sm">
                    Filtrar
                </button>
                @if(request()->has('search') || request()->has('risk'))
                    <a href="{{ route('reconditioning.planner') }}" class="w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded transition-colors text-sm ml-2 inline-block text-center mt-2 md:mt-0">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 text-green-700 text-sm font-medium">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($items as $item)
        <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden flex flex-col md:flex-row relative">
            <!-- Risk Indicator Stripe -->
            <div class="w-full md:w-2
                @if($item->risk_level === 1) bg-red-600
                @elseif($item->risk_level === 2) bg-yellow-500
                @else bg-green-500
                @endif
            "></div>

            <div class="p-6 flex-1 flex flex-col md:flex-row items-start md:items-center justify-between">
                
                <div class="mb-4 md:mb-0 flex-1">
                    <div class="flex items-center mb-1">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full mr-2
                            @if($item->risk_level === 1) bg-red-100 text-red-800
                            @elseif($item->risk_level === 2) bg-yellow-100 text-yellow-800
                            @else bg-green-100 text-green-800
                            @endif
                        ">
                            Nivel {{ $item->risk_level }}
                        </span>
                        <span class="text-xs text-gray-500 font-mono">Traslado: {{ $item->transfer_number }}</span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ $item->item_code }}</h3>
                    <p class="text-sm text-gray-600">{{ $item->item ? $item->item->description : 'N/A' }}</p>
                    
                    <div class="mt-3 flex flex-wrap gap-4 text-sm">
                        <div class="text-gray-700 font-medium">
                            <i class="fas fa-cube text-gray-400 w-4"></i> {{ number_format($item->quantity, 2) }} {{ $item->uom }}
                        </div>
                        <div class="text-gray-700">
                            <i class="fas fa-barcode text-gray-400 w-4"></i> Lote: <span class="font-mono">{{ $item->lot_number }}</span>
                        </div>
                        <div class="text-gray-700">
                            <i class="far fa-calendar-alt text-gray-400 w-4"></i> Vence: {{ $item->expiration_date->format('d/m/Y') }}
                        </div>
                    </div>
                </div>

                <div class="md:pl-6 md:border-l border-gray-100 flex flex-col space-y-3 min-w-[200px]">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase mb-1">Requerimientos</p>
                        <div class="flex flex-wrap gap-1">
                            @if($item->req_label > 0)<span class="text-[10px] bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded">{{ $item->req_label }} Etiquetas</span>@endif
                            @if($item->req_box > 0)<span class="text-[10px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">{{ $item->req_box }} Plegadizas</span>@endif
                            @if(!$item->req_label && !$item->req_box && !$item->req_others)
                                <span class="text-[10px] text-gray-400">Sin req. específicos</span>
                            @endif
                        </div>
                    </div>
                    @if($item->status == 'Terminado')
                        <button 
                            @click="selectedItemId = {{ $item->id }}; 
                                    maxQty = {{ $item->quantity }}; 
                                    quantityToRelease = {{ $item->quantity }};
                                    selectedItemReqs = { label: {{ $item->req_label ?: 0 }}, box: {{ $item->req_box ?: 0 }}, others: '{{ $item->req_others }}' };
                                    destination = 'PT'; rejectionReason = ''; showReleaseModal = true" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-2 px-4 rounded transition-colors shadow-sm flex items-center justify-center">
                            <i class="fas fa-share-square mr-2"></i> Generar Salida
                        </button>
                    @else
                        <button 
                            @click="openFinishModal({{ $item }})"
                            class="w-full bg-[#0A2540] hover:bg-slate-800 text-white text-sm font-bold py-2 px-4 rounded transition-colors shadow-sm flex items-center justify-center">
                            <i class="fas fa-check mr-2"></i> Marcar Terminado
                        </button>
                    @endif
                </div>

            </div>
        </div>
        @empty
        <div class="bg-white p-10 text-center rounded border border-gray-200">
            <i class="fas fa-clipboard-check text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">Todo al día</h3>
            <p class="text-gray-500">No hay tareas pendientes en reacondicionamiento.</p>
        </div>
        @endforelse
    </div>

    <!-- Modal Cierre de Proceso -->
    <div x-show="showCompleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="showCompleteModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showCompleteModal" x-transition.scale class="inline-block align-bottom bg-white rounded text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="`/reacondicionamiento/${selectedItemId}/completar`" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-box-open text-green-600 text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                    Cerrar Proceso de Reacondicionamiento
                                </h3>
                                <div class="mt-2 text-sm text-gray-500">
                                    <p>Ingrese la cantidad de insumos consumidos para actualizar el Kardex. Si no utilizó alguno, déjelo en blanco o en cero.</p>
                                </div>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Etiquetas Utilizadas</label>
                                        <input type="number" name="used_labels" x-model="usedLabels" min="0"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-aurofarma-blue focus:border-aurofarma-blue transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Plegadizas Utilizadas</label>
                                        <input type="number" name="used_boxes" x-model="usedBoxes" min="0"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-aurofarma-blue focus:border-aurofarma-blue transition-colors">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirmar y Terminar
                        </button>
                        <button type="button" @click="showCompleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Release Modal (Unificado) -->
    <div x-show="showReleaseModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showReleaseModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showReleaseModal" x-transition.scale class="inline-block align-bottom bg-white rounded text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="`/reacondicionamiento/${selectedItemId}/salida`" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-truck-loading text-aurofarma-blue"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900">Generar Salida de Reacondicionamiento</h3>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Cantidad a Liberar *</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" step="0.01" name="quantity_to_release" x-model="quantityToRelease" :max="maxQty" min="0.01" required
                                            class="w-full border-gray-300 rounded shadow-sm focus:border-aurofarma-blue focus:ring focus:ring-blue-200 text-lg font-bold">
                                        <span class="text-gray-500 font-medium">/ <span x-text="maxQty"></span></span>
                                    </div>
                                    <p x-show="quantityToRelease < maxQty" class="text-xs text-orange-600 font-bold mt-1">
                                        <i class="fas fa-info-circle"></i> Se generará una salida PARCIAL.
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bodega de Destino *</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center p-3 border rounded cursor-pointer transition-colors" :class="{'border-green-500 bg-green-50': destination === 'PT'}">
                                            <input type="radio" name="destination_warehouse" value="PT" x-model="destination" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                            <span class="ml-3 font-medium text-gray-900 text-sm">Bodega PT (Producto Terminado Apto)</span>
                                        </label>
                                        <label class="flex items-center p-3 border rounded cursor-pointer transition-colors" :class="{'border-red-500 bg-red-50': destination === 'RZ'}">
                                            <input type="radio" name="destination_warehouse" value="RZ" x-model="destination" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                                            <span class="ml-3 font-medium text-gray-900 text-sm">Bodega RZ (Rechazo / Cuarentena)</span>
                                        </label>
                                    </div>
                                </div>

                                <div x-show="selectedItemReqs.label == 0 && selectedItemReqs.box == 0 && selectedItemReqs.others" 
                                     class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded text-amber-800 text-[10px]">
                                    <p class="font-bold mb-1"><i class="fas fa-exclamation-circle"></i> RECORDATORIO DE MATERIALES:</p>
                                    <p>Se indicó: <span class="font-bold" x-text="selectedItemReqs.others"></span>.</p>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Actividad Realizada / Otros Materiales</label>
                                    <textarea name="activity_performed" rows="2" class="w-full border-gray-300 rounded shadow-sm text-xs" placeholder="Ej. Re-etiquetado manual..."></textarea>
                                </div>

                                <div class="mt-4 bg-gray-50 p-3 rounded border border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número Traslado Salida (Siesa) *</label>
                                    <input type="text" name="exit_transfer_number" required class="w-full border-gray-300 rounded shadow-sm text-sm mb-2">
                                    
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PDF del Traslado <span class="text-[10px] text-gray-400 font-normal">(Opcional: puedes subirlo después)</span></label>
                                    <input type="file" name="exit_transfer_pdf" accept="application/pdf" class="w-full text-xs">
                                </div>
                                
                                <div x-show="destination === 'RZ'" class="mt-4 bg-red-50 p-3 rounded border border-red-200">
                                    <label class="block text-sm font-medium text-red-800 mb-1">Motivo de Rechazo *</label>
                                    <textarea name="rejection_reason" x-model="rejectionReason" :required="destination === 'RZ'" class="w-full border-red-300 rounded text-sm mb-2" rows="2"></textarea>
                                    <input type="file" name="rejection_photo" accept="image/*" :required="destination === 'RZ'" class="w-full text-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Confirmar Salida</button>
                        <button type="button" @click="showReleaseModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
