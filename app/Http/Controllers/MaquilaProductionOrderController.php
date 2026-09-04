<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaquilaProductionOrder;
use App\Models\MaquilaItem;
use App\Models\MaquilaDelivery;
use App\Models\Maquilador;
use App\Models\Product;
use App\Models\Item;
use App\Models\AuditLog;
use App\Services\Cfr21SignatureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaquilaProductionOrderController extends Controller
{
    protected $cfr21Service;

    public function __construct(Cfr21SignatureService $cfr21Service)
    {
        $this->cfr21Service = $cfr21Service;
    }

    /**
     * Auto-migración en caliente para garantizar esquema en cualquier base de datos
     */
    protected function ensureSchema()
    {
        // Migraciones y seeders gestionados vía /run-migrations para no penalizar requests
        return true;
    }

    /**
     * Dashboard de Maquilas Externas & Control 360° de Batch Records (Optimizado)
     */
    public function dashboard(Request $request)
    {
        $this->ensureSchema();

        $statusFilter = $request->query('estado');
        $search = trim($request->query('buscar', ''));
        $maquiladorFilter = $request->query('maquilador_id');

        $query = MaquilaProductionOrder::with(['maquilador', 'items.deliveries', 'creator', 'dtUser', 'qaUser']);

        // Filtro por Estado del Ciclo de Vida
        if ($statusFilter && $statusFilter !== 'todos') {
            if ($statusFilter === 'creada') {
                $query->whereIn('estado', ['OP CREADA', 'borrador']);
            } elseif ($statusFilter === 'produccion') {
                $query->whereIn('estado', ['OP EN PRODUCCION', 'enviada_a_maquila', 'en_proceso', 'entrega_parcial']);
            } elseif ($statusFilter === 'br_pendiente') {
                $query->whereIn('estado', ['OP TERMINADA - BR PENDIENTE', 'completada_pendiente_liquidacion']);
            } elseif ($statusFilter === 'revision') {
                $query->whereIn('estado', ['BR REVISION DT', 'BR REVISION CALIDAD']);
            } elseif ($statusFilter === 'cerrado') {
                $query->whereIn('estado', ['BR CERRADO', 'liquidada', 'cerrada_tecnicamente']);
            } elseif ($statusFilter === 'abierto') {
                $query->where('estado', 'BR ABIERTO');
            }
        }

        // Búsqueda inteligente por OP, ODM, Pre-Orden, Lote o Producto
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('op', 'LIKE', "%{$search}%")
                  ->orWhere('numero_odm', 'LIKE', "%{$search}%")
                  ->orWhere('pre_orden', 'LIKE', "%{$search}%")
                  ->orWhere('lote', 'LIKE', "%{$search}%")
                  ->orWhere('producto_nombre', 'LIKE', "%{$search}%")
                  ->orWhereHas('items', function ($itemQ) use ($search) {
                      $itemQ->where('codigo_item', 'LIKE', "%{$search}%")
                            ->orWhere('descripcion_producto', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($maquiladorFilter) {
            $query->where('maquilador_id', $maquiladorFilter);
        }

        $orders = $query->latest('id')->get();

        // Métricas y KPIs de Planta consolidadas en 1 sola consulta SQL ultrarrápida
        $kpis = \Illuminate\Support\Facades\Cache::remember('maquila_dashboard_kpis_v1', 30, function () {
                $hasLeadTime = \Illuminate\Support\Facades\Schema::hasColumn('maquila_production_orders', 'lead_time_dias');
                $leadTimeExpr = $hasLeadTime ? "AVG(lead_time_dias)" : "0";

                $raw = DB::table('maquila_production_orders')
                    ->whereNull('deleted_at')
                    ->selectRaw("
                        COUNT(*) as total,
                        COUNT(CASE WHEN estado IN ('OP EN PRODUCCION', 'enviada_a_maquila', 'en_proceso', 'entrega_parcial') THEN 1 END) as produccion,
                        COUNT(CASE WHEN estado IN ('OP TERMINADA - BR PENDIENTE', 'completada_pendiente_liquidacion') THEN 1 END) as br_pendiente,
                        COUNT(CASE WHEN estado IN ('BR REVISION DT', 'BR REVISION CALIDAD') THEN 1 END) as revision,
                        COUNT(CASE WHEN estado IN ('BR CERRADO', 'liquidada', 'cerrada_tecnicamente') THEN 1 END) as cerrado,
                        AVG(rendimiento_real) as avg_yield,
                        {$leadTimeExpr} as avg_lead_time
                    ")->first();

                return [
                    'totalOps' => (int) ($raw->total ?? 0),
                    'opsEnProduccion' => (int) ($raw->produccion ?? 0),
                    'opsBrPendiente' => (int) ($raw->br_pendiente ?? 0),
                    'opsEnRevision' => (int) ($raw->revision ?? 0),
                    'opsBrCerrado' => (int) ($raw->cerrado ?? 0),
                    'rendimientoPromedioGlobal' => $raw->avg_yield ? round($raw->avg_yield, 2) : 100.0,
                    'leadTimePromedio' => $raw->avg_lead_time ? round($raw->avg_lead_time, 1) : 0.0,
                ];
            } catch (\Throwable $e) {
                return [
                    'totalOps' => 0,
                    'opsEnProduccion' => 0,
                    'opsBrPendiente' => 0,
                    'opsEnRevision' => 0,
                    'opsBrCerrado' => 0,
                    'rendimientoPromedioGlobal' => 100.0,
                    'leadTimePromedio' => 0.0,
                ];
            }
        });

        $totalOps = $kpis['totalOps'];
        $opsEnProduccion = $kpis['opsEnProduccion'];
        $opsBrPendiente = $kpis['opsBrPendiente'];
        $opsEnRevision = $kpis['opsEnRevision'];
        $opsBrCerrado = $kpis['opsBrCerrado'];
        $rendimientoPromedioGlobal = $kpis['rendimientoPromedioGlobal'];
        $leadTimePromedio = $kpis['leadTimePromedio'];

        $maquiladores = \Illuminate\Support\Facades\Cache::remember('maquiladores_activos_v1', 300, function () {
            return Maquilador::where('activo', true)->orderBy('nombre')->get();
        });

        return view('maquila.dashboard', compact(
            'orders',
            'totalOps',
            'opsEnProduccion',
            'opsBrPendiente',
            'opsEnRevision',
            'opsBrCerrado',
            'rendimientoPromedioGlobal',
            'leadTimePromedio',
            'maquiladores',
            'statusFilter',
            'search',
            'maquiladorFilter'
        ));
    }

    /**
     * Paso 1: Formulario de Creación de Orden de Maquila
     */
    public function create()
    {
        $this->ensureSchema();

        $maquiladores = Maquilador::where('activo', true)->orderBy('nombre')->get();
        $productos = Product::where('status', 'ACTIVO')->orderBy('name')->get();

        // Sugerencia correlativa de ODM
        $year = date('Y');
        $countThisYear = MaquilaProductionOrder::whereYear('fecha_creacion', $year)->count() + 1;
        $nextOdm = 'ODM-' . $year . '-' . str_pad($countThisYear, 3, '0', STR_PAD_LEFT);

        return view('maquila.create', compact('maquiladores', 'productos', 'nextOdm'));
    }

    /**
     * Paso 1 (Store): Guarda la OP con estado OP CREADA y redirige al Dashboard
     */
    public function store(Request $request)
    {
        // Normalizar ODM si viene con prefijo o separado
        $odmRaw = trim($request->input('numero_odm') ?: $request->input('numero_odm_valor', ''));
        if (!empty($odmRaw) && !str_starts_with(strtoupper($odmRaw), 'ODM-')) {
            $odmRaw = 'ODM-' . $odmRaw;
        }
        if (!empty($odmRaw)) {
            $request->merge(['numero_odm' => strtoupper($odmRaw)]);
        }

        $validated = $request->validate([
            'fecha_creacion' => 'required|date',
            'pre_orden_numero' => 'required|string',
            'op' => 'required|string|max:50',
            'numero_odm' => 'required|string|unique:maquila_production_orders,numero_odm',
            'producto_nombre' => 'required|string|max:255',
            'producto_id' => 'nullable|exists:products,id',
            'forma_farmaceutica' => 'nullable|string|max:100',
            'lote' => 'required|string|max:50',
            'tamano_lote' => 'required|numeric|min:0.001',
            'tamano_lote_unidad' => 'nullable|string|max:20',
            'fecha_fabricacion' => 'required|regex:/^\d{4}-\d{2}$/',
            'fecha_vencimiento' => 'required|regex:/^\d{4}-\d{2}$/',
            'vigencia_meses' => 'nullable|integer|min:1|max:120',
            'maquilador_id' => 'required|exists:maquiladores,id',
            'observaciones' => 'nullable|string',

            // Presentaciones (Repeater)
            'items' => 'required|array|min:1',
            'items.*.codigo_item' => 'required|string',
            'items.*.presentacion' => 'required|string',
            'items.*.cantidad_programada' => 'required|numeric|min:0.001',
            'items.*.unidad_medida' => 'required|string',
            'items.*.sdm' => 'nullable|string',
        ]);

        // Formatear Pre Orden en estándar PL-XX-G
        $cleanPre = strtoupper(trim($validated['pre_orden_numero']));
        if (preg_match('/^PL-(.+)-G$/i', $cleanPre, $matches)) {
            $preOrdenFinal = 'PL-' . $matches[1] . '-G';
        } else {
            $cleanPre = preg_replace('/[^A-Z0-9]/', '', $cleanPre);
            $preOrdenFinal = 'PL-' . $cleanPre . '-G';
        }

        DB::beginTransaction();
        try {
            $maquilador = Maquilador::findOrFail($validated['maquilador_id']);

            $order = MaquilaProductionOrder::create([
                'fecha_creacion' => $validated['fecha_creacion'],
                'pre_orden' => $preOrdenFinal,
                'op' => strtoupper(trim($validated['op'])),
                'numero_odm' => strtoupper(trim($validated['numero_odm'])),
                'producto_nombre' => strtoupper(trim($validated['producto_nombre'])),
                'producto_id' => $validated['producto_id'] ?? null,
                'forma_farmaceutica' => strtoupper(trim($validated['forma_farmaceutica'] ?? 'POLVO ORAL')),
                'lote' => strtoupper(trim($validated['lote'])),
                'tamano_lote' => $validated['tamano_lote'],
                'unidad_medida' => strtoupper(trim($validated['tamano_lote_unidad'] ?? 'KG')),
                'fecha_fabricacion' => $validated['fecha_fabricacion'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'vigencia_meses' => (int) ($validated['vigencia_meses'] ?? 24),
                'maquilador_id' => $validated['maquilador_id'],
                'estado' => 'OP CREADA',
                'usuario_creador_id' => Auth::id(),
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Guardar presentaciones asociadas
            foreach ($validated['items'] as $itemData) {
                MaquilaItem::create([
                    'maquila_production_order_id' => $order->id,
                    'codigo_item' => strtoupper(trim($itemData['codigo_item'])),
                    'descripcion_producto' => $order->producto_nombre,
                    'presentacion' => strtoupper(trim($itemData['presentacion'])),
                    'forma_farmaceutica' => $order->forma_farmaceutica,
                    'lote_fisico' => $order->lote,
                    'cantidad_programada' => $itemData['cantidad_programada'],
                    'unidad_medida' => strtoupper(trim($itemData['unidad_medida'])),
                    'sdm' => !empty($itemData['sdm']) ? strtoupper(trim($itemData['sdm'])) : null,
                    'fecha_fabricacion' => $order->fecha_fabricacion . '-01',
                    'fecha_vencimiento' => $order->fecha_vencimiento . '-01',
                ]);
            }

            // Registro en Audit Trail (CFR 21 Part 11)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'CREAR_OP_MAQUILA',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Creación de OP Maquila {$order->op} (Pre-Orden: {$order->pre_orden}, ODM: {$order->numero_odm}, Lote: {$order->lote}) para maquilador {$maquilador->nombre}. Estado inicial: OP CREADA.",
                'new_values' => json_encode($order->toArray()),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->route('maquila.index')
                ->with('success', "Orden de Producción {$order->op} ({$order->pre_orden}) guardada correctamente con estado OP CREADA.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar la orden de producción: ' . $e->getMessage());
        }
    }

    /**
     * Paso 2: Acción "OP ENVIADA A MAQUILADOR" -> cambia a OP EN PRODUCCION
     */
    public function enviarMaquilador(Request $request, $id)
    {
        $validated = $request->validate([
            'fecha_envio_maquila' => 'nullable|date'
        ]);

        $order = MaquilaProductionOrder::findOrFail($id);

        DB::beginTransaction();
        try {
            $fechaEnvio = $validated['fecha_envio_maquila'] ?? Carbon::today();

            $order->update([
                'fecha_envio_maquila' => $fechaEnvio,
                'estado' => 'OP EN PRODUCCION'
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'ENVIAR_OP_MAQUILADOR',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "OP {$order->op} (ODM: {$order->numero_odm}) enviada al maquilador con fecha {$fechaEnvio}. Estado actualizado a OP EN PRODUCCION.",
                'new_values' => json_encode(['estado' => 'OP EN PRODUCCION', 'fecha_envio_maquila' => $fechaEnvio]),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', "OP {$order->op} enviada al maquilador exitosamente. Estado: OP EN PRODUCCION.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar envío: ' . $e->getMessage());
        }
    }

    /**
     * Paso 3 (Formulario): Pantalla de Recepción de Producto (Ingresos Parciales o Total)
     */
    public function recepcionForm($id)
    {
        $order = MaquilaProductionOrder::with(['maquilador', 'items.deliveries.user'])->findOrFail($id);

        return view('maquila.recepcion', compact('order'));
    }

    /**
     * Paso 3 (Store): Guarda el ingreso parcial o total y calcula el rendimiento
     */
    public function storeRecepcion(Request $request, $id)
    {
        $validated = $request->validate([
            'fecha_ingreso' => 'required|date',
            'numero_factura' => 'required|string|max:100',
            'esm' => 'required|string|max:100',
            'tipo_recepcion' => 'required|in:PARCIAL,TOTAL',
            'cantidades' => 'required|array',
            'observaciones' => 'nullable|string'
        ]);

        $order = MaquilaProductionOrder::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            $totalIngresadoEnEsteMovimiento = 0;

            foreach ($validated['cantidades'] as $itemId => $cantidad) {
                $qty = (float) $cantidad;
                if ($qty > 0) {
                    $item = MaquilaItem::where('maquila_production_order_id', $order->id)->findOrFail($itemId);

                    // Actualizar el número ESM si no lo tenía
                    if (empty($item->esm)) {
                        $item->update(['esm' => strtoupper(trim($validated['esm']))]);
                    }

                    $deliveryPayload = [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'odm' => $order->numero_odm,
                        'factura' => $validated['numero_factura'],
                        'esm' => $validated['esm'],
                        'cantidad' => $qty,
                        'tipo' => $validated['tipo_recepcion'],
                        'timestamp' => now()->toIso8601String()
                    ];

                    MaquilaDelivery::create([
                        'maquila_item_id' => $item->id,
                        'fecha_recepcion' => $validated['fecha_ingreso'],
                        'numero_remision_factura' => strtoupper(trim($validated['numero_factura'])),
                        'numero_factura' => strtoupper(trim($validated['numero_factura'])),
                        'esm' => strtoupper(trim($validated['esm'])),
                        'tipo_entrega' => $validated['tipo_recepcion'],
                        'cantidad_recibida' => $qty,
                        'usuario_registro_id' => Auth::id(),
                        'hash_integridad' => hash('sha256', json_encode($deliveryPayload)),
                        'observaciones' => $validated['observaciones'] ?? null,
                    ]);

                    $totalIngresadoEnEsteMovimiento += $qty;
                }
            }

            // Actualizar estado de la orden
            if ($validated['tipo_recepcion'] === 'TOTAL') {
                $order->update([
                    'estado' => 'OP TERMINADA - BR PENDIENTE'
                ]);
                $msg = "Ingreso TOTAL registrado para la OP {$order->op}. Estado actualizado a OP TERMINADA - BR PENDIENTE.";
            } else {
                // Sigue en producción con entregas parciales registradas
                $order->update([
                    'estado' => 'OP EN PRODUCCION'
                ]);
                $msg = "Ingreso PARCIAL registrado exitosamente ({$totalIngresadoEnEsteMovimiento} unidades). La orden continúa abierta para recibir más parciales.";
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'RECEPCION_PRODUCTO_MAQUILA',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Recepción de producto ({$validated['tipo_recepcion']}) - Factura: {$validated['numero_factura']}, ESM: {$validated['esm']}. Cantidad total ingresada en movimiento: {$totalIngresadoEnEsteMovimiento}.",
                'new_values' => json_encode(['estado' => $order->estado, 'tipo_recepcion' => $validated['tipo_recepcion']]),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->route('maquila.show', $order->id)->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar la recepción: ' . $e->getMessage());
        }
    }

    /**
     * Paso 4: Llegada del Batch Record & Archivo Físico -> cambia a BR REVISION DT
     */
    public function registrarLlegadaBr(Request $request, $id)
    {
        $validated = $request->validate([
            'fecha_llegada_br' => 'required|date',
            'total_producto_terminado_fabricado' => 'required|numeric|min:0.001',
            'posicion_archivo_fisico' => 'required|string|max:255'
        ]);

        $order = MaquilaProductionOrder::findOrFail($id);

        DB::beginTransaction();
        try {
            $base = $order->tamano_lote > 0 ? $order->tamano_lote : $order->total_programado;
            $rendimiento = $base > 0
                ? round(($validated['total_producto_terminado_fabricado'] / $base) * 100, 2)
                : 100.0;

            $order->update([
                'fecha_llegada_br' => $validated['fecha_llegada_br'],
                'total_producto_terminado_fabricado' => $validated['total_producto_terminado_fabricado'],
                'rendimiento_real' => $rendimiento,
                'posicion_archivo_fisico' => strtoupper(trim($validated['posicion_archivo_fisico'])),
                'estado' => 'BR REVISION DT'
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'LLEGADA_BATCH_RECORD',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Llegada del Batch Record físico para OP {$order->op} (Lote: {$order->lote}). Total fabricado: {$order->total_producto_terminado_fabricado}, Rendimiento: {$rendimiento}%. Ubicación física: {$order->posicion_archivo_fisico}. Estado: BR REVISION DT.",
                'new_values' => json_encode($order->only(['fecha_llegada_br', 'total_producto_terminado_fabricado', 'rendimiento_real', 'posicion_archivo_fisico', 'estado'])),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', "Batch Record ingresado al archivo físico en '{$order->posicion_archivo_fisico}'. Rendimiento: {$rendimiento}%. Estado: BR REVISION DT.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar llegada del BR: ' . $e->getMessage());
        }
    }

    /**
     * Paso 5: Revisión Director Técnico & Producción -> pasa a BR REVISION CALIDAD
     */
    public function revisionDt(Request $request, $id)
    {
        $validated = $request->validate([
            'estado_br_dt' => 'required|in:ABIERTO,CERRADO',
            'comentario_dt' => 'required|string|min:3'
        ]);

        $order = MaquilaProductionOrder::findOrFail($id);

        DB::beginTransaction();
        try {
            $order->update([
                'estado_br_dt' => $validated['estado_br_dt'],
                'comentario_dt' => $validated['comentario_dt'],
                'fecha_revision_dt' => now(),
                'usuario_dt_id' => Auth::id(),
                'estado' => 'BR REVISION CALIDAD'
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'REVISION_DT_BATCH_RECORD',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Revisión DT y Producción para OP {$order->op}: Decisión = {$validated['estado_br_dt']}. Comentario: {$validated['comentario_dt']}. Avanza a BR REVISION CALIDAD.",
                'new_values' => json_encode(['estado_br_dt' => $validated['estado_br_dt'], 'comentario_dt' => $validated['comentario_dt'], 'estado' => 'BR REVISION CALIDAD']),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', "Revisión DT y Producción completada ({$validated['estado_br_dt']}). El Batch Record avanza a BR REVISION CALIDAD.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar revisión DT: ' . $e->getMessage());
        }
    }

    /**
     * Paso 6: Revisión Aseguramiento de Calidad (QA) & Liberación Final
     */
    public function revisionCalidad(Request $request, $id)
    {
        $validated = $request->validate([
            'certificado_fisicoquimico' => 'required|in:SI,NO,NO_APLICA',
            'certificado_microbiologico' => 'required|in:SI,NO,NO_APLICA',
            'certificado_endotoxinas' => 'required|in:SI,NO,NO_APLICA',
            'liberar_br' => 'nullable|boolean',
            'fecha_liberacion_br' => 'nullable|date',
            'estado_br_calidad' => 'required|in:ABIERTO,CERRADO',
            'observaciones_calidad' => 'nullable|string'
        ]);

        $order = MaquilaProductionOrder::findOrFail($id);

        DB::beginTransaction();
        try {
            // Regla de resolución de cierre:
            // Si DT = CERRADO Y Calidad = CERRADO -> BR CERRADO
            // Si cualquiera es ABIERTO -> BR ABIERTO
            $estadoFinal = ($order->estado_br_dt === 'CERRADO' && $validated['estado_br_calidad'] === 'CERRADO')
                ? 'BR CERRADO'
                : 'BR ABIERTO';

            $order->update([
                'certificado_fisicoquimico' => $validated['certificado_fisicoquimico'],
                'certificado_microbiologico' => $validated['certificado_microbiologico'],
                'certificado_endotoxinas' => $validated['certificado_endotoxinas'],
                'liberar_br' => !empty($validated['liberar_br']),
                'fecha_liberacion_br' => !empty($validated['liberar_br']) ? ($validated['fecha_liberacion_br'] ?? Carbon::today()) : null,
                'estado_br_calidad' => $validated['estado_br_calidad'],
                'observaciones_calidad' => $validated['observaciones_calidad'] ?? null,
                'usuario_calidad_id' => Auth::id(),
                'estado' => $estadoFinal
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'REVISION_QA_LIBERACION_BATCH_RECORD',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Revisión Aseguramiento de Calidad (QA) para OP {$order->op}: Decisión = {$validated['estado_br_calidad']}. Liberado: " . ($order->liberar_br ? 'SÍ' : 'NO') . ". Resolución Final: {$estadoFinal}.",
                'new_values' => json_encode([
                    'cert_fq' => $order->certificado_fisicoquimico,
                    'cert_micro' => $order->certificado_microbiologico,
                    'cert_endo' => $order->certificado_endotoxinas,
                    'estado_qa' => $order->estado_br_calidad,
                    'estado_final' => $estadoFinal
                ]),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            $resolucionMsg = $estadoFinal === 'BR CERRADO'
                ? "¡Batch Record y Orden CERRADOS y Liberados formalmente bajo norma 21 CFR Part 11!"
                : "Revisión guardada. El Batch Record permanece ABIERTO debido a observaciones pendientes de resolución.";

            return redirect()->back()->with('success', $resolucionMsg);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar revisión de calidad: ' . $e->getMessage());
        }
    }

    /**
     * Vista Detallada 360° y Radar de Trazabilidad del Lote
     */
    public function show($id)
    {
        $this->ensureSchema();

        $order = MaquilaProductionOrder::with([
            'maquilador',
            'creator',
            'dtUser',
            'qaUser',
            'product',
            'items.deliveries.user',
        ])->findOrFail($id);

        return view('maquila.radar', compact('order'));
    }

    /**
     * API Fetch Autocompletado de ítems por código (Autocompleta producto, forma farmacéutica y presentación)
     */
    public function apiGetItem($codigo)
    {
        $code = strtoupper(trim($codigo));

        // 0. Buscar en Catálogo Maestro Especializado de Maquilas (maquila_catalog_items)
        if (Schema::hasTable('maquila_catalog_items')) {
            $catItem = DB::table('maquila_catalog_items')
                ->where('codigo_item', $code)
                ->orWhere('codigo_item', 'LIKE', "%{$code}%")
                ->first();

            if ($catItem) {
                return response()->json([
                    'found' => true,
                    'codigo' => $catItem->codigo_item,
                    'descripcion' => $catItem->producto_nombre,
                    'presentacion' => $catItem->presentacion,
                    'unidad' => $catItem->unidad_medida,
                    'producto_id' => null,
                    'producto_nombre' => $catItem->producto_nombre,
                    'forma_farmaceutica' => $catItem->forma_farmaceutica,
                    'vigencia_meses' => $catItem->vigencia_meses ?? 24,
                ]);
            }
        }

        // 1. Buscar en tabla items por item_code
        $item = DB::table('items')->where('item_code', $code)->first();
        if ($item) {
            $uom = in_array(strtoupper($item->inventory_uom ?? ''), ['UND', 'UNIDAD', 'FRASCO', 'CAJA', 'BOLSA']) ? $item->inventory_uom : 'KG';

            // Buscar si coincide con algún producto del catálogo para extraer su forma farmacéutica
            $matchedProduct = DB::table('products')
                ->where('name', 'LIKE', "%{$item->description}%")
                ->orWhere('name', 'LIKE', "%{$item->reference}%")
                ->first();

            return response()->json([
                'found' => true,
                'codigo' => $item->item_code,
                'descripcion' => $item->description,
                'presentacion' => $item->ext_1_detail ?: ($item->reference ?: 'UNIDAD'),
                'unidad' => $uom,
                'producto_id' => $matchedProduct ? $matchedProduct->id : null,
                'producto_nombre' => $matchedProduct ? $matchedProduct->name : $item->description,
                'forma_farmaceutica' => $matchedProduct ? $matchedProduct->pharmaceutical_form : 'POLVO ORAL',
                'vigencia_meses' => 24,
            ]);
        }

        // 2. Buscar por coincidencia parcial
        $itemLike = DB::table('items')->where('item_code', 'LIKE', "%{$code}%")->first();
        if ($itemLike) {
            $uom = in_array(strtoupper($itemLike->inventory_uom ?? ''), ['UND', 'UNIDAD', 'FRASCO', 'CAJA', 'BOLSA']) ? $itemLike->inventory_uom : 'KG';

            $matchedProduct = DB::table('products')
                ->where('name', 'LIKE', "%{$itemLike->description}%")
                ->first();

            return response()->json([
                'found' => true,
                'codigo' => $itemLike->item_code,
                'descripcion' => $itemLike->description,
                'presentacion' => $itemLike->ext_1_detail ?: 'UNIDAD',
                'unidad' => $uom,
                'producto_id' => $matchedProduct ? $matchedProduct->id : null,
                'producto_nombre' => $matchedProduct ? $matchedProduct->name : $itemLike->description,
                'forma_farmaceutica' => $matchedProduct ? $matchedProduct->pharmaceutical_form : 'POLVO ORAL',
            ]);
        }

        // 3. Buscar en tabla products por code o name
        $product = DB::table('products')->where('code', $code)->orWhere('name', 'LIKE', "%{$code}%")->first();
        if ($product) {
            return response()->json([
                'found' => true,
                'codigo' => $product->code ?? $code,
                'descripcion' => $product->name,
                'presentacion' => $product->presentation ?? 'FRASCO',
                'unidad' => $product->base_unit ?? 'KG',
                'producto_id' => $product->id,
                'producto_nombre' => $product->name,
                'forma_farmaceutica' => $product->pharmaceutical_form ?? 'SOLUCIÓN INYECTABLE',
            ]);
        }

        return response()->json([
            'found' => false,
            'message' => 'Código de ítem no encontrado en el catálogo maestro.'
        ]);
    }
}
