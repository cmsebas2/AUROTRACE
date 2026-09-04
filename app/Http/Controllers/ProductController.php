<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductManufacturingPlan;
use App\Models\PlanStep;
use App\Models\PlanStepIngredient;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * EBR Master Builder: Mostrar Formulario de Edición
     */
    public function editInstructivo($id)
    {
        $producto = Product::with(['ingredients', 'activePlan.steps.ingredients'])->findOrFail($id);
        
        // Si no tiene plan activo, creamos uno en memoria para el formulario
        $plan = $producto->activePlan ?: new ProductManufacturingPlan([
            'product_id' => $id,
            'master_code_header' => null,
            'internal_code' => null,
            'version' => '01',
            'issue_date' => now(),
            'active' => true
        ]);

        return view('productos.instructivo.edit', compact('producto', 'plan'));
    }

    /**
     * EBR Master Builder: Guardar/Actualizar Instructivo
     */
    public function updateInstructivo(Request $request, $id)
    {
        $producto = Product::findOrFail($id);

        $request->validate([
            'master_code_header' => 'required|string',
            'internal_code' => 'required|string',
            'version' => 'required|string',
            'issue_date' => 'required|date',
            'ica_registry' => 'nullable|string',
            'objective' => 'nullable|string',
            'requirements' => 'nullable|string',
            'equipment' => 'nullable|string',
            'potency_adjustment_logic' => 'nullable|string',
            'observations' => 'nullable|string',
            'sterilization_method' => 'nullable|string',
            'master_batch_size' => 'nullable|numeric',
            'steps' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // 1. Desactivar planes anteriores si es uno nuevo o simplemente actualizar el actual
            // Para simplificar esta versión, buscamos el activo y le caemos encima o creamos uno nuevo.
            $plan = $producto->activePlan; // Use the loaded activePlan relationship
            if (!$plan) {
                $plan = new ProductManufacturingPlan(['product_id' => $producto->id]);
            }

            $plan->fill([
                'master_code' => $request->master_code_header,
                'master_code_header' => $request->master_code_header,
                'internal_code' => $request->internal_code,
                'version' => $request->version,
                'issue_date' => $request->issue_date,
                'ica_registry' => $request->ica_registry,
                'objective' => $request->objective,
                'requirements' => $request->requirements,
                'equipment' => $request->equipment,
                'potency_adjustment_logic' => $request->potency_adjustment_logic,
                'observations' => $request->observations,
                'sterilization_method' => $request->sterilization_method,
                'master_batch_size' => $request->master_batch_size,
                'active' => true,
            ]);
            $plan->save();

            // 2. Limpiar pasos anteriores (Sincronización destructiva para simplicidad de demo)
            $plan->steps()->delete();

            // 3. Re-crear pasos e ingredientes
            if ($request->has('steps')) {
                foreach ($request->steps as $index => $stepData) {
                    $step = $plan->steps()->create([
                        'step_number' => $index + 1,
                        'type'        => $stepData['type'],
                        'description' => $stepData['description'],
                        // JS send 'mixing_time' for theoretical_time_minutes
                        'theoretical_time_minutes' => $stepData['mixing_time'] ?? $stepData['theoretical_time_minutes'] ?? null,
                        // JS sends 'agitation_speed' for target_rpm
                        'target_rpm'  => $stepData['agitation_speed'] ?? $stepData['target_rpm'] ?? null,
                        'mesh_size'   => $stepData['mesh_size'] ?? null,
                        'ipc_test_type'     => $stepData['ipc_test_type'] ?? null,
                        'ipc_specification' => $stepData['ipc_specification'] ?? null,
                    ]);

                    if (isset($stepData['ingredients'])) {
                        foreach ($stepData['ingredients'] as $ingData) {
                            if (isset($ingData['id'])) {
                                $step->ingredients()->create([
                                    'formula_ingredient_id' => $ingData['id'],
                                    'unit' => $ingData['unit'] ?? 'KG',
                                    'theoretical_quantity' => $ingData['theoretical_quantity'] ?? 0,
                                    'percentage_allocation' => 0 // Re-calculable
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Instructivo Maestro guardado correctamente.',
                    'redirect' => route('productos.show', $id)
                ]);
            }

            return redirect()->route('productos.show', $id)->with('success', 'Instructivo EBR actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar instructivo: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Error al guardar instructivo: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Auto-población del catálogo maestro si es necesario (con caché de 2 horas)
     */
    protected function ensureSchema()
    {
        // El esquema y catálogo maestro se sincronizan vía /run-migrations para no bloquear peticiones web
        return true;
    }

    public function index()
    {
        $this->ensureSchema();

        $products = \App\Models\Product::orderBy('name')->get()->map(function($product) {
            return [
                'name' => $product->name,
                'full_name' => collect([$product->name, $product->presentation])->filter()->implode(' - '),
                'id' => $product->id,
                'image' => $product->image ?? strtolower(str_replace(' ', '-', $product->name)) . '.png',
                'ica_license' => $product->ica_license
            ];
        });

        return view('productos.index', compact('products'));
    }

    public function show($id)
    {
        // Buscar el producto por ID e incluir ingredientes y presentaciones
        $productDb = \App\Models\Product::with(['ingredients.presentation', 'presentations'])->findOrFail($id);

        $rawMaterials = [];
        $packaging = [];

        foreach ($productDb->ingredients as $ing) {
            $itemData = [
                'code' => $ing->material_code,
                'description' => $ing->material_name,
                'tipo_material' => $ing->material_type,
                'material_clasificacion' => $ing->specific_material_type ?? 'N.A.',
                'function' => $ing->function ?: '-',
                'quantity' => floatval($ing->percentage), // Muestra el porcentaje
                'unit' => $ing->unit, 
                'presentation_name' => $ing->presentation ? $ing->presentation->name : 'Universal'
            ];

            if (strtoupper($ing->material_type) === 'MATERIA PRIMA') {
                $rawMaterials[] = $itemData;
            } else {
                $packaging[] = $itemData;
            }
        }

        $product = [
            'id' => $productDb->id,
            'name' => $productDb->name,
            'presentation_name' => collect($productDb->presentations->pluck('name'))->implode(', '),
            'image' => $productDb->image ?? strtolower(str_replace(' ', '-', $productDb->name)) . '.png',
            'pharmaceutical_form' => $productDb->pharmaceutical_form ?? 'POLVO ORAL',
            'presentations' => $productDb->presentations,
            'status' => $productDb->status,
            'ica_license' => $productDb->ica_license,
            'formula_maestra' => $productDb->formula_maestra,
            'manufactured_lots' => $productDb->productionOrders()
                ->whereYear('created_at', date('Y'))
                ->count(),
            'base_batch_size' => number_format($productDb->base_batch_size, 2),
            'base_unit' => $productDb->base_unit,
            'raw_materials' => $rawMaterials,
            'packaging' => $packaging,
            'files' => [], // Sin archivos ya que la base es todo BD
            'steps' => $productDb->steps()->with('ingredients.formulaIngredient')->get(),
            'product_code' => collect($productDb->presentations->pluck('presentation_code'))->implode(', '),
            'active_plan' => $productDb->activePlan,
            'active_ingredient_concentration' => $productDb->active_ingredient_concentration
        ];

        return view('productos.show', [
            'product' => $product,
            'productDb' => $productDb
        ]);
    }

    public function imprimirFicha($id)
    {
        $productDb = \App\Models\Product::with(['ingredients.presentation', 'presentations'])->findOrFail($id);

        $rawMaterials = [];
        $packaging = [];

        foreach ($productDb->ingredients as $ing) {
            $itemData = [
                'code' => $ing->material_code,
                'description' => $ing->material_name,
                'tipo_material' => $ing->material_type,
                'material_clasificacion' => $ing->specific_material_type ?? 'N.A.',
                'function' => $ing->function ?: '-',
                'quantity' => floatval($ing->percentage),
                'unit' => $ing->unit,
                'presentation_name' => $ing->presentation ? $ing->presentation->name : 'Universal'
            ];

            if (strtoupper($ing->material_type) === 'MATERIA PRIMA') {
                $rawMaterials[] = $itemData;
            } else {
                $packaging[] = $itemData;
            }
        }

        $product = [
            'id' => $productDb->id,
            'name' => $productDb->name,
            'presentation_name' => collect($productDb->presentations->pluck('name'))->implode(', '),
            'pharmaceutical_form' => $productDb->pharmaceutical_form ?? 'POLVO ORAL',
            'status' => $productDb->status,
            'base_batch_size' => number_format($productDb->base_batch_size, 2),
            'base_unit' => $productDb->base_unit,
            'raw_materials' => $rawMaterials,
            'packaging' => $packaging,
            'product_code' => collect($productDb->presentations->pluck('presentation_code'))->implode(', '),
            'active_ingredient_concentration' => $productDb->active_ingredient_concentration
        ];

        return view('productos.imprimir', compact('product'));
    }
    public function create()
    {
        // Cargar catálogo unificado de ítems activos (Materias Primas y Empaque)
        $all_items = \App\Models\Item::select('id', 'item_code', 'description', 'inventory_uom')
            ->get()
            ->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'codigo' => trim((string)$item->item_code),
                    'nombre' => trim((string)$item->description),
                    'unidad' => (string)($item->inventory_uom ?? 'UND')
                ];
            });

        return view('productos.crear', compact('all_items'));
    }

    public function edit($id)
    {
        $producto = \App\Models\Product::with(['ingredients', 'presentations.materials.item', 'presentations.packaging_materials.item'])->findOrFail($id);
        
        // --- REPARACIÓN AUTOMÁTICA DE MATERIALES HUÉRFANOS ---
        // Si hay materiales guardados sin presentation_id, los vinculamos a la primera presentación
        if ($producto->presentations->count() > 0) {
            $orphanCount = \App\Models\FormulaIngredient::where('product_id', $id)
                ->whereNull('presentation_id')
                ->where('material_type', '!=', 'MATERIA PRIMA')
                ->count();
            
            if ($orphanCount > 0) {
                $firstPresId = $producto->presentations->first()->id;
                \App\Models\FormulaIngredient::where('product_id', $id)
                    ->whereNull('presentation_id')
                    ->where('material_type', '!=', 'MATERIA PRIMA')
                    ->update(['presentation_id' => $firstPresId]);
                
                // Recargar relación para el renderizado
                $producto->load(['presentations.materials.item']);
            }
        }

        // Cargar catálogo unificado de ítems activos
        $all_items = \App\Models\Item::select('id', 'item_code', 'description', 'inventory_uom')
            ->get()
            ->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'codigo' => trim((string)$item->item_code),
                    'nombre' => trim((string)$item->description),
                    'unidad' => (string)($item->inventory_uom ?? 'UND')
                ];
            });

        return view('productos.editar', compact('producto', 'all_items'));
    }

    public function apiGetItem($codigo)
    {
        $code = strtoupper(trim((string)$codigo));
        $item = \Illuminate\Support\Facades\DB::table('items')
            ->whereRaw('UPPER(CAST(item_code AS TEXT)) = ?', [$code])
            ->select('id', 'item_code', 'description', 'ext_1_detail', 'inventory_uom')
            ->first();

        if (!$item) {
            $item = \Illuminate\Support\Facades\DB::table('items')
                ->whereRaw('UPPER(CAST(item_code AS TEXT)) LIKE ?', ["%{$code}%"])
                ->select('id', 'item_code', 'description', 'ext_1_detail', 'inventory_uom')
                ->first();
        }

        if ($item) {
            return response()->json([
                'success' => true,
                'codigo' => (string)$item->item_code,
                'name' => (string)$item->description,
                'description' => (string)$item->description,
                'ext_1_detail' => (string)($item->ext_1_detail ?? ''),
                'unit' => (string)($item->inventory_uom ?? 'UND')
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Ítem no encontrado en catálogo'], 404);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ica_license' => 'nullable|string|max:255',
            'formula_maestra' => 'nullable|string|max:255',
            'vigencia_meses' => 'nullable|integer|min:0',
            'pharmaceutical_form' => 'required|string|max:255',
            'active_ingredient_concentration' => 'nullable|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'raw_materials' => 'array',
            'presentations' => 'array',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . strtolower(str_replace(' ', '_', $request->name)) . '.' . $request->image->extension();
            $request->image->move(public_path('img/productos'), $imageName);
        }

        $product = \App\Models\Product::create([
            'name' => $request->name,
            'ica_license' => $request->ica_license,
            'formula_maestra' => $request->formula_maestra,
            'vigencia_meses' => $request->vigencia_meses,
            'pharmaceutical_form' => $request->pharmaceutical_form,
            'active_ingredient_concentration' => $request->active_ingredient_concentration ?? 0,
            'image' => $imageName,
            'base_batch_size' => 10000, 
            'base_unit' => 'UND',
            'status' => 'ACTIVO'
        ]);

        // 1. Ingredientes a granel (presentation_id = null)
        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $rm) {
                if (!empty($rm['code']) && ($rm['name'] ?? '') !== 'No encontrado') {
                    $product->ingredients()->create([
                        'presentation_id' => null,
                        'material_code' => strtoupper($rm['code']),
                        'material_name' => $rm['name'] ?? 'S/N',
                        'material_type' => 'MATERIA PRIMA',
                        'function' => $rm['function'] ?? null,
                        'unit' => $rm['unit'] ?? 'KIL',
                        'percentage' => $rm['percentage'] ?? 0,
                    ]);
                }
            }
        }

        // 2. Presentaciones y Materiales
        $allPresentationsString = [];
        if ($request->has('presentations')) {
            foreach ($request->presentations as $pKey => $presData) {
                if (!empty($presData['name']) && $presData['name'] !== 'No encontrado') {
                    $allPresentationsString[] = $presData['name'];
                    
                    $presCode = strtoupper(trim($presData['presentation_code'] ?? $presData['codigo_sku'] ?? ''));
                    if (empty($presCode)) {
                        $presCode = 'PRD-' . $product->id . '-' . ($pKey + 1);
                    }

                    $presentation = $product->presentations()->create([
                        'presentation_code' => $presCode,
                        'name' => $presData['name']
                    ]);

                    // Sincronizar automáticamente en la tabla items (Base Macro Unificada)
                    if (\Illuminate\Support\Facades\Schema::hasTable('items') && !empty($presCode)) {
                        \Illuminate\Support\Facades\DB::table('items')->updateOrInsert(
                            ['item_code' => $presCode],
                            [
                                'description' => $presData['name'],
                                'ext_1_detail' => $presData['name'],
                                'inventory_type' => 'PRODUCTO TERMINADO',
                                'item_type' => 'MANUFACTURADO',
                                'inventory_uom' => $product->base_unit ?? 'UND',
                                'is_manufactured' => true,
                                'is_sold' => true,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }

                    // Materiales (fallbacks: materials, packaging)
                    $materials = $presData['materials'] ?? $presData['packaging'] ?? null;
                    if ($materials && is_array($materials)) {
                        foreach ($materials as $pkg) {
                            if (!empty($pkg['item_id'])) {
                                $item = \App\Models\Item::find($pkg['item_id']);
                                if ($item) {
                                    $product->ingredients()->create([
                                        'presentation_id' => $presentation->id,
                                        'material_code' => $item->item_code,
                                        'material_name' => $item->description,
                                        'material_type' => $pkg['type'] ?? $item->inventory_type ?? 'MATERIAL DE EMPAQUE',
                                        'specific_material_type' => $pkg['material'] ?? null,
                                        'unit' => $pkg['unit'] ?? $item->inventory_uom ?? 'UND',
                                        'percentage' => $pkg['percentage'] ?? $pkg['cantidad'] ?? 0,
                                        'quantity' => $pkg['percentage'] ?? $pkg['cantidad'] ?? 0,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            $product->update(['presentation' => implode(', ', $allPresentationsString)]);
        }

        // 3. Pasos de fabricación
        if ($request->has('steps') && is_array($request->steps)) {
            $stepCount = 1;
            foreach ($request->steps as $stepData) {
                if (!empty($stepData['description'])) {
                    $newStep = $product->steps()->create([
                        'step_number' => $stepCount++,
                        'type' => $stepData['type'],
                        'description' => $stepData['description'],
                        'theoretical_time_minutes' => $stepData['time'] ?? null,
                        'target_rpm' => $stepData['rpm'] ?? null,
                    ]);

                    if ($stepData['type'] === 'CARGA' && isset($stepData['ingredients'])) {
                        foreach ($stepData['ingredients'] as $ingData) {
                            if (!empty($ingData['code'])) {
                                $formulaIng = $product->ingredients()->where('material_code', strtoupper($ingData['code']))->whereNull('presentation_id')->first();
                                if ($formulaIng) {
                                    $newStep->ingredients()->create([
                                        'formula_ingredient_id' => $formulaIng->id,
                                        'percentage_allocation' => $ingData['percentage'] ?? 100
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return redirect()->route('productos.index')->with('success', "Producto {$product->name} creado exitosamente.");
    }

    public function update(Request $request, $id)
    {
        $product = \App\Models\Product::findOrFail($id);

        // LOG GLOBAL PARA DEPURACIÓN (Solicitado por el usuario)
        \Illuminate\Support\Facades\Log::info("Iniciando actualización de producto ID: {$id}", [
            'raw_materials' => $request->raw_materials,
            'presentations' => $request->presentations
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'ica_license' => 'nullable|string|max:255',
            'formula_maestra' => 'nullable|string|max:255',
            'vigencia_meses' => 'nullable|integer|min:0',
            'pharmaceutical_form' => 'required|string|max:255',
            'active_ingredient_concentration' => 'nullable|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'raw_materials' => 'array',
            'presentations' => 'array',
        ]);

        $imageName = $product->image;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . strtolower(str_replace(' ', '_', $request->name)) . '.' . $request->image->extension();
            $request->image->move(public_path('img/productos'), $imageName);
        }

        $product->update([
            'name' => $request->name,
            'ica_license' => $request->ica_license,
            'formula_maestra' => $request->formula_maestra,
            'vigencia_meses' => $request->vigencia_meses,
            'pharmaceutical_form' => $request->pharmaceutical_form,
            'active_ingredient_concentration' => $request->active_ingredient_concentration ?? 0,
            'image' => $imageName,
        ]);

        // 1. Materias Primas a granel (solo presentation_id = null)
        // Borramos los ingredientes a granel previos
        $product->ingredients()->whereNull('presentation_id')->delete();
        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $rm) {
                if (!empty($rm['code']) && ($rm['name'] ?? '') !== 'No encontrado') {
                    $product->ingredients()->create([
                        'presentation_id' => null,
                        'material_code' => strtoupper($rm['code']),
                        'material_name' => $rm['name'] ?? 'S/N',
                        'material_type' => 'MATERIA PRIMA',
                        'function' => $rm['function'] ?? null,
                        'unit' => $rm['unit'] ?? 'KIL',
                        'percentage' => $rm['percentage'] ?? 0,
                    ]);
                }
            }
        }

        // 2. Presentaciones y sus materiales
        $keepPresentations = [];
        $allPresentationsString = [];
        if ($request->has('presentations')) {
            foreach ($request->presentations as $pKey => $presData) {
                if (!empty($presData['name']) && $presData['name'] !== 'No encontrado') {
                    $allPresentationsString[] = $presData['name'];
                    
                    $presCode = strtoupper(trim($presData['presentation_code'] ?? $presData['codigo_sku'] ?? ''));
                    if (empty($presCode)) {
                        $presCode = 'PRD-' . $product->id . '-' . (is_numeric($pKey) ? $pKey : ($presentation->id ?? 1));
                    }

                    $presentation = $product->presentations()->updateOrCreate(
                        ['id' => is_numeric($pKey) ? $pKey : null],
                        [
                            'presentation_code' => $presCode,
                            'name' => $presData['name']
                        ]
                    );
                    $keepPresentations[] = $presentation->id;

                    // Sincronizar automáticamente en la tabla items (Base Macro Unificada)
                    if (\Illuminate\Support\Facades\Schema::hasTable('items') && !empty($presCode)) {
                        \Illuminate\Support\Facades\DB::table('items')->updateOrInsert(
                            ['item_code' => $presCode],
                            [
                                'description' => $presData['name'],
                                'ext_1_detail' => $presData['name'],
                                'inventory_type' => 'PRODUCTO TERMINADO',
                                'item_type' => 'MANUFACTURADO',
                                'inventory_uom' => $product->base_unit ?? 'UND',
                                'is_manufactured' => true,
                                'is_sold' => true,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }

                    // LOG PARA DEPURACIÓN (Solicitado por el usuario)
                    \Illuminate\Support\Facades\Log::info("Procesando presentación ID: {$presentation->id}", ['data' => $presData]);

                    // SEGURIDAD RCA: Solo sincronizar materiales SI la llave existe en el request.
                    // Si no viene la llave, asumimos que no hubo cambios y NO borramos nada.
                    $materialsExistInRequest = isset($presData['packaging']) || isset($presData['materials']);
                    $materials = $presData['packaging'] ?? $presData['materials'] ?? null;
                    $keepMaterials = [];
                    
                    if ($materialsExistInRequest && is_array($materials)) {
                        foreach ($materials as $pkg) {
                            $itemId = $pkg['item_id'] ?? null;
                            $ingredientId = $pkg['id'] ?? null;

                            // SI tiene ID, lo agregamos a keep para NO borrarlo, aunque el item_id falle
                            if ($ingredientId) {
                                $keepMaterials[] = $ingredientId;
                            }

                            if ($itemId) {
                                $item = \App\Models\Item::find($itemId);
                                if ($item) {
                                    $valorPorcentaje = $pkg['percentage'] ?? $pkg['cantidad'] ?? 0;
                                    
                                    // Sincronización exacta por ID
                                    $ingredient = $product->ingredients()->updateOrCreate(
                                        [
                                            'id' => $ingredientId,
                                            'presentation_id' => $presentation->id
                                        ],
                                        [
                                            'material_code' => $item->item_code,
                                            'material_name' => $item->description,
                                            'material_type' => $pkg['type'] ?? $item->inventory_type ?? 'MATERIAL DE EMPAQUE',
                                            'specific_material_type' => $pkg['material'] ?? null,
                                            'unit' => $pkg['unit'] ?? $item->inventory_uom ?? 'UND',
                                            'percentage' => $valorPorcentaje,
                                            'quantity' => $valorPorcentaje,
                                        ]
                                    );
                                    // Aseguramos que el ID real (si fue nuevo) esté en keep
                                    if (!$ingredientId) {
                                        $keepMaterials[] = $ingredient->id;
                                    }
                                }
                            }
                        }

                        // SOLO BORRAR si el usuario envió el array de materiales (asumimos sincronización intencional)
                        $product->ingredients()
                            ->where('presentation_id', $presentation->id)
                            ->whereNotIn('id', $keepMaterials)
                            ->delete();
                    }
                }
            }
            // Sincronización de presentaciones: Eliminar las que fueron quitadas del formulario
            $product->presentations()->whereNotIn('id', $keepPresentations)->each(function($p) {
                $p->materials()->delete();
                $p->delete();
            });
            $product->update(['presentation' => implode(', ', $allPresentationsString)]);
        }
        // RCA FIX: Eliminado el else que borraba todo. Si no vienen presentaciones, no tocamos lo que hay.

        // 3. Pasos de fabricación (Delete & Recreate)
        $product->steps()->delete();
        if ($request->has('steps') && is_array($request->steps)) {
            $stepCount = 1;
            foreach ($request->steps as $stepData) {
                if (!empty($stepData['description'])) {
                    $newStep = $product->steps()->create([
                        'step_number' => $stepCount++,
                        'type' => $stepData['type'],
                        'description' => $stepData['description'],
                        'theoretical_time_minutes' => $stepData['time'] ?? null,
                        'target_rpm' => $stepData['rpm'] ?? null,
                    ]);

                    if ($stepData['type'] === 'CARGA' && isset($stepData['ingredients'])) {
                        foreach ($stepData['ingredients'] as $ingData) {
                            if (!empty($ingData['code'])) {
                                $formulaIng = $product->ingredients()->where('material_code', strtoupper($ingData['code']))->whereNull('presentation_id')->first();
                                if ($formulaIng) {
                                    $newStep->ingredients()->create([
                                        'formula_ingredient_id' => $formulaIng->id,
                                        'percentage_allocation' => $ingData['percentage'] ?? 100
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return redirect()->route('productos.show', $product->id)->with('success', "Producto {$product->name} actualizado exitosamente.");
    }

    /**
     * Elimina un producto y todas sus dependencias (fórmula, presentaciones, planes EBR, etc.)
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $productName = $product->name;

        // 1. Encontrar IDs de presentaciones para limpiar tablas pivote/transaccionales
        $presentationIds = $product->presentations()->pluck('id');

        // 2. Limpiar registros transaccionales (Órdenes de Producción, Despejes, etc.)
        // Limpiamos op_presentations ligado a las presentaciones de este producto
        DB::table('op_presentations')->whereIn('presentation_id', $presentationIds)->delete();

        // Limpiamos OPs asociadas al producto y sus hijos
        \App\Models\ProductionOrder::where('product_id', $product->id)->each(function($order) {
            $order->opPresentations()->delete();
            $order->lineClearances()->delete();
            
            $dispensing = \App\Models\Dispensing::where('production_order_id', $order->id)->first();
            if ($dispensing) {
                \App\Models\DispensingDetail::where('dispensing_id', $dispensing->id)->delete();
                $dispensing->delete();
            }
            $order->delete();
        });

        // 3. Eliminar ingredientes de la fórmula (Bulk)
        $product->ingredients()->delete();

        // 4. Eliminar pasos de fabricación legados (si existen)
        $product->steps()->delete();

        // 5. Eliminar Planes de Manufactura (EBR) y sus hijos
        $product->manufacturingPlans()->each(function($plan) {
            $plan->steps()->each(function($step) {
                $step->ingredients()->delete();
                $step->delete();
            });
            $plan->delete();
        });

        // 6. Eliminar Presentaciones y sus materiales específicos
        // Se hace DESPUÉS de limpiar op_presentations para evitar Foreign Key error
        $product->presentations()->each(function($presentation) {
            $presentation->materials()->delete();
            $presentation->delete();
        });

        // 7. Finalmente borrar el producto
        $product->delete();

        return redirect()->route('productos.index')->with('success', "El producto '{$productName}' y todos sus registros asociados han sido eliminados permanentemente.");
    }

    /**
     * Eliminar un plan de manufactura (Instructivo Maestro)
     */
    public function deleteInstructivo($id)
    {
        try {
            DB::beginTransaction();
            $plan = ProductManufacturingPlan::findOrFail($id);
            
            // Eliminar dependencias (pasos e ingredientes)
            foreach ($plan->steps as $step) {
                $step->ingredients()->delete();
                $step->delete();
            }
            
            $plan->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Instructivo Maestro eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar instructivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
