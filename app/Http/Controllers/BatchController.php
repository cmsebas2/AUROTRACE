<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\LineClearance;
use App\Models\BatchPackagingResult;
use App\Models\BatchPackagingWeightControl;
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
    public function iniciar()
    {
        // Cargamos productos con sus presentaciones para tener los pesos disponibles en el front
        $productos = Product::with('presentations')
            ->where('status', 'ACTIVO')
            ->get();
            
        return view('batch.iniciar', compact('productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'op_number' => 'required|string|max:255|unique:production_orders',
            'product_id' => 'required|exists:products,id',
            'lote' => 'required|string|max:255|unique:production_orders',
            'presentations' => 'required|array|min:1',
            'presentations.*.id' => 'required|exists:product_presentations,id',
            'presentations.*.units' => 'required|integer|min:1',
            'bulk_size_kg' => 'required|numeric|min:0.001',
            'manufacture_date' => 'required|string|size:10', // YYYY-MM-DD
            'expiration_date' => 'required|string|max:7',  // YYYY-MM
            'destruction_date' => 'required|string|max:7', // YYYY-MM
        ]);

        try {
            DB::beginTransaction();

            $op = \App\Models\ProductionOrder::create([
                'op_number' => $request->op_number,
                'product_id' => $request->product_id,
                'lote' => $request->lote,
                'bulk_size_kg' => $request->bulk_size_kg,
                'unit' => 'KG',
                'manufacturing_date' => \Carbon\Carbon::createFromFormat('Y-m-d', $request->manufacture_date)->format('Y-m-d'),
                'expiration_date' => \Carbon\Carbon::createFromFormat('Y-m', $request->expiration_date)->endOfMonth()->format('Y-m-d'),
                'destruction_date' => \Carbon\Carbon::createFromFormat('Y-m', $request->destruction_date)->endOfMonth()->format('Y-m-d'),
                'maquilador' => $request->maquilador ?? 'LABORATORIOS AUROFARMA',
                'status' => 'PESAJE',
            ]);

            foreach ($request->presentations as $pData) {
                // El total_kg para esta fila se calcula de nuevo en el server o se confía en el front (mejor recalcular si es crítico, pero para este MVP tomaremos el del front o lo recalculamos aquí)
                $presentation = \App\Models\ProductPresentation::find($pData['id']);
                // Extraer el peso numérico del nombre (ej. "25 KG" -> 25)
                preg_match('/(\d+(?:\.\d+)?)/', $presentation->name, $matches);
                $peso = isset($matches[1]) ? floatval($matches[1]) : 0;
                $calculatedKg = $pData['units'] * $peso;

                $op->opPresentations()->create([
                    'presentation_id' => $pData['id'],
                    'units_to_produce' => $pData['units'],
                    'total_kg' => $calculatedKg,
                ]);
            }

            DB::commit();

            return redirect()->route('batch.conciliacion', $op->lote)->with('success', "Orden de Producción {$request->op_number} (Lote {$request->lote}) abierta exitosamente. Proceda con la Conciliación de Materiales.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al guardar la OP: ' . $e->getMessage()]);
        }
    }

    public function createReconciliation(ProductionOrder $batch)
    {
        $op = $batch->load(['product', 'opPresentations.presentation']);

        $reconciliations = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->get();

        if ($reconciliations->isEmpty()) {
            // Auto population
            $formulaIngredients = \App\Models\FormulaIngredient::where('product_id', $op->product_id)
                ->whereNull('presentation_id')
                ->get();

            foreach ($formulaIngredients as $fi) {
                \App\Models\OpMaterialReconciliation::create([
                    'production_order_id' => $op->id,
                    'type' => 'Materia Prima',
                    'description' => $fi->material_name . ' (' . $fi->material_code . ')',
                    'unit' => $fi->unit ?? 'KG',
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
                        'type' => 'Material de Empaque',
                        'description' => $pm->material_name . ' (' . $pm->material_code . ')',
                        'unit' => $pm->unit ?? 'UND',
                    ]);
                }
            }

            $reconciliations = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->get();
        }

        $materiasPrimas = $reconciliations->where('type', 'Materia Prima');
        $materialEmpaque = $reconciliations->where('type', 'Material de Empaque');
        
        // Estado General
        $firmado = $reconciliations->first()->signed_by ?? null;
        $qa_firmado = $reconciliations->first()->qa_user_id ?? null;

        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-conciliacion', compact('op', 'materiasPrimas', 'materialEmpaque', 'firmado', 'qa_firmado', 'reconciliations', 'operarios', 'calidad'));
    }

    public function storeReconciliation(Request $request, ProductionOrder $batch)
    {
        $op = $batch;
        
        if (isset($request->items)) {
            foreach ($request->items as $itemId => $data) {
                $item = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->find($itemId);
                if ($item) {
                    $item->update([
                        'lote' => $data['lote'] ?? $item->lote,
                        'received_qty' => $data['received_qty'] ?? $item->received_qty,
                    ]);
                }
            }
        }
        
        return response()->json(['success' => true, 'message' => 'Lotes de conciliación auto-guardados.']);
    }

    public function signReconciliation(Request $request, ProductionOrder $batch)
    {
        $op = $batch;
        
        $request->validate([
            'password' => 'required|string',
            'on_behalf_of_id' => 'required|exists:users,id',
        ]);

        try {
            // Validar firma reforzada
            $this->signatureService->verify($request->on_behalf_of_id, $request->password);

            // Apply signature to all rows
            \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)->update([
                'signed_by' => $request->on_behalf_of_id,
                'signed_at' => now(),
                'date' => now(),
            ]);

            // Logger Enterprise
            $this->signatureService->logSignature(
                'Firma de Conciliación de Materiales',
                'App\Models\ProductionOrder',
                $op->id,
                $request->all(),
                $request->on_behalf_of_id
            );

            return redirect()->route('batch.despeje', $op)->with('success', 'Conciliación Inicial Firmada bajo el estándar E.T.A.P.A.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'La firma falló: ' . $e->getMessage()]);
        }
    }

    public function createLineClearance(ProductionOrder $batch)
    {
        $op = $batch->load('product');
        
        $despejes = \App\Models\LineClearance::with(['realizadoPor', 'verificadoPor'])->where('production_order_id', $op->id)->get();
        $ordenAreas = ['Dispensación', 'Fabricación', 'Envasado', 'Acondicionado'];
        $areaActual = 'Completado';
        
        foreach ($ordenAreas as $area) {
            $despejeArea = $despejes->firstWhere('area', $area);
            if (!$despejeArea) {
                // If the area doesn't exist at all, this is the current active section for START
                $areaActual = $area;
                break;
            } else if (is_null($despejeArea->hora_fin)) {
                // If the area exists but lacks a finish time, it is STILL the active active section for FINISH
                $areaActual = $area;
                break;
            }
        }

        $productoAnteriorAuto = 'N/A';
        $loteAnteriorAuto = 'N/A';

        if ($areaActual !== 'Completado') {
            // Find the last finished OP (or one that at least started Fabricación/Dispensación depending on the area)
            // Exclude current OP
            $ultimoDespejeMismaArea = \App\Models\LineClearance::where('area', $areaActual)
                ->where('production_order_id', '!=', $op->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultimoDespejeMismaArea) {
                $opAnterior = \App\Models\ProductionOrder::with('product')->find($ultimoDespejeMismaArea->production_order_id);
                if ($opAnterior && $opAnterior->product) {
                    $productoAnteriorAuto = $opAnterior->product->name;
                    $loteAnteriorAuto = $opAnterior->lote;
                }
            } else {
                // Fallback: If no cleared line in history for this area, try looking for ANY previous OP
                $opAnterior = \App\Models\ProductionOrder::with('product')
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

    public function storeLineClearance(Request $request, ProductionOrder $batch)
    {
        $op = $batch;
        
        $existingClearance = LineClearance::where('production_order_id', $op->id)
            ->where('area', $request->area)
            ->first();

        // Si ya existe, significa que el usuario SOLO está volviendo a firmar la hora de salida. Solo validamos el área.
        if ($existingClearance) {
            $request->validate([
                'area' => 'required|string',
            ]);
        } else {
            // Si es nuevo, validamos absolutamente todo el formulario inical de Despeje
            $request->validate([
                'area' => 'required|string',
                'fecha_inicio' => 'required|date',
                'hora_inicio' => 'required',
                'producto_anterior' => 'required|string|max:255',
                'lote_anterior' => 'required|string|max:255',
                'respuestas' => 'required|array|size:12',
                'diferencial_presion' => 'nullable|string|max:255',
            ]);
        }

        try {
            DB::beginTransaction();

            $existingClearance = LineClearance::where('production_order_id', $op->id)
                ->where('area', $request->area)
                ->first();

            if ($existingClearance) {
                // If it exists, this means the user is returning from the module to SIGN OFF the exit time.
                $existingClearance->update([
                    'fecha_fin' => now()->toDateString(),
                    'hora_fin' => now()->toTimeString(),
                    'verificado_por' => Auth::id(), // Temporary logic: Same user signs for now unless specific QA login is used
                ]);
            } else {
                // Initial creation (Start of the area clearance)
                LineClearance::create([
                    'production_order_id' => $op->id,
                    'area' => $request->area,
                    'fecha_inicio' => $request->fecha_inicio,
                    'hora_inicio' => $request->hora_inicio,
                    'producto_anterior' => $request->producto_anterior,
                    'lote_anterior' => $request->lote_anterior,
                    'respuestas_checklist' => $request->respuestas,
                    'diferencial_presion' => $request->diferencial_presion,
                    'realizado_por' => Auth::id(),
                    // fecha_fin and hora_fin are purposely left null here.
                ]);
            }

            // Removed status update for the OP to respect original ENUM state

            DB::commit();

            // Dynamic Routing Map
            $routeMap = [
                'Dispensación' => 'batch.dispensacion',
                'Fabricación'  => 'batch.fabricacion',
            ];

            if ($existingClearance) {
                // If they are signing off the END of an area
                if ($request->area === 'Fabricación') {
                    // This is Firma 2 for Fabricación, going back to Despeje to allow the NEXT module
                    return redirect()->route('batch.despeje', $op)
                        ->with('success', 'Despeje final de Fabricación firmado. Proceda con la siguiente fase.');
                }
                
                // Keep returning to Despeje if finishing Dispensación (Firma 2)
                return redirect()->route('batch.despeje', $op)
                    ->with('success', "Cierre de {$request->area} firmado. Puede continuar con la siguiente etapa.");
            }
 
            if (array_key_exists($request->area, $routeMap)) {
                // If Apertura (Firma 1) of Fabricación, jump right into the module
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

    public function validateQaCredentials(Request $request, ProductionOrder $batch)
    {
        $request->validate([
            'password' => 'required|string',
            'on_behalf_of_id' => 'nullable|exists:users,id',
        ]);

        try {
            $userId = $request->on_behalf_of_id ?? Auth::id();
            $this->signatureService->verify($userId, $request->password);

            // Re-verificar que el usuario tenga rol de Calidad o Admin para acciones de verificación
            // El usuario responsable es quien se seleccionó (o el actual si no se delegó)
            $qaUser = \App\Models\User::find($userId);
            
            // Si es una firma DELEGADA, verificamos el rol del responsable (on_behalf_of_id)
            if ($request->on_behalf_of_id && $request->on_behalf_of_id != $qaUser->id) {
                $responsible = \App\Models\User::find($request->on_behalf_of_id);
                if (!$responsible->hasPermission('verificacion_controles_en_proceso')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El responsable seleccionado no tiene permisos de Calidad/Dirección Técnica.'
                    ], 403);
                }
            } else {
                // Si firma el mismo usuario, verificamos su propio rol
                if (!$qaUser->hasPermission('verificacion_controles_en_proceso')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usted no tiene permisos de Calidad para realizar esta verificación.'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'user_id' => $request->on_behalf_of_id ?? $qaUser->id,
                'user_name' => $request->on_behalf_of_id ? \App\Models\User::find($request->on_behalf_of_id)->name : $qaUser->name,
                'message' => 'Credenciales verificadas con éxito.'
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

    public function storeQaVerification(Request $request, ProductionOrder $batch)
    {
        $op = $batch;
        
        $request->validate([
            'qa_user_id' => 'required|exists:users,id',
            'area' => 'required|string',
            'qa_presion_diferencial_conforme' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $existingClearance = LineClearance::where('production_order_id', $op->id)
                ->where('area', $request->area)
                ->first();

            if ($existingClearance) {
                // Cierre de despeje con doble verificación
                $existingClearance->update([
                    'fecha_fin' => now()->toDateString(),
                    'hora_fin' => now()->toTimeString(),
                    'verificado_por' => $request->qa_user_id,
                    'qa_presion_diferencial_conforme' => $request->boolean('qa_presion_diferencial_conforme'),
                ]);
            } else {
                // Apertura de despeje
                LineClearance::create([
                    'production_order_id' => $op->id,
                    'area' => $request->area,
                    'fecha_inicio' => $request->fecha_inicio,
                    'hora_inicio' => $request->hora_inicio,
                    'producto_anterior' => $request->producto_anterior,
                    'lote_anterior' => $request->lote_anterior,
                    'respuestas_checklist' => $request->respuestas,
                    'diferencial_presion' => $request->diferencial_presion,
                    'realizado_por' => Auth::id(), // El firmante es el operario actual, pero el QA verifica
                    'verificado_por' => $request->qa_user_id,
                    'qa_presion_diferencial_conforme' => $request->boolean('qa_presion_diferencial_conforme'),
                ]);
            }

            // Registro en Audit Trail Enterprise
            $this->signatureService->logSignature(
                "Firma de Verificación QA - Despeje: {$request->area}",
                'App\Models\ProductionOrder',
                $op->id,
                $request->all(),
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

    // --- NUEVO MÓDULO DE DISPENSACIÓN ---

    public function createDispensing(ProductionOrder $batch)
    {
        $op = $batch->load([
            'product.ingredients' => function($query) {
                $query->where('material_type', 'MATERIA PRIMA')
                      ->where('unit', '!=', 'UND');
            },
            'product.ingredients.item', 
            'opPresentations.presentation'
        ]);
        
        // Ensure Dispensing record exists
        $dispensing = \App\Models\Dispensing::firstOrCreate(
            ['production_order_id' => $op->id],
            ['status' => 'EN PROCESO'] // Default status
        );

        // Load existing dispensing details
        $dispensingDetails = \App\Models\DispensingDetail::with('realizadoPor')
            ->where('dispensing_id', $dispensing->id)
            ->get();

        // Propagation logic: Fetch reconciled batch numbers from the previous step
        $reconciliations = \App\Models\OpMaterialReconciliation::where('production_order_id', $op->id)
            ->where('type', 'Materia Prima')
            ->get();
            
        $reconciledBatches = [];
        foreach ($reconciliations as $rec) {
            $reconciledBatches[$rec->description] = $rec->lote;
        }
            
        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();
            
        return view('batch.batch-dispensacion', compact('op', 'dispensing', 'dispensingDetails', 'reconciledBatches', 'operarios', 'calidad'));
    }

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
            
            \App\Models\DispensingDetail::create([
                'dispensing_id'          => $dispensing->id,
                'formula_ingredient_id'  => $request->formula_ingredient_id,
                'lote_mp'                => $request->lote_mp,
                'fecha'                  => now()->toDateString(),
                'cantidad_teorica'       => round($request->cantidad_teorica, 2),
                'cantidad_real'          => round($request->cantidad_real, 2),
                'hora_inicio'            => $request->hora_inicio,
                'hora_final'             => $request->hora_final,
                'realizado_por'          => $request->on_behalf_of_id ?? Auth::id(),
            ]);

            $ingredient = \App\Models\FormulaIngredient::find($request->formula_ingredient_id);
            $payload = array_merge($request->all(), [
                '_metadata' => [
                    'materia_prima' => $ingredient->material_name ?? 'N/A',
                    'peso_teorico_kg' => $request->cantidad_teorica,
                    'peso_real_kg' => $request->cantidad_real,
                    'dentro_de_rango' => abs($request->cantidad_real - $request->cantidad_teorica) <= ($request->cantidad_teorica * 0.05)
                ]
            ]);

            // Log Enterprise (Dispensación no requiere password re-check por ingrediente en este nivel, pero logueamos)
            $this->signatureService->logSignature(
                "Pesaje de Ingrediente: {$request->lote_mp}",
                'App\Models\ProductionOrder',
                $op->id,
                $payload,
                $request->on_behalf_of_id
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'realizado_por_name' => Auth::user()->name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function closeDispensing(Request $request, ProductionOrder $batch)
    {
        $op = $batch;
        $dispensing = \App\Models\Dispensing::where('production_order_id', $op->id)->firstOrFail();
        
        $request->validate([
            'observaciones' => 'nullable|string',
            'qa_user_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();


            $dispensing->update([
                'observaciones' => $request->observaciones,
                'fecha_inicio' => $dispensing->created_at, // O la del primer detail
                'fecha_fin' => now(),
                'realizado_por' => Auth::id(),
                'fecha_realizado' => now(),
                'verificado_por' => $request->qa_user_id,
                'fecha_verificado' => now(),
                'status' => 'COMPLETADO',
            ]);

            // 1. Cerrar automáticamente el Despeje de Línea de Dispensación
            $clearance = \App\Models\LineClearance::where('production_order_id', $op->id)
                ->where('area', 'Dispensación')
                ->whereNull('hora_fin')
                ->first();
                
            if ($clearance) {
                $clearance->update([
                    'fecha_fin' => now()->toDateString(),
                    'hora_fin' => now()->toTimeString(),
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

    public function createManufacturing(ProductionOrder $batch)
    {
        $op = $batch->load([
            'product.activePlan.steps.ingredients.formulaIngredient',
            'product.ingredients', // Fallback
            'opPresentations.presentation',
            'manufacturingExecutions.user',
            'manufacturingExecutions.qaUser'
        ]);


        // Required logic: ensure Line Clearance for Fabricacion is STARTED (Firma 1) before allowing access
        $clearanceStarted = LineClearance::where('production_order_id', $op->id)
            ->where('area', 'Fabricación')
            ->exists();

        if (!$clearanceStarted) {
            return redirect()->route('batch.despeje', $op)
                ->with('error', 'Debe llenar el checklist y firmar la apertura del Despeje de Fabricación antes de ingresar al tanque.');
        }

        // Sincronización de Fecha en Tiempo Real (Requerimiento IF-304-1)
        // Solo actualizamos si no ha sido finalizada o si se requiere que sea "hoy" al abrir para ejecución
        $fechaHoy = now();
        $vigenciaMeses = $op->product->vigencia_meses ?? 0;
        $vencimiento = (clone $fechaHoy)->addMonths($vigenciaMeses)->endOfMonth();
        $destruccion = (clone $vencimiento)->addMonths(12)->endOfMonth();

        $op->update([
            'manufacturing_date' => $fechaHoy->toDateString(),
            'expiration_date' => $vencimiento->toDateString(),
            'destruction_date' => $destruccion->toDateString(),
        ]);

        $plan = $op->product->activePlan;
        
        $executions = $op->manufacturingExecutions()->with(['user', 'qaUser'])->get();
        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();
 
        return view('batch.batch-fabricacion', compact('op', 'plan', 'executions', 'operarios', 'calidad'));
    }

    public function storeManufacturingStep(Request $request, ProductionOrder $batch)
    {
        return $this->storeManufacturingStepDynamic($request, $batch);
    }

    public function finishManufacturing(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        // Validación estricta CFR 21 Parte 11
        $request->validate([
            'signature_password' => ['required', new \App\Rules\Cfr21Signature()],
            'signature_reason' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            // Inyectar el motivo de firma para el Audit Trail subyacente
            $op->audit_reason = $request->signature_reason;

            $op->update([
                'status' => 'ACONDICIONAMIENTO'
            ]);

            // Logger propio del módulo de firmas
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
            if ($percent > 5) $hasDeviation = true;
        }

        // Control de Desviación Peso (+/- 5%)
        if ($request->filled('yield_kg')) {
            $theoretical = $planStepIngredient?->theoretical_quantity ?? $op->bulk_size_kg;
            if ($theoretical > 0) {
                $diff = abs($request->yield_kg - $theoretical);
                $percent = ($diff / $theoretical) * 100;
                if ($percent > 5) $hasDeviation = true;
            }
        }

        $request->validate([
            'plan_step_id' => 'required|exists:plan_steps,id',
            'plan_step_ingredient_id' => 'nullable|exists:plan_step_ingredients,id',
            'step_type' => 'required|string',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'rpm' => 'nullable|numeric',
            'elapsed_minutes' => 'nullable|numeric',
            'yield_kg' => 'nullable|numeric',
            'ipc_result' => 'nullable|string',
            'observations' => [Rule::requiredIf($hasDeviation), 'nullable', 'string'],
        ], [
            'observations.required' => 'ALERTA DE DESVIACIÓN: El valor ingresado supera el 5% de desviación. Debe justificar el motivo en las observaciones.'
        ]);

        return DB::transaction(function() use ($request, $op, $hasDeviation) {
            // Log de Desviación si aplica
            if ($hasDeviation) {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'ALERTA DE DESVIACIÓN',
                    'model_type' => 'App\Models\ProductionOrder',
                    'model_id' => $op->id,
                    'new_values' => json_encode($request->only(['rpm', 'yield_kg', 'observations'])),
                    'reason' => 'Desviación superior al 5% detectada en la captura.',
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
                $execution = \App\Models\ManufacturingExecution::create([
                    'production_order_id' => $op->id,
                    'plan_step_id' => $request->plan_step_id,
                    'plan_step_ingredient_id' => $request->plan_step_ingredient_id,
                    'step_type' => $request->step_type,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'rpm' => $request->rpm,
                    'elapsed_minutes' => $request->elapsed_minutes,
                    'yield_kg' => $request->yield_kg,
                    'ipc_result' => $request->ipc_result,
                    'observations' => $request->observations,
                    'user_id' => $request->on_behalf_of_id ?? Auth::id(),
                    'signed_at' => now(),
                ]);

                $this->signatureService->logSignature(
                    "Firma de Operario - Manufactura: {$request->step_type}",
                    'App\Models\ProductionOrder',
                    $op->id,
                    $request->all(),
                    $request->on_behalf_of_id
                );

                // If it's a yield step, we might want to update the production order status or bulk size
                if ($request->step_type === 'RENDIMIENTO' && $request->yield_kg) {
                    // Future logic for closing order if all steps done
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Paso registrado por el operario. Pendiente verificación de QA.',
                    'execution_id' => $execution->id,
                    'user' => Auth::user()->name,
                    'signed_at' => \Carbon\Carbon::parse($execution->signed_at)->format('H:i'),
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar el paso: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    public function verifyManufacturingStepDynamic(Request $request, ProductionOrder $batch)
    {
        $op = $batch;

        // SEGURIDAD BPM: Verificar Permiso
        abort_if(!auth()->user()->hasPermission('verificacion_controles_en_proceso'), 403, 'No tiene permisos para realizar verificaciones en proceso.');

        $request->validate([
            'execution_id'      => 'required|exists:manufacturing_executions,id',
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
                'qa_user_id' => auth()->id(),
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

            // If it's the final yield (Step 4), update the Production Order Status
            if ($execution->step_type === 'RENDIMIENTO' && $execution->plan_step_id === null) {
                $op->update(['status' => 'ACONDICIONAMIENTO']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paso verificado correctamente por Calidad.',
                'qa_user_name' => $execution->qaUser->name ?? 'CALIDAD',
                'qa_time' => \Carbon\Carbon::parse($execution->qa_verified_at)->format('H:i')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la firma de Calidad: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createPackaging(ProductionOrder $batch)
    {
        $op = $batch->load(['product', 'opPresentations.presentation', 'packagingResult', 'packagingWeightControls']);
        
        // Auto-captura de inicio si no existe
        if (!$op->packagingResult) {
            BatchPackagingResult::create([
                'production_order_id' => $op->id,
                'start_time' => now(),
                'status' => 'PENDIENTE'
            ]);
            $op->refresh();
        }

        $operarios = \App\Models\User::operarios()->get();
        $calidad = \App\Models\User::calidad()->get();

        return view('batch.batch-envase', compact('op', 'operarios', 'calidad'));
    }

    public function storePackaging(Request $request, ProductionOrder $batch)
    {
        $res = $batch->packagingResult;
        if ($res && $res->signed_at) {
            return response()->json(['success' => false, 'message' => 'Módulo bloqueado por firma.'], 403);
        }

        $request->validate([
            'units_obtained' => 'required|integer|min:0',
            'average_weight' => 'required|numeric',
        ]);

        $data = $request->all();
        
        // Sanitize booleans
        $data['color_conforme'] = $request->color_conforme == '1' || $request->color_conforme == 'true';
        $data['odor_conforme'] = $request->odor_conforme == '1' || $request->odor_conforme == 'true';
        $data['texture_conforme'] = $request->texture_conforme == '1' || $request->texture_conforme == 'true';
        $data['particles_free'] = $request->particles_free == '1' || $request->particles_free == 'true';

        // Cálculo de Rendimiento
        $yield = $this->calculateFinalYield($batch, $request->units_obtained);
        
        // Regla de Negocio: 90% - 110%
        $isWithinRange = ($yield >= 90.00 && $yield <= 110.00);
        $finalStatus = $isWithinRange ? 'COMPLETADO' : 'CUARENTENA';

        $res->update(array_merge($data, [
            'units_obtained' => $request->units_obtained,
            'user_id' => $request->on_behalf_of_id ?? Auth::id(),
            'signed_at' => now(),
            'end_time' => now(),
            'status' => 'COMPLETADO'
        ]));

        $this->signatureService->logSignature(
            "Firma de Cierre de Envase (Rendimiento: {$yield}%)",
            'App\Models\ProductionOrder',
            $batch->id,
            $request->all(),
            $request->on_behalf_of_id
        );

        $batch->update([
            'final_yield_percentage' => $yield,
            'status' => $finalStatus
        ]);

        return response()->json([
            'success' => true,
            'yield' => $yield,
            'status' => $finalStatus,
            'is_within_range' => $isWithinRange,
            'user' => Auth::user()->name,
            'signed_at' => now()->format('d/m/Y H:i'),
            'end_time' => now()->format('H:i')
        ]);
    }

    private function calculateFinalYield(ProductionOrder $batch, int $unitsObtained): float
    {
        // Obtener el peso maestro de la presentación (usamos la primera asociada a la OP)
        $opPresentation = $batch->opPresentations->first();
        $theoreticalWeight = $opPresentation->presentation->theoretical_weight ?? 0;
        
        if ($theoreticalWeight <= 0 || $batch->bulk_size_kg <= 0) {
            return 0;
        }

        // El peso de la presentación está en g (gramos), el bulk_size en kg
        // Convertimos todo a KG para el cálculo
        $totalWeightObtainedKg = ($unitsObtained * $theoreticalWeight) / 1000;
        
        $yield = ($totalWeightObtainedKg / $batch->bulk_size_kg) * 100;
        
        return round($yield, 2);
    }

    public function storePackagingWeight(Request $request, ProductionOrder $batch)
    {
        if ($batch->packagingResult && $batch->packagingResult->signed_at) {
            return response()->json(['success' => false, 'message' => 'Registro bloqueado por firma.'], 403);
        }

        $request->validate(['weight' => 'required|numeric']);
        
        $control = BatchPackagingWeightControl::create([
            'production_order_id' => $batch->id,
            'weight' => $request->weight,
            'controlled_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'weight' => $control->weight,
            'time' => $control->controlled_at->format('H:i')
        ]);
    }

    public function verifyPackaging(Request $request, ProductionOrder $batch)
    {
        $res = $batch->packagingResult;
        if (!$res || !$res->signed_at) {
            return response()->json(['success' => false, 'message' => 'El operario debe firmar primero.'], 400);
        }

        $res->update([
            'qa_user_id' => $request->qa_user_id,
            'qa_verified_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'qa_user_name' => $res->qaUser->name,
            'qa_time' => $res->qa_verified_at->format('H:i')
        ]);
    }

}
