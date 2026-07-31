<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Técnica - {{ $product['name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; background: white; }
            .print-container { width: 100%; border: none; shadow: none; margin: 0; padding: 0; }
        }
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-4xl mx-auto bg-white p-10 shadow-lg border border-gray-200 print-container">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-blue-900 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-blue-900 uppercase">Ficha Técnica de Producto</h1>
                <p class="text-gray-500 font-bold tracking-widest mt-1">SISTEMA MES / EBR - AUROFARMA</p>
            </div>
            <div class="text-right">
                <div class="bg-blue-900 text-white px-4 py-2 font-bold rounded">
                    CÓD: {{ $product['product_code'] }}
                </div>
                <p class="text-xs text-gray-400 mt-2 italic">Fecha de Impresión: {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Info Principal -->
        <div class="grid grid-cols-2 gap-8 mb-10">
            <div class="space-y-3">
                <p><span class="text-xs uppercase font-bold text-gray-500 block">Nombre del Producto</span> <span class="text-xl font-bold text-gray-800">{{ $product['name'] }}</span></p>
                <p><span class="text-xs uppercase font-bold text-gray-500 block">Forma Farmacéutica</span> <span class="font-semibold text-gray-700">{{ $product['pharmaceutical_form'] }}</span></p>
            </div>
            <div class="space-y-3">
                <p><span class="text-xs uppercase font-bold text-gray-500 block">Presentaciones</span> <span class="font-semibold text-gray-700">{{ $product['presentation_name'] }}</span></p>
                <p><span class="text-xs uppercase font-bold text-gray-500 block">Lote Base</span> <span class="font-mono font-bold text-blue-900">{{ $product['base_batch_size'] }} {{ $product['base_unit'] }}</span></p>
                <p><span class="text-xs uppercase font-bold text-gray-500 block">Concentración P.A.</span> <span class="font-mono font-bold text-emerald-700">{{ $product['active_ingredient_concentration'] ?? '0' }}%</span></p>
            </div>
        </div>

        <!-- Master Formula Table -->
        <div class="mb-10">
            <h2 class="bg-gray-800 text-white px-4 py-1 text-sm font-bold uppercase tracking-widest mb-4">Fórmula Maestra (Materias Primas)</h2>
            <table class="w-full text-sm border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100 italic">
                        <th class="border border-gray-300 px-4 py-2 text-left">Código</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Descripción Material</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">Unidad</th>
                        <th class="border border-gray-300 px-4 py-2 text-right">Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product['raw_materials'] as $ing)
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-mono">{{ $ing['code'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 font-bold">{{ $ing['description'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">{{ $ing['unit'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right font-mono">{{ number_format($ing['quantity'], 2, '.', '') }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Packaging Table -->
        <div class="mb-10">
            <h2 class="bg-blue-900 text-white px-4 py-1 text-sm font-bold uppercase tracking-widest mb-4">Material de Envase y Empaque</h2>
            <table class="w-full text-sm border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-blue-50 italic">
                        <th class="border border-gray-300 px-4 py-2 text-left">Código</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Nombre del Material</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Tipo Material</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">MATERIAL</th>
                        <th class="border border-gray-300 px-4 py-2 text-center">U.M.</th>
                        <th class="border border-gray-300 px-4 py-2 text-right">Cant. (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product['packaging'] as $ing)
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 font-mono">{{ $ing['code'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 font-bold">{{ $ing['description'] }}</td>
                        <td class="border border-gray-300 px-4 py-2">{{ $ing['tipo_material'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 font-bold">{{ $ing['material_clasificacion'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center">{{ $ing['unit'] }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-right font-mono">{{ number_format($ing['quantity'], 2, '.', '') }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-20 flex justify-between gap-10">
            <div class="flex-1 border-t border-gray-400 pt-2 text-center">
                <p class="text-[10px] font-bold uppercase text-gray-500">Elaborado por:</p>
                <div class="mt-8 h-10 border-b border-dashed border-gray-300"></div>
                <p class="text-xs mt-1">Dirección Técnica</p>
            </div>
            <div class="flex-1 border-t border-gray-400 pt-2 text-center">
                <p class="text-[10px] font-bold uppercase text-gray-500">Aprobado por:</p>
                <div class="mt-8 h-10 border-b border-dashed border-gray-300"></div>
                <p class="text-xs mt-1">Garantía de Calidad</p>
            </div>
        </div>

        <div class="mt-12 text-center no-print">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-full shadow-lg transition-all transform hover:scale-105">
                🖨️ CONFIRMAR IMPRESIÓN
            </button>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Uncomment to auto-trigger print dialog
            // window.print();
        };
    </script>
</body>
</html>
