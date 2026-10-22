<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductManufacturingPlan;
use App\Models\FormulaIngredient;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\Dispensing;
use App\Models\DispensingDetail;
use App\Data\MasterItemsCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * CONTROLADOR DE CATÁLOGO DE PRODUCTOS Y EBR MASTER BUILDER - ProductController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Administra las Fórmulas Maestras de Aurofarma y sus Instructivos de Manufactura:
 * 1. Catálogo de Productos (`index`, `show`, `imprimirFicha`):
 *    Muestra la ficha técnica del producto, su composición porcentual (Materias Primas
 *    a granel con `presentation_id = null`) y sus Materiales de Envase/Empaque
 *    asociados a cada Presentación Comercial (`presentation_id != null`).
 * 2. Creación y Edición (`create`, `store`, `edit`, `update`, `destroy`):
 *    Guarda la fórmula maestra y sincroniza las presentaciones comerciales con la
 *    tabla unificada `items`.
 * 3. Configurador EBR (`editInstructivo`, `updateInstructivo`, `deleteInstructivo`):
 *    Permite diseñar los pasos del instructivo de fabricación (tiempos de agitación,
 *    RPM, mallas, controles en proceso IPC e ingredientes por etapa).
 * ============================================================================
 */
class ProductController extends Controller
{
    /**
     * EBR Master Builder: Mostrar formulario de edición del Instructivo Maestro.
     */
    public function editInstructivo($id)
    {
        $producto = Product::with(['ingredients', 'activePlan.steps.ingredients'])->findOrFail($id);

        $plan = $producto->activePlan ?: new ProductManufacturingPlan([
            'product_id'         => $id,
            'master_code_header' => null,
            'internal_code'      => null,
            'version'            => '01',
            'issue_date'         => now(),
            'active'             => true,
        ]);

        return view('productos.instructivo.edit', compact('producto', 'plan'));
    }

    /**
     * EBR Master Builder: Guardar o actualizar el Instructivo Maestro de Manufactura.
     */
    public function updateInstructivo(Request $request, $id)
    {
        $producto = Product::findOrFail($id);

        $request->validate([
            'master_code_header'       => 'required|string',
            'internal_code'            => 'required|string',
            'version'                  => 'required|string',
            'issue_date'               => 'required|date',
            'ica_registry'             => 'nullable|string',
            'objective'                => 'nullable|string',
            'requirements'             => 'nullable|string',
            'equipment'                => 'nullable|string',
            'potency_adjustment_logic' => 'nullable|string',
            'observations'             => 'nullable|string',
            'sterilization_method'     => 'nullable|string',
            'master_batch_size'        => 'nullable|numeric',
            'steps'                    => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $plan = $producto->activePlan;
            if (!$plan) {
                $plan = new ProductManufacturingPlan(['product_id' => $producto->id]);
            }

            $plan->fill([
                'master_code'              => $request->master_code_header,
                'master_code_header'       => $request->master_code_header,
                'internal_code'            => $request->internal_code,
                'version'                  => $request->version,
                'issue_date'               => $request->issue_date,
                'ica_registry'             => $request->ica_registry,
                'objective'                => $request->objective,
                'requirements'             => $request->requirements,
                'equipment'                => $request->equipment,
                'potency_adjustment_logic' => $request->potency_adjustment_logic,
                'observations'             => $request->observations,
                'sterilization_method'     => $request->sterilization_method,
                'master_batch_size'        => $request->master_batch_size,
                'active'                   => true,
            ]);
            $plan->save();

            // Limpiar pasos previos e ingredientes de pasos antes de recrearlos
            foreach ($plan->steps as $oldStep) {
                $oldStep->ingredients()->delete();
            }
            $plan->steps()->delete();

            if ($request->has('steps')) {
                foreach ($request->steps as $index => $stepData) {
                    $step = $plan->steps()->create([
                        'step_number'              => $index + 1,
                        'type'                     => $stepData['type'],
                        'description'              => $stepData['description'],
                        'theoretical_time_minutes' => $stepData['mixing_time'] ?? $stepData['theoretical_time_minutes'] ?? null,
                        'target_rpm'               => $stepData['agitation_speed'] ?? $stepData['target_rpm'] ?? null,
                        'mesh_size'                => $stepData['mesh_size'] ?? null,
                        'ipc_test_type'            => $stepData['ipc_test_type'] ?? null,
                        'ipc_specification'        => $stepData['ipc_specification'] ?? null,
                    ]);

                    if (isset($stepData['ingredients'])) {
                        foreach ($stepData['ingredients'] as $ingData) {
                            if (isset($ingData['id'])) {
                                $step->ingredients()->create([
                                    'formula_ingredient_id' => $ingData['id'],
                                    'unit'                  => $ingData['unit'] ?? 'KG',
                                    'theoretical_quantity'  => $ingData['theoretical_quantity'] ?? 0,
                                    'percentage_allocation' => 0,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Instructivo Maestro guardado correctamente.',
                    'redirect' => route('productos.show', $id),
                ]);
            }

            return redirect()->route('productos.show', $id)->with('success', 'Instructivo EBR actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar instructivo: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Error al guardar instructivo: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Auto-healing de esquema para asegurar las nuevas columnas de ficha inicial de producto en Supabase / Vercel.
     */
    protected function ensureSchema()
    {
        try {
            // 1. Sentencias DDL nativas PostgreSQL (Supabase) compatibles con Transaction Pooler (puerto 6543)
            $pgStatements = [
                "ALTER TABLE products ADD COLUMN IF NOT EXISTS active_ingredient TEXT NULL",
                "ALTER TABLE products ADD COLUMN IF NOT EXISTS maquilador TEXT NULL",
                "ALTER TABLE products ADD COLUMN IF NOT EXISTS vida_util_unidad VARCHAR(30) DEFAULT 'MESES'",
                "ALTER TABLE products ADD COLUMN IF NOT EXISTS active_ingredients_json TEXT NULL",
                "ALTER TABLE products ADD COLUMN IF NOT EXISTS maquiladores_json TEXT NULL",
                "ALTER TABLE products ADD COLUMN IF NOT EXISTS presentations_json TEXT NULL",
                "ALTER TABLE products ALTER COLUMN image TYPE TEXT",
                "ALTER TABLE product_presentations ADD COLUMN IF NOT EXISTS quantity_value NUMERIC(12,2) NULL",
                "ALTER TABLE product_presentations ADD COLUMN IF NOT EXISTS unit_measure VARCHAR(20) NULL",
                "ALTER TABLE product_presentations ADD COLUMN IF NOT EXISTS container_type VARCHAR(255) NULL",
            ];

            foreach ($pgStatements as $sql) {
                try {
                    DB::statement($sql);
                } catch (\Throwable $inner) {
                    // Fallback silencioso si el motor local es SQLite/MySQL
                }
            }
        } catch (\Throwable $e) {
            // Continuar de forma segura
        }

        return true;
    }

    /**
     * Lista todos los productos del catálogo maestro con soporte de búsqueda y filtros dinámicos.
     */
    public function index()
    {
        $this->ensureSchema();

        $products = Product::orderBy('name')->get()->map(function ($product) {
            return [
                'id'                  => $product->id,
                'name'                => $product->name,
                'full_name'           => collect([$product->name, $product->presentation])->filter()->implode(' - '),
                'image'               => $product->image ?? strtolower(str_replace(' ', '-', $product->name)) . '.png',
                'ica_license'         => $product->ica_license,
                'pharmaceutical_form' => $product->pharmaceutical_form,
                'presentation'        => $product->presentation,
                'active_ingredient'   => $product->active_ingredient,
                'maquilador'          => $product->maquilador,
                'vigencia_meses'      => $product->vigencia_meses,
            ];
        });

        return view('productos.index', compact('products'));
    }

    /**
     * Muestra la ficha técnica completa de un producto, su fórmula y presentaciones.
     */
    public function show($id)
    {
        $this->ensureSchema();

        $productDb = Product::with(['ingredients.presentation', 'presentations'])->findOrFail($id);

        $rawMaterials = [];
        $packaging = [];

        foreach ($productDb->ingredients as $ing) {
            $itemData = [
                'code'                   => $ing->material_code,
                'description'            => $ing->material_name,
                'tipo_material'          => $ing->material_type,
                'material_clasificacion' => $ing->specific_material_type ?? 'N.A.',
                'function'               => $ing->function ?: '-',
                'quantity'               => floatval($ing->percentage),
                'unit'                   => $ing->unit,
                'presentation_name'      => $ing->presentation ? $ing->presentation->name : 'Universal',
            ];

            if (strtoupper((string) $ing->material_type) === 'MATERIA PRIMA') {
                $rawMaterials[] = $itemData;
            } else {
                $packaging[] = $itemData;
            }
        }

        $product = [
            'id'                              => $productDb->id,
            'name'                            => $productDb->name,
            'presentation_name'               => collect($productDb->presentations->pluck('name'))->implode(', ') ?: ($productDb->presentation ?? ''),
            'image'                           => $productDb->image ?? strtolower(str_replace(' ', '-', $productDb->name)) . '.png',
            'pharmaceutical_form'             => $productDb->pharmaceutical_form ?? 'POLVO ORAL',
            'presentations'                   => $productDb->presentations,
            'status'                          => $productDb->status,
            'ica_license'                     => $productDb->ica_license,
            'formula_maestra'                 => $productDb->formula_maestra,
            'vigencia_meses'                  => $productDb->vigencia_meses,
            'vida_util_unidad'                => $productDb->vida_util_unidad ?? 'MESES',
            'active_ingredients_list'         => $productDb->active_ingredients_list,
            'maquiladores_list'               => $productDb->maquiladores_list,
            'manufactured_lots'               => $productDb->productionOrders()->whereYear('created_at', date('Y'))->count(),
            'base_batch_size'                 => number_format($productDb->base_batch_size, 2),
            'base_unit'                       => $productDb->base_unit,
            'raw_materials'                   => $rawMaterials,
            'packaging'                       => $packaging,
            'files'                           => [],
            'steps'                           => $productDb->steps()->with('ingredients.formulaIngredient')->get(),
            'product_code'                    => collect($productDb->presentations->pluck('presentation_code'))->implode(', '),
            'active_plan'                     => $productDb->activePlan,
            'active_ingredient_concentration' => $productDb->active_ingredient_concentration,
        ];

        return view('productos.show', [
            'product'   => $product,
            'productDb' => $productDb,
        ]);
    }

    /**
     * Genera la vista de impresión de la Ficha Técnica del Producto.
     */
    public function imprimirFicha($id)
    {
        $productDb = Product::with(['ingredients.presentation', 'presentations'])->findOrFail($id);

        $rawMaterials = [];
        $packaging = [];

        foreach ($productDb->ingredients as $ing) {
            $itemData = [
                'code'                   => $ing->material_code,
                'description'            => $ing->material_name,
                'tipo_material'          => $ing->material_type,
                'material_clasificacion' => $ing->specific_material_type ?? 'N.A.',
                'function'               => $ing->function ?: '-',
                'quantity'               => floatval($ing->percentage),
                'unit'                   => $ing->unit,
                'presentation_name'      => $ing->presentation ? $ing->presentation->name : 'Universal',
            ];

            if (strtoupper((string) $ing->material_type) === 'MATERIA PRIMA') {
                $rawMaterials[] = $itemData;
            } else {
                $packaging[] = $itemData;
            }
        }

        $product = [
            'id'                              => $productDb->id,
            'name'                            => $productDb->name,
            'presentation_name'               => collect($productDb->presentations->pluck('name'))->implode(', '),
            'pharmaceutical_form'             => $productDb->pharmaceutical_form ?? 'POLVO ORAL',
            'status'                          => $productDb->status,
            'base_batch_size'                 => number_format($productDb->base_batch_size, 2),
            'base_unit'                       => $productDb->base_unit,
            'raw_materials'                   => $rawMaterials,
            'packaging'                       => $packaging,
            'product_code'                    => collect($productDb->presentations->pluck('presentation_code'))->implode(', '),
            'active_ingredient_concentration' => $productDb->active_ingredient_concentration,
        ];

        return view('productos.imprimir', compact('product'));
    }

    /**
     * Muestra el formulario para crear un nuevo producto en el catálogo (Paso 1: Ficha Técnica Inicial).
     */
    public function create()
    {
        $this->ensureSchema();

        $all_items = Item::select('id', 'item_code', 'description', 'inventory_uom')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'id'     => $item->id,
                    'codigo' => trim((string) $item->item_code),
                    'nombre' => trim((string) $item->description),
                    'unidad' => (string) ($item->inventory_uom ?? 'UND'),
                ];
            });

        $maquiladores = [];
        try {
            if (Schema::hasTable('maquiladores')) {
                $maquiladores = \App\Models\Maquilador::orderBy('nombre')->pluck('nombre')->all();
            }
        } catch (\Throwable $e) {}

        if (empty($maquiladores)) {
            $maquiladores = [
                'QOPPA PHARMA', 'INSEC', 'RATAR', 'DECNO', 'ITALCOL S.A. FUNZA',
                'ITALCOL S.A. CARTAGENA', 'PROQUIVET', 'PRONATUCOL', 'GDI',
                'LIXMAR', 'ARJONA', 'FARMANDINA', 'FARMATEC', 'SFC',
                'PLANTA PROPIA AUROFARMA',
            ];
        }

        return view('productos.crear', compact('all_items', 'maquiladores'));
    }

    /**
     * Muestra el formulario para editar un producto existente y sus presentaciones.
     */
    public function edit($id)
    {
        $producto = Product::with(['ingredients', 'presentations.materials.item', 'presentations.packaging_materials.item'])->findOrFail($id);

        // Reparación automática si existen materiales de empaque sin presentation_id asociado
        if ($producto->presentations->count() > 0) {
            $orphanCount = FormulaIngredient::where('product_id', $id)
                ->whereNull('presentation_id')
                ->where('material_type', '!=', 'MATERIA PRIMA')
                ->count();

            if ($orphanCount > 0) {
                $firstPresId = $producto->presentations->first()->id;
                FormulaIngredient::where('product_id', $id)
                    ->whereNull('presentation_id')
                    ->where('material_type', '!=', 'MATERIA PRIMA')
                    ->update(['presentation_id' => $firstPresId]);

                $producto->load(['presentations.materials.item']);
            }
        }

        $all_items = Item::select('id', 'item_code', 'description', 'inventory_uom')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'id'     => $item->id,
                    'codigo' => trim((string) $item->item_code),
                    'nombre' => trim((string) $item->description),
                    'unidad' => (string) ($item->inventory_uom ?? 'UND'),
                ];
            });

        return view('productos.editar', compact('producto', 'all_items'));
    }

    /**
     * API de autocompletado de ítems por código (consulta catálogo maestro y tabla `items`).
     */
    public function apiGetItem($codigo)
    {
        $code = strtoupper(trim((string) $codigo));

        $exactDesc = MasterItemsCatalog::find($code);
        if ($exactDesc !== null) {
            return response()->json([
                'success'      => true,
                'codigo'       => $code,
                'name'         => $exactDesc,
                'description'  => $exactDesc,
                'ext_1_detail' => '',
                'unit'         => 'UND',
            ]);
        }

        $item = DB::table('items')
            ->whereRaw('UPPER(CAST(item_code AS TEXT)) = ?', [$code])
            ->select('id', 'item_code', 'description', 'ext_1_detail', 'inventory_uom')
            ->first();

        if (!$item) {
            $item = DB::table('items')
                ->whereRaw('UPPER(CAST(item_code AS TEXT)) LIKE ?', ["%{$code}%"])
                ->select('id', 'item_code', 'description', 'ext_1_detail', 'inventory_uom')
                ->first();
        }

        if ($item) {
            return response()->json([
                'success'      => true,
                'codigo'       => (string) $item->item_code,
                'name'         => (string) $item->description,
                'description'  => (string) $item->description,
                'ext_1_detail' => (string) ($item->ext_1_detail ?? ''),
                'unit'         => (string) ($item->inventory_uom ?? 'UND'),
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Ítem no encontrado en catálogo'], 404);
    }

    /**
     * Almacena un nuevo producto con su ficha inicial (Activos, Presentaciones + Envase, Vida Útil, Maquiladores + Lote Min/Max e Imagen opcional).
     */
    public function store(Request $request)
    {
        $this->ensureSchema();

        $request->validate([
            'name'                            => 'required|string|max:255',
            'ica_license'                     => 'nullable|string|max:255',
            'formula_maestra'                 => 'nullable|string|max:255',
            'vigencia_meses'                  => 'nullable|numeric|min:0',
            'vida_util_unidad'                => 'nullable|string|max:30',
            'pharmaceutical_form'             => 'required|string|max:255',
            'active_ingredient_concentration' => 'nullable|numeric|min:0',
            'active_ingredients'              => 'nullable|array',
            'presentations'                   => 'nullable|array',
            'maquiladores'                    => 'nullable|array',
            'image'                           => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'raw_materials'                   => 'nullable|array',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $mime = $file->getMimeType() ?: 'image/png';
            $base64Image = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

            $targetDir = public_path('img/productos');
            if (!env('VERCEL') && (is_writable($targetDir) || (!file_exists($targetDir) && @mkdir($targetDir, 0775, true)))) {
                try {
                    $fileName = time() . '_' . preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $request->name))) . '.' . $file->extension();
                    $file->move($targetDir, $fileName);
                    $imageName = $fileName;
                } catch (\Throwable $e) {
                    $imageName = $base64Image;
                }
            } else {
                $imageName = $base64Image;
            }
        }

        // Procesar Principios Activos
        $activeIngredientsList = [];
        if ($request->has('active_ingredients') && is_array($request->active_ingredients)) {
            foreach ($request->active_ingredients as $act) {
                $actName = trim($act['name'] ?? '');
                $actConc = trim((string) ($act['concentration'] ?? ''));
                $actUnit = trim($act['unit'] ?? '%');
                if ($actName !== '' || $actConc !== '') {
                    $activeIngredientsList[] = [
                        'name'          => $actName ?: 'Principio Activo',
                        'concentration' => $actConc,
                        'unit'          => $actUnit ?: '%',
                    ];
                }
            }
        }

        $primaryActiveName = !empty($activeIngredientsList)
            ? implode(' + ', array_map(fn($a) => $a['name'] . ($a['concentration'] !== '' ? ' (' . $a['concentration'] . ' ' . $a['unit'] . ')' : ''), $activeIngredientsList))
            : ($request->input('active_ingredient') ?? null);

        $primaryActiveConc = !empty($activeIngredientsList) && is_numeric($activeIngredientsList[0]['concentration'])
            ? (float) $activeIngredientsList[0]['concentration']
            : (float) ($request->active_ingredient_concentration ?? 0);

        // Procesar Vida Útil
        $vidaUtilUnidad = strtoupper(trim($request->input('vida_util_unidad', 'MESES')));
        $rawVigencia = $request->input('vigencia_meses');
        $vigenciaMeses = ($rawVigencia !== null && $rawVigencia !== '') ? (int) round((float) $rawVigencia) : null;

        // Procesar Maquiladores y Tamaños de Lote Mínimo / Máximo
        $maquiladoresList = [];
        if ($request->has('maquiladores') && is_array($request->maquiladores)) {
            foreach ($request->maquiladores as $maq) {
                $maqName = trim($maq['name'] ?? '');
                $minBatch = ($maq['min_batch'] ?? '') !== '' ? (float) $maq['min_batch'] : null;
                $minUnit  = strtoupper(trim($maq['min_unit'] ?? 'Kg')) === 'L' ? 'L' : 'Kg';
                $maxBatch = ($maq['max_batch'] ?? '') !== '' ? (float) $maq['max_batch'] : null;
                $maxUnit  = strtoupper(trim($maq['max_unit'] ?? 'Kg')) === 'L' ? 'L' : 'Kg';

                if ($maqName !== '' || $minBatch !== null || $maxBatch !== null) {
                    $maquiladoresList[] = [
                        'name'      => $maqName ?: 'PLANTA PRINCIPAL',
                        'min_batch' => $minBatch,
                        'min_unit'  => $minUnit,
                        'max_batch' => $maxBatch,
                        'max_unit'  => $maxUnit,
                    ];

                    if ($maqName !== '') {
                        try {
                            Maquilador::firstOrCreate(
                                ['name' => strtoupper($maqName)],
                                ['status' => 'ACTIVO']
                            );
                        } catch (\Throwable $e) {
                            // Ignore duplicate or schema issues
                        }
                    }
                }
            }
        }

        $primaryMaquilador = !empty($maquiladoresList)
            ? implode(', ', array_unique(array_column($maquiladoresList, 'name')))
            : ($request->input('maquilador') ?? null);

        $baseBatchSize = !empty($maquiladoresList) && !empty($maquiladoresList[0]['max_batch'])
            ? (float) $maquiladoresList[0]['max_batch']
            : (!empty($maquiladoresList) && !empty($maquiladoresList[0]['min_batch']) ? (float) $maquiladoresList[0]['min_batch'] : 1000);

        $baseUnit = !empty($maquiladoresList) && !empty($maquiladoresList[0]['max_unit'])
            ? $maquiladoresList[0]['max_unit']
            : 'KG';

        $product = DB::transaction(function () use (
            $request,
            $imageName,
            $activeIngredientsList,
            $primaryActiveName,
            $primaryActiveConc,
            $vigenciaMeses,
            $vidaUtilUnidad,
            $maquiladoresList,
            $primaryMaquilador,
            $baseBatchSize,
            $baseUnit
        ) {
            $product = Product::create([
                'name'                            => trim($request->name),
                'ica_license'                     => $request->ica_license ? trim($request->ica_license) : null,
                'formula_maestra'                 => $request->formula_maestra,
                'vigencia_meses'                  => $vigenciaMeses,
                'vida_util_unidad'                => $vidaUtilUnidad,
                'pharmaceutical_form'             => strtoupper(trim($request->pharmaceutical_form)),
                'active_ingredient'               => $primaryActiveName,
                'active_ingredient_concentration' => $primaryActiveConc,
                'active_ingredients_json'         => $activeIngredientsList,
                'maquilador'                      => $primaryMaquilador,
                'maquiladores_json'               => $maquiladoresList,
                'image'                           => $imageName,
                'base_batch_size'                 => $baseBatchSize,
                'base_unit'                       => $baseUnit,
                'status'                          => 'ACTIVO',
            ]);

            // 1. Ingredientes a granel (si se envían en pasos posteriores)
            if ($request->has('raw_materials') && is_array($request->raw_materials)) {
                foreach ($request->raw_materials as $rm) {
                    if (!empty($rm['code']) && ($rm['name'] ?? '') !== 'No encontrado') {
                        $product->ingredients()->create([
                            'presentation_id' => null,
                            'material_code'   => strtoupper($rm['code']),
                            'material_name'   => $rm['name'] ?? 'S/N',
                            'material_type'   => 'MATERIA PRIMA',
                            'function'        => $rm['function'] ?? null,
                            'unit'            => $rm['unit'] ?? 'KIL',
                            'percentage'      => $rm['percentage'] ?? 0,
                        ]);
                    }
                }
            }

            // 2. Presentaciones Comerciales y Tipo de Envase
            $allPresentationsString = [];
            $presentationsJsonList = [];

            if ($request->has('presentations') && is_array($request->presentations)) {
                $presIndex = 0;
                foreach ($request->presentations as $pKey => $presData) {
                    $presIndex++;
                    $qtyVal = isset($presData['quantity']) && $presData['quantity'] !== '' ? (float) $presData['quantity'] : null;
                    $rawUnit = trim($presData['unit'] ?? 'mL');
                    $unitMap = ['G' => 'g', 'KG' => 'Kg', 'ML' => 'mL', 'L' => 'L', 'UND' => 'UND'];
                    $unitMeasure = $unitMap[strtoupper($rawUnit)] ?? $rawUnit;
                    $containerType = trim($presData['container_type'] ?? '');
                    $explicitName = trim($presData['name'] ?? '');

                    if ($qtyVal === null && $explicitName === '' && $containerType === '') {
                        continue;
                    }

                    // Construir nombre legible de la presentación
                    if ($explicitName !== '' && $explicitName !== 'No encontrado') {
                        $presDisplayName = $explicitName;
                    } else {
                        $qtyFormatted = $qtyVal !== null ? rtrim(rtrim(number_format($qtyVal, 2, '.', ''), '0'), '.') : '';
                        $presDisplayName = trim("{$qtyFormatted} {$unitMeasure}");
                        if ($containerType !== '') {
                            $presDisplayName .= " ({$containerType})";
                        }
                    }

                    $allPresentationsString[] = $presDisplayName;

                    $presCode = strtoupper(trim($presData['presentation_code'] ?? $presData['code'] ?? $presData['codigo_sku'] ?? ''));
                    if (empty($presCode)) {
                        $presCode = 'PRD-' . $product->id . '-P' . $presIndex;
                    }

                    $presentation = $product->presentations()->create([
                        'presentation_code'  => $presCode,
                        'name'               => $presDisplayName,
                        'quantity_value'     => $qtyVal,
                        'unit_measure'       => $unitMeasure,
                        'container_type'     => $containerType ?: null,
                        'theoretical_weight' => $presData['theoretical_weight'] ?? ($qtyVal ?? 0),
                    ]);

                    $presentationsJsonList[] = [
                        'id'                => $presentation->id,
                        'presentation_code' => $presCode,
                        'name'              => $presDisplayName,
                        'quantity'          => $qtyVal,
                        'unit'              => $unitMeasure,
                        'container_type'    => $containerType,
                    ];

                    $materials = $presData['materials'] ?? $presData['packaging'] ?? null;
                    if ($materials && is_array($materials)) {
                        foreach ($materials as $pkg) {
                            if (!empty($pkg['item_id'])) {
                                $item = Item::find($pkg['item_id']);
                                if ($item) {
                                    $product->ingredients()->create([
                                        'presentation_id'        => $presentation->id,
                                        'material_code'          => $item->item_code,
                                        'material_name'          => $item->description,
                                        'material_type'          => $pkg['type'] ?? $item->inventory_type ?? 'MATERIAL DE EMPAQUE',
                                        'specific_material_type' => $pkg['material'] ?? null,
                                        'unit'                   => $pkg['unit'] ?? $item->inventory_uom ?? 'UND',
                                        'percentage'             => $pkg['percentage'] ?? $pkg['cantidad'] ?? 0,
                                        'quantity'               => $pkg['percentage'] ?? $pkg['cantidad'] ?? 0,
                                    ]);
                                }
                            }
                        }
                    }
                }

                $product->update([
                    'presentation'       => implode(', ', $allPresentationsString),
                    'presentations_json' => $presentationsJsonList,
                ]);
            }

            // 3. Pasos de fabricación iniciales (si se envían desde el formulario)
            if ($request->has('steps') && is_array($request->steps)) {
                $stepCount = 1;
                foreach ($request->steps as $stepData) {
                    if (!empty($stepData['description'])) {
                        $newStep = $product->steps()->create([
                            'step_number'              => $stepCount++,
                            'type'                     => $stepData['type'],
                            'description'              => $stepData['description'],
                            'theoretical_time_minutes' => $stepData['time'] ?? null,
                            'target_rpm'               => $stepData['rpm'] ?? null,
                        ]);

                        if ($stepData['type'] === 'CARGA' && isset($stepData['ingredients'])) {
                            foreach ($stepData['ingredients'] as $ingData) {
                                if (!empty($ingData['code'])) {
                                    $formulaIng = $product->ingredients()
                                        ->where('material_code', strtoupper($ingData['code']))
                                        ->whereNull('presentation_id')
                                        ->first();
                                    if ($formulaIng) {
                                        $newStep->ingredients()->create([
                                            'formula_ingredient_id' => $formulaIng->id,
                                            'percentage_allocation' => $ingData['percentage'] ?? 100,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $product;
        });

        return redirect()->route('productos.show', $product->id)->with('success', "Producto «{$product->name}» creado exitosamente. Ahora puedes configurar su Fórmula Maestra y Materiales de Empaque cuando lo desees.");
    }

    /**
     * Actualiza un producto existente, sincronizando sus materias primas y presentaciones.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        Log::info("Iniciando actualización de producto ID: {$id}", [
            'raw_materials' => $request->raw_materials,
            'presentations' => $request->presentations,
        ]);

        $request->validate([
            'name'                            => 'required|string|max:255',
            'ica_license'                     => 'nullable|string|max:255',
            'formula_maestra'                 => 'nullable|string|max:255',
            'vigencia_meses'                  => 'nullable|integer|min:0',
            'pharmaceutical_form'             => 'required|string|max:255',
            'active_ingredient_concentration' => 'nullable|numeric|min:0|max:100',
            'image'                           => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'raw_materials'                   => 'array',
            'presentations'                   => 'array',
        ]);

        $imageName = $product->image;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . strtolower(str_replace(' ', '_', $request->name)) . '.' . $request->image->extension();
            $request->image->move(public_path('img/productos'), $imageName);
        }

        DB::transaction(function () use ($request, $product, $imageName) {
            $product->update([
                'name'                            => $request->name,
                'ica_license'                     => $request->ica_license,
                'formula_maestra'                 => $request->formula_maestra,
                'vigencia_meses'                  => $request->vigencia_meses,
                'pharmaceutical_form'             => $request->pharmaceutical_form,
                'active_ingredient_concentration' => $request->active_ingredient_concentration ?? 0,
                'image'                           => $imageName,
            ]);

            // 1. Materias Primas a granel (presentation_id = null)
            // Actualizamos por código/ID para conservar las llaves foráneas en instructivos EBR y dispensaciones
            $keepRawIds = [];
            if ($request->has('raw_materials')) {
                foreach ($request->raw_materials as $rm) {
                    if (!empty($rm['code']) && ($rm['name'] ?? '') !== 'No encontrado') {
                        $matCode = strtoupper(trim($rm['code']));
                        $existingRaw = $product->ingredients()
                            ->whereNull('presentation_id')
                            ->where('material_code', $matCode)
                            ->first();

                        if ($existingRaw) {
                            $existingRaw->update([
                                'material_name' => $rm['name'] ?? 'S/N',
                                'material_type' => 'MATERIA PRIMA',
                                'function'      => $rm['function'] ?? null,
                                'unit'          => $rm['unit'] ?? 'KIL',
                                'percentage'    => $rm['percentage'] ?? 0,
                            ]);
                            $keepRawIds[] = $existingRaw->id;
                        } else {
                            $createdRaw = $product->ingredients()->create([
                                'presentation_id' => null,
                                'material_code'   => $matCode,
                                'material_name'   => $rm['name'] ?? 'S/N',
                                'material_type'   => 'MATERIA PRIMA',
                                'function'        => $rm['function'] ?? null,
                                'unit'            => $rm['unit'] ?? 'KIL',
                                'percentage'      => $rm['percentage'] ?? 0,
                            ]);
                            $keepRawIds[] = $createdRaw->id;
                        }
                    }
                }
            }

            // Eliminar materias primas a granel quitadas del formulario (limpiando primero referencias en plan_step_ingredients)
            $removedRawIds = $product->ingredients()
                ->whereNull('presentation_id')
                ->whereNotIn('id', $keepRawIds)
                ->pluck('id');

            if ($removedRawIds->isNotEmpty()) {
                DB::table('plan_step_ingredients')->whereIn('formula_ingredient_id', $removedRawIds)->delete();
                DB::table('product_step_ingredients')->whereIn('formula_ingredient_id', $removedRawIds)->delete();
                $product->ingredients()->whereIn('id', $removedRawIds)->delete();
            }

            // 2. Presentaciones y sus materiales de envase/empaque
            $keepPresentations = [];
            $allPresentationsString = [];
            if ($request->has('presentations')) {
                $presIndex = 0;
                foreach ($request->presentations as $pKey => $presData) {
                    $presIndex++;
                    if (!empty($presData['name']) && $presData['name'] !== 'No encontrado') {
                        $allPresentationsString[] = $presData['name'];

                        $presCode = strtoupper(trim($presData['presentation_code'] ?? $presData['code'] ?? $presData['codigo_sku'] ?? ''));
                        if (empty($presCode)) {
                            $presCode = 'PRD-' . $product->id . '-' . (is_numeric($pKey) ? $pKey : $presIndex);
                        }

                        $updatePresAttrs = [
                            'presentation_code' => $presCode,
                            'name'              => $presData['name'],
                        ];
                        if (isset($presData['theoretical_weight'])) {
                            $updatePresAttrs['theoretical_weight'] = $presData['theoretical_weight'];
                        }

                        $presentation = $product->presentations()->updateOrCreate(
                            ['id' => is_numeric($pKey) ? $pKey : null],
                            $updatePresAttrs
                        );
                        $keepPresentations[] = $presentation->id;

                        if (Schema::hasTable('items') && !empty($presCode)) {
                            DB::table('items')->updateOrInsert(
                                ['item_code' => $presCode],
                                [
                                    'description'     => $presData['name'],
                                    'ext_1_detail'    => $presData['name'],
                                    'inventory_type'  => 'PRODUCTO TERMINADO',
                                    'item_type'       => 'MANUFACTURADO',
                                    'inventory_uom'   => $product->base_unit ?? 'UND',
                                    'is_manufactured' => true,
                                    'is_sold'         => true,
                                    'updated_at'      => now(),
                                ]
                            );
                        }

                        Log::info("Procesando presentación ID: {$presentation->id}", ['data' => $presData]);

                        // Solo sincronizar materiales si el formulario envió las llaves 'packaging' o 'materials'
                        $materialsExistInRequest = isset($presData['packaging']) || isset($presData['materials']);
                        $materials = $presData['packaging'] ?? $presData['materials'] ?? null;
                        $keepMaterials = [];

                        if ($materialsExistInRequest && is_array($materials)) {
                            foreach ($materials as $pkg) {
                                $itemId = $pkg['item_id'] ?? null;
                                $ingredientId = $pkg['id'] ?? null;

                                if ($ingredientId) {
                                    $keepMaterials[] = $ingredientId;
                                }

                                if ($itemId) {
                                    $item = Item::find($itemId);
                                    if ($item) {
                                        $valorPorcentaje = $pkg['percentage'] ?? $pkg['cantidad'] ?? 0;

                                        $ingredient = $product->ingredients()->updateOrCreate(
                                            [
                                                'id'              => $ingredientId,
                                                'presentation_id' => $presentation->id,
                                            ],
                                            [
                                                'material_code'          => $item->item_code,
                                                'material_name'          => $item->description,
                                                'material_type'          => $pkg['type'] ?? $item->inventory_type ?? 'MATERIAL DE EMPAQUE',
                                                'specific_material_type' => $pkg['material'] ?? null,
                                                'unit'                   => $pkg['unit'] ?? $item->inventory_uom ?? 'UND',
                                                'percentage'             => $valorPorcentaje,
                                                'quantity'               => $valorPorcentaje,
                                            ]
                                        );

                                        if (!$ingredientId) {
                                            $keepMaterials[] = $ingredient->id;
                                        }
                                    }
                                }
                            }

                            $product->ingredients()
                                ->where('presentation_id', $presentation->id)
                                ->whereNotIn('id', $keepMaterials)
                                ->delete();
                        }
                    }
                }

                $product->presentations()->whereNotIn('id', $keepPresentations)->each(function ($p) {
                    $p->materials()->delete();
                    $p->delete();
                });
                $product->update(['presentation' => implode(', ', $allPresentationsString)]);
            }

            // 3. Pasos de fabricación legados
            $product->steps()->each(function ($s) {
                $s->ingredients()->delete();
            });
            $product->steps()->delete();

            if ($request->has('steps') && is_array($request->steps)) {
                $stepCount = 1;
                foreach ($request->steps as $stepData) {
                    if (!empty($stepData['description'])) {
                        $newStep = $product->steps()->create([
                            'step_number'              => $stepCount++,
                            'type'                     => $stepData['type'],
                            'description'              => $stepData['description'],
                            'theoretical_time_minutes' => $stepData['time'] ?? null,
                            'target_rpm'               => $stepData['rpm'] ?? null,
                        ]);

                        if ($stepData['type'] === 'CARGA' && isset($stepData['ingredients'])) {
                            foreach ($stepData['ingredients'] as $ingData) {
                                if (!empty($ingData['code'])) {
                                    $formulaIng = $product->ingredients()
                                        ->where('material_code', strtoupper($ingData['code']))
                                        ->whereNull('presentation_id')
                                        ->first();
                                    if ($formulaIng) {
                                        $newStep->ingredients()->create([
                                            'formula_ingredient_id' => $formulaIng->id,
                                            'percentage_allocation' => $ingData['percentage'] ?? 100,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });

        return redirect()->route('productos.show', $product->id)->with('success', "Producto {$product->name} actualizado exitosamente.");
    }

    /**
     * Elimina un producto y todas sus dependencias en el orden correcto de llaves foráneas.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $productName = $product->name;

        DB::transaction(function () use ($product) {
            $presentationIds = $product->presentations()->pluck('id');

            // 1. Limpiar op_presentations ligadas a las presentaciones del producto
            DB::table('op_presentations')->whereIn('presentation_id', $presentationIds)->delete();

            // 2. Limpiar Órdenes de Producción asociadas y todas sus tablas hijas
            ProductionOrder::where('product_id', $product->id)->each(function ($order) {
                $order->opPresentations()->delete();
                $order->lineClearances()->delete();
                $order->opMaterialReconciliations()->delete();
                $order->manufacturingExecutions()->delete();
                $order->packagingWeightControls()->delete();
                $order->packagingResult()->delete();

                $dispensing = Dispensing::where('production_order_id', $order->id)->first();
                if ($dispensing) {
                    DispensingDetail::where('dispensing_id', $dispensing->id)->delete();
                    $dispensing->delete();
                }
                $order->delete();
            });

            // 3. Eliminar Planes de Manufactura (EBR) y sus ingredientes de pasos ANTES de borrar formula_ingredients
            $product->manufacturingPlans()->each(function ($plan) {
                $plan->steps()->each(function ($step) {
                    $step->ingredients()->delete();
                    $step->delete();
                });
                $plan->delete();
            });

            // 4. Eliminar pasos de fabricación legados
            $product->steps()->each(function ($step) {
                $step->ingredients()->delete();
                $step->delete();
            });

            // 5. Eliminar Presentaciones y sus materiales
            $product->presentations()->each(function ($presentation) {
                $presentation->materials()->delete();
                $presentation->delete();
            });

            // 6. Eliminar ingredientes a granel restantes
            $product->ingredients()->delete();

            // 7. Eliminar el producto maestro
            $product->delete();
        });

        return redirect()->route('productos.index')->with('success', "El producto '{$productName}' y todos sus registros asociados han sido eliminados permanentemente.");
    }

    /**
     * Elimina un plan de manufactura (Instructivo Maestro).
     */
    public function deleteInstructivo($id)
    {
        try {
            DB::beginTransaction();
            $plan = ProductManufacturingPlan::findOrFail($id);

            foreach ($plan->steps as $step) {
                $step->ingredients()->delete();
                $step->delete();
            }

            $plan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Instructivo Maestro eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar instructivo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
