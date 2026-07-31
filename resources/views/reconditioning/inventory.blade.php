@extends('layouts.app')

@section('header_title', 'Inventario de Bodega - Reacondicionamiento')

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ 
    view: 'detailed', 
    showReleaseModal: false, 
    selectedItemId: null, 
    destination: 'PT', 
    rejectionReason: '', 
    showUploadModal: false, 
    showEditModal: false, 
    editableItem: {},
    showCompleteModal: false,
    usedLabels: 0,
    usedBoxes: 0,
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
    
    <!-- Header & Toggle -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-[#0A2540] flex items-center">
            <i class="fas fa-boxes text-aurofarma-blue mr-3"></i>
            Inventario General
        </h2>
        
        <div class="mt-4 md:mt-0 flex bg-white rounded shadow-sm border border-gray-200 p-1">
            <button @click="view = 'compact'" :class="{'bg-[#0A2540] text-white': view === 'compact', 'text-gray-500 hover:bg-gray-100': view !== 'compact'}" class="px-4 py-2 rounded text-sm font-medium transition-colors">
                <i class="fas fa-layer-group mr-2"></i> Vista Compacta
            </button>
            <button @click="view = 'detailed'" :class="{'bg-[#0A2540] text-white': view === 'detailed', 'text-gray-500 hover:bg-gray-100': view !== 'detailed'}" class="px-4 py-2 rounded text-sm font-medium transition-colors">
                <i class="fas fa-list-ul mr-2"></i> Vista por Traslado
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('reconditioning.inventory') }}" method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
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
                    <a href="{{ route('reconditioning.inventory') }}" class="w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded transition-colors text-sm ml-2 inline-block text-center mt-2 md:mt-0">
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

    <!-- Compact View -->
    <div x-show="view === 'compact'" class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden" x-transition>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#0A2540] text-white uppercase text-xs tracking-wider">
                    <th class="p-4 font-semibold border-b border-gray-700">Código</th>
                    <th class="p-4 font-semibold border-b border-gray-700">Descripción</th>
                    <th class="p-4 font-semibold border-b border-gray-700">Lote</th>
                    <th class="p-4 font-semibold border-b border-gray-700 text-right">Cantidad Total</th>
                    <th class="p-4 font-semibold border-b border-gray-700 text-center">Registros (Estibas)</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-200">
                @forelse($compactGroups as $group)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="p-4 font-medium text-gray-900">{{ $group['item_code'] }}</td>
                    <td class="p-4 text-gray-700">{{ $group['description'] }}</td>
                    <td class="p-4 font-mono text-gray-600">{{ $group['lot_number'] }}</td>
                    <td class="p-4 text-right font-bold text-[#0A2540]">
                        {{ number_format($group['total_quantity'], 2) }} <span class="text-xs text-gray-500 font-normal">{{ $group['uom'] }}</span>
                    </td>
                    <td class="p-4 text-center">
                        <span class="inline-block bg-blue-100 text-aurofarma-blue text-xs font-bold px-2 py-1 rounded-full">
                            {{ $group['records'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-gray-500">No hay productos en reacondicionamiento.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Detailed View (Grouped by Transfer) -->
    <div x-show="view === 'detailed'" class="space-y-8" x-transition>
        @php $groupedItems = $items->groupBy('transfer_number'); @endphp
        
        @forelse($groupedItems as $transferNumber => $itemsInTransfer)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <span class="bg-[#0A2540] text-white text-[10px] font-bold px-2 py-0.5 rounded mr-3 uppercase tracking-tighter">TRASLADO</span>
                        <h3 class="font-bold text-[#0A2540] text-sm">{{ $transferNumber }}</h3>
                    </div>
                    <button @click="selectedItemId = {{ $itemsInTransfer->first()->id }}; showUploadModal = true" 
                            class="text-xs font-bold text-orange-600 hover:text-orange-800 flex items-center transition-colors">
                        <i class="fas fa-cloud-upload-alt mr-1.5"></i> 
                        {{ $itemsInTransfer->first()->transfer_pdf_path ? 'Actualizar PDF' : 'Subir PDF' }}
                    </button>
                </div>
                @if($itemsInTransfer->first()->transfer_pdf_path)
                    <a href="{{ Storage::url($itemsInTransfer->first()->transfer_pdf_path) }}" target="_blank" class="text-xs font-bold text-aurofarma-blue hover:underline flex items-center">
                        <i class="fas fa-file-pdf mr-1.5"></i> Ver Soporte Original
                    </a>
                @endif
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-max">
                    <thead>
                        <tr class="bg-white text-gray-500 uppercase text-[10px] font-bold tracking-wider border-b">
                            <th class="p-3">Producto / Descripción</th>
                            <th class="p-3">Lote</th>
                            <th class="p-3 text-right">Cantidad</th>
                            <th class="p-3">Ubicación</th>
                            <th class="p-3">Ingreso</th>
                            <th class="p-3">Riesgo</th>
                            <th class="p-3 text-center">Estado</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-200">
                        @foreach($itemsInTransfer as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3">
                                <div class="font-bold text-gray-900">{{ $item->item_code }}</div>
                                <div class="text-[10px] text-gray-500 truncate w-48">{{ $item->item ? $item->item->description : 'N/A' }}</div>
                            </td>
                            <td class="p-3 font-mono text-gray-600">{{ $item->lot_number }}</td>
                            <td class="p-3 text-right font-bold text-[#0A2540]">
                                {{ number_format($item->quantity, 2) }} <span class="text-xs text-gray-500 font-normal">{{ $item->uom }}</span>
                            </td>
                            <td class="p-3 text-gray-600">{{ $item->location ?: '--' }}</td>
                            <td class="p-3 text-gray-600 text-xs">{{ $item->created_at ? $item->created_at->format('Y-m-d') : '--' }}</td>
                            <td class="p-3">
                                @if($item->risk_level === 1)
                                    <span class="inline-block w-3 h-3 rounded-full bg-red-600 shadow" title="Riesgo Crítico"></span>
                                @elseif($item->risk_level === 2)
                                    <span class="inline-block w-3 h-3 rounded-full bg-yellow-500 shadow" title="Riesgo Medio"></span>
                                @else
                                    <span class="inline-block w-3 h-3 rounded-full bg-green-500 shadow" title="Riesgo Normal"></span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($item->status == 'Terminado')
                                    <span class="text-[10px] font-bold text-green-700 bg-green-100 px-2 py-1 rounded">Terminado</span>
                                @elseif($item->status == 'En Proceso')
                                    <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-1 rounded">En Proceso</span>
                                @else
                                    <span class="text-[10px] font-bold text-gray-700 bg-gray-200 px-2 py-1 rounded">Pendiente</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center space-x-3">
                                    @if($item->transfer_pdf_path)
                                        <a href="{{ Storage::url($item->transfer_pdf_path) }}" target="_blank" class="text-green-600 hover:text-green-800" title="Ver PDF">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                    @else
                                        <button @click="selectedItemId = {{ $item->id }}; showUploadModal = true" class="text-orange-500 hover:text-orange-700" title="Subir PDF">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </button>
                                    @endif

                                    <button @click="selectedItemId = {{ $item->id }}; editableItem = { lot_number: '{{ $item->lot_number }}', manufacturer: '{{ $item->manufacturer }}', location: '{{ $item->location }}', req_label: {{ $item->req_label ?: 0 }}, req_box: {{ $item->req_box ?: 0 }}, req_others: '{{ $item->req_others }}' }; showEditModal = true" class="text-blue-500 hover:text-blue-700 transition-colors" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    @if($item->status != 'Terminado')
                                        <button @click="openFinishModal({{ $item }})" class="text-[#0A2540] hover:text-slate-800 transition-colors" title="Terminar">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    @else
                                        <button @click="selectedItemId = {{ $item->id }}; 
                                                        maxQty = {{ $item->quantity }}; 
                                                        quantityToRelease = {{ $item->quantity }};
                                                        selectedItemReqs = { label: {{ $item->req_label ?: 0 }}, box: {{ $item->req_box ?: 0 }}, others: '{{ $item->req_others }}' };
                                                        destination = 'PT'; rejectionReason = ''; showReleaseModal = true" 
                                                class="text-green-600 hover:text-green-800 transition-colors" title="Generar Salida">
                                            <i class="fas fa-share-square"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white p-10 text-center rounded border border-gray-200 shadow-sm">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No hay registros para mostrar.</p>
        </div>
        @endforelse
    </div>

    <!-- Upload PDF Modal -->
    <div x-show="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showUploadModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
            <div x-show="showUploadModal" x-transition.scale class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-md w-full z-50">
                <form :action="`/reacondicionamiento/${selectedItemId}/upload-transfer`" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6 border-b border-gray-200 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 mb-4">
                            <i class="fas fa-file-pdf text-orange-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Adjuntar Soporte de Traslado (Siesa)</h3>
                        <p class="text-xs text-gray-500 mt-2">Seleccione el archivo PDF oficial generado por Siesa para este traslado.</p>
                        
                        <div class="mt-6">
                            <input type="file" name="transfer_pdf" accept="application/pdf" required
                                class="w-full border border-dashed border-gray-300 rounded-lg p-4 text-sm text-gray-600 hover:bg-gray-50 transition-colors bg-white">
                        </div>

                        <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-100 text-left">
                            <label class="flex items-start cursor-pointer">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="apply_to_all" value="1" checked
                                        class="h-4 w-4 text-aurofarma-blue border-gray-300 rounded focus:ring-aurofarma-blue">
                                </div>
                                <div class="ml-3 text-xs">
                                    <span class="font-bold text-[#0A2540]">Sincronización Multiproducto</span>
                                    <p class="text-blue-700 mt-0.5">¿Deseas aplicar este PDF a todos los productos que comparten este mismo número de traslado?</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex flex-row-reverse space-x-reverse space-x-3">
                        <button type="submit" class="bg-[#0A2540] hover:bg-slate-800 text-white font-bold py-2 px-6 rounded text-sm shadow-md transition-all">Subir Archivo</button>
                        <button type="button" @click="showUploadModal = false" class="text-gray-700 font-medium py-2 px-6 rounded text-sm hover:bg-gray-100">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Complete Modal -->
    <div x-show="showCompleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCompleteModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
            <div x-show="showCompleteModal" x-transition.scale class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg w-full z-50">
                <form :action="`/reacondicionamiento/${selectedItemId}/completar`" method="POST">
                    @csrf
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="fas fa-box-open text-green-600 mr-3"></i> Cierre de Reacondicionamiento
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Informe el consumo real de materiales para este lote.</p>
                        
                        <div class="mt-6 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Etiquetas Utilizadas</label>
                                <input type="number" name="used_labels" x-model="usedLabels" min="0" class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:ring-aurofarma-blue">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Plegadizas Utilizadas</label>
                                <input type="number" name="used_boxes" x-model="usedBoxes" min="0" class="w-full border-gray-300 rounded px-3 py-2 text-sm focus:ring-aurofarma-blue">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex flex-row-reverse space-x-reverse space-x-3">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded text-sm shadow-sm">Confirmar y Cerrar</button>
                        <button type="button" @click="showCompleteModal = false" class="text-gray-700 font-medium py-2 px-6 rounded text-sm hover:bg-gray-100">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Release Modal -->
    <div x-show="showReleaseModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showReleaseModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
            <div x-show="showReleaseModal" x-transition.scale class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg w-full z-50">
                    <div x-data="{ 
                        handleReleaseVerified(detail) {
                            // Sincronizar token
                            axios.defaults.headers.common['X-CSRF-TOKEN'] = detail.new_token;
                            document.querySelector('meta[name=csrf-token]').content = detail.new_token;
                            
                            // Añadir campos de firma al form
                            let form = this.$refs.releaseForm;
                            
                            let userField = document.createElement('input');
                            userField.type = 'hidden'; userField.name = 'signature_user_id'; userField.value = detail.user_id;
                            form.appendChild(userField);
                            
                            let nameField = document.createElement('input');
                            nameField.type = 'hidden'; nameField.name = 'signature_user_name'; nameField.value = detail.user_name;
                            form.appendChild(nameField);

                            // Enviar form
                            form.submit();
                        }
                    }">
                        <form :action="`/reacondicionamiento/${selectedItemId}/salida`" method="POST" enctype="multipart/form-data" x-ref="releaseForm">
                            @csrf
                            <div class="p-6 border-b border-gray-200 max-h-[80vh] overflow-y-auto">
                                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                    <i class="fas fa-truck-loading text-aurofarma-blue mr-3"></i> Liberación y Salida
                                </h3>
                                
                                <div class="mt-6">
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cantidad a Liberar *</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" step="0.01" name="quantity_to_release" x-model="quantityToRelease" :max="maxQty" min="0.01" required
                                            class="w-full border-gray-300 rounded px-3 py-2 text-lg font-bold focus:ring-aurofarma-blue">
                                        <span class="text-gray-400 font-bold">/ <span x-text="maxQty"></span></span>
                                    </div>
                                    <template x-if="quantityToRelease < maxQty">
                                        <p class="text-[10px] text-orange-600 font-bold mt-1 uppercase tracking-tight">
                                            <i class="fas fa-info-circle"></i> Salida PARCIAL: El saldo restante se mantendrá en inventario.
                                        </p>
                                    </template>
                                </div>

                                <div class="mt-6">
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Bodega de Destino *</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex flex-col items-center p-3 border rounded cursor-pointer transition-all" :class="{'border-green-500 bg-green-50': destination === 'PT', 'border-gray-200': destination !== 'PT'}">
                                            <input type="radio" name="destination_warehouse" value="PT" x-model="destination" class="sr-only">
                                            <i class="fas fa-check-circle mb-1" :class="destination === 'PT' ? 'text-green-600' : 'text-gray-200'"></i>
                                            <span class="text-[10px] font-bold uppercase">PRODUCTO TERMINADO</span>
                                        </label>
                                        <label class="flex flex-col items-center p-3 border rounded cursor-pointer transition-all" :class="{'border-red-500 bg-red-50': destination === 'RZ', 'border-gray-200': destination !== 'RZ'}">
                                            <input type="radio" name="destination_warehouse" value="RZ" x-model="destination" class="sr-only">
                                            <i class="fas fa-times-circle mb-1" :class="destination === 'RZ' ? 'text-red-600' : 'text-gray-200'"></i>
                                            <span class="text-[10px] font-bold uppercase">RECHAZO / RZ</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-6 bg-gray-50 p-4 rounded border">
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Traslado Salida (Siesa) *</label>
                                    <input type="text" name="exit_transfer_number" required class="w-full border-gray-300 rounded text-sm mb-3">
                                    
                                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Documento PDF <span class="font-normal lowercase">(opcional)</span></label>
                                    <input type="file" name="exit_transfer_pdf" accept="application/pdf" class="w-full text-[10px]">
                                </div>

                                <div x-show="destination === 'RZ'" class="mt-6 p-4 bg-red-50 border border-red-100 rounded">
                                    <label class="block text-xs font-bold text-red-800 uppercase mb-1">Motivo Rechazo *</label>
                                    <textarea name="rejection_reason" x-model="rejectionReason" :required="destination === 'RZ'" class="w-full border-red-200 rounded text-sm mb-3" rows="2"></textarea>
                                    <label class="block text-xs font-bold text-red-800 uppercase mb-1">Evidencia Fotográfica *</label>
                                    <input type="file" name="rejection_photo" accept="image/*" :required="destination === 'RZ'" class="w-full text-[10px]">
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse space-x-reverse space-x-3 border-t">
                                <x-cfr21-signature-flow 
                                    module="REACONDICIONAMIENTO" 
                                    action="Liberación de Producto" 
                                    role="ANALISTA DE PRODUCCION"
                                    buttonText="Confirmar y Generar Acta"
                                    buttonClass="'bg-[#0A2540] hover:bg-slate-800 text-white font-bold py-2.5 px-8 rounded text-sm shadow-md transition-all'"
                                    @signature-verified="handleReleaseVerified($event.detail)"
                                />
                                <button type="button" @click="showReleaseModal = false" class="text-gray-600 font-medium py-2.5 px-6 rounded text-sm hover:bg-gray-100">Cancelar</button>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showEditModal" x-transition.opacity class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
            <div x-show="showEditModal" x-transition.scale class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-2xl w-full z-50">
                <form :action="`/reacondicionamiento/${selectedItemId}/editar`" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="fas fa-edit text-blue-600 mr-3"></i> Editar Datos del Registro
                        </h3>
                        
                        <div class="mt-6 grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Número de Lote *</label>
                                <input type="text" name="lot_number" x-model="editableItem.lot_number" required class="w-full border-gray-300 rounded px-3 py-2 text-sm font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fabricante</label>
                                <input type="text" name="manufacturer" x-model="editableItem.manufacturer" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ubicación Física</label>
                                <input type="text" name="location" x-model="editableItem.location" class="w-full border-gray-300 rounded px-3 py-2 text-sm uppercase">
                            </div>
                        </div>

                        <div class="mt-8 border-t pt-4">
                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-4 tracking-wider">Requerimientos de Insumos</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Etiquetas Req.</label>
                                    <input type="number" name="req_label" x-model="editableItem.req_label" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Plegadizas Req.</label>
                                    <input type="number" name="req_box" x-model="editableItem.req_box" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Otros Requerimientos / Notas</label>
                                    <input type="text" name="req_others" x-model="editableItem.req_others" class="w-full border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse space-x-reverse space-x-3">
                        <button type="submit" class="bg-[#0A2540] hover:bg-slate-800 text-white font-bold py-2.5 px-8 rounded text-sm shadow-md">Guardar Cambios</button>
                        <button type="button" @click="showEditModal = false" class="text-gray-600 font-medium py-2.5 px-6 rounded text-sm hover:bg-gray-100">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
