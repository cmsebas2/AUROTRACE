<?php

/**
 * ============================================================================
 * CONTROLADOR: ProductionOrderController (Gestión Pre-Operativa de OPs)
 * ============================================================================
 * ¿QUÉ HACE ESTE ARCHIVO? (Explicación "For Dummies")
 * Este controlador administra todo el ciclo de vida de una Orden de Producción (OP)
 * ANTES de que entre a los tanques de fabricación en planta:
 *
 *   - Acto 1 (OP_CREADA): Creación de la OP y explosión automática de materiales
 *     según la Fórmula Maestra y las presentaciones seleccionadas (`create`, `store`).
 *   - Acto 2 (AJ_CREADO): Cálculo de Ajuste de Principios Activos (APIs) según
 *     potencia y humedad del lote de materia prima (`ajusteActivos`, `storeAjusteActivos`, `firmarAjuste`).
 *   - Acto 2B (AJ_VERIFICADO): Verificación de Calidad sobre el ajuste (`verificarAjuste`, `firmarVerificarAjuste`).
 *   - Acto 3 (OP_VERIFICADA): Verificación y cierre del documento maestro OP (`verificarFinal`).
 *   - Acto 4 y 5 (COD_CREADO / COD_VERIFICADO): Solicitud y Aprobación de Codificado (`solicitudCodificado`, `aprobarCodificado`).
 *   - Acto 6 y 7 (COA_CREADO / COA_VERIFICADO): Carga, unificación PDF y aprobación de
 *     Certificados de Análisis (COAs) de materiales (`coasForm`, `storeCoas`, `aprobarCoasForm`, `mergeCoasPdf`).
 *
 * Todas las firmas electrónicas cumplen con la norma FDA 21 CFR Parte 11 (GEMINI.md Art. 3).
 * ============================================================================
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use App\Models\Product;
use App\Models\FormulaIngredient;
use App\Models\OpMaterialReconciliation;
use App\Models\User;
use setasign\Fpdi\Fpdi;
use App\Models\ProductPresentation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Cfr21SignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ProductionOrderController extends Controller
{
    /**
     * Muestra el formulario para crear una nueva OP o, si recibe `?lote=`,
     * abre la vista de verificación final de una OP existente.
     */
    public function create(Request $request)
    {
        $lote = $request->query('lote');

        // ── MODO VERIFICACIÓN: ?lote= presente ──────────────────────────
        if ($lote) {
            $op = ProductionOrder::where('lote', $lote)
                ->with([
                    'product',
                    'opMaterialReconciliations',
                    'opPresentations.presentation',
                ])
                ->firstOrFail();

            // Johann v9.2: Carga forzada y refresco de relaciones para asegurar datos finales en el PDF
            $op->refresh();
            $op->load([
                'product.ingredients',
                'opMaterialReconciliations',
                'opPresentations.presentation'
            ]);

            $explosionData = $op->opMaterialReconciliations->map(function ($m) {
                return [
                    'material_code' => $m->material_code,
                    'type'          => $m->type,
                    'material_name' => $m->description,
                    'function'      => $m->function,
                    'unit'          => $m->unit,
                    'required_qty'  => $m->required_qty,
                    'lots'          => $m->lote ? [['numero' => $m->lote]] : []
                ];
            });

            // Ruta exclusiva para el Acto 3 — espejo de la DB, sin motor de cálculo
            return view('batch.verificar-op', compact('op', 'explosionData'));
        }

        // ── MODO CREACIÓN: formulario limpio ─────────────────────────────
        $productos      = Product::with(['presentations', 'ingredients'])->where('status', 'ACTIVO')->get();
        $presentaciones = ProductPresentation::all();
        return view('batch.iniciar', compact('productos', 'presentaciones'));
    }

    /**
     * Endpoint JSON que devuelve los ingredientes y presentaciones de un producto
     * cuando el usuario lo selecciona en el formulario de creación de OP.
     */
    public function apiGetProductData($id)
    {
        $product = Product::with(['ingredients' => function ($query) {
            $query->orderBy('material_type');
        }])->findOrFail($id);

        return response()->json([
            'ica_license'         => $product->ica_license,
            'pharmaceutical_form' => $product->pharmaceutical_form,
            'vigencia_meses'      => $product->vigencia_meses,
            'base_batch_size'     => $product->base_batch_size,
            'base_unit'           => $product->base_unit,
            'formula_maestra'     => $product->formula_maestra,
            'ingredients'         => $product->ingredients,
            'presentations'       => $product->presentations
        ]);
    }

    /**
     * Guarda o actualiza una Orden de Producción (OP), sus presentaciones,
     * la explosión de materiales y el registro forense en el Audit Trail.
     */
    public function store(Request $request)
    {
        // Debug inicial
        \Log::info("INICIANDO GUARDADO DE OP", ['payload' => $request->except(['password', 'signature_password'])]);

        try {
            DB::beginTransaction();

            // Normalización de Fechas (SQL Format YYYY-MM-DD)
            $mDate = $request->manufacturing_date ? Carbon::parse($request->manufacturing_date)->format('Y-m-d') : now()->format('Y-m-d');
            $eDate = $request->expiration_date ? Carbon::parse($request->expiration_date)->endOfMonth()->format('Y-m-d') : now()->addMonths(24)->endOfMonth()->format('Y-m-d');
            $dDate = $request->destruction_date ? Carbon::parse($request->destruction_date)->format('Y-m-d') : Carbon::parse($mDate)->addYears(5)->format('Y-m-d');

            // 1. Obtener OP si existe para persistencia de estados y firmas
            $existingOp = ProductionOrder::where('op_number', $request->op_number)->first();

            // Lógica de Estados (Regla de Oro: Johann)
            $hasRealizado = $request->realizado_por || ($existingOp && $existingOp->realizado_por);
            $hasVerificado = $request->verificado_por || ($existingOp && $existingOp->verificado_por);

            if ($hasRealizado && $hasVerificado) {
                // Ambos firmados -> Cierre total de la OP (Fase 3)
                $status = 'OP_VERIFICADA';
            } elseif (!$existingOp) {
                // Nueva orden -> Fase 1 (Esperando Ajuste)
                $status = 'OP_CREADA';
            } else {
                // Mantener estado actual (AJ_CREADO, AJ_VERIFICADO, etc.)
                $status = $existingOp->status;
            }

            // Preservar firmas existentes si el request viene vacío (evitar sobreescritura accidental)
            $realizadoPor = $request->realizado_por ?: ($existingOp ? $existingOp->realizado_por : null);
            $realizadoFecha = $request->realizado_fecha ?: ($existingOp ? $existingOp->realizado_fecha : null);
            $verificadoPor = $request->verificado_por ?: ($existingOp ? $existingOp->verificado_por : null);
            $verificadoFecha = $request->verificado_fecha ?: ($existingOp ? $existingOp->verificado_fecha : null);

            $op = ProductionOrder::updateOrCreate(
                ['op_number' => $request->op_number],
                [
                    'product_id'         => $request->product_id,
                    'lote'               => $request->lote,
                    'bulk_size_kg'       => $request->bulk_size_kg,
                    'unit'               => $request->unidad_lote ?? 'KG',
                    'manufacturing_date' => $mDate,
                    'expiration_date'    => $eDate,
                    'destruction_date'   => $dDate,
                    'maquilador'         => 'LABORATORIOS AUROFARMA',
                    'status'             => $status,
                    'realizado_por'      => $realizadoPor,
                    'realizado_fecha'    => $realizadoFecha,
                    'realizado_id'       => ($request->realizado_id && is_numeric($request->realizado_id)) ? $request->realizado_id : ($existingOp ? $existingOp->realizado_id : null),
                    'realizado_at'       => $realizadoFecha ? (Carbon::hasFormat($realizadoFecha, 'd/m/Y H:i:s') ? Carbon::createFromFormat('d/m/Y H:i:s', $realizadoFecha) : Carbon::parse($realizadoFecha)) : ($existingOp ? $existingOp->realizado_at : null),
                    'verificado_por'     => $verificadoPor,
                    'verificado_id'      => ($request->verificado_id && is_numeric($request->verificado_id)) ? $request->verificado_id : ($existingOp ? $existingOp->verificado_id : null),
                    'verificado_at'      => $verificadoFecha ? (Carbon::hasFormat($verificadoFecha, 'd/m/Y H:i:s') ? Carbon::createFromFormat('d/m/Y H:i:s', $verificadoFecha) : Carbon::parse($verificadoFecha)) : ($existingOp ? $existingOp->verificado_at : null),
                    'verificado_fecha'   => $verificadoFecha ? (Carbon::hasFormat($verificadoFecha, 'd/m/Y H:i:s') ? Carbon::createFromFormat('d/m/Y H:i:s', $verificadoFecha)->format('Y-m-d H:i:s') : $verificadoFecha) : null,
                    'theoretical_kg'     => $request->theoretical_kg ?? 0,
                    'theoretical_units'  => $request->theoretical_units ?? 0,
                    'actual_kg'          => $request->actual_kg ?? 0,
                    'actual_units'       => $request->actual_units ?? 0,
                    'yield_percentage'   => $request->yield_percentage ?? 0
                ]
            );

            // Johann v9.0: Disparador 2 - Cierre de Verificación Final (A3PPR0007)
            if ($status === 'OP_VERIFICADA') {
                $this->generateAndStoreBatchPdf($op, 'MASTER');
            }

            // Limpiar relaciones previas si es una actualización para evitar duplicidad
            $op->opPresentations()->delete();

            // 2. Registrar Presentaciones
            if ($request->has('presentations')) {
                foreach ($request->presentations as $p) {
                    if (isset($p['id']) && isset($p['quantity']) && $p['id']) {
                        $op->opPresentations()->create([
                            'presentation_id'  => $p['id'],
                            'units_to_produce' => $p['quantity'],
                            'total_kg'         => 0
                        ]);
                    }
                }
            }

            // 3. Explotar Insumos (Persistencia de Balances)
            $explosion = is_array($request->explosion_data) ? $request->explosion_data : json_decode($request->explosion_data, true);
            if (is_array($explosion)) {
                // Obtenemos los códigos que vienen en la explosión para saber qué borrar (si alguno ya no existe)
                $codigosEnExplosion = collect($explosion)->pluck('material_code')->filter()->toArray();

                // Borrar solo los que ya no están en la nueva explosión
                $op->opMaterialReconciliations()->whereNotIn('material_code', $codigosEnExplosion)->delete();

                foreach ($explosion as $item) {
                    $existingRecon = $op->opMaterialReconciliations()->where('material_code', $item['material_code'])->first();

                    // Johann v3.5: Extracción robusta de lotes
                    $loteMP = null;
                    if (isset($item['lots']) && is_array($item['lots'])) {
                        $loteMP = collect($item['lots'])
                            ->pluck('numero')
                            ->filter(fn($val) => !empty($val) && $val !== 'null')
                            ->unique()
                            ->implode(', ');

                        if (!empty($loteMP)) {
                            \Log::info("LOTE CAPTURADO PARA {$item['material_code']}: {$loteMP}");
                        }
                    } elseif (isset($item['lote'])) {
                        // Johann v3.6: Fallback para vistas de solo lectura o verificación que envían lote como string
                        $loteMP = $item['lote'];
                    }

                    OpMaterialReconciliation::updateOrCreate(
                        [
                            'production_order_id' => $op->id,
                            'material_code'       => $item['material_code']
                        ],
                        [
                            'type'         => $item['type'] ?? 'MATERIA PRIMA',
                            'description'  => $item['material_name'] ?? ($item['description'] ?? '---'),
                            'function'     => $item['function'] ?? '---',
                            'unit'         => $item['unit'],
                            'required_qty' => (is_numeric($item['required_qty'])) ? $item['required_qty'] : 0,
                            'lote'         => $loteMP, // PERSISTENCIA DE LOTE MP
                            'date'         => $existingRecon ? $existingRecon->date : now(),
                            'observations' => $existingRecon ? $existingRecon->observations : null
                        ]
                    );
                }
            }

            // 4. Audit Trail Mandate (Regla 4) — DENTRO de la transacción
            // Johann v9.6: Enriquecimiento de metadatos forenses (ICA, Formula, Producto y Payload Original)
            $product = Product::find($op->product_id);
            $mDateFormatted = $op->manufacturing_date ? Carbon::parse($op->manufacturing_date)->format('Y-m') : '---';

            $enrichedMetadata = [
                'detalles_op' => [
                    'producto'          => $product->name ?? '---',
                    'licencia_ica'      => $product->ica_license ?? '---',
                    'formula_maestra'   => $product->formula_maestra ?? '---',
                    'lote'              => $op->lote,
                    'numero_op'         => $op->op_number,
                    'fecha_fabricacion' => $mDateFormatted,
                    'fecha_vencimiento' => $op->expiration_date ? Carbon::parse($op->expiration_date)->format('Y-m-d') : '---',
                    'estado_inicial'    => $op->status,
                    'tamaño_lote'       => "{$op->bulk_size_kg} {$op->unit}"
                ],
                'presentaciones' => $op->opPresentations()->with('presentation')->get()->map(function ($p) {
                    return [
                        'nombre'   => $p->presentation->name ?? "ID: {$p->presentation_id}",
                        'cantidad' => $p->units_to_produce
                    ];
                })->toArray(),
                'desglosado_materiales' => collect($explosion)
                    ->sortBy(function ($item) {
                        $order = ['MATERIA PRIMA' => 1, 'ENVASE' => 2, 'EMPAQUE' => 3];
                        return $order[strtoupper($item['type'] ?? 'MATERIA PRIMA')] ?? 99;
                    })
                    ->map(function ($item) {
                        // Extraer lotes para el log
                        $lotesStr = '---';
                        if (isset($item['lots']) && is_array($item['lots'])) {
                            $lotesStr = collect($item['lots'])->pluck('numero')->filter()->implode(', ');
                        } elseif (isset($item['lote'])) {
                            $lotesStr = $item['lote'];
                        }

                        return [
                            'codigo'             => $item['material_code'],
                            'nombre'             => $item['material_name'] ?? ($item['description'] ?? '---'),
                            'tipo'               => $item['type'] ?? 'MATERIA PRIMA',
                            'cantidad_requerida' => $item['required_qty'] . " " . ($item['unit'] ?? ''),
                            'lote_asignado'      => $lotesStr ?: '---'
                        ];
                    })->values()->toArray()
            ];

            // Fusionar payload original con datos enriquecidos para no perder nada
            $finalPayload = array_merge($op->toArray(), ['METADATOS_ENRIQUECIDOS' => $enrichedMetadata]);

            DB::table('audit_logs')->insert([
                'user_id'    => Auth::id(),
                'action'     => 'CREACION OP INTELIGENTE',
                'model_type' => 'App\Models\ProductionOrder',
                'model_id'   => $op->id,
                'new_values' => json_encode($finalPayload),
                'reason'     => "Creación de Orden de Producción [Lote: {$op->lote}] bajo formato A3PPR0007 | Estado: {$op->status}",
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'redirect' => route('op.ejecucion'),
                'message'  => "Orden {$op->op_number} generada correctamente."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("ERROR CRÍTICO AL GENERAR OP (AJAX): " . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error técnico al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista las Órdenes de Producción en fase de Ejecución Documental (Ajustes, Verificación, Codificado).
     */
    public function indexExecution()
    {
        $ops = ProductionOrder::whereIn('status', [
                'OP CREADA',
                'PROGRAMADA',
                'PENDIENTE',
                'OP_CREADA',
                'AJ_CREADO',
                'AJ_VERIFICADO',
                'AJUSTE REALIZADO',
                'OP_VERIFICADA',
                'COD_CREADO',
                'COD_VERIFICADO',
                'COA_CREADO',
                'EN PRODUCCIÓN',
                'FINALIZADO',
                'PESAJE',
                'MANUFACTURA',
                'ACONDICIONAMIENTO'
            ])
            ->where(function ($q) {
                $q->whereNull('codificado_elaborado_id')
                  ->orWhereNull('codificado_aprobado_id')
                  ->orWhereNotNull('coas_aprobado_id');
            })
            ->with(['product', 'opPresentations.presentation', 'lineClearances'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('batch.ops-ejecucion', compact('ops'));
    }

    /**
     * Lista las Órdenes de Producción activas en el panel de monitoreo de planta.
     */
    public function indexActive()
    {
        $ops = ProductionOrder::active()
            ->with(['product', 'opPresentations.presentation', 'lineClearances'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('batch.ops-monitoreo', compact('ops'));
    }

    /**
     * Paso A4PPR0007: Verificación Ajustes de Principios Activos (Vista de Elaboración).
     */
    public function ajusteActivos($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product.ingredients', 'opMaterialReconciliations'])
            ->firstOrFail();

        // Obtener APIs trazadores directamente del catálogo de productos para asegurar consistencia
        $productApis = $op->product->ingredients->filter(function ($ing) {
            return mb_strtoupper($ing->function) === 'API';
        });

        return view('batch.ajuste-activos', compact('op', 'productApis'));
    }

    /**
     * Guarda los cálculos de ajuste de potencia y humedad de Principios Activos (APIs).
     */
    public function storeAjusteActivos(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();

        if (!in_array($op->status, ['OP CREADA', 'PROGRAMADA', 'PENDIENTE', 'OP_CREADA', 'AJ_CREADO'])) {
            return redirect()->route('op.ejecucion')->with('error', 'No se puede modificar el ajuste para esta orden en su estado actual.');
        }

        try {
            DB::beginTransaction();

            if ($request->has('ajustes')) {
                foreach ($request->ajustes as $materialCode => $data) {
                    if (isset($data['cantidad_final']) && $data['cantidad_final'] !== '') {
                        $reconciliation = OpMaterialReconciliation::where('production_order_id', $op->id)
                            ->where('material_code', $materialCode)
                            ->first();

                        if (!$reconciliation) {
                            throw new \Exception("ERROR DE INTEGRIDAD: No se encontró el material " . $materialCode);
                        }

                        $rawQty = $data['cantidad_final'] ?? '0';
                        $cleanQty = str_replace(',', '.', $rawQty);
                        $finalQty = floatval($cleanQty);

                        // REGLA DE ORO: No guardar si la cantidad es 0 (error de formulario)
                        if ($finalQty <= 0) {
                            \Log::warning("Intento de guardar cantidad 0 para material " . $materialCode);
                            continue;
                        }

                        // Parseo robusto de los 4 pilares
                        $bh  = floatval(str_replace(',', '.', $data['bh'] ?? 0));
                        $bs  = floatval(str_replace(',', '.', $data['bs'] ?? 0));
                        $hum = floatval(str_replace(',', '.', $data['humedad'] ?? 0));
                        $pct = floatval(str_replace(['%', ','], ['', '.'], $data['ajuste'] ?? 0));

                        $updated = $reconciliation->update([
                            'required_qty'      => $finalQty,
                            'bh_valor'          => $bh,
                            'bs_valor'          => $bs,
                            'humedad_valor'     => $hum,
                            'ajuste_porcentaje' => $pct,
                            'lote'              => $data['lote'] ?? $reconciliation->lote,
                            'observations'      => $data['observaciones'] ?? null
                        ]);

                        if (!$updated) {
                            throw new \Exception("ERROR DE ESCRITURA: No se pudo actualizar el material " . $materialCode);
                        }
                    }
                }
            }

            // ACTO 2: Tras guardar ajustes exitosamente, la OP pasa a AJ_CREADO
            $op->status = 'AJ_CREADO';
            $op->save();

            DB::commit();

            return redirect()->route('op.ejecucion')->with('success', 'Ajustes de Principios Activos registrados. Pendiente de verificación por Calidad.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("ERROR AL GUARDAR AJUSTE DE ACTIVOS: " . $e->getMessage());
            return back()->withInput()->with('error', 'Error al guardar el ajuste de activos: ' . $e->getMessage());
        }
    }

    /**
     * Firma electrónica CFR 21 Parte 11 para la elaboración del Ajuste de Activos.
     */
    public function firmarAjuste(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();

        // Uso del Servicio Universal de Firma (Johann v3.0)
        $user = app(Cfr21SignatureService::class)->validateSignature($request->username, $request->password);

        if ($user) {
            $op->update([
                'realizado_id'  => $user->id,
                'realizado_por' => $user->name,
                'realizado_at'  => now(),
                'status'        => 'AJ_CREADO'
            ]);

            $now = now();
            $compact = $request->compact ? true : false;

            return response()->json([
                'success'        => true,
                'user_name'      => $user->name,
                'timestamp'      => $now->format('Y-m-d H:i:s'),
                'signature_html' => app(Cfr21SignatureService::class)->renderSignatureHtml($user->name, $now, $compact),
                'new_token'      => csrf_token(),
                'redirect'       => route('op.crear', ['lote' => $op->lote])
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
    }

    /**
     * Paso A4PPR0007-V: Verificación de Ajustes (Aseguramiento de Calidad).
     */
    public function verificarAjuste($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product.ingredients', 'opMaterialReconciliations'])
            ->firstOrFail();

        if (!in_array($op->status, ['AJ_CREADO', 'AJ_VERIFICADO'])) {
            return redirect()->route('op.ejecucion')->with('error', 'Esta orden no requiere verificación de ajuste actualmente.');
        }

        $productApis = $op->product->ingredients->filter(function ($ing) {
            return mb_strtoupper($ing->function) === 'API';
        });

        return view('batch.verificar-ajuste', compact('op', 'productApis'));
    }

    /**
     * Confirma y guarda el paso de Verificación de Ajuste por Calidad.
     */
    public function storeVerificarAjuste(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();

        // Tras la verificación de Calidad, la OP pasa a AJ_VERIFICADO para permitir Fase 3 (Verificar OP)
        $op->status = 'AJ_VERIFICADO';
        $op->save();

        return redirect()->route('op.ejecucion')->with('success', 'Verificación de ajuste (Calidad) exitosa. La Orden de Producción ya puede ser verificada para cierre.');
    }

    /**
     * Firma electrónica CFR 21 Parte 11 para la Verificación de Ajuste por Calidad.
     */
    public function firmarVerificarAjuste(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $user = app(Cfr21SignatureService::class)->validateSignature($request->username, $request->password);

        if ($user) {
            $op->update([
                'verificado_id'  => $user->id,
                'verificado_por' => $user->name,
                'verificado_at'  => now(),
                'status'         => 'AJ_VERIFICADO'
            ]);

            // Johann v9.0: Disparador 1 - Cierre Técnico de Ajuste (A4PPR0007)
            $this->generateAndStoreBatchPdf($op, 'A4PPR0007');

            $now = now();
            return response()->json([
                'success'        => true,
                'user_id'        => $user->id,
                'user_name'      => $user->name,
                'timestamp'      => $now->format('Y-m-d H:i:s'),
                'signature_html' => app(Cfr21SignatureService::class)->renderSignatureHtml($user->name, $now, false),
                'new_token'      => csrf_token()
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
    }

    /**
     * Vista de Verificación Final de la Orden de Producción (Acto 3).
     */
    public function verificarFinal($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product', 'opPresentations.presentation', 'opMaterialReconciliations'])
            ->firstOrFail();

        // Mapear reconciliaciones para el formato esperado por Alpine.js (explosionData)
        $explosionData = $op->opMaterialReconciliations->map(function ($m) {
            return [
                'material_code' => $m->material_code,
                'type'          => $m->type,
                'material_name' => $m->description,
                'function'      => $m->function,
                'unit'          => $m->unit,
                'required_qty'  => $m->required_qty,
                'lots'          => $m->lote ? [['numero' => $m->lote]] : []
            ];
        });

        return view('batch.verificar-op', compact('op', 'explosionData'));
    }

    /**
     * Paso A6PPR0007: Solicitud de Codificado (Momento 1: Elaboración).
     */
    public function solicitudCodificado($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product.ingredients', 'opMaterialReconciliations', 'opPresentations.presentation'])
            ->firstOrFail();

        // Si ya está aprobado, no tiene sentido estar aquí
        if ($op->codificado_aprobado_id) {
            return redirect()->route('op.ejecucion')->with('info', 'Esta solicitud ya ha sido finalizada.');
        }

        if ($op->status !== 'OP_VERIFICADA') {
            return redirect()->route('op.ejecucion')->with('error', 'La Orden de Producción debe estar VERIFICADA para generar la solicitud.');
        }

        return view('batch.solicitud-codificado', compact('op'));
    }

    /**
     * Guarda las observaciones de la Solicitud de Codificado una vez firmada la elaboración.
     */
    public function storeSolicitudCodificado(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $op->refresh(); // Refrescar para capturar la firma del AJAX

        // Validamos que al menos esté firmado por elaboración en DB
        if (!$op->codificado_elaborado_id) {
            return back()->with('error', 'Debe aplicar la firma de ELABORACIÓN antes de guardar la solicitud.');
        }

        $op->update([
            'codificado_observaciones' => $request->observaciones
        ]);

        return redirect()->route('op.ejecucion')->with('success', 'Solicitud de Codificado elaborada exitosamente. Ahora el supervisor puede proceder con la aprobación.');
    }

    /**
     * Paso A6PPR0007: Aprobación de Codificado (Momento 2: Cierre).
     */
    public function aprobarCodificado($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product.ingredients', 'opMaterialReconciliations', 'opPresentations.presentation'])
            ->firstOrFail();

        // Si ya está aprobado, no tiene sentido estar aquí
        if ($op->codificado_aprobado_id) {
            return redirect()->route('op.ejecucion')->with('info', 'Esta solicitud ya ha sido finalizada.');
        }

        // Solo permitir si ya fue elaborado
        if (!$op->codificado_elaborado_id) {
            return redirect()->route('op.solicitud_codificado', $lote);
        }

        return view('batch.aprobar-codificado', compact('op'));
    }

    /**
     * Finaliza la Aprobación de Codificado y transfiere el lote a Aseguramiento de Calidad.
     */
    public function storeAprobarCodificado(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $op->refresh(); // Refrescar para capturar la firma del AJAX

        if (!$op->codificado_aprobado_id) {
            return back()->with('error', 'Debe aplicar la firma de APROBACIÓN antes de finalizar.');
        }

        // Al aprobar, la OP automáticamente queda lista para el dashboard de Calidad (indexQuality)
        return redirect()->route('op.ejecucion')->with('success', 'Solicitud de Codificado aprobada. El lote ha sido transferido a Aseguramiento de Calidad.');
    }

    /**
     * Firma electrónica CFR 21 Parte 11 para Solicitud/Aprobación de Codificado.
     */
    public function firmarSolicitudCodificado(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $user = app(Cfr21SignatureService::class)->validateSignature($request->username, $request->password);

        if ($user) {
            $type = $request->type; // 'elaborado' o 'aprobado'

            if ($type === 'elaborado') {
                $op->update([
                    'codificado_elaborado_id'  => $user->id,
                    'codificado_elaborado_por' => $user->name,
                    'codificado_elaborado_at'  => now(),
                    'status'                   => 'COD_CREADO'
                ]);
            } else {
                $op->update([
                    'codificado_aprobado_id'  => $user->id,
                    'codificado_aprobado_por' => $user->name,
                    'codificado_aprobado_at'  => now(),
                    'status'                  => 'COD_VERIFICADO'
                ]);
            }

            $now = now();
            return response()->json([
                'success'        => true,
                'user_name'      => $user->name,
                'timestamp'      => $now->format('Y-m-d H:i:s'),
                'signature_html' => app(Cfr21SignatureService::class)->renderSignatureHtml($user->name, $now, false),
                'new_token'      => csrf_token()
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
    }

    /**
     * Eliminación permanente de una OP (Exclusivo para Administradores con firma CFR 21).
     * Limpia todas las tablas hijas antes de eliminar la OP y guarda un snapshot forense en AuditLog.
     */
    public function destroy(Request $request, ProductionOrder $batch)
    {
        // 1. Restricción de Rol
        if (!auth()->user()->hasRole(['ADMIN', 'Administrador', 'admin'])) {
            return back()->with('error', 'No tiene permisos para ejecutar eliminaciones permanentes.');
        }

        // 2. Validación de Firma Electrónica (Protocolo CFR 21)
        $request->validate([
            'password' => ['required', new \App\Rules\Cfr21Signature()],
            'reason'   => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $lote = $batch->lote;

            // CAPTURA DE SNAPSHOT PRE-ELIMINACIÓN (Para Audit Trail)
            $snapshot = [
                'lote'            => $batch->lote,
                'producto'        => $batch->product?->name ?? 'N/A',
                'estado_anterior' => $batch->status,
                'cantidad'        => $batch->bulk_size_kg . ' KG',
                'creado_el'       => $batch->created_at?->format('d/m/Y H:i') ?? '---',
                'autorizado_por'  => auth()->user()->name
            ];

            // 3. Limpieza de Relaciones (Hard Delete en orden de llaves foráneas)
            if ($batch->dispensing) {
                $batch->dispensing->dispensingDetails()->delete();
                $batch->dispensing()->delete();
            }
            $batch->packagingWeightControls()->delete();
            $batch->packagingResult()->delete();
            $batch->opPresentations()->forceDelete();
            $batch->lineClearances()->forceDelete();
            $batch->opMaterialReconciliations()->forceDelete();
            $batch->manufacturingExecutions()->forceDelete();

            // 4. Eliminación Permanente de la OP
            $batch->forceDelete();

            // 5. Audit Trail Enriquecido (CFR 21)
            DB::table('audit_logs')->insert([
                'user_id'    => Auth::id(),
                'action'     => 'ELIMINACIÓN PERMANENTE',
                'model_type' => 'App\Models\ProductionOrder',
                'model_id'   => null,
                'old_values' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                'new_values' => null,
                'reason'     => "PURGA DE SISTEMA: " . $request->reason,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('op.activas')->with('success', "La Orden de Producción Lote {$lote} ha sido eliminada permanentemente del sistema.");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("ERROR EN ELIMINACIÓN DE OP: " . $e->getMessage());
            return back()->with('error', 'Error crítico al eliminar la OP: ' . $e->getMessage());
        }
    }

    /**
     * Johann v9.0: Motor de Persistencia de Expedientes en Servidor.
     * Genera y guarda versiones de PDF sin sobrescribir (Protocolo Auditoría).
     */
    private function generateAndStoreBatchPdf(ProductionOrder $op, $type = 'MASTER')
    {
        try {
            // Johann v9.2: Refresco de modelo para capturar cambios persistidos segundos antes
            $op->refresh();
            $op->load(['product.ingredients', 'opMaterialReconciliations', 'opPresentations.presentation']);

            $productos = Product::with(['presentations', 'ingredients'])->where('status', 'ACTIVO')->get();
            $presentaciones = ProductPresentation::all();
            $productApis = $op->product->ingredients->filter(fn($ing) => mb_strtoupper($ing->function) === 'API');
            $is_pdf = true;

            $view = ($type === 'MASTER') ? 'batch-records.master-pdf' : 'batch.ajuste-activos';

            $pdf = Pdf::loadView($view, compact('op', 'productos', 'presentaciones', 'productApis', 'is_pdf'))
                ->setPaper('letter', 'landscape')
                ->setOption(['isRemoteEnabled' => true, 'chroot' => public_path()]);

            $folder = "batch_records/{$op->lote}";
            $filename = "{$type}_{$op->lote}.pdf";
            $path = "{$folder}/{$filename}";

            // Lógica de No Sobrescribir (Control de Versiones)
            if (Storage::disk('local')->exists($path)) {
                $count = 1;
                while (Storage::disk('local')->exists("{$folder}/{$type}_{$op->lote}_V{$count}.pdf")) {
                    $count++;
                }
                $path = "{$folder}/{$type}_{$op->lote}_V{$count}.pdf";
            }

            Storage::disk('local')->put($path, $pdf->output());
            \Log::info("PDF EBR GUARDADO: {$path}");

        } catch (\Exception $e) {
            \Log::error("ERROR GENERANDO PDF AUTOMÁTICO: " . $e->getMessage());
        }
    }

    // ---------------------------------------------------------
    // MÓDULO DE ASEGURAMIENTO DE CALIDAD (COAS) - Johann v10.0
    // ---------------------------------------------------------

    /**
     * Bandeja de Aseguramiento de Calidad para OPs pendientes de carga o aprobación de COAs.
     */
    public function indexQuality()
    {
        $ops = ProductionOrder::whereNotNull('codificado_elaborado_id')
            ->whereNotNull('codificado_aprobado_id')
            ->whereNull('coas_aprobado_id') // Ocultar si ya completaron la fase de COAs
            ->with(['product', 'opPresentations.presentation'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('batch.ops-calidad', compact('ops'));
    }

    /**
     * Formulario de carga de Certificados de Análisis (COAs) de insumos.
     */
    public function coasForm($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product.ingredients', 'opMaterialReconciliations' => function ($q) {
                $q->orderByRaw("CASE UPPER(type) WHEN 'MATERIA PRIMA' THEN 1 WHEN 'ENVASE' THEN 2 WHEN 'EMPAQUE' THEN 3 ELSE 4 END, id ASC");
            }, 'opPresentations.presentation'])
            ->firstOrFail();

        // Must have completed codificado
        if (!$op->codificado_elaborado_id || !$op->codificado_aprobado_id) {
            return redirect()->route('op.ejecucion')->with('error', 'La Orden de Producción debe tener la solicitud de codificado completa para ingresar a COAs.');
        }

        return view('batch.coas', compact('op'));
    }

    /**
     * Guarda los números de análisis, fechas de vencimiento y archivos PDF de los COAs.
     */
    public function storeCoas(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();

        // Protect from modifications if already "Aprobado"
        if ($op->coas_aprobado_id) {
            return redirect()->route('op.calidad')->with('error', 'Los COAs ya han sido aprobados y no pueden modificarse.');
        }

        try {
            DB::beginTransaction();

            $op->update([
                'coas_observaciones' => $request->observaciones,
                'status'             => 'COA_CREADO'
            ]);

            if ($request->has('materials')) {
                foreach ($request->materials as $matId => $data) {
                    $recon = OpMaterialReconciliation::where('id', $matId)
                        ->where('production_order_id', $op->id)
                        ->first();

                    if ($recon) {
                        $updateData = [];

                        if (isset($data['n_analisis'])) {
                            $updateData['n_analisis'] = $data['n_analisis'];
                        }
                        if (isset($data['fecha_vencimiento_coa'])) {
                            $updateData['fecha_vencimiento_coa'] = $data['fecha_vencimiento_coa'];
                        }

                        // File Upload
                        if ($request->hasFile("materials.{$matId}.coa_file")) {
                            $file = $request->file("materials.{$matId}.coa_file");
                            $path = $file->store("coas/{$op->lote}", 'public');
                            $updateData['coa_pdf_path'] = $path;
                        }

                        if (!empty($updateData)) {
                            $recon->update($updateData);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('op.calidad')->with('success', 'COAs guardados exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("ERROR GUARDANDO COAS: " . $e->getMessage());
            return back()->with('error', 'Error al guardar COAs: ' . $e->getMessage());
        }
    }

    /**
     * Firma electrónica CFR 21 Parte 11 para Realización o Aprobación de COAs.
     */
    public function firmarCoas(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $type = $request->input('type'); // 'realizado' o 'aprobado'

        $user = app(Cfr21SignatureService::class)->validateSignature($request->username, $request->password);

        if ($user) {
            if ($type === 'realizado') {
                $op->update([
                    'coas_realizado_id'  => $user->id,
                    'coas_realizado_por' => $user->name,
                    'coas_realizado_at'  => now(),
                ]);
            } elseif ($type === 'aprobado') {
                $op->update([
                    'coas_aprobado_id'  => $user->id,
                    'coas_aprobado_por' => $user->name,
                    'coas_aprobado_at'  => now(),
                    'status'            => 'COA_VERIFICADO'
                ]);
            }

            return response()->json([
                'success'        => true,
                'user_name'      => $user->name,
                'timestamp'      => now()->format('Y-m-d H:i:s'),
                'signature_html' => app(Cfr21SignatureService::class)->renderSignatureHtml($user->name, now(), false),
                'new_token'      => csrf_token()
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
    }

    /**
     * Vista de Aprobación Final de COAs por Aseguramiento de Calidad.
     */
    public function aprobarCoasForm($lote)
    {
        $op = ProductionOrder::where('lote', $lote)
            ->with(['product.ingredients', 'opMaterialReconciliations' => function ($q) {
                $q->orderByRaw("CASE UPPER(type) WHEN 'MATERIA PRIMA' THEN 1 WHEN 'ENVASE' THEN 2 WHEN 'EMPAQUE' THEN 3 ELSE 4 END, id ASC");
            }, 'opPresentations.presentation'])
            ->firstOrFail();

        // Solo permitir si ya fue realizado
        if (!$op->coas_realizado_id) {
            return redirect()->route('op.coas', $lote);
        }

        return view('batch.aprobar-coas', compact('op'));
    }

    /**
     * Confirma el cierre de Aprobación de COAs.
     */
    public function storeAprobarCoas(Request $request, $lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $op->refresh();

        if (!$op->coas_aprobado_id) {
            return back()->with('error', 'Debe aplicar la firma de APROBACIÓN antes de finalizar.');
        }

        return redirect()->route('op.calidad')->with('success', 'COAs aprobados exitosamente. El lote puede continuar su proceso.');
    }

    /**
     * Unifica todos los PDFs de COAs cargados para una OP en un solo documento PDF continuo.
     */
    public function mergeCoasPdf($lote)
    {
        $op = ProductionOrder::where('lote', $lote)->firstOrFail();
        $materials = $op->opMaterialReconciliations()
            ->orderByRaw("CASE UPPER(type) WHEN 'MATERIA PRIMA' THEN 1 WHEN 'ENVASE' THEN 2 WHEN 'EMPAQUE' THEN 3 ELSE 4 END, id ASC")
            ->whereNotNull('coa_pdf_path')
            ->get();

        if ($materials->isEmpty()) {
            return back()->with('error', 'No hay archivos PDF cargados para unificar.');
        }

        $pdf = new Fpdi();

        foreach ($materials as $m) {
            $filePath = storage_path('app/public/' . $m->coa_pdf_path);
            if (file_exists($filePath)) {
                try {
                    $pageCount = $pdf->setSourceFile($filePath);
                    for ($n = 1; $n <= $pageCount; $n++) {
                        $tplIdx = $pdf->importPage($n);
                        $specs = $pdf->getTemplateSize($tplIdx);
                        $pdf->AddPage($specs['orientation'], [$specs['width'], $specs['height']]);
                        $pdf->useTemplate($tplIdx);
                    }
                } catch (\Exception $e) {
                    // Si un PDF falla, saltar al siguiente
                    continue;
                }
            }
        }

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="COAS_UNIFICADOS_' . $lote . '.pdf"');
    }

    /**
     * Alias de compatibilidad hacia GlobalSignatureController::validateSignature.
     */
    public function validateSignature(Request $request)
    {
        return app(GlobalSignatureController::class)->validateSignature($request);
    }
}
