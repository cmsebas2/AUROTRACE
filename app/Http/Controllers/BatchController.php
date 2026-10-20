<?php

/**
 * ============================================================================
 * CONTROLADOR: BatchController (Ejecución de Planta - Flujo EBR)
 * ============================================================================
 * ¿QUÉ HACE ESTE ARCHIVO? (Explicación "For Dummies")
 * Este controlador gestiona el trabajo físico en planta (Electronic Batch Record)
 * dividido en 5 etapas secuenciales:
 *
 *   1. Conciliación de Materiales (`createReconciliation`, `storeReconciliation`, `signReconciliation`):
 *      Verifica los lotes e insumos recibidos en bodega para la Orden de Producción.
 *   2. Despeje de Línea (`createLineClearance`, `storeLineClearance`, `storeQaVerification`):
 *      Checklist de limpieza y presión diferencial para las áreas:
 *      Dispensación -> Fabricación -> Envasado -> Acondicionado.
 *   3. Dispensación / Pesaje (`createDispensing`, `storeDispensingDetail`, `closeDispensing`):
 *      Registro de pesaje ingrediente por ingrediente con alerta de desviación (>5%).
 *   4. Fabricación / Manufactura (`createManufacturing`, `storeManufacturingStepDynamic`, `verifyManufacturingStepDynamic`, `finishManufacturing`):
 *      Ejecución paso a paso en el tanque (RPM, tiempo, peso, IPC) con control de
 *      desviación del 5% (GEMINI.md Art. 2.2) y doble firma (Operario + Calidad).
 *   5. Envase y Rendimiento (`createPackaging`, `storePackaging`, `storePackagingWeight`, `verifyPackaging`):
 *      Control de peso de envases y cálculo del Rendimiento Operativo (Yield).
 *      Si el rendimiento sale del rango 90.00% - 110.00%, el lote pasa automáticamente
 *      a estado `CUARENTENA` (GEMINI.md Art. 2.1).
 * ============================================================================
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\LineClearance;
use App\Models\BatchPackagingResult;
use App\Models\BatchPackagingWeightControl;
use App\Models\ProductPresentation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\AuditLog;
use App\Models\PlanStep;
use App\Models\PlanStepIngredient;
use App\Services\SignatureService;

class BatchController extends Controller
{
    protected $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Muestra la pantalla de apertura de una nueva Orden de Producción.
     */
    public function iniciar()
    {
        $productos = Product::with(['presentations', 'ingredients'])
            ->where('status', 'ACTIVO')
            ->get();
        $presentaciones = ProductPresentation::all();

        return view('batch.iniciar', compact('productos', 'presentaciones'));
    }

    /**
     * Guarda una OP desde el flujo clásico de Batch y redirige a Conciliación.
     */
    public function store(Request $request)
    {
        $request->validate([
            'op_number'            => 'required|string|max:255|unique:production_orders',
            'product_id'           => 'required|exists:products,id',
            'lote'                 => 'required|string|max:255|unique:production_orders',
            'presentations'        => 'required|array|min:1',
            'presentations.*.id'   => 'required|exists:product_presentations,id',
            'presentations.*.units'=> 'required|integer|min:1',
            'bulk_size_kg'         => 'required|numeric|min:0.001',
            'manufacture_date'     => 'required|string|size:10', // YYYY-MM-DD
            'expiration_date'      => 'required|string|max:7',   // YYYY-MM
            'destruction_date'     => 'required|string|max:7',   // YYYY-MM
        ]);

        try {
            DB::beginTransaction();

            $op = ProductionOrder::create([
                'op_number'          => $request->op_number,
                'product_id'         => $request->product_id,
                'lote'               => $request->lote,
                'bulk_size_kg'       => $request->bulk_size_kg,
                'unit'               => 'KG',
                'manufacturing_date' => \Carbon\Carbon::createFromFormat('Y-m-d', $request->manufacture_date)->format('Y-m-d'),
                'expiration_date'    => \Carbon\Carbon::createFromFormat('Y-m', $request->expiration_date)->endOfMonth()->format('Y-m-d'),
                'destruction_date'   => \Carbon\Carbon::createFromFormat('Y-m', $request->destruction_date)->endOfMonth()->format('Y-m-d'),
                'maquilador'         => $request->maquilador ?? 'LABORATORIOS AUROFARMA',
                'status'             => 'PESAJE',
            ]);

            foreach ($request->presentations as $pData) {
                $presentation = ProductPresentation::find($pData['id']);
                preg_match('/(\d+(?:\.\d+)?)/', $presentation->name ?? '', $matches);
                $peso = isset($matches[1]) ? floatval($matches[1]) : 0;
                $calculatedKg = $pData['units'] * $peso;

                $op->opPresentations()->create([
                    'presentation_id'  => $pData['id'],
                    'units_to_produce' => $pData['units'],
                    'total_kg'         => $calculatedKg,
                ]);
            }

            DB::commit();

            return redirect()->route('batch.conciliacion', $op->lote)->with('success', "Orden de Producción {$request->op_number} (Lote {$request->lote}) abierta exitosamente. Proceda con la Conciliación de Materiales.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al guardar la OP: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // 1. MÓDULO DE CONCILIACIÓN DE MATERIALES
    // =========================================================================

    /**
     * Muestra la tabla de Conciliación de Materiales (Materias Primas y Empaque).
     * Soporta tanto los tipos creados en mayúsculas ('MATERIA PRIMA', 'ENVASE', 'EMPAQUE')
     * como los tipos en formato título ('Materia Prima', 'Material de Empaque').
     */
    public function createReconciliation(ProductionOrder $batch)
    {
        $op = $batch->load(['product', 'opPresentations.presentation']);

        $reconciliations = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->get();

        if ($reconciliations->isEmpty()) {
            // Auto-población si la OP no tenía explosión previa
            $formulaIngredients = \App\Models\FormulaIngredient::where('product_id', $op->product_id)
                ->whereNull('presentation_id')
                ->get();

            foreach ($formulaIngredients as $fi) {
                \App\Models\OpMaterialReconciliation::create([
                    'production_order_id' => $op->id,
                    'material_code'       => $fi->material_code,
                    'type'                => 'Materia Prima',
                    'description'         => $fi->material_name . ' (' . $fi->material_code . ')',
                    'unit'                => $fi->unit ?? 'KG',
                ]);
            }

            $presentationIds = $op->opPresentations->pluck('presentation_id')->toArray();
            if (!empty($presentationIds)) {
                $packagingMaterials = \App\Models\FormulaIngredient::where('product_id', $op->product_id)
                    ->whereIn('presentation_id', $presentationIds)
                    ->get();

                foreach ($packagingMaterials as $pm) {
                    \App\Models\OpMaterialReconciliation::create([
                        'production_order_id' => $op->id,
                        'material_code'       => $pm->material_code,
                        'type'                => 'Material de Empaque',
                        'description'         => $pm->material_name . ' (' . $pm->material_code . ')',
                        'unit'                => $pm->unit ?? 'UND',
                    ]);
                }
            }

            $reconciliations = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->get();
        }

        // Filtrado insensible a mayúsculas/minúsculas para compatibilidad total con OP Inteligente
        $materiasPrimas = $reconciliations->filter(function ($r) {
            return in_array(mb_strtoupper(trim($r->type ?? '')), ['MATERIA PRIMA', 'MP']);
        });
        $materialEmpaque = $reconciliations->reject(function ($r) {
            return in_array(mb_strtoupper(trim($r->type ?? '')), ['MATERIA PRIMA', 'MP']);
        });

        // Estado General
        $firmado = $reconciliations->first()?->signed_by ?? null;
        $qa_firmado = $reconciliations->first()?->qa_user_id ?? null;

        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-conciliacion', compact('op', 'materiasPrimas', 'materialEmpaque', 'firmado', 'qa_firmado', 'reconciliations', 'operarios', 'calidad'));
    }

    /**
     * Auto-guarda vía AJAX los lotes y cantidades recibidas en la Conciliación.
     */
    public function storeReconciliation(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        if (isset($request->items)) {
            foreach ($request->items as $itemId => $data) {
                $item = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->find($itemId);
                if ($item) {
                    $item->update([
                        'lote'         => $data['lote'] ?? $item->lote,
                        'received_qty' => $data['received_qty'] ?? $item->received_qty,
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Lotes de conciliación auto-guardados.']);
    }

    /**
     * Firma electrónicamente la Conciliación de Materiales y avanza al Despeje de Línea.
     */
    public function signReconciliation(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        $request->validate([
            'password'        => 'required|string',
            'on_behalf_of_id' => 'required|exists:users,id',
        ]);

        try {
            // Validar firma reforzada
            $this->signatureService->verify($request->on_behalf_of_id, $request->password);

            // Aplicar firma a todas las filas de conciliación de esta OP
            \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->update([
                'signed_by' => $request->on_behalf_of_id,
                'signed_at' => now(),
                'date'      => now(),
            ]);

            // Logger Enterprise
            $this->signatureService->logSignature(
                'Firma de Conciliación de Materiales',
                'App\Models\ProductionOrder',
                $op->id,
                $request->except(['password']),
                $request->on_behalf_of_id
            );

            return redirect()->route('batch.despeje', $op)->with('success', 'Conciliación Inicial Firmada bajo el estándar E.T.A.P.A.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'La firma falló: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // 2. MÓDULO DE DESPEJE DE LÍNEA (LINE CLEARANCE)
    // =========================================================================

    /**
     * Muestra el formulario de Despeje de Línea e identifica automáticamente
     * cuál es el área activa actual y cuál fue el producto/lote anterior en esa área.
     */
    public function createLineClearance(ProductionOrder $batch)
    {
        $op = $batch->load('product');

        $despejes = LineClearance::with(['realizadoPor', 'verificadoPor'])->where('production_order_id', $op->id)->get();
        $ordenAreas = ['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'];
        $areaActual = 'Completado';

        foreach ($ordenAreas as $area) {
            $despejeArea = $despejes->firstWhere('area', $area);
            if (!$despejeArea) {
                $areaActual = $area;
                break;
            } elseif (is_null($despejeArea->hora_fin)) {
                $areaActual = $area;
                break;
            }
        }

        $productoAnteriorAuto = 'N/A';
        $loteAnteriorAuto = 'N/A';

        if ($areaActual !== 'Completado') {
            $ultimoDespejeMismaArea = LineClearance::where('area', $areaActual)
                ->where('production_order_id', '!=', $op->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultimoDespejeMismaArea) {
                $opAnterior = ProductionOrder::with('product')->find($ultimoDespejeMismaArea->production_order_id);
                if ($opAnterior && $opAnterior->product) {
                    $productoAnteriorAuto = $opAnterior->product->name;
                    $loteAnteriorAuto = $opAnterior->lote;
                }
            } else {
                $opAnterior = ProductionOrder::with('product')
                    ->where('id', '<', $op->id)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($opAnterior && $opAnterior->product) {
                    $productoAnteriorAuto = $opAnterior->product->name;
                    $loteAnteriorAuto = $opAnterior->lote;
                } else {
                    $productoAnteriorAuto = 'Primer Lote en Sistema';
                }
            }
        }

        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-despeje', compact('op', 'areaActual', 'productoAnteriorAuto', 'loteAnteriorAuto', 'despejes', 'operarios', 'calidad'));
    }

    /**
     * Guarda la apertura o el cierre de un Despeje de Línea por parte del operario.
     */
    public function storeLineClearance(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        $existingClearance = LineClearance::where('production_order_id', $op->id)
            ->where('area', $request->area)
            ->first();

        if ($existingClearance) {
            $request->validate([
                'area' => 'required|string',
            ]);
        } else {
            $request->validate([
                'area'                => 'required|string',
                'fecha_inicio'        => 'required|date',
                'hora_inicio'         => 'required',
                'producto_anterior'   => 'required|string|max:255',
                'lote_anterior'       => 'required|string|max:255',
                'respuestas'          => 'required|array|size:12',
                'diferencial_presion' => 'nullable|string|max:255',
            ]);
        }

        try {
            DB::beginTransaction();

            $existingClearance = LineClearance::where('production_order_id', $op->id)
                ->where('area', $request->area)
                ->first();

            if ($existingClearance) {
                $existingClearance->update([
                    'fecha_fin'      => now()->toDateString(),
                    'hora_fin'       => now()->toTimeString(),
                    'verificado_por' => Auth::id(),
                ]);
            } else {
                LineClearance::create([
                    'production_order_id'  => $op->id,
                    'area'                 => $request->area,
                    'fecha_inicio'         => $request->fecha_inicio,
                    'hora_inicio'          => $request->hora_inicio,
                    'producto_anterior'    => $request->producto_anterior,
                    'lote_anterior'        => $request->lote_anterior,
                    'respuestas_checklist' => $request->respuestas,
                    'diferencial_presion'  => $request->diferencial_presion,
                    'realizado_por'        => Auth::id(),
                ]);
            }

            DB::commit();

            $routeMap = [
                'Dispensación' => 'batch.dispensacion',
                'Fabricación'  => 'batch.fabricacion',
                'Envasado'     => 'batch.envase',
            ];

            if ($existingClearance) {
                if ($request->area === 'Fabricación') {
                    return redirect()->route('batch.despeje', $op)
                        ->with('success', 'Despeje final de Fabricación firmado. Proceda con la siguiente fase.');
                }

                return redirect()->route('batch.despeje', $op)
                    ->with('success', "Cierre de {$request->area} firmado. Puede continuar con la siguiente etapa.");
            }

            if (array_key_exists($request->area, $routeMap)) {
                if ($request->area === 'Fabricación') {
                    return redirect()->route('batch.fabricacion', $op)
                        ->with('success', "Apertura de Fabricación guardada. Iniciando mezcla en Tanque...");
                }

                return redirect()->route($routeMap[$request->area], $op)
                    ->with('success', "Apertura de {$request->area} guardada correctamente. Iniciando módulo.");
            }

            return redirect()->route('dashboard')
                ->with('warning', "Despeje de Línea para {$request->area} guardado, pero no hay módulo asignado.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al guardar el Despeje de Línea: ' . $e->getMessage()]);
        }
    }

    /**
     * Valida vía AJAX las credenciales de un inspector de Calidad (QA) para autorizar despejes o pesajes.
     */
    public function validateQaCredentials(Request $request, ProductionOrder $batch)
    {
        $request->validate([
            'password'        => 'required|string',
            'on_behalf_of_id' => 'nullable|exists:users,id',
        ]);

        try {
            $userId = $request->on_behalf_of_id ?? Auth::id();
            $this->signatureService->verify($userId, $request->password);

            $qaUser = \App\Models\User::find($userId);

            if ($request->on_behalf_of_id && $request->on_behalf_of_id != $qaUser->id) {
                $responsible = \App\Models\User::find($request->on_behalf_of_id);
                if (!$responsible || !$responsible->hasPermission('verificacion_controles_en_proceso')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El responsable seleccionado no tiene permisos de Calidad/Dirección Técnica.'
                    ], 403);
                }
            } else {
                if (!$qaUser || !$qaUser->hasPermission('verificacion_controles_en_proceso')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usted no tiene permisos de Calidad para realizar esta verificación.'
                    ], 403);
                }
            }

            return response()->json([
                'success'   => true,
                'user_id'   => $request->on_behalf_of_id ?? $qaUser->id,
                'user_name' => $request->on_behalf_of_id ? \App\Models\User::find($request->on_behalf_of_id)->name : $qaUser->name,
                'message'   => 'Credenciales verificadas con éxito.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación: ' . $e->getMessage()
            ], 401);
        }
    }

    /**
     * Registra la verificación de Calidad (QA) sobre el Despeje de Línea.
     */
    public function storeQaVerification(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        $request->validate([
            'qa_user_id'                      => 'required|exists:users,id',
            'area'                            => 'required|string',
            'qa_presion_diferencial_conforme' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $existingClearance = LineClearance::where('production_order_id', $op->id)
                ->where('area', $request->area)
                ->first();

            if ($existingClearance) {
                $existingClearance->update([
                    'fecha_fin'                       => now()->toDateString(),
                    'hora_fin'                        => now()->toTimeString(),
                    'verificado_por'                  => $request->qa_user_id,
                    'qa_presion_diferencial_conforme' => $request->boolean('qa_presion_diferencial_conforme'),
                ]);
            } else {
                LineClearance::create([
                    'production_order_id'             => $op->id,
                    'area'                            => $request->area,
                    'fecha_inicio'                    => $request->fecha_inicio,
                    'hora_inicio'                     => $request->hora_inicio,
                    'producto_anterior'               => $request->producto_anterior,
                    'lote_anterior'                   => $request->lote_anterior,
                    'respuestas_checklist'            => $request->respuestas,
                    'diferencial_presion'             => $request->diferencial_presion,
                    'realizado_por'                   => Auth::id(),
                    'verificado_por'                  => $request->qa_user_id,
                    'qa_presion_diferencial_conforme' => $request->boolean('qa_presion_diferencial_conforme'),
                ]);
            }

            // Registro en Audit Trail Enterprise
            $this->signatureService->logSignature(
                "Firma de Verificación QA - Despeje: {$request->area}",
                'App\Models\ProductionOrder',
                $op->id,
                $request->except(['password', 'qa_password']),
                $request->qa_user_id
            );

            DB::commit();

            $routeMap = [
                'Dispensación' => 'batch.dispensacion',
                'Fabricación'  => 'batch.fabricacion',
                'Envasado'     => 'batch.envase',
            ];

            if ($existingClearance) {
                if ($request->area === 'Fabricación') {
                    session()->flash('success', 'Despeje final de Fabricación verificado por Calidad. Proceda con la siguiente fase.');
                    return response()->json(['success' => true, 'redirect' => route('batch.despeje', $op)]);
                }
                session()->flash('success', "Cierre de {$request->area} verificado por Calidad. Puede continuar con la siguiente etapa.");
                return response()->json(['success' => true, 'redirect' => route('batch.despeje', $op)]);
            }

            if (array_key_exists($request->area, $routeMap)) {
                if ($request->area === 'Fabricación') {
                    session()->flash('success', "Apertura de Fabricación verificada por Calidad. Iniciando módulo...");
                    return response()->json(['success' => true, 'redirect' => route('batch.fabricacion', $op)]);
                }
                session()->flash('success', "Apertura de {$request->area} verificada por Calidad. Iniciando módulo...");
                return response()->json(['success' => true, 'redirect' => route($routeMap[$request->area], $op)]);
            }

            session()->flash('warning', "Despeje de Línea para {$request->area} verificado, pero no hay módulo asignado.");
            return response()->json(['success' => true, 'redirect' => route('dashboard')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al guardar la verificación: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 3. MÓDULO DE DISPENSACIÓN (PESAJE DE MATERIAS PRIMAS)
    // =========================================================================

    /**
     * Muestra la pantalla de Dispensación / Pesaje de Materias Primas.
     */
    public function createDispensing(ProductionOrder $batch)
    {
        $op = $batch->load([
            'product.ingredients' => function ($query) {
                $query->where('material_type', 'MATERIA PRIMA')
                      ->where('unit', '!=', 'UND');
            },
            'product.ingredients.item',
            'opPresentations.presentation'
        ]);

        // Asegurar que exista el registro maestro de Dispensación
        $dispensing = \App\Models\Dispensing::firstOrCreate(
            ['production_order_id' => $op->id],
            ['status' => 'EN PROCESO']
        );

        // Cargar pesajes ya registrados
        $dispensingDetails = \App\Models\DispensingDetail::with('realizadoPor')
            ->where('dispensing_id', $dispensing->id)
            ->get();

        // Propagar números de lote desde la Conciliación de Materiales (insensible a mayúsculas/minúsculas)
        $reconciliations = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)
            ->get()
            ->filter(function ($rec) {
                return in_array(mb_strtoupper(trim($rec->type ?? '')), ['MATERIA PRIMA', 'MP']);
            });

        $reconciledBatches = [];
        foreach ($reconciliations as $rec) {
            if ($rec->description) {
                $reconciledBatches[$rec->description] = $rec->lote;
            }
            if ($rec->material_code) {
                $reconciledBatches[$rec->material_code] = $rec->lote;
                $reconciledBatches["{$rec->description} ({$rec->material_code})"] = $rec->lote;
            }
        }

        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-dispensacion', compact('op', 'dispensing', 'dispensingDetails', 'reconciledBatches', 'operarios', 'calidad'));
    }

    /**
     * Registra el pesaje individual de un ingrediente y verifica la Regla del 5% (GEMINI.md Art. 2.2).
     */
    public function storeDispensingDetail(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        $request->validate([
            'formula_ingredient_id' => 'required|exists:formula_ingredients,id',
            'lote_mp'               => 'required|string|max:100',
            'cantidad_teorica'      => 'required|numeric',
            'cantidad_real'         => 'required|numeric',
            'hora_inicio'           => 'required',
            'hora_final'            => 'required',
        ]);

        $dispensing = \App\Models\Dispensing::firstOrCreate(
            ['production_order_id' => $op->id]
        );

        try {
            DB::beginTransaction();

            $signerId = $request->on_behalf_of_id ?? Auth::id();

            \App\Models\DispensingDetail::create([
                'dispensing_id'         => $dispensing->id,
                'formula_ingredient_id' => $request->formula_ingredient_id,
                'lote_mp'               => $request->lote_mp,
                'fecha'                 => now()->toDateString(),
                'cantidad_teorica'      => round($request->cantidad_teorica, 2),
                'cantidad_real'         => round($request->cantidad_real, 2),
                'hora_inicio'           => $request->hora_inicio,
                'hora_final'            => $request->hora_final,
                'realizado_por'         => $signerId,
            ]);

            $ingredient = \App\Models\FormulaIngredient::find($request->formula_ingredient_id);
            $dentroDeRango = $request->cantidad_teorica > 0
                ? (abs($request->cantidad_real - $request->cantidad_teorica) <= ($request->cantidad_teorica * 0.05))
                : true;

            $payload = array_merge($request->except(['password']), [
                '_metadata' => [
                    'materia_prima'   => $ingredient->material_name ?? 'N/A',
                    'peso_teorico_kg' => $request->cantidad_teorica,
                    'peso_real_kg'    => $request->cantidad_real,
                    'dentro_de_rango' => $dentroDeRango
                ]
            ]);

            // Alerta de Desviación si supera el 5% (GEMINI.md Artículo 2.2)
            if (!$dentroDeRango) {
                AuditLog::create([
                    'user_id'    => Auth::id(),
                    'action'     => 'ALERTA DE DESVIACIÓN',
                    'model_type' => 'App\Models\ProductionOrder',
                    'model_id'   => $op->id,
                    'new_values' => json_encode($payload['_metadata']),
                    'reason'     => "Desviación de peso superior al 5% en Dispensación ({$ingredient?->material_name} - Lote MP: {$request->lote_mp}).",
                    'ip_address' => $request->ip(),
                ]);
            }

            $this->signatureService->logSignature(
                "Pesaje de Ingrediente: {$request->lote_mp}",
                'App\Models\ProductionOrder',
                $op->id,
                $payload,
                $request->on_behalf_of_id
            );

            DB::commit();

            $signerUser = $request->on_behalf_of_id ? \App\Models\User::find($request->on_behalf_of_id) : Auth::user();

            return response()->json([
                'success'            => true,
                'realizado_por_name' => $signerUser?->name ?? Auth::user()?->name ?? 'OPERARIO'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cierra el módulo de Dispensación con verificación de Calidad, cierra el despeje
     * de Dispensación y mueve la OP al estado `MANUFACTURA`.
     */
    public function closeDispensing(Request $request, ProductionOrder $batch)
    {
        $op = $batch;
        $dispensing = \App\Models\Dispensing::where('production_order_id', $op->id)->firstOrFail();

        $request->validate([
            'observaciones' => 'nullable|string',
            'qa_user_id'    => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $dispensing->update([
                'observaciones'    => $request->observaciones,
                'fecha_inicio'     => $dispensing->created_at,
                'fecha_fin'        => now(),
                'realizado_por'    => Auth::id(),
                'fecha_realizado'  => now(),
                'verificado_por'   => $request->qa_user_id,
                'fecha_verificado' => now(),
                'status'           => 'COMPLETADO',
            ]);

            // 1. Cerrar automáticamente el Despeje de Línea de Dispensación
            $clearance = LineClearance::where('production_order_id', $op->id)
                ->where('area', 'Dispensación')
                ->whereNull('hora_fin')
                ->first();

            if ($clearance) {
                $clearance->update([
                    'fecha_fin'      => now()->toDateString(),
                    'hora_fin'       => now()->toTimeString(),
                    'verificado_por' => $request->qa_user_id,
                ]);
            }

            // 2. Actualizar estado de la OP
            $op->update(['status' => 'MANUFACTURA']);

            DB::commit();

            return redirect()->route('batch.fabricacion', $op)->with('success', 'Dispensación verificada y cerrada exitosamente. Proceda con la etapa de Fabricación.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al cerrar la dispensación: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // 4. MÓDULO DE FABRICACIÓN / MANUFACTURA EN TANQUE
    // =========================================================================

    /**
     * Muestra el Plan Maestro de Fabricación paso a paso.
     * Requiere que el Despeje de Línea de Fabricación haya sido iniciado previamente.
     */
    public function createManufacturing(ProductionOrder $batch)
    {
        $op = $batch->load([
            'product.activePlan.steps.ingredients.formulaIngredient',
            'product.ingredients',
            'opPresentations.presentation',
            'manufacturingExecutions.user',
            'manufacturingExecutions.qaUser'
        ]);

        // Asegurar que el Despeje de Fabricación esté iniciado antes de entrar al tanque
        $clearanceStarted = LineClearance::where('production_order_id', $op->id)
            ->where('area', 'Fabricación')
            ->exists();

        if (!$clearanceStarted) {
            return redirect()->route('batch.despeje', $op)
                ->with('error', 'Debe llenar el checklist y firmar la apertura del Despeje de Fabricación antes de ingresar al tanque.');
        }

        // Inicializar fechas de manufactura/vencimiento solo si no están definidas aún
        if (!$op->manufacturing_date || !$op->expiration_date) {
            $fechaHoy = $op->manufacturing_date ? \Carbon\Carbon::parse($op->manufacturing_date) : now();
            $vigenciaMeses = ($op->product && $op->product->vigencia_meses > 0) ? $op->product->vigencia_meses : 24;
            $vencimiento = (clone $fechaHoy)->addMonths($vigenciaMeses)->endOfMonth();
            $destruccion = (clone $vencimiento)->addMonths(12)->endOfMonth();

            $op->update([
                'manufacturing_date' => $fechaHoy->toDateString(),
                'expiration_date'    => $op->expiration_date ?: $vencimiento->toDateString(),
                'destruction_date'   => $op->destruction_date ?: $destruccion->toDateString(),
            ]);
        }

        $plan = $op->product->activePlan;

        $executions = $op->manufacturingExecutions()->with(['user', 'qaUser'])->get();
        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-fabricacion', compact('op', 'plan', 'executions', 'operarios', 'calidad'));
    }

    /**
     * Alias hacia `storeManufacturingStepDynamic`.
     */
    public function storeManufacturingStep(Request $request, ProductionOrder $batch)
    {
        return $this->storeManufacturingStepDynamic($request, $batch);
    }

    /**
     * Finaliza el módulo de Manufactura con firma electrónica CFR 21 y avanza a `ACONDICIONAMIENTO`.
     */
    public function finishManufacturing(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        // Validación estricta CFR 21 Parte 11
        $request->validate([
            'signature_password' => ['required', new \App\Rules\Cfr21Signature()],
            'signature_reason'   => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $op->audit_reason = $request->signature_reason;

            $op->update([
                'status' => 'ACONDICIONAMIENTO'
            ]);

            $this->signatureService->logSignature(
                "Finalización de Manufactura: " . $request->signature_reason,
                'App\Models\ProductionOrder',
                $op->id,
                ['status' => 'ACONDICIONAMIENTO'],
                Auth::id()
            );

            DB::commit();

            return redirect()->route('batch.despeje', $op->lote)
                ->with('success', 'Fabricación finalizada y bloqueada. Firma Electrónica CFR 21 válida. Proceda al Despeje de Línea de Envase.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cerrar fabricación: ' . $e->getMessage());
        }
    }

    /**
     * Registra un paso dinámico de manufactura (Adición, Agitación, IPC, Rendimiento).
     * Aplica la Regla del 5% (GEMINI.md Art. 2.2) sobre RPM y Peso: si hay desviación >5%,
     * exige observación obligatoria y genera una `ALERTA DE DESVIACIÓN` en `audit_logs`.
     */
    public function storeManufacturingStepDynamic(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        $planStep = PlanStep::find($request->plan_step_id);
        $planStepIngredient = $request->plan_step_ingredient_id ? PlanStepIngredient::find($request->plan_step_ingredient_id) : null;

        $hasDeviation = false;

        // Control de Desviación RPM (+/- 5%)
        if ($request->filled('rpm') && $planStep && $planStep->target_rpm > 0) {
            $diff = abs($request->rpm - $planStep->target_rpm);
            $percent = ($diff / $planStep->target_rpm) * 100;
            if ($percent > 5) {
                $hasDeviation = true;
            }
        }

        // Control de Desviación Peso (+/- 5%)
        if ($request->filled('yield_kg')) {
            $theoretical = $planStepIngredient?->theoretical_quantity ?? $op->bulk_size_kg;
            if ($theoretical > 0) {
                $diff = abs($request->yield_kg - $theoretical);
                $percent = ($diff / $theoretical) * 100;
                if ($percent > 5) {
                    $hasDeviation = true;
                }
            }
        }

        $request->validate([
            'plan_step_id'            => 'required|exists:plan_steps,id',
            'plan_step_ingredient_id' => 'nullable|exists:plan_step_ingredients,id',
            'step_type'               => 'required|string',
            'start_time'              => 'nullable',
            'end_time'                => 'nullable',
            'rpm'                     => 'nullable|numeric',
            'elapsed_minutes'         => 'nullable|numeric',
            'yield_kg'                => 'nullable|numeric',
            'ipc_result'              => 'nullable|string',
            'observations'            => [Rule::requiredIf($hasDeviation), 'nullable', 'string'],
        ], [
            'observations.required' => 'ALERTA DE DESVIACIÓN: El valor ingresado supera el 5% de desviación. Debe justificar el motivo en las observaciones.'
        ]);

        return DB::transaction(function () use ($request, $op, $hasDeviation) {
            // Log de Desviación si aplica (GEMINI.md Art. 2.2)
            if ($hasDeviation) {
                AuditLog::create([
                    'user_id'    => Auth::id(),
                    'action'     => 'ALERTA DE DESVIACIÓN',
                    'model_type' => 'App\Models\ProductionOrder',
                    'model_id'   => $op->id,
                    'new_values' => json_encode($request->only(['rpm', 'yield_kg', 'observations'])),
                    'reason'     => 'Desviación superior al 5% detectada en la captura.',
                    'ip_address' => $request->ip(),
                ]);
            }

            // SEGURIDAD BPM: Bloqueo de Integridad Crítico con Bloqueo de Fila (Race Conditions)
            if ($op->manufacturingExecutions()
                ->where('plan_step_id', $request->plan_step_id)
                ->where('plan_step_ingredient_id', $request->plan_step_ingredient_id)
                ->whereNotNull('signed_at')
                ->lockForUpdate()
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso Denegado: Registro Bloqueado por Firma.'
                ], 403);
            }

            try {
                $signerId = $request->on_behalf_of_id ?? Auth::id();

                $execution = \App\Models\ManufacturingExecution::create([
                    'production_order_id'     => $op->id,
                    'plan_step_id'            => $request->plan_step_id,
                    'plan_step_ingredient_id' => $request->plan_step_ingredient_id,
                    'step_type'               => $request->step_type,
                    'start_time'              => $request->start_time,
                    'end_time'                => $request->end_time,
                    'rpm'                     => $request->rpm,
                    'elapsed_minutes'         => $request->elapsed_minutes,
                    'yield_kg'                => $request->yield_kg,
                    'ipc_result'              => $request->ipc_result,
                    'observations'            => $request->observations,
                    'user_id'                 => $signerId,
                    'signed_at'               => now(),
                ]);

                $this->signatureService->logSignature(
                    "Firma de Operario - Manufactura: {$request->step_type}",
                    'App\Models\ProductionOrder',
                    $op->id,
                    $request->except(['password', 'signature_password']),
                    $request->on_behalf_of_id
                );

                $signerUser = $request->on_behalf_of_id ? \App\Models\User::find($request->on_behalf_of_id) : Auth::user();

                return response()->json([
                    'success'      => true,
                    'message'      => 'Paso registrado por el operario. Pendiente verificación de QA.',
                    'execution_id' => $execution->id,
                    'user'         => $signerUser?->name ?? Auth::user()?->name ?? 'OPERARIO',
                    'signed_at'    => \Carbon\Carbon::parse($execution->signed_at)->format('H:i'),
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar el paso: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Registra la firma de Verificación en Proceso de Calidad (QA) para un paso de manufactura.
     */
    public function verifyManufacturingStepDynamic(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        // SEGURIDAD BPM: Verificar Permiso
        abort_if(!auth()->user()->hasPermission('verificacion_controles_en_proceso'), 403, 'No tiene permisos para realizar verificaciones en proceso.');

        $request->validate([
            'execution_id'       => 'required|exists:manufacturing_executions,id',
            'signature_password' => ['required', new \App\Rules\Cfr21Signature()],
            'signature_reason'   => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $execution = \App\Models\ManufacturingExecution::where('production_order_id', $op->id)
                ->findOrFail($request->execution_id);

            // SEGURIDAD BPM: Verificar si ya tiene firma de QA
            if ($execution->qa_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso Denegado: Este paso ya cuenta con verificación de Calidad.'
                ], 403);
            }

            // 21 CFR Part 11: Guardar firma
            $execution->update([
                'qa_user_id'     => auth()->id(),
                'qa_verified_at' => now(),
            ]);

            // Log de Firma Electrónica
            $this->signatureService->logSignature(
                $request->signature_reason,
                'App\Models\ManufacturingExecution',
                $execution->id,
                ['status' => 'VERIFICADO_QA'],
                auth()->id()
            );

            $execution->load('qaUser');

            if ($execution->step_type === 'RENDIMIENTO' && $execution->plan_step_id === null) {
                $op->update(['status' => 'ACONDICIONAMIENTO']);
            }

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Paso verificado correctamente por Calidad.',
                'qa_user_name' => $execution->qaUser->name ?? 'CALIDAD',
                'qa_time'      => \Carbon\Carbon::parse($execution->qa_verified_at)->format('H:i')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la firma de Calidad: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // 5. MÓDULO DE ENVASE, ACONDICIONAMIENTO Y RENDIMIENTO (YIELD)
    // =========================================================================

    /**
     * Muestra la pantalla de Envase y Control de Peso.
     */
    public function createPackaging(ProductionOrder $batch)
    {
        $op = $batch->load(['product', 'opPresentations.presentation', 'packagingResult', 'packagingWeightControls']);

        // Auto-captura de inicio si no existe
        if (!$op->packagingResult) {
            BatchPackagingResult::create([
                'production_order_id' => $op->id,
                'start_time'          => now(),
                'status'              => 'PENDIENTE'
            ]);
            $op->refresh();
            $op->load(['product', 'opPresentations.presentation', 'packagingResult', 'packagingWeightControls']);
        }

        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-envase', compact('op', 'operarios', 'calidad'));
    }

    /**
     * Guarda el cierre de Envase, calcula el Rendimiento Operativo (Yield) y aplica
     * la Invariante de Calidad (GEMINI.md Art. 2.1):
     * - Si el Yield está entre 90.00% y 110.00% -> `COMPLETADO`
     * - Si está fuera del rango -> `CUARENTENA`
     */
    public function storePackaging(Request $request, ProductionOrder $batch)
    {
        $res = $batch->packagingResult ?: BatchPackagingResult::firstOrCreate(
            ['production_order_id' => $batch->id],
            ['start_time' => now(), 'status' => 'PENDIENTE']
        );

        if ($res && $res->signed_at) {
            return response()->json(['success' => false, 'message' => 'Módulo bloqueado por firma.'], 403);
        }

        $request->validate([
            'units_obtained' => 'required|integer|min:0',
            'average_weight' => 'required|numeric',
        ]);

        $data = $request->all();

        // Sanitizar booleanos organolépticos
        $data['color_conforme'] = $request->color_conforme == '1' || $request->color_conforme == 'true';
        $data['odor_conforme'] = $request->odor_conforme == '1' || $request->odor_conforme == 'true';
        $data['texture_conforme'] = $request->texture_conforme == '1' || $request->texture_conforme == 'true';
        $data['particles_free'] = $request->particles_free == '1' || $request->particles_free == 'true';

        // Cálculo de Rendimiento (GEMINI.md Artículo 2.1)
        $yield = $this->calculateFinalYield($batch, (int) $request->units_obtained);

        // Regla de Negocio: Rango de Aceptación 90.00% - 110.00%
        $isWithinRange = ($yield >= 90.00 && $yield <= 110.00);
        $finalStatus = $isWithinRange ? 'COMPLETADO' : 'CUARENTENA';

        $signerId = $request->on_behalf_of_id ?? Auth::id();

        $res->update(array_merge($data, [
            'units_obtained' => $request->units_obtained,
            'user_id'        => $signerId,
            'signed_at'      => now(),
            'end_time'       => now(),
            'status'         => 'COMPLETADO'
        ]));

        $this->signatureService->logSignature(
            "Firma de Cierre de Envase (Rendimiento: {$yield}%)",
            'App\Models\ProductionOrder',
            $batch->id,
            $request->except(['password']),
            $request->on_behalf_of_id
        );

        $batch->update([
            'final_yield_percentage' => $yield,
            'status'                 => $finalStatus
        ]);

        $signerUser = $request->on_behalf_of_id ? \App\Models\User::find($request->on_behalf_of_id) : Auth::user();

        return response()->json([
            'success'         => true,
            'yield'           => $yield,
            'status'          => $finalStatus,
            'is_within_range' => $isWithinRange,
            'user'            => $signerUser?->name ?? Auth::user()?->name ?? 'OPERARIO',
            'signed_at'       => now()->format('d/m/Y H:i'),
            'end_time'        => now()->format('H:i')
        ]);
    }

    /**
     * Calcula el porcentaje de rendimiento final del lote en función de las unidades
     * obtenidas y el peso teórico de la presentación (o las unidades teóricas de la OP).
     */
    private function calculateFinalYield(ProductionOrder $batch, int $unitsObtained): float
    {
        $opPresentation = $batch->opPresentations()->with('presentation')->first();
        $theoreticalWeight = $opPresentation?->presentation?->theoretical_weight ?? 0;

        if ($theoreticalWeight > 0 && $batch->bulk_size_kg > 0) {
            // El peso de la presentación está en g (gramos), el bulk_size en kg
            $totalWeightObtainedKg = ($unitsObtained * $theoreticalWeight) / 1000;
            $yield = ($totalWeightObtainedKg / $batch->bulk_size_kg) * 100;
            return round($yield, 2);
        }

        // Fallback seguro si la presentación no tiene theoretical_weight en gramos pero existen unidades teóricas
        $theoreticalUnits = $batch->theoretical_units ?: ($opPresentation?->units_to_produce ?? 0);
        if ($theoreticalUnits > 0) {
            return round(($unitsObtained / $theoreticalUnits) * 100, 2);
        }

        return 0;
    }

    /**
     * Registra un muestreo de control de peso durante el proceso de envasado.
     */
    public function storePackagingWeight(Request $request, ProductionOrder $batch)
    {
        if ($batch->packagingResult && $batch->packagingResult->signed_at) {
            return response()->json(['success' => false, 'message' => 'Registro bloqueado por firma.'], 403);
        }

        $request->validate(['weight' => 'required|numeric']);

        $control = BatchPackagingWeightControl::create([
            'production_order_id' => $batch->id,
            'weight'              => $request->weight,
            'controlled_at'       => now()
        ]);

        return response()->json([
            'success' => true,
            'weight'  => $control->weight,
            'time'    => $control->controlled_at->format('H:i')
        ]);
    }

    /**
     * Registra la firma de verificación de Calidad (QA) para el módulo de Envase.
     */
    public function verifyPackaging(Request $request, ProductionOrder $batch)
    {
        $res = $batch->packagingResult;
        if (!$res || !$res->signed_at) {
            return response()->json(['success' => false, 'message' => 'El operario debe firmar primero.'], 400);
        }

        $qaUserId = $request->qa_user_id ?? Auth::id();

        $res->update([
            'qa_user_id'     => $qaUserId,
            'qa_verified_at' => now()
        ]);

        $res->load('qaUser');

        return response()->json([
            'success'      => true,
            'qa_user_name' => $res->qaUser?->name ?? Auth::user()?->name ?? 'CALIDAD',
            'qa_time'      => $res->qa_verified_at->format('H:i')
        ]);
    }
}
