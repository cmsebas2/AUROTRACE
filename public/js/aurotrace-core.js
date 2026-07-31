/* =========================================================
   ⚠️ CRITICAL CORE LOGIC - DO NOT MODIFY - VER 1.0
   - Motor de Cálculo (Fuerza Bruta)
   - Fetch de Fórmula Maestra y Explosión de Materiales
   - Generación de Filas de Presentación
   Cualquier alteración a este flujo está ESTRICTAMENTE PROHIBIDA
   sin autorización de nivel ADMIN (Johann).
   ========================================================= */

function mb_strtoupper(str) {
    return str ? str.toUpperCase() : '';
}

// =========================================================
// ⚠️ AUROFORMAT: MOTOR DE ESTANDARIZACIÓN BPM (v1.0)
// =========================================================
window.AuroFormat = {
    decimal: function(num) { 
        let parsed = parseFloat(num);
        return isNaN(parsed) ? '0.00' : parsed.toFixed(2); 
    },
    fecha: function(dateStr) { 
        if(!dateStr) return '';
        let d = new Date(dateStr + 'T00:00:00');
        return isNaN(d.getTime()) ? '' : d.toISOString().split('T')[0];
    },
    vencimiento: function(dateStr) { 
        if(!dateStr) return '';
        let d = new Date(dateStr + 'T00:00:00');
        // Formato AAAA-MM para vencimientos de largo plazo
        return isNaN(d.getTime()) ? '' : d.toISOString().split('T')[0].substring(0, 7);
    }
};

function validarFormularioCompleto() {
    console.log("--- Iniciando Validación por Fuerza Bruta (DOM + Alpine) ---");
    
    // 1. Identificación Básica
    const product = document.getElementById('select-producto').value;
    const lote = document.getElementById('input-lote').value.trim();
    const op = document.getElementById('input-op').value.trim();
    const fm = document.getElementById('input-formula-maestra').value;
    const batchSize = parseFloat(document.getElementById('tamaño_lote').value) || 0;

    if (!product || !lote || !op || fm === 'S/N' || batchSize <= 0) {
        console.warn("--- Validación Fallida: Datos de encabezado incompletos ---");
        return false;
    }

    // 2. Verificación de Presentaciones
    const presentaciones = document.querySelectorAll('.fila-presentacion');
    if (presentaciones.length === 0) {
        console.warn("--- Validación Fallida: No hay presentaciones ---");
        return false;
    }

    let presentationsOk = true;
    presentaciones.forEach(row => {
        const cant = row.querySelector('.cantidad-presentacion').value;
        const sel = row.querySelector('.peso-presentacion').value;
        if (!sel || !cant || parseFloat(cant) <= 0) presentationsOk = false;
    });

    if (!presentationsOk) {
        console.warn("--- Validación Fallida: Cantidades en presentaciones inválidas ---");
        return false;
    }

    // 3. Verificación de Lotes (Johann v2.2)
    // Se eliminó la validación de balance (Requerido vs Entregado) 
    // ya que eso pertenece a la fase de Reconciliación.
    
    console.log("--- Validación Exitosa (Datos de Creación OK) ---");
    return true;
}

function actualizarCamposProducto(select) {
    const id = select.value;
    const product = window.productCatalog[id];
    const alpine = window.Alpine ? Alpine.$data(document.querySelector('[x-data]')) : null;
    
    const inputIca = document.getElementById('input-ica');
    const inputForma = document.getElementById('input-forma');
    const inputVida = document.getElementById('input-vida');
    const inputVencimiento = document.getElementById('input-vencimiento');
    const inputEmision = document.getElementById('input-emision');
    const inputFormula = document.getElementById('input-formula-maestra');

    if (product) {
        const hoy = new Date().toISOString().split('T')[0];
        
        if (alpine) {
            alpine.producto_id = id;
            alpine.productData.ica_license = product.ica_license || '';
            alpine.productData.pharmaceutical_form = product.pharmaceutical_form || '';
            alpine.productData.vigencia_meses = product.vigencia_meses || 0;
            alpine.productData.formula_maestra = product.formula_maestra || 'S/N';
            alpine.productData.base_batch_size = parseFloat(product.base_batch_size) || 1;
            alpine.productData.base_unit = product.base_unit || 'KG';
            
            let dDest = new Date(hoy + 'T00:00:00');
            dDest.setFullYear(dDest.getFullYear() + 5);
            alpine.formData.destruction_date = dDest.toISOString().split('T')[0];
        }

        inputIca.value = product.ica_license || '';
        inputForma.value = product.pharmaceutical_form || '';
        inputVida.value = product.vigencia_meses ? product.vigencia_meses + ' Meses' : '---';
        inputFormula.value = product.formula_maestra || 'S/N';
        inputEmision.value = hoy;
        
        if (product.vigencia_meses) {
            let d = new Date(hoy + 'T00:00:00');
            d.setMonth(d.getMonth() + parseInt(product.vigencia_meses));
            inputVencimiento.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        } else {
            inputVencimiento.value = '';
        }

        const body = document.getElementById('tabla-presentaciones-body');
        if (body) body.innerHTML = '';
        
        if (alpine) alpine.fetchProductData();

    } else {
        inputIca.value = ''; inputForma.value = ''; inputVida.value = '---'; inputVencimiento.value = '';
        inputFormula.value = 'S/N';
        if (alpine) {
            alpine.producto_id = '';
            alpine.productData = { ica_license: '', pharmaceutical_form: '', formula_maestra: '', ingredients: [] };
        }
    }
}

let filaPresentacionIndex = 0;

function agregarFilaNativa() {
    const productId = document.getElementById('select-producto').value;
    if (!productId) {
        return Swal.fire('Atención', 'Seleccione un producto en la Sección 1 primero.', 'warning');
    }

    const product = window.productCatalog[productId];
    if (!product || !product.presentations || product.presentations.length === 0) {
        return Swal.fire('Error', 'Este producto no tiene presentaciones registradas.', 'error');
    }

    const body = document.getElementById('tabla-presentaciones-body');
    const index = filaPresentacionIndex++;
    
    let optionsHtml = '<option value="" data-peso="0" data-um="">Seleccione...</option>';
    product.presentations.forEach(p => {
        const fullDesc = `${product.name} X ${p.name}`;
        const matches = p.name.match(/(\d+(?:\.\d+)?)\s*([a-zA-Z]+)/);
        const peso = matches ? matches[1] : 0;
        const um = matches ? matches[2] : '';

        optionsHtml += `<option value="${p.id}" data-code="${p.presentation_code}" data-peso="${peso}" data-um="${um}">${fullDesc}</option>`;
    });

    const rowHtml = `
        <tr id="fila-pres-${index}" class="fila-presentacion">
            <td><input type="text" id="cod-pres-${index}" name="presentaciones[index][code]" class="input-invisible txt-centro" readonly placeholder="---"></td>
            <td>
                <select id="sel-pres-${index}" name="presentaciones[index][id]" class="input-invisible peso-presentacion" onchange="actualizarFilaPresentacion(${index}, this); calcularFuerzaBruta()" :disabled="realizadoPor.signed">
                    ${optionsHtml}
                </select>
            </td>
            <td style="display: flex; align-items: center;">
                <input type="number" id="cant-pres-${index}" name="presentaciones[index][quantity]" class="input-invisible txt-centro txt-bold cantidad-presentacion" style="width: 80%;" placeholder="0" required onkeyup="calcularFuerzaBruta()" onchange="calcularFuerzaBruta()" :disabled="realizadoPor.signed">
                <button type="button" onclick="eliminarFilaNativa(${index})" class="text-red-500 no-print" style="width: 20%; border:none; background:none; cursor:pointer;" x-show="!realizadoPor.signed">&times;</button>
            </td>
        </tr>
    `;
    
    body.insertAdjacentHTML('beforeend', rowHtml);
}

function actualizarFilaPresentacion(index, select) {
    const option = select.options[select.selectedIndex];
    const code = option.getAttribute('data-code');
    const inputCode = document.getElementById(`cod-pres-${index}`);
    if (inputCode) inputCode.value = code || '';
    
    const row = document.getElementById(`fila-pres-${index}`);
    row.setAttribute('data-peso-unitario', option.getAttribute('data-peso') || 0);
    row.setAttribute('data-um-unitario', option.getAttribute('data-um') || '');

    calcularFuerzaBruta();
}

window.calcularFuerzaBruta = function() {
    /* =========================================================
       ⚠️ PROTOCOLO DE INMUTABILIDAD (Johann v2.1)
       En esta vista (/ops/crear), los campos de RECONCILIACIÓN 
       (Entrega, Devolución, Consumo) son ESTRICTAMENTE READONLY.
       El motor solo debe tocar: Tamaño de Lote y Requerimiento.
       ========================================================= */
    console.log("--- Ejecutando Plan de Rescate (Fuerza Bruta) ---");
    let total = 0;
    let cantidades = 0;
    let detectUM = "---";

    document.querySelectorAll('.fila-presentacion').forEach(row => {
        const input = row.querySelector('.cantidad-presentacion');
        const select = row.querySelector('.peso-presentacion');
        
        if (input && select) {
            let c = parseFloat(input.value) || 0;
            const option = select.options[select.selectedIndex];
            let p = parseFloat(option?.getAttribute('data-peso') || 0);
            
            total += (c * p);
            cantidades += c;

            // Detección de U.M. por Fuerza Bruta (Johann)
            const txt = option?.text || "";
            if (txt.toUpperCase().includes("KG")) detectUM = "KG";
            else if (txt.toUpperCase().includes("ML")) detectUM = "ML";
            else if (txt.toUpperCase().includes("L")) detectUM = "L";
            else if (txt.toUpperCase().includes(" G ")) detectUM = "G";
        }
    });
    
    const field = document.getElementById('tamaño_lote');
    if (field) {
        field.value = window.AuroFormat.decimal(total);
    }

    // Inyección de U.M. Directa al HTML (Johann)
    const umSpan = document.getElementById('span-um');
    if (umSpan && detectUM !== "---") {
        umSpan.innerText = detectUM;
    }
    
    if (window.alpineComponentContext) {
        window.alpineComponentContext.bulkSizeTotal = total;
        window.alpineComponentContext.explodeFormula(cantidades);
    }
};

function eliminarFilaNativa(index) {
    const row = document.getElementById(`fila-pres-${index}`);
    if (row) {
        row.remove();
        calcularFuerzaBruta();
    }
}

function testCalculo() {
    console.log("--- Ejecutando Test Harcoded ---");
    const input = document.getElementById('tamaño_lote');
    if (input) {
        input.value = "100.00";
        Swal.fire('Test ID', 'El campo tamaño_lote recibió el valor 100.00 correctamente.', 'success');
    } else {
        Swal.fire('Error ID', 'No se encontró el elemento con ID tamaño_lote', 'error');
    }
}
