@extends('layouts.app')

@section('header_title', 'Entrada Multiproducto - Reacondicionamiento')

@section('content')
<div class="max-w-7xl mx-auto" x-data="reconditioningForm()">
    <div class="bg-white rounded shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-lg font-bold text-[#0A2540] uppercase tracking-wide">
                <i class="fas fa-boxes-stacked mr-2 text-aurofarma-blue"></i> Registro de Ingreso Masivo
            </h2>
            <div class="text-xs font-semibold text-gray-500">AUROTRACE V2</div>
        </div>

        <form action="{{ route('reconditioning.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <!-- Cabecera de Traslado (Única) -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-100 mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-[#0A2540] mb-1">Número de Traslado Siesa (Origen) *</label>
                    <input type="text" name="transfer_number" x-model="transferNumber" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm uppercase focus:ring-aurofarma-blue focus:border-aurofarma-blue transition-colors font-mono font-bold">
                    <p class="text-[10px] text-blue-600 mt-1 uppercase font-semibold">Este número se aplicará a todos los ítems de esta sesión.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-[#0A2540] mb-1">Documento de Soporte (PDF) <span class="text-xs font-normal text-gray-400">(Opcional)</span></label>
                    <input type="file" name="transfer_pdf" accept="application/pdf"
                        class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-aurofarma-blue hover:file:bg-blue-200 transition-colors bg-white">
                </div>
            </div>

            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 text-red-700 text-sm font-bold">
                    <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                </div>
            @endif

            <!-- Listado de Ítems -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-md font-bold text-gray-800 uppercase flex items-center">
                    <i class="fas fa-list-ul mr-2 text-gray-400"></i> Detalle de Productos
                </h3>
                <button type="button" @click="addItem()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-xs font-bold transition-all shadow-sm flex items-center">
                    <i class="fas fa-plus mr-2"></i> Añadir Producto
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-200 min-w-max">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] uppercase text-gray-600 tracking-wider">
                            <th class="border border-gray-200 p-2 text-left w-64">Ítem / Descripción</th>
                            <th class="border border-gray-200 p-2 text-left w-32">Lote</th>
                            <th class="border border-gray-200 p-2 text-left w-32">Vencimiento</th>
                            <th class="border border-gray-200 p-2 text-left w-24">Cant.</th>
                            <th class="border border-gray-200 p-2 text-left w-24">UOM</th>
                            <th class="border border-gray-200 p-2 text-left w-24">Origen</th>
                            <th class="border border-gray-200 p-2 text-left w-32">Ubicación</th>
                            <th class="border border-gray-200 p-2 text-left">Mat. Requeridos</th>
                            <th class="border border-gray-200 p-2 text-center w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-gray-50 transition-colors align-top" :class="item.riskColor">
                                <td class="border border-gray-200 p-2">
                                    <input type="text" :name="`items[${index}][item_code]`" x-model="item.item_code" 
                                        @input.debounce.500ms="fetchItem(index)" required placeholder="CÓDIGO"
                                        class="w-full border-gray-300 rounded px-2 py-1 text-xs uppercase focus:ring-aurofarma-blue mb-1">
                                    <div class="text-[10px] text-gray-500 font-semibold leading-tight h-8 overflow-hidden" x-text="item.description"></div>
                                    <input type="hidden" :name="`items[${index}][description]`" :value="item.description">
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <input type="text" :name="`items[${index}][lot_number]`" x-model="item.lot_number" required
                                        class="w-full border-gray-300 rounded px-2 py-1 text-xs uppercase focus:ring-aurofarma-blue font-mono">
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <input type="date" :name="`items[${index}][expiration_date]`" x-model="item.expiration_date" @change="calculateRisk(index)" required
                                        class="w-full border-gray-300 rounded px-2 py-1 text-xs focus:ring-aurofarma-blue">
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <input type="number" step="0.01" :name="`items[${index}][quantity]`" x-model="item.quantity" required
                                        class="w-full border-gray-300 rounded px-2 py-1 text-xs font-bold">
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <select :name="`items[${index}][uom]`" x-model="item.uom" class="w-full border-gray-300 rounded px-1 py-1 text-xs focus:ring-aurofarma-blue">
                                        <option value="UND">UND</option>
                                        <option value="KIL">KIL</option>
                                    </select>
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <div class="flex items-center space-x-1">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" :name="`items[${index}][is_external]`" value="1" x-model="item.is_external" @change="calculateRisk(index)" class="sr-only peer">
                                            <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-red-600"></div>
                                        </label>
                                        <span class="text-[9px] font-bold" :class="item.is_external ? 'text-red-600' : 'text-blue-600'" x-text="item.is_external ? 'TERCERO' : 'AUROF.'"></span>
                                    </div>
                                    <input type="text" :name="`items[${index}][manufacturer]`" x-model="item.manufacturer" placeholder="Fabricante"
                                        class="w-full border-gray-300 rounded px-1 py-0.5 text-[9px] mt-1">
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <input type="text" :name="`items[${index}][location]`" x-model="item.location" placeholder="ESTIBA/RACK"
                                        class="w-full border-gray-300 rounded px-2 py-1 text-[10px] uppercase focus:ring-aurofarma-blue">
                                </td>
                                <td class="border border-gray-200 p-2">
                                    <div class="grid grid-cols-2 gap-1 mb-1">
                                        <div class="flex flex-col">
                                            <span class="text-[8px] font-bold text-gray-500">ETIQUETAS</span>
                                            <input type="number" :name="`items[${index}][req_label]`" x-model="item.req_label" min="0" 
                                                class="border border-gray-300 rounded px-1 py-0.5 text-[10px] w-full">
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[8px] font-bold text-gray-500">PLEGADIZAS</span>
                                            <input type="number" :name="`items[${index}][req_box]`" x-model="item.req_box" min="0"
                                                class="border border-gray-300 rounded px-1 py-0.5 text-[10px] w-full">
                                        </div>
                                    </div>
                                    <input type="text" :name="`items[${index}][req_others]`" x-model="item.req_others" placeholder="OTROS (EJ: 500 INSERTOS)"
                                        class="w-full border border-gray-300 rounded px-1 py-0.5 text-[9px]">
                                </td>
                                <td class="border border-gray-200 p-2 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-between items-center pt-8 mt-8 border-t border-gray-200">
                <div class="text-xs text-gray-500 italic">
                    <i class="fas fa-info-circle mr-1 text-blue-500"></i> Los colores en las filas indican el nivel de riesgo FEFO/Origen.
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('reconditioning.inventory') }}" class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded hover:bg-gray-50 transition-colors">Cancelar</a>
                    <button type="submit" class="bg-[#0A2540] hover:bg-slate-800 px-10 py-2.5 text-white font-bold rounded transition-all shadow-md flex items-center text-sm">
                        <i class="fas fa-save mr-2"></i> Procesar Ingreso Multiproducto
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reconditioningForm', () => ({
            transferNumber: '',
            items: [],

            init() {
                this.addItem(); // Empezar con una fila
            },

            addItem() {
                this.items.push({
                    item_code: '',
                    description: 'Busque código...',
                    lot_number: '',
                    expiration_date: '',
                    quantity: 0,
                    uom: 'UND',
                    is_external: false,
                    manufacturer: 'AUROFARMA S.A.S.',
                    location: '',
                    req_label: 0,
                    req_box: 0,
                    req_others: '',
                    riskColor: '',
                    loading: false
                });
            },

            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                } else {
                    alert('Debe haber al menos un producto en el ingreso.');
                }
            },

            async fetchItem(index) {
                let code = this.items[index].item_code;
                if (code.length < 3) return;

                this.items[index].loading = true;
                try {
                    const response = await axios.get(`/api/items/${code}`);
                    if (response.data && response.data.name) {
                        this.items[index].description = response.data.name;
                    } else {
                        this.items[index].description = 'PRODUCTO NO ENCONTRADO';
                    }
                } catch (error) {
                    this.items[index].description = 'ERROR EN BÚSQUEDA';
                } finally {
                    this.items[index].loading = false;
                }
            },

            calculateRisk(index) {
                let item = this.items[index];
                
                // Si es Externo (Tercero) -> Rojo Crítico (Nivel 1)
                if (item.is_external) {
                    item.riskColor = 'bg-red-50';
                    return;
                }

                // Si es Aurofarma, calcular por fecha
                if (item.expiration_date) {
                    let exp = new Date(item.expiration_date);
                    let now = new Date();
                    let diffMonths = (exp.getFullYear() - now.getFullYear()) * 12 + (exp.getMonth() - now.getMonth());

                    if (diffMonths < 3) {
                        item.riskColor = 'bg-red-50'; // Crítico
                    } else if (diffMonths >= 3 && diffMonths <= 6) {
                        item.riskColor = 'bg-yellow-50'; // Medio
                    } else {
                        item.riskColor = 'bg-green-50'; // Normal
                    }
                } else {
                    item.riskColor = '';
                }
            }
        }));
    });
</script>
<style>
    .bg-red-50 { background-color: #fef2f2 !important; }
    .bg-yellow-50 { background-color: #fefce8 !important; }
    .bg-green-50 { background-color: #f0fdf4 !important; }
</style>
@endpush
