@extends('layouts.app')

@section('header_title', 'Crear Nuevo Producto')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        @if ($errors->any())
            <div class="mb-4 bg-red-50 p-4 rounded-lg border border-red-200">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Hay errores con tu solicitud:</h3>
                        <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('productos.store') }}" method="POST" id="product_form" enctype="multipart/form-data">
            @csrf

            <!-- SECCIÓN A: DATOS GENERALES -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-6 border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-aurofarma-blue flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        DATOS GENERALES DEL PRODUCTO
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                        
                        <div class="lg:col-span-3">
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Nombre Base del Producto <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" required class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Ej. Q-MUTIN MAX">
                        </div>

                        <!-- Registro ICA, Fórmula Maestra y Vigencia -->
                        <div class="lg:col-span-2">
                            <label for="ica_license" class="block text-sm font-bold text-gray-700 mb-1">Registro ICA</label>
                            <input type="text" name="ica_license" id="ica_license" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Ej. 12345-AL">
                        </div>

                        <div class="lg:col-span-2">
                            <label for="formula_maestra" class="block text-sm font-bold text-gray-700 mb-1">Fórmula Maestra</label>
                            <input type="text" name="formula_maestra" id="formula_maestra" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="Ej. FM-0000">
                        </div>

                        <div class="lg:col-span-1">
                            <label for="vigencia_meses" class="block text-sm font-bold text-gray-700 mb-1">Vigencia (Meses)</label>
                            <input type="number" name="vigencia_meses" id="vigencia_meses" min="0" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm" placeholder="24">
                        </div>

                        <!-- Forma Farmacéutica e Imagen -->
                        <div class="lg:col-span-3">
                            <label for="pharmaceutical_form" class="block text-sm font-bold text-gray-700 mb-1">Forma Farmacéutica <span class="text-red-500">*</span></label>
                            <select name="pharmaceutical_form" id="pharmaceutical_form" required class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg shadow-sm">
                                <option value="">Seleccione una forma...</option>
                                <option value="Polvo Oral / Soluble">Polvo Oral / Soluble</option>
                                <option value="Solución Inyectable">Solución Inyectable</option>
                                <option value="Solución Oral">Solución Oral</option>
                                <option value="Premix">Premix</option>
                                <option value="Ungüento">Ungüento</option>
                                <option value="Líquido">Líquido</option>
                            </select>
                        </div>

                        <div class="lg:col-span-3">
                            <label for="active_ingredient_concentration" class="block text-sm font-bold text-gray-700 mb-1">Concentración del Principio Activo (%)</label>
                            <div class="relative rounded-lg shadow-sm">
                                <input type="number" step="0.01" name="active_ingredient_concentration" id="active_ingredient_concentration" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full py-2 px-3 sm:text-sm border-gray-300 rounded-lg" placeholder="Ej. 10.50">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-3">
                            <label for="image" class="block text-sm font-bold text-gray-700 mb-1">Imagen del Producto (PNG, JPG)</label>
                            <input type="file" name="image" id="image" accept=".png,.jpg,.jpeg" class="focus:ring-aurofarma-blue focus:border-aurofarma-blue block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-aurofarma-blue file:text-white hover:file:bg-blue-700 transition cursor-pointer border border-gray-300 rounded-lg shadow-sm">
                        </div>
                    </div>
                </div>
            </div> <!-- Cierre Sección A -->

            <!-- SECCIÓN 1: FÓRMULA MAESTRA A GRANEL -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-6 border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100 bg-emerald-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-emerald-800 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        SECCIÓN 1: FÓRMULA MAESTRA A GRANEL
                    </h3>
                    <span class="bg-emerald-200 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                        Base de cálculo: 10,000 Unidades / KG
                    </span>
                </div>
                
                <div class="p-6">
                    <div class="flex justify-between items-end mb-3">
                        <p class="text-sm text-gray-600">Materiales primarios y excipientes que conforman la mezcla universal.</p>
                        <button type="button" onclick="addRawMaterialRow()" class="text-sm bg-emerald-100 hover:bg-emerald-200 text-emerald-800 py-2 px-4 rounded-lg font-bold transition-colors flex items-center shadow-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Agregar Materia Prima
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200" id="raw_materials_table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Código</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Nombre</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Función</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">U.M</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40 min-w-[160px]">%</th>
                                    <th scope="col" class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="raw_materials_body">
                                <!-- Filas inyectadas por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- Cierre Sección 1 -->

            <!-- SECCIÓN 2: PRESENTACIONES Y ACONDICIONAMIENTO -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-8 border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100 bg-indigo-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-indigo-800 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        SECCIÓN 2: PRESENTACIONES Y EMPAQUE
                    </h3>
                    <button type="button" onclick="addPresentationBlock()" class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-bold transition-colors flex items-center shadow-md">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Añadir Nueva Presentación
                    </button>
                </div>
                
                <div class="p-6 bg-gray-50" id="presentations_container">
                    <p class="text-sm text-gray-500 mb-4 italic text-center" id="empty_presentations_msg">No hay presentaciones agregadas. Haz clic en "Añadir Nueva Presentación" para comenzar.</p>
                    <!-- Bloques de presentaciones inyectados por JS -->
                </div>
            </div> <!-- Cierre Sección 2 -->

            <!-- Botón Submit -->
            <div class="flex justify-end mb-10">
                <button type="submit" class="bg-aurofarma-blue py-4 px-10 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-aurofarma-blue flex items-center transition-transform hover:-translate-y-1">
                    Guardar Producto y Fórmulas
                    <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </div>
            
        </form> <!-- Cierre Formulario -->

    </div> <!-- Cierre container max-w-7xl -->
</div> <!-- Cierre container py-6 -->
@endsection

@push('scripts')
<script>
    let rmIndex = 0;
    let presentationIndex = 0;
    
    // Rastreador de índices de empaque por cada presentación: { presIndex: pkgIndex }
    let pkgCounters = {}; 

    let rmTimers = {};
    function autocompletarMateriaPrima(inputEl) {
        if (!inputEl) return;
        const row = inputEl.closest('tr');
        if (!row) return;
        const nameInput = row.querySelector('.rm-name');
        const codigo = String(inputEl.value || '').trim().toUpperCase();

        if (!codigo) {
            if (nameInput) {
                nameInput.value = '';
                nameInput.classList.remove('bg-red-50', 'text-red-700', 'border-red-300', 'bg-emerald-50', 'text-emerald-800');
            }
            return;
        }

        const timerKey = inputEl.name || 'rm_' + Math.random();
        clearTimeout(rmTimers[timerKey]);
        rmTimers[timerKey] = setTimeout(async () => {
            try {
                // 1. Buscar en catálogo en memoria (Respuesta instantánea 0ms)
                let itemLocal = null;
                if (typeof catalogoItems !== 'undefined' && Array.isArray(catalogoItems)) {
                    itemLocal = catalogoItems.find(i => {
                        if (!i) return false;
                        const c = String(i.codigo || '').trim().toUpperCase();
                        return c === codigo || (c && codigo && c.replace(/^0+/, '') === codigo.replace(/^0+/, ''));
                    });
                    if (!itemLocal && codigo.length >= 3) {
                        itemLocal = catalogoItems.find(i => {
                            if (!i) return false;
                            const c = String(i.codigo || '').trim().toUpperCase();
                            return c.includes(codigo) || codigo.includes(c);
                        });
                    }
                }

                if (itemLocal && itemLocal.nombre && nameInput) {
                    nameInput.value = itemLocal.nombre;
                    nameInput.classList.remove('bg-red-50', 'text-red-700', 'border-red-300');
                    nameInput.classList.add('bg-emerald-50', 'text-emerald-800');
                    setTimeout(() => nameInput.classList.remove('bg-emerald-50', 'text-emerald-800'), 1200);
                    return;
                }

                // 2. Si no está en memoria, consultar la API en tiempo real
                const resp = await fetch(`/api/items/${encodeURIComponent(codigo)}`);
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success && nameInput) {
                        nameInput.value = data.description || data.name || data.ext_1_detail || '';
                        nameInput.classList.remove('bg-red-50', 'text-red-700', 'border-red-300');
                        nameInput.classList.add('bg-emerald-50', 'text-emerald-800');
                        setTimeout(() => nameInput.classList.remove('bg-emerald-50', 'text-emerald-800'), 1200);
                    } else if (nameInput) {
                        nameInput.value = 'No encontrado';
                        nameInput.classList.remove('bg-emerald-50', 'text-emerald-800');
                        nameInput.classList.add('bg-red-50', 'text-red-700', 'border-red-300');
                    }
                } else if (nameInput) {
                    nameInput.value = 'No encontrado';
                    nameInput.classList.remove('bg-emerald-50', 'text-emerald-800');
                    nameInput.classList.add('bg-red-50', 'text-red-700', 'border-red-300');
                }
            } catch (err) {
                console.error('Error buscando materia prima:', err);
                if (nameInput) {
                    nameInput.value = 'No encontrado';
                    nameInput.classList.remove('bg-emerald-50', 'text-emerald-800');
                    nameInput.classList.add('bg-red-50', 'text-red-700', 'border-red-300');
                }
            }
        }, 150);
    }

    // Helper retrocompatible
    function fetchItemName(code, nameInputEl) {
        if (!nameInputEl) return;
        const row = nameInputEl.closest('tr');
        const codeInput = row ? row.querySelector('input[name*="[code]"]') : null;
        if (codeInput) autocompletarMateriaPrima(codeInput);
    }

    // Add Raw Material Row
    function addRawMaterialRow() {
        const tbody = document.getElementById('raw_materials_body');
        const tr = document.createElement('tr');
        tr.className = "hover:bg-emerald-50 transition-colors";
        
        tr.innerHTML = `
            <td class="px-4 py-2">
                <input type="text" name="raw_materials[${rmIndex}][code]" required 
                       class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500 font-mono uppercase" 
                       placeholder="Cód..." 
                       oninput="autocompletarMateriaPrima(this)"
                       onblur="autocompletarMateriaPrima(this)"
                       onchange="autocompletarMateriaPrima(this)"
                       onpaste="setTimeout(() => autocompletarMateriaPrima(this), 50)">
            </td>
            <td class="px-4 py-2 relative">
                <input type="text" name="raw_materials[${rmIndex}][name]" required readonly 
                       class="rm-name w-full py-1.5 px-2 text-sm border-gray-200 rounded bg-gray-100 text-gray-700 cursor-not-allowed transition-all" 
                       placeholder="Automático según Código...">
            </td>
            <td class="px-4 py-2">
                <input type="text" name="raw_materials[${rmIndex}][function]" required class="w-full py-1.5 px-2 text-sm border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500" placeholder="Ej. API">
            </td>
            <td class="px-4 py-2">
                <input type="text" readonly value="KIL" class="w-full py-1.5 px-2 text-sm border-gray-200 rounded bg-gray-100 text-gray-500 text-center cursor-not-allowed font-medium">
            </td>
            <td class="px-4 py-3">
                <input type="number" step="0.01" name="raw_materials[${rmIndex}][percentage]" required 
                    class="w-full text-center text-2xl font-black text-blue-900 bg-blue-50 border-2 border-blue-400 rounded-lg p-3 shadow-inner focus:ring-4 focus:ring-blue-300 focus:border-blue-600 transition-all" 
                    style="min-width: 140px;" placeholder="0.00">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50" title="Eliminar fila">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        rmIndex++;
    }

    // Add Presentation Block
    function addPresentationBlock() {
        document.getElementById('empty_presentations_msg').style.display = 'none';
        
        const container = document.getElementById('presentations_container');
        const blockDiv = document.createElement('div');
        
        const currentPresIndex = presentationIndex;
        pkgCounters[currentPresIndex] = 0; // Inicializar contador de empaques para esta tarjeta
        
        blockDiv.className = "bg-white border border-indigo-100 shadow-md rounded-xl p-6 mb-6 relative overflow-hidden";
        blockDiv.innerHTML = `
            <div class="absolute top-0 left-0 w-2 h-full bg-indigo-500"></div>
            
            <div class="flex justify-between items-start mb-4 pl-4 grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-bold text-indigo-900 mb-1">Código SKU (Ítem) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="presentations[${currentPresIndex}][presentation_code]" required 
                               class="pres-sku-input focus:ring-indigo-500 focus:border-indigo-500 block w-full py-2 px-3 text-sm font-mono font-black border-indigo-200 rounded-lg shadow-sm text-indigo-900 uppercase" 
                               placeholder="Ej. 10001"
                               oninput="autocompletarPresentacionDesdeSku(this)"
                               onblur="autocompletarPresentacionDesdeSku(this)"
                               onchange="autocompletarPresentacionDesdeSku(this)"
                               onpaste="setTimeout(() => autocompletarPresentacionDesdeSku(this), 50)">
                        <span class="pres-spinner hidden absolute right-2.5 top-2.5 text-indigo-600">
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                        </span>
                    </div>
                    <span class="text-[10px] text-gray-500 mt-1 block">
                        <i class="fas fa-search text-[9px] text-indigo-500 mr-0.5"></i> Busca en <code>items</code> y autocompleta la presentación
                    </span>
                </div>
                <div class="col-span-6">
                    <label class="block text-sm font-bold text-indigo-900 mb-1">Nombre de la Presentación <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="presentations[${currentPresIndex}][name]" required readonly
                               class="pres-name-input block w-full py-2 px-3 sm:text-base font-bold border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-800 cursor-not-allowed placeholder-gray-400 transition-all" 
                               placeholder="Automático según Código SKU...">
                        <span class="pres-check hidden absolute right-2.5 top-2.5 text-emerald-500 text-sm" title="Presentación encontrada en catálogo">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                </div>
                <div class="col-span-2 flex justify-end items-end pb-1">
                    <button type="button" onclick="this.closest('.bg-white').remove()" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center p-2 rounded hover:bg-red-50 transition-colors" title="Eliminar Presentación">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Eliminar
                    </button>
                </div>
            </div>

            <div class="pl-4 mt-6">
                <div class="flex justify-between items-end mb-3">
                    <h5 class="font-bold text-gray-700 text-sm uppercase tracking-wide">Materiales de Envase y Empaque</h5>
                    <button type="button" onclick="addPackagingRowToPresentation(${currentPresIndex}, this)" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-1.5 px-3 rounded font-bold transition-colors flex items-center border border-indigo-200">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Agregar Material
                    </button>
                </div>
                
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Código</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre del Material</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Tipo Material</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">MATERIAL</th>
                                <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 80px;">U.M</th>
                                <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-48">PORCENTAJE (%)</th>
                                <th scope="col" class="px-4 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 pkg-tbody">
                            <!-- Filas inyectadas aquí para esta presentación -->
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        container.appendChild(blockDiv);
        
        // Auto-anexar el primer empaque para comodidad
        const addBtn = blockDiv.querySelector('button[onclick^="addPackagingRow"]');
        addPackagingRowToPresentation(currentPresIndex, addBtn);
        
        presentationIndex++;
    }

    // --- NUEVA LÓGICA DE BÚSQUEDA POR CÓDIGO ---
    const catalogoItems = @json($all_items);

    function buscarMaterialPorCodigo(codigo, container) {
        const itemNombreInput = container.querySelector('.pkg-name');
        const hiddenIdInput = container.querySelector('.hidden-item-id');
        
        let codigoDigitado = String(codigo || '').trim().toUpperCase();

        if (!codigoDigitado) {
            itemNombreInput.value = '';
            hiddenIdInput.value = '';
            itemNombreInput.classList.remove('bg-red-50', 'text-red-800');
            return;
        }

        const itemEncontrado = catalogoItems.find(i => i && String(i.codigo || '').trim().toUpperCase() === codigoDigitado);

        if (itemEncontrado) {
            itemNombreInput.value = itemEncontrado.nombre;
            hiddenIdInput.value = itemEncontrado.id;
            itemNombreInput.classList.remove('bg-red-50', 'text-red-800');
            itemNombreInput.classList.add('bg-green-50');
            setTimeout(() => itemNombreInput.classList.remove('bg-green-50'), 1000);
        } else {
            itemNombreInput.value = 'Material no encontrado';
            hiddenIdInput.value = '';
            itemNombreInput.classList.add('bg-red-50', 'text-red-800');
        }
    }

    // --- AUTOCOMPLETADO DE PRESENTACIÓN A PARTIR DEL CÓDIGO SKU (TABLA ITEMS) ---
    let presSkuTimers = {};
    function autocompletarPresentacionDesdeSku(inputEl) {
        if (!inputEl) return;
        const card = inputEl.closest('.bg-white') || inputEl.closest('.border-indigo-100');
        if (!card) return;
        const nameInput = card.querySelector('.pres-name-input') || card.querySelector('input[name*="[name]"]');
        const spinner = card.querySelector('.pres-spinner');
        const checkIcon = card.querySelector('.pres-check');
        const codigo = String(inputEl.value || '').trim().toUpperCase();

        if (!codigo) {
            if (nameInput) {
                nameInput.value = '';
                nameInput.classList.remove('bg-red-50', 'text-red-700', 'border-red-300', 'bg-emerald-50', 'text-emerald-800');
            }
            if (checkIcon) checkIcon.classList.add('hidden');
            return;
        }

        const timerKey = inputEl.name || 'sku_default';
        clearTimeout(presSkuTimers[timerKey]);
        presSkuTimers[timerKey] = setTimeout(async () => {
            if (spinner) spinner.classList.remove('hidden');
            try {
                // 1. Buscar en catálogo en memoria (Respuesta instantánea 0ms)
                let itemLocal = null;
                if (typeof catalogoItems !== 'undefined' && Array.isArray(catalogoItems)) {
                    itemLocal = catalogoItems.find(i => {
                        if (!i) return false;
                        const c = String(i.codigo || '').trim().toUpperCase();
                        return c === codigo || (c && codigo && c.replace(/^0+/, '') === codigo.replace(/^0+/, ''));
                    });
                    if (!itemLocal && codigo.length >= 3) {
                        itemLocal = catalogoItems.find(i => {
                            if (!i) return false;
                            const c = String(i.codigo || '').trim().toUpperCase();
                            return c.includes(codigo) || codigo.includes(c);
                        });
                    }
                }

                if (itemLocal && itemLocal.nombre && nameInput) {
                    nameInput.value = itemLocal.nombre;
                    nameInput.classList.remove('bg-red-50', 'text-red-700', 'border-red-300');
                    nameInput.classList.add('bg-emerald-50', 'text-emerald-800');
                    setTimeout(() => nameInput.classList.remove('bg-emerald-50', 'text-emerald-800'), 1200);
                    if (checkIcon) checkIcon.classList.remove('hidden');
                    if (spinner) spinner.classList.add('hidden');
                    return;
                }

                // 2. Si no está en memoria, consultar la API en tiempo real
                const resp = await fetch(`/api/items/${encodeURIComponent(codigo)}`);
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success && nameInput) {
                        nameInput.value = data.description || data.name || data.ext_1_detail || '';
                        nameInput.classList.remove('bg-red-50', 'text-red-700', 'border-red-300');
                        nameInput.classList.add('bg-emerald-50', 'text-emerald-800');
                        setTimeout(() => nameInput.classList.remove('bg-emerald-50', 'text-emerald-800'), 1200);
                        if (checkIcon) checkIcon.classList.remove('hidden');
                    } else if (nameInput) {
                        nameInput.value = 'No encontrado';
                        nameInput.classList.remove('bg-emerald-50', 'text-emerald-800');
                        nameInput.classList.add('bg-red-50', 'text-red-700', 'border-red-300');
                        if (checkIcon) checkIcon.classList.add('hidden');
                    }
                } else if (nameInput) {
                    nameInput.value = 'No encontrado';
                    nameInput.classList.remove('bg-emerald-50', 'text-emerald-800');
                    nameInput.classList.add('bg-red-50', 'text-red-700', 'border-red-300');
                    if (checkIcon) checkIcon.classList.add('hidden');
                }
            } catch (err) {
                console.error('Error buscando ítem SKU:', err);
                if (nameInput) {
                    nameInput.value = 'No encontrado';
                    nameInput.classList.remove('bg-emerald-50', 'text-emerald-800');
                    nameInput.classList.add('bg-red-50', 'text-red-700', 'border-red-300');
                }
                if (checkIcon) checkIcon.classList.add('hidden');
            } finally {
                if (spinner) spinner.classList.add('hidden');
            }
        }, 150);
    }

    // Add Packaging Row to specific Presentation
    function addPackagingRowToPresentation(presId, buttonEl) {
        const tbody = buttonEl.closest('.pl-4').querySelector('.pkg-tbody');
        const currentPkgId = pkgCounters[presId];
        
        const tr = document.createElement('tr');
        tr.className = "hover:bg-indigo-50 transition-colors material-row";
        
        tr.innerHTML = `
            <td class="px-4 py-2">
                <input type="text" class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 font-mono uppercase input-codigo-material" 
                    placeholder="Digite Código" 
                    oninput="buscarMaterialPorCodigo(this.value, this.closest('tr'))">
                <input type="hidden" name="presentations[${presId}][materials][${currentPkgId}][item_id]" class="hidden-item-id">
            </td>
            <td class="px-4 py-2">
                <input type="text" readonly class="pkg-name w-full py-1 px-2 text-xs border-gray-200 rounded bg-gray-100 text-gray-700 cursor-not-allowed" placeholder="Busque un código...">
            </td>
            <td class="px-4 py-2">
                <input type="text" name="presentations[${presId}][materials][${currentPkgId}][type]" required class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej. Frasco">
            </td>
            <td class="px-4 py-2">
                <select name="presentations[${presId}][materials][${currentPkgId}][material]" required class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 font-bold bg-amber-50">
                    <option value="N.A.">N.A.</option>
                    <option value="ETIQUETA">ETIQUETA</option>
                    <option value="PLEGADIZA">PLEGADIZA</option>
                    <option value="SOBRE">SOBRE</option>
                    <option value="JERINGA">JERINGA</option>
                    <option value="COLLARIN">COLLARIN</option>
                    <option value="GARRAFA">GARRAFA</option>
                </select>
            </td>
            <td class="px-4 py-2 text-center" style="min-width: 80px;">
                <input type="text" name="presentations[${presId}][materials][${currentPkgId}][unit]" value="UND" class="w-full py-1 px-2 text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 text-center font-medium form-control" placeholder="UND">
            </td>
            <td class="px-4 py-3">
                <input type="number" step="0.01" name="presentations[${presId}][materials][${currentPkgId}][percentage]" required 
                    class="w-full text-center text-2xl font-black text-blue-900 bg-blue-50 border-2 border-blue-400 rounded-lg p-3 shadow-inner focus:ring-4 focus:ring-blue-300 focus:border-blue-600 transition-all" 
                    style="min-width: 140px;" placeholder="0.00">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1 rounded-full hover:bg-red-50" title="Eliminar empaque">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        pkgCounters[presId]++;
    }

    // Initialize with 1 Raw Material and 1 Presentation Block
    addRawMaterialRow();
    addPresentationBlock();
    
</script>
@endpush
