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
            'active_ingredients'              => $productDb->active_ingredients_list,
            'active_ingredients_list'         => $productDb->active_ingredients_list,
            'maquiladores'                    => $productDb->maquiladores_list,
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

        $all_items = [];
        try {
            $all_items = Item::select('id', 'item_code', 'description', 'inventory_uom')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'     => $item->id,
                        'codigo' => trim((string) $item->item_code),
                        'nombre' => trim((string) $item->description),
                        'unidad' => (string) ($item->inventory_uom ?? 'UND'),
                    ];
                })->values()->all();
        } catch (\Throwable $e) {}

        return view('productos.show', [
            'product'   => $product,
            'productDb' => $productDb,
            'all_items' => $all_items,
        ]);
    }

    /**
     * Guarda o actualiza la Fórmula Maestra (Materias Primas a Granel y Materiales de Empaque) desde la ficha del producto (/productos/{id}).
     */
    public function saveFormulaMaestra(Request $request, $id)
    {
        $this->ensureSchema();

        $product = Product::with(['ingredients', 'presentations'])->findOrFail($id);

        DB::transaction(function () use ($request, $product) {
            if ($request->filled('formula_maestra')) {
                $product->update([
                    'formula_maestra' => strtoupper(trim($request->input('formula_maestra'))),
                ]);
            }

            // 1. Sincronizar Materias Primas a Granel (presentation_id = null, material_type = MATERIA PRIMA)
            $keepRawIds = [];
            if ($request->has('raw_materials') && is_array($request->raw_materials)) {
                foreach ($request->raw_materials as $rm) {
                    $code = strtoupper(trim($rm['code'] ?? ''));
                    $name = trim($rm['name'] ?? '');
                    if ($code === '' && $name === '') {
                        continue;
                    }

                    $existingRaw = $code !== ''
                        ? $product->ingredients()->whereNull('presentation_id')->where('material_code', $code)->first()
                        : null;

                    $attrs = [
                        'presentation_id' => null,
                        'material_code'   => $code ?: 'MP-' . rand(100, 999),
                        'material_name'   => $name ?: 'MATERIA PRIMA',
                        'material_type'   => 'MATERIA PRIMA',
                        'function'        => trim($rm['function'] ?? 'EXCIPIENTE'),
                        'unit'            => trim($rm['unit'] ?? 'KIL'),
                        'percentage'      => (float) ($rm['percentage'] ?? 0),
                    ];

                    if ($existingRaw) {
                        $existingRaw->update($attrs);
                        $keepRawIds[] = $existingRaw->id;
                    } else {
                        $created = $product->ingredients()->create($attrs);
                        $keepRawIds[] = $created->id;
                    }
                }
            }

            $removedRawIds = $product->ingredients()
                ->whereNull('presentation_id')
                ->where('material_type', 'MATERIA PRIMA')
                ->whereNotIn('id', $keepRawIds)
                ->pluck('id');

            if ($removedRawIds->isNotEmpty()) {
                try {
                    DB::table('plan_step_ingredients')->whereIn('formula_ingredient_id', $removedRawIds)->delete();
                    DB::table('product_step_ingredients')->whereIn('formula_ingredient_id', $removedRawIds)->delete();
                } catch (\Throwable $e) {}
                $product->ingredients()->whereIn('id', $removedRawIds)->delete();
            }

            // 2. Sincronizar Materiales de Empaque y Acondicionamiento
            if ($request->has('packaging_materials') && is_array($request->packaging_materials)) {
                // Limpiar materiales de empaque previos y recrear según la tabla enviada
                $product->ingredients()->where('material_type', '!=', 'MATERIA PRIMA')->delete();

                $defaultPresId = $product->presentations->first()->id ?? null;

                foreach ($request->packaging_materials as $pkg) {
                    $code = strtoupper(trim($pkg['code'] ?? ''));
                    $name = trim($pkg['name'] ?? '');
                    if ($code === '' && $name === '') {
                        continue;
                    }

                    $presId = !empty($pkg['presentation_id']) && is_numeric($pkg['presentation_id'])
                        ? (int) $pkg['presentation_id']
                        : $defaultPresId;

                    $qty = (float) ($pkg['percentage'] ?? 0);

                    $product->ingredients()->create([
                        'presentation_id'        => $presId,
                        'material_code'          => $code ?: 'EMP-' . rand(100, 999),
                        'material_name'          => $name ?: 'MATERIAL DE EMPAQUE',
                        'material_type'          => trim($pkg['type'] ?? 'MATERIAL DE EMPAQUE'),
                        'specific_material_type' => trim($pkg['classification'] ?? 'N.A.'),
                        'unit'                   => trim($pkg['unit'] ?? 'UND'),
                        'percentage'             => $qty,
                        'quantity'               => $qty,
                    ]);
                }
            }
        });

        return redirect()->route('productos.show', $product->id)->with('success', "Fórmula Maestra de «{$product->name}» guardada exitosamente.");
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
        $this->ensureSchema();

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
     * Actualiza un producto existente, sincronizando su ficha inicial (Activos, Presentaciones, Envases, Vida Útil, Maquiladores)
     * y opcionalmente su Fórmula Maestra y Materiales de Empaque (BOM).
     */
    public function update(Request $request, $id)
    {
        $this->ensureSchema();

        $product = Product::findOrFail($id);

        $request->validate([
            'name'                            => 'required|string|max:255',
            'ica_license'                     => 'nullable|string|max:255',
            'formula_maestra'                 => 'nullable|string|max:255',
            'vigencia_meses'                  => 'nullable|numeric|min:0',
            'pharmaceutical_form'             => 'required|string|max:255',
            'active_ingredient_concentration' => 'nullable|numeric|min:0',
            'active_ingredients'              => 'nullable|array',
            'ficha_presentations'             => 'nullable|array',
            'maquiladores'                    => 'nullable|array',
            'image'                           => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'raw_materials'                   => 'nullable|array',
            'presentations'                   => 'nullable|array',
        ]);

        $imageName = $product->image;
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
        $activeIngredientsList = $product->active_ingredients_list;
        if ($request->has('active_ingredients') && is_array($request->active_ingredients)) {
            $activeIngredientsList = [];
            foreach ($request->active_ingredients as $act) {
                $actName = trim($act['name'] ?? '');
                $actConc = trim((string) ($act['concentration'] ?? ''));
                if ($actName !== '' || $actConc !== '') {
                    $activeIngredientsList[] = [
                        'name'          => $actName ?: 'Principio Activo',
                        'concentration' => $actConc,
                        'unit'          => '%',
                    ];
                }
            }
        }

        $primaryActiveName = !empty($activeIngredientsList)
            ? implode(' + ', array_map(fn($a) => $a['name'] . ($a['concentration'] !== '' ? ' (' . $a['concentration'] . ' %)' : ''), $activeIngredientsList))
            : $product->active_ingredient;

        $primaryActiveConc = !empty($activeIngredientsList) && is_numeric($activeIngredientsList[0]['concentration'])
            ? (float) $activeIngredientsList[0]['concentration']
            : (float) ($request->active_ingredient_concentration ?? $product->active_ingredient_concentration ?? 0);

        // Procesar Maquiladores y Tamaños de Lote Mínimo / Máximo (Kg / L)
        $maquiladoresList = $product->maquiladores_list;
        if ($request->has('maquiladores') && is_array($request->maquiladores)) {
            $maquiladoresList = [];
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
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        $primaryMaquilador = !empty($maquiladoresList)
            ? implode(', ', array_unique(array_column($maquiladoresList, 'name')))
            : $product->maquilador;

        $baseBatchSize = !empty($maquiladoresList) && !empty($maquiladoresList[0]['max_batch'])
            ? (float) $maquiladoresList[0]['max_batch']
            : (!empty($maquiladoresList) && !empty($maquiladoresList[0]['min_batch']) ? (float) $maquiladoresList[0]['min_batch'] : ($product->base_batch_size ?? 1000));

        $baseUnit = !empty($maquiladoresList) && !empty($maquiladoresList[0]['max_unit'])
            ? $maquiladoresList[0]['max_unit']
            : ($product->base_unit ?? 'Kg');

        $rawVigencia = $request->input('vigencia_meses');
        $vigenciaMeses = ($rawVigencia !== null && $rawVigencia !== '') ? (int) round((float) $rawVigencia) : $product->vigencia_meses;

        DB::transaction(function () use (
            $request,
            $product,
            $imageName,
            $activeIngredientsList,
            $primaryActiveName,
            $primaryActiveConc,
            $maquiladoresList,
            $primaryMaquilador,
            $baseBatchSize,
            $baseUnit,
            $vigenciaMeses
        ) {
            $product->update([
                'name'                            => trim($request->name),
                'ica_license'                     => $request->ica_license ? trim($request->ica_license) : null,
                'formula_maestra'                 => $request->formula_maestra,
                'vigencia_meses'                  => $vigenciaMeses,
                'vida_util_unidad'                => 'MESES',
                'pharmaceutical_form'             => strtoupper(trim($request->pharmaceutical_form)),
                'active_ingredient'               => $primaryActiveName,
                'active_ingredient_concentration' => $primaryActiveConc,
                'active_ingredients_json'         => $activeIngredientsList,
                'maquilador'                      => $primaryMaquilador,
                'maquiladores_json'               => $maquiladoresList,
                'base_batch_size'                 => $baseBatchSize,
                'base_unit'                       => $baseUnit,
                'image'                           => $imageName,
            ]);

            // Sincronizar Presentaciones y Tipo de Envase de la Ficha Inicial (si vienen en ficha_presentations)
            if ($request->has('ficha_presentations') && is_array($request->ficha_presentations)) {
                $keepFichaPresIds = [];
                $allPresNames = [];
                $presJsonList = [];
                $fIdx = 0;

                foreach ($request->ficha_presentations as $fp) {
                    $fIdx++;
                    $presId = !empty($fp['id']) && is_numeric($fp['id']) ? (int) $fp['id'] : null;
                    $qtyVal = isset($fp['quantity']) && $fp['quantity'] !== '' ? (float) $fp['quantity'] : null;
                    $rawUnit = trim($fp['unit'] ?? 'mL');
                    $unitMap = ['G' => 'g', 'KG' => 'Kg', 'ML' => 'mL', 'L' => 'L', 'UND' => 'UND'];
                    $unitMeasure = $unitMap[strtoupper($rawUnit)] ?? $rawUnit;
                    $containerType = trim($fp['container_type'] ?? '');

                    if ($qtyVal === null && $containerType === '') {
                        continue;
                    }

                    $qtyFormatted = $qtyVal !== null ? rtrim(rtrim(number_format($qtyVal, 2, '.', ''), '0'), '.') : '';
                    $presDisplayName = trim("{$qtyFormatted} {$unitMeasure}");
                    if ($containerType !== '') {
                        $presDisplayName .= " ({$containerType})";
                    }

                    $allPresNames[] = $presDisplayName;

                    $existingPres = $presId ? $product->presentations()->find($presId) : null;
                    $presCode = $existingPres && $existingPres->presentation_code
                        ? $existingPres->presentation_code
                        : ('PRD-' . $product->id . '-P' . $fIdx);

                    if ($existingPres) {
                        $existingPres->update([
                            'name'               => $presDisplayName,
                            'quantity_value'     => $qtyVal,
                            'unit_measure'       => $unitMeasure,
                            'container_type'     => $containerType ?: null,
                            'theoretical_weight' => $qtyVal ?? $existingPres->theoretical_weight ?? 0,
                        ]);
                        $presentationObj = $existingPres;
                    } else {
                        $presentationObj = $product->presentations()->create([
                            'presentation_code'  => $presCode,
                            'name'               => $presDisplayName,
                            'quantity_value'     => $qtyVal,
                            'unit_measure'       => $unitMeasure,
                            'container_type'     => $containerType ?: null,
                            'theoretical_weight' => $qtyVal ?? 0,
                        ]);
                    }

                    $keepFichaPresIds[] = $presentationObj->id;
                    $presJsonList[] = [
                        'id'                => $presentationObj->id,
                        'presentation_code' => $presentationObj->presentation_code,
                        'name'              => $presDisplayName,
                        'quantity'          => $qtyVal,
                        'unit'              => $unitMeasure,
                        'container_type'    => $containerType,
                    ];
                }

                // Si no se enviaron presentaciones desde el bloque inferior de BOM, limpiar las eliminadas en la ficha
                if (!$request->has('presentations') && !empty($keepFichaPresIds)) {
                    $product->presentations()->whereNotIn('id', $keepFichaPresIds)->each(function ($p) {
                        $p->materials()->delete();
                        $p->delete();
                    });
                }

                $product->update([
                    'presentation'       => implode(', ', $allPresNames),
                    'presentations_json' => $presJsonList,
                ]);
            }

            // 1. Materias Primas a granel (presentation_id = null)
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

                // Eliminar materias primas a granel quitadas del formulario
                $removedRawIds = $product->ingredients()
                    ->whereNull('presentation_id')
                    ->whereNotIn('id', $keepRawIds)
                    ->pluck('id');

                if ($removedRawIds->isNotEmpty()) {
                    DB::table('plan_step_ingredients')->whereIn('formula_ingredient_id', $removedRawIds)->delete();
                    DB::table('product_step_ingredients')->whereIn('formula_ingredient_id', $removedRawIds)->delete();
                    $product->ingredients()->whereIn('id', $removedRawIds)->delete();
                }
            }

            // 2. Materiales de empaque por presentación (bloque colapsable Paso 2)
            if ($request->has('presentations') && is_array($request->presentations)) {
                foreach ($request->presentations as $pKey => $presData) {
                    $presentation = is_numeric($pKey) ? $product->presentations()->find($pKey) : null;
                    if (!$presentation && !empty($presData['name']) && $presData['name'] !== 'No encontrado') {
                        $presCode = strtoupper(trim($presData['presentation_code'] ?? $presData['code'] ?? ('PRD-' . $product->id . '-' . rand(10, 99))));
                        $presentation = $product->presentations()->create([
                            'presentation_code'  => $presCode,
                            'name'               => $presData['name'],
                            'theoretical_weight' => $presData['theoretical_weight'] ?? 0,
                        ]);
                    }

                    if ($presentation) {
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
            }
        });

        return redirect()->route('productos.show', $product->id)->with('success', "Producto «{$product->name}» actualizado exitosamente en la base de datos.");
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
