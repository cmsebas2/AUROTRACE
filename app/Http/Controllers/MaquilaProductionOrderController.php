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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        try {
            if (Schema::hasTable('maquila_production_orders')) {
                Schema::table('maquila_production_orders', function ($table) {
                    if (!Schema::hasColumn('maquila_production_orders', 'unidad_medida')) {
                        $table->string('unidad_medida', 20)->nullable()->default('KG');
                    }
                    if (!Schema::hasColumn('maquila_production_orders', 'vigencia_meses')) {
                        $table->integer('vigencia_meses')->nullable()->default(24);
                    }
                    if (!Schema::hasColumn('maquila_production_orders', 'fecha_destruccion_br')) {
                        $table->string('fecha_destruccion_br', 20)->nullable();
                    }
                    if (!Schema::hasColumn('maquila_production_orders', 'lead_time_dias')) {
                        $table->integer('lead_time_dias')->nullable()->default(0);
                    }
                });

                try {
                    DB::statement('ALTER TABLE "maquila_production_orders" DROP CONSTRAINT IF EXISTS "maquila_production_orders_estado_check"');
                    DB::statement('ALTER TABLE "maquila_production_orders" DROP CONSTRAINT IF EXISTS "maquila_production_orders_tipo_producto_check"');
                    DB::statement('ALTER TABLE "maquila_production_orders" ALTER COLUMN "estado" TYPE VARCHAR(60)');
                } catch (\Throwable $e) {}

                // Limpiar registros eliminados lógicamente para liberar números de ODM/OP en PostgreSQL
                try {
                    DB::table('maquila_production_orders')->whereNotNull('deleted_at')->delete();
                } catch (\Throwable $e) {}
            }

            if (Schema::hasTable('maquila_items')) {
                try {
                    DB::statement('ALTER TABLE "maquila_items" DROP CONSTRAINT IF EXISTS "maquila_items_unidad_medida_check"');
                    DB::statement('ALTER TABLE "maquila_items" ALTER COLUMN "unidad_medida" TYPE VARCHAR(30) USING "unidad_medida"::text');
                } catch (\Throwable $e) {}
                try {
                    DB::statement('ALTER TABLE "maquila_items" ADD COLUMN IF NOT EXISTS "forma_farmaceutica" VARCHAR(100)');
                    DB::statement('ALTER TABLE "maquila_items" ADD COLUMN IF NOT EXISTS "esm" VARCHAR(100)');
                } catch (\Throwable $e) {}
            }

            if (Schema::hasTable('maquiladores')) {
                try {
                    DB::statement("DELETE FROM maquiladores WHERE nombre ~ '^[0-9]' OR nombre IN ('4 MILLONES', '24 G', '5 ML', '5 KG', '200 L')");
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            Log::warning('Error en ensureSchema: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * Guardia de permisos: Calidad solo tiene permisos de lectura y Dictamen Calidad (QA)
     */
    protected function checkQaNotAllowed()
    {
        if (Auth::check() && Auth::user()->isQualityUser() && !Auth::user()->hasRole(['admin', 'ADMIN', 'Administrador'])) {
            abort(403, 'Acceso Denegado: Su usuario de Calidad solo tiene permisos de lectura y emisión de Dictamen de Calidad.');
        }
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
            } elseif ($statusFilter === 'revision_qa') {
                $query->where('estado', 'BR REVISION CALIDAD');
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

        // Búsqueda inteligente universal por cualquier parámetro (Case-insensitive en PostgreSQL)
        if ($search !== '') {
            $likeOp = (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') ? 'ILIKE' : 'LIKE';
            $term = "%{$search}%";

            $query->where(function ($q) use ($term, $likeOp) {
                $q->where('op', $likeOp, $term)
                  ->orWhere('numero_odm', $likeOp, $term)
                  ->orWhere('pre_orden', $likeOp, $term)
                  ->orWhere('lote', $likeOp, $term)
                  ->orWhere('producto_nombre', $likeOp, $term)
                  ->orWhere('forma_farmaceutica', $likeOp, $term)
                  ->orWhere('posicion_archivo_fisico', $likeOp, $term)
                  ->orWhere('estado', $likeOp, $term)
                  ->orWhere('observaciones', $likeOp, $term)
                  ->orWhereHas('maquilador', function ($maqQ) use ($term, $likeOp) {
                      $maqQ->where('nombre', $likeOp, $term);
                  })
                  ->orWhereHas('items', function ($itemQ) use ($term, $likeOp) {
                      $itemQ->where('codigo_item', $likeOp, $term)
                            ->orWhere('presentacion', $likeOp, $term)
                            ->orWhere('descripcion_producto', $likeOp, $term)
                            ->orWhere('sdm', $likeOp, $term)
                            ->orWhere('esm', $likeOp, $term);
                  });
            });
        }

        if ($maquiladorFilter) {
            $query->where('maquilador_id', $maquiladorFilter);
        }

        try {
            $orders = $query->latest('id')->paginate(50)->withQueryString();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error consultando maquila_production_orders: ' . $e->getMessage());
            $orders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        }

        // Métricas y KPIs de Planta consolidadas en 1 sola consulta SQL ultrarrápida
        $kpis = \Illuminate\Support\Facades\Cache::remember('maquila_dashboard_kpis_v1', 30, function () {
            try {
                try {
                    Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $e) {}
                
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
            try {
                return Maquilador::whereRaw('"activo" IS NOT FALSE')->orderBy('nombre')->get();
            } catch (\Throwable $e) {
                return collect();
            }
        });

        return view('maquila.dashboard', compact(
            'orders',
            'kpis',
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
        $this->checkQaNotAllowed();
        $this->ensureSchema();

        try {
            $maquiladores = Maquilador::whereRaw('"activo" IS NOT FALSE')
                ->whereRaw('"nombre" !~ \'^[0-9]\'')
                ->whereNotIn('nombre', ['4 MILLONES', '24 G', '5 ML', '5 KG', '200 L'])
                ->orderBy('nombre')
                ->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error cargando maquiladores activos: ' . $e->getMessage());
            $maquiladores = collect();
        }
        $productos = Product::where('status', 'ACTIVO')->orderBy('name')->get();

        // Sugerencia correlativa de ODM
        $year = date('Y');
        $countThisYear = MaquilaProductionOrder::whereYear('fecha_creacion', $year)->count() + 1;
        $nextOdm = 'ODM-' . $year . '-' . str_pad($countThisYear, 3, '0', STR_PAD_LEFT);

        return view('maquila.create', compact('maquiladores', 'productos', 'nextOdm'));
    }

    /**
     * Generador de Lote según la norma alfanumérica de 7 caracteres (XYYZZVV):
     * X  : Último dígito del año (ej: 6 para 2026, 3 para 2023)
     * YY : Mes de creación en 2 dígitos (ej: 01 para Enero)
     * ZZ : Abreviatura de 2 letras del producto (ej: AN para Anapiran)
     * VV : Consecutivo anual de 2 dígitos del producto (ej: 01)
     */
    public static function generarSugerenciaLote($productoNombre, $fechaCreacion = null)
    {
        $date = $fechaCreacion ? \Carbon\Carbon::parse($fechaCreacion) : now();
        $x = substr((string)$date->year, -1);
        $yy = str_pad((string)$date->month, 2, '0', STR_PAD_LEFT);
        
        $cleanName = strtoupper(trim(preg_replace('/[^A-Z0-9]/i', '', $productoNombre)));
        $zz = substr($cleanName, 0, 2);
        if (strlen($zz) < 2) {
            $zz = str_pad($zz, 2, 'X', STR_PAD_RIGHT);
        }

        $prefix = $x . $yy . $zz;
        $count = MaquilaProductionOrder::whereYear('fecha_creacion', $date->year)
            ->where('lote', 'LIKE', $prefix . '%')
            ->count() + 1;
        $vv = str_pad((string)$count, 2, '0', STR_PAD_LEFT);

        return $prefix . $vv;
    }

    /**
     * Paso 1 (Store): Guarda la OP con estado OP CREADA y redirige al Dashboard
     */
    public function store(Request $request)
    {
        $this->checkQaNotAllowed();
        $this->ensureSchema();

        // 1. Normalizar ODM si viene con prefijo o separado
        $odmRaw = trim($request->input('numero_odm') ?: $request->input('numero_odm_valor', ''));
        if (empty($odmRaw)) {
            $year = date('Y');
            $countThisYear = MaquilaProductionOrder::whereYear('fecha_creacion', $year)->count() + 1;
            $odmRaw = 'ODM-' . $year . '-' . str_pad($countThisYear, 3, '0', STR_PAD_LEFT);
        } elseif (!str_starts_with(strtoupper($odmRaw), 'ODM-')) {
            $odmRaw = 'ODM-' . $odmRaw;
        }
        $request->merge(['numero_odm' => strtoupper($odmRaw)]);

        // 2. Normalizar producto_id (no fallar si no existe en tabla products)
        $prodId = $request->input('producto_id');
        if (empty($prodId) || !is_numeric($prodId) || !Product::where('id', $prodId)->exists()) {
            $request->merge(['producto_id' => null]);
        }

        // 3. Normalizar y limpiar ítems / presentaciones
        $rawItems = $request->input('items', []);
        if (is_array($rawItems)) {
            $cleanItems = [];
            foreach ($rawItems as $it) {
                $code = trim($it['codigo_item'] ?? '');
                $pres = trim($it['presentacion'] ?? '');
                if (!empty($code) || !empty($pres)) {
                    $cant = isset($it['cantidad_programada']) && is_numeric($it['cantidad_programada']) && (float)$it['cantidad_programada'] > 0
                        ? (float)$it['cantidad_programada']
                        : 1.0;
                    $cleanItems[] = [
                        'codigo_item' => strtoupper($code ?: 'GEN-ITEM'),
                        'presentacion' => strtoupper($pres ?: ($request->input('producto_nombre') ?: 'PRESENTACIÓN')),
                        'cantidad_programada' => $cant,
                        'unidad_medida' => !empty($it['unidad_medida']) ? strtoupper(trim($it['unidad_medida'])) : 'UND',
                        'sdm' => !empty($it['sdm']) ? strtoupper(trim($it['sdm'])) : null,
                    ];
                }
            }
            if (count($cleanItems) > 0) {
                $request->merge(['items' => $cleanItems]);
            }
        }

        $validated = $request->validate([
            'fecha_creacion' => 'required|date',
            'pre_orden_numero' => 'required|string',
            'op' => 'required|string|max:50',
            'numero_odm' => ['required', 'string', \Illuminate\Validation\Rule::unique('maquila_production_orders', 'numero_odm')->whereNull('deleted_at')],
            'producto_nombre' => 'required|string|max:255',
            'producto_id' => 'nullable',
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

            // Calcular fecha de destrucción del Batch Record (+1 año post-vencimiento según ICA)
            $fechaVenc = $validated['fecha_vencimiento'];
            $fechaDestruccion = null;
            if (preg_match('/^(\d{4})-(\d{2})$/', $fechaVenc, $m)) {
                $fechaDestruccion = ((int)$m[1] + 1) . '-' . $m[2];
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fechaVenc, $m)) {
                $fechaDestruccion = ((int)$m[1] + 1) . '-' . $m[2] . '-' . $m[3];
            } else {
                try {
                    $fechaDestruccion = Carbon::parse($fechaVenc)->addYear()->format('Y-m');
                } catch (\Throwable $e) {}
            }

            $userId = Auth::id() ?? DB::table('users')->value('id') ?? 1;

            $orderData = [
                'fecha_creacion' => $validated['fecha_creacion'],
                'pre_orden' => $preOrdenFinal,
                'op' => strtoupper(trim($validated['op'])),
                'numero_odm' => strtoupper(trim($validated['numero_odm'])),
                'producto_nombre' => strtoupper(trim($validated['producto_nombre'])),
                'producto_id' => $validated['producto_id'] ?? null,
                'forma_farmaceutica' => strtoupper(trim($validated['forma_farmaceutica'] ?? 'POLVO ORAL')),
                'lote' => strtoupper(trim($validated['lote'])),
                'tamano_lote' => $validated['tamano_lote'],
                'fecha_fabricacion' => $validated['fecha_fabricacion'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'fecha_destruccion_br' => $fechaDestruccion,
                'vigencia_meses' => (int) ($validated['vigencia_meses'] ?? 24),
                'maquilador_id' => $validated['maquilador_id'],
                'estado' => 'OP CREADA',
                'usuario_creador_id' => $userId,
                'observaciones' => $validated['observaciones'] ?? null,
            ];

            if (Schema::hasColumn('maquila_production_orders', 'unidad_medida')) {
                $orderData['unidad_medida'] = strtoupper(trim($validated['tamano_lote_unidad'] ?? 'KG'));
            }

            $order = MaquilaProductionOrder::create($orderData);

            // Guardar presentaciones asociadas
            foreach ($validated['items'] as $itemData) {
                $itemFab = $order->fecha_fabricacion;
                if (preg_match('/^\d{4}-\d{2}$/', $itemFab)) {
                    $itemFab .= '-01';
                }
                $itemVenc = $order->fecha_vencimiento;
                if (preg_match('/^\d{4}-\d{2}$/', $itemVenc)) {
                    $itemVenc .= '-01';
                }

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
                    'fecha_fabricacion' => $itemFab,
                    'fecha_vencimiento' => $itemVenc,
                ]);
            }

            // Invalida cache de dashboard
            Cache::forget('maquila_dashboard_kpis_v1');

            // Registro en Audit Trail (CFR 21 Part 11)
            try {
                AuditLog::create([
                    'user_id' => $userId,
                    'action' => 'CREAR_OP_MAQUILA',
                    'model_type' => 'App\Models\MaquilaProductionOrder',
                    'model_id' => $order->id,
                    'reason' => substr("Creación de OP Maquila {$order->op} (Pre-Orden: {$order->pre_orden}, ODM: {$order->numero_odm}, Lote: {$order->lote}) para maquilador {$maquilador->nombre}. Estado inicial: OP CREADA.", 0, 250),
                    'new_values' => json_encode($order->toArray()),
                    'ip_address' => $request->ip()
                ]);
            } catch (\Throwable $e) {
                Log::warning('AuditLog warning al crear OP Maquila: ' . $e->getMessage());
            }

            DB::commit();

            return redirect()->route('maquila.index')
                ->with('success', "Orden de Producción {$order->op} ({$order->pre_orden}) guardada exitosamente con estado OP CREADA.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error guardando OP Maquila: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Error al guardar la orden de producción: ' . $e->getMessage());
        }
    }

    /**
     * Paso 2: Acción "OP ENVIADA A MAQUILADOR" -> cambia a OP EN PRODUCCION
     */
    public function enviarMaquilador(Request $request, $id)
    {
        $this->checkQaNotAllowed();
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
        $this->checkQaNotAllowed();
        $order = MaquilaProductionOrder::with(['maquilador', 'items.deliveries.user'])->findOrFail($id);

        return view('maquila.recepcion', compact('order'));
    }

    /**
     * Paso 3 (Store): Guarda el ingreso parcial o total y calcula el rendimiento
     */
    public function storeRecepcion(Request $request, $id)
    {
        $this->checkQaNotAllowed();
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

            // Actualizar estado y redirección según el tipo de recepción
            if ($validated['tipo_recepcion'] === 'TOTAL') {
                $order->update([
                    'estado' => 'OP TERMINADA - BR PENDIENTE'
                ]);
                $msg = "Ingreso TOTAL del producto registrado exitosamente para la OP {$order->op}. Estado actualizado a OP TERMINADA - BR PENDIENTE. Cuando la carpeta física del Batch Record llegue a la planta, use el botón 'Llegada BR' en el dashboard para asignar la posición en archivo.";

                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'RECEPCION_PRODUCTO_MAQUILA',
                    'model_type' => 'App\Models\MaquilaProductionOrder',
                    'model_id' => $order->id,
                    'reason' => "Recepción TOTAL de producto - Factura: {$validated['numero_factura']}, ESM: {$validated['esm']}. Cantidad ingresada: {$totalIngresadoEnEsteMovimiento}. Estado actualizado a OP TERMINADA - BR PENDIENTE.",
                    'new_values' => json_encode(['estado' => $order->estado, 'tipo_recepcion' => 'TOTAL']),
                    'ip_address' => $request->ip()
                ]);

                DB::commit();

                // Redirigir al Dashboard de Maquilas (BR queda en cola PENDIENTE hasta su llegada física)
                return redirect()->route('maquila.index')->with('success', $msg);
            } else {
                // Sigue en producción con entregas parciales registradas
                $order->update([
                    'estado' => 'OP EN PRODUCCION'
                ]);
                $msg = "Ingreso PARCIAL registrado exitosamente ({$totalIngresadoEnEsteMovimiento} unidades). La orden continúa abierta en producción.";

                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'RECEPCION_PRODUCTO_MAQUILA',
                    'model_type' => 'App\Models\MaquilaProductionOrder',
                    'model_id' => $order->id,
                    'reason' => "Recepción PARCIAL de producto - Factura: {$validated['numero_factura']}, ESM: {$validated['esm']}. Cantidad parcial ingresada: {$totalIngresadoEnEsteMovimiento}.",
                    'new_values' => json_encode(['estado' => $order->estado, 'tipo_recepcion' => 'PARCIAL']),
                    'ip_address' => $request->ip()
                ]);

                DB::commit();

                // Para Ingreso PARCIAL -> Volver al Dashboard de Maquilas (NO pedir ubicación de Batch Record)
                return redirect()->route('maquila.index')->with('success', $msg);
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar la recepción: ' . $e->getMessage());
        }
    }

    /**
     * Paso 4 (Formulario Completo): Pantalla de Llegada de Batch Record y Asignación de Archivo Físico
     */
    public function llegadaBrForm($id)
    {
        $this->checkQaNotAllowed();
        $order = MaquilaProductionOrder::with(['maquilador', 'items.deliveries'])->findOrFail($id);

        $itemsData = [];
        $totalFabricadoSum = 0;

        if ($order->items->isNotEmpty()) {
            foreach ($order->items as $item) {
                $recibido = (float) $item->deliveries->sum('cantidad_recibida');
                $sugerido = $recibido > 0 ? $recibido : (float) $item->cantidad_programada;
                if ($sugerido <= 0 && $order->items->count() === 1) {
                    $sugerido = (float) $order->tamano_lote;
                }
                $totalFabricadoSum += $sugerido;
                $itemsData[] = [
                    'id' => $item->id,
                    'codigo_item' => $item->codigo_item ?? 'N/A',
                    'presentacion' => $item->presentacion ?? $order->producto_nombre,
                    'unidad_medida' => $item->unidad_medida ?? 'UND',
                    'cantidad_programada' => (float) $item->cantidad_programada,
                    'cantidad_recibida' => $recibido,
                    'cantidad_fabricada' => $sugerido,
                ];
            }
        } else {
            $sugerido = (float) ($order->total_programado > 0 ? $order->total_programado : $order->tamano_lote);
            $totalFabricadoSum = $sugerido;
            $itemsData[] = [
                'id' => 0,
                'codigo_item' => 'PT-01',
                'presentacion' => $order->producto_nombre,
                'unidad_medida' => $order->unidad_medida ?? 'UND',
                'cantidad_programada' => $sugerido,
                'cantidad_recibida' => $sugerido,
                'cantidad_fabricada' => $sugerido,
            ];
        }

        // Si la orden ya tenía un total fabricado registrado previamente y no tiene desglose variable:
        if ($order->total_producto_terminado_fabricado > 0 && count($itemsData) === 1 && $itemsData[0]['cantidad_fabricada'] <= 0) {
            $itemsData[0]['cantidad_fabricada'] = (float) $order->total_producto_terminado_fabricado;
            $totalFabricadoSum = (float) $order->total_producto_terminado_fabricado;
        }

        return view('maquila.llegada-br', compact('order', 'itemsData', 'totalFabricadoSum'));
    }

    /**
     * Paso 4: Llegada del Batch Record & Archivo Físico -> cambia a BR REVISION DT
     */
    public function registrarLlegadaBr(Request $request, $id)
    {
        $this->checkQaNotAllowed();
        $validated = $request->validate([
            'fecha_llegada_br' => 'required|date',
            'total_producto_terminado_fabricado' => 'required|numeric|min:0.001',
            'posicion_archivo_fisico' => 'required|string|max:255',
            'cantidades_fabricadas' => 'nullable|array',
        ]);

        $order = MaquilaProductionOrder::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Asegurar que el total fabricado se arrastre de la suma de cada presentación
            if (!empty($request->cantidades_fabricadas) && is_array($request->cantidades_fabricadas)) {
                $sumFabricado = 0;
                foreach ($request->cantidades_fabricadas as $qty) {
                    $sumFabricado += (float) $qty;
                }
                if ($sumFabricado > 0) {
                    $validated['total_producto_terminado_fabricado'] = $sumFabricado;
                }
            }

            $base = $order->total_programado > 0 ? $order->total_programado : $order->tamano_lote;
            $rendimiento = $base > 0
                ? round(($validated['total_producto_terminado_fabricado'] / $base) * 100, 2)
                : 100.0;

            $posicionInput = strtoupper(trim($validated['posicion_archivo_fisico']));
            $numArch = null;
            $slot = 1;

            if (preg_match('/ARCHIVADOR\s*#?\s*(\d+)/i', $posicionInput, $matches)) {
                $numArch = (int)$matches[1];
            }
            if (preg_match('/SLOT\s*([1-4])/i', $posicionInput, $matchesSlot)) {
                $slot = (int)$matchesSlot[1];
            }

            if ($numArch && $numArch >= 1 && $numArch <= 210 && Schema::hasTable('batch_record_archive_locations')) {
                $nivel = (int)ceil($numArch / 42);
                $cara = ($numArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                $posicionInput = "R 1 N {$nivel} A {$numArch} S {$slot}";

                // Verificar si el slot físico está ocupado por otra orden distinta
                $conflict = DB::table('batch_record_archive_locations')
                    ->where('rack', 'RACK 1')
                    ->where('archivador_numero', $numArch)
                    ->where('slot', $slot)
                    ->where(function($q) use ($order) {
                        $q->where('maquila_production_order_id', '!=', $order->id)
                          ->orWhereNull('maquila_production_order_id');
                    })
                    ->first();

                if ($conflict && strtoupper(trim($conflict->lote)) !== strtoupper(trim($order->lote))) {
                    DB::rollBack();
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "El Archivador #{$numArch} Slot {$slot} ya se encuentra ocupado por el Lote '{$conflict->lote}' (OP {$conflict->op_number}). Por favor seleccione un slot disponible.");
                }

                DB::table('batch_record_archive_locations')
                    ->where('maquila_production_order_id', $order->id)
                    ->orWhere('lote', $order->lote)
                    ->delete();

                DB::table('batch_record_archive_locations')->updateOrInsert(
                    [
                        'rack' => 'RACK 1',
                        'nivel' => $nivel,
                        'archivador_numero' => $numArch,
                        'slot' => $slot,
                    ],
                    [
                        'cara' => $cara,
                        'lote' => strtoupper(trim($order->lote)),
                        'op_number' => $order->op,
                        'producto_nombre' => $order->producto_nombre,
                        'tipo_origen' => 'MAQUILA',
                        'maquila_production_order_id' => $order->id,
                        'fecha_archivo' => $validated['fecha_llegada_br'],
                        'updated_at' => now(),
                    ]
                );
            }

            $order->update([
                'fecha_llegada_br' => $validated['fecha_llegada_br'],
                'total_producto_terminado_fabricado' => $validated['total_producto_terminado_fabricado'],
                'rendimiento_real' => $rendimiento,
                'posicion_archivo_fisico' => $posicionInput,
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

            return redirect()->route('maquila.show', $order->id)
                ->with('success', "Batch Record ingresado exitosamente al archivo físico en '{$order->posicion_archivo_fisico}'. Total Fabricado: " . number_format($order->total_producto_terminado_fabricado, 2) . " unidades. Rendimiento Operativo: {$rendimiento}%. Estado: BR REVISION DT.");

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
        $this->checkQaNotAllowed();
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

            $isLiberar = !empty($validated['liberar_br']) || $request->input('liberar_br') == '1' || $request->input('liberar_br') === 'on' || $request->input('liberar_br') === true;

            $order->update([
                'certificado_fisicoquimico' => $validated['certificado_fisicoquimico'],
                'certificado_microbiologico' => $validated['certificado_microbiologico'],
                'certificado_endotoxinas' => $validated['certificado_endotoxinas'],
                'liberar_br' => $isLiberar ? DB::raw('true') : DB::raw('false'),
                'fecha_liberacion_br' => $isLiberar ? ($validated['fecha_liberacion_br'] ?? Carbon::today()) : null,
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
     * Catálogo Corporativo Nativo de Aurofarma (170 productos oficiales con código AXXXXX)
     */
    public static function getMasterCatalog(): array
    {
        return [
            'A11000' => ['nombre' => 'AUROMECK INYECTABLE', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '5304-DB'],
            'A11001' => ['nombre' => 'AUROMECK INYECTABLE', 'presentacion' => 'Frasco x 500 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '5304-DB'],
            'A11002' => ['nombre' => 'AUROMECK INYECTABLE', 'presentacion' => 'Frasco x 200 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '5304-DB'],
            'A11003' => ['nombre' => 'ANAPIRAN', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5906-DB'],
            'A11004' => ['nombre' => 'BOLDEBIG 50', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6009-MV'],
            'A11005' => ['nombre' => 'BOLDEBIG 50', 'presentacion' => 'Frasco x 250 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6009-MV'],
            'A11006' => ['nombre' => 'BOLDEBIG 50', 'presentacion' => 'Frasco x 500 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6009-MV'],
            'A11007' => ['nombre' => 'CABATEL', 'presentacion' => 'Frasco x 20 mL', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2332-DB'],
            'A11008' => ['nombre' => 'CABATEL', 'presentacion' => 'Frasco x 500 mL', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2332-DB'],
            'A11009' => ['nombre' => 'AURO DIARREGAN', 'presentacion' => 'Caja x 10 Sobres', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3494-MV'],
            'A11010' => ['nombre' => 'AURO DIARREGAN', 'presentacion' => 'Caja x 50 Sobres', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3494-MV'],
            'A11011' => ['nombre' => 'ERIPANTO INYECTABLE', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '4797-DB'],
            'A11012' => ['nombre' => 'ERIPANTO MASTITIS', 'presentacion' => 'Caja 4 x 12 mL', 'forma' => 'SUSPENSIÓN INTRAMAMARIA', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2347-DB'],
            'A11013' => ['nombre' => 'ERIPANTO', 'presentacion' => 'Sobre x 24 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2347-DB'],
            'A11014' => ['nombre' => 'Q FOS 25', 'presentacion' => 'Sobre x 250 GR', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8135-MV'],
            'A11015' => ['nombre' => 'Q FOS 25', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8135-MV'],
            'A11016' => ['nombre' => 'Q NORFLOXAN', 'presentacion' => 'Frasco x 20 mL', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 30, 'ica' => '7933-MV'],
            'A11017' => ['nombre' => 'Q NORFLOXAN', 'presentacion' => 'Frasco x 100 mL', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 30, 'ica' => '7933-MV'],
            'A11018' => ['nombre' => 'Q NORFLOXAN', 'presentacion' => 'Frasco x 1000 mL', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 30, 'ica' => '7933-MV'],
            'A11019' => ['nombre' => 'Q OXY 200 LA', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8107-MV'],
            'A11020' => ['nombre' => 'Q OXY 200 LA', 'presentacion' => 'Frasco x 500 mL', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8107-MV'],
            'A11021' => ['nombre' => 'Q TARTILO 100', 'presentacion' => 'Sobre x 250 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 18, 'ica' => '7934-MV'],
            'A11022' => ['nombre' => 'Q TARTILO 100', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 18, 'ica' => '7934-MV'],
            'A11023' => ['nombre' => 'QTYCON', 'presentacion' => 'Tubo x 30 G', 'forma' => 'GEL ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5554-MV'],
            'A11024' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 10 mL', 'forma' => 'SUSPENSIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '5320-DB'],
            'A11025' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SUSPENSIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '5320-DB'],
            'A11026' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 100 mL', 'forma' => 'SUSPENSIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '5320-DB'],
            'A11027' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 50 mL', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5630-DB'],
            'A11028' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 10 mL', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5630-DB'],
            'A11029' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 100 mL', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5630-DB'],
            'A11030' => ['nombre' => 'SULFATROPHIN', 'presentacion' => 'Frasco x 1000 mL', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5630-DB'],
            'A11031' => ['nombre' => 'AURO UNGUENTO', 'presentacion' => 'Tarro x 100 G', 'forma' => 'UNGÜENTO', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '3439-BD'],
            'A11032' => ['nombre' => 'AURO UNGUENTO', 'presentacion' => 'Tarro x 500 G', 'forma' => 'UNGÜENTO', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '3439-BD'],
            'A11033' => ['nombre' => 'AUROTEL', 'presentacion' => 'Jeringa x 2.5 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4474-DB'],
            'A11034' => ['nombre' => 'AUROTEL', 'presentacion' => 'Jeringa x 5.0 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4474-DB'],
            'A11035' => ['nombre' => 'AUROZOLE 25 CO', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '6631-MV'],
            'A11036' => ['nombre' => 'AUROZOLE 25 CO', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '6631-MV'],
            'A11037' => ['nombre' => 'AUROZOLE 25 CO', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '6631-MV'],
            'A11038' => ['nombre' => 'AUROZOLE 25 CO', 'presentacion' => 'Frasco x 2000 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '6631-MV'],
            'A11039' => ['nombre' => 'AVICUR POLVO', 'presentacion' => 'Sobre x 20 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '4638-DB'],
            'A11040' => ['nombre' => 'AVICUR POLVO', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '4638-DB'],
            'A11041' => ['nombre' => 'AVICUR POLVO', 'presentacion' => 'Bolsa x 12.5 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '4638-DB'],
            'A11042' => ['nombre' => 'PORCIX', 'presentacion' => 'Sobre x 20 GR', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '2539-DB'],
            'A11043' => ['nombre' => 'Q IVERMEC 3.5', 'presentacion' => 'Frasco x 50 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '8008-MV'],
            'A11044' => ['nombre' => 'Q IVERMEC 3.5', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '8008-MV'],
            'A11045' => ['nombre' => 'Q IVERMEC 3.5', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '8008-MV'],
            'A11046' => ['nombre' => 'RAFOXANIDE', 'presentacion' => 'Jeringa x 30 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3678-DB'],
            'A11047' => ['nombre' => 'RAFOXANIDE', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3678-DB'],
            'A11048' => ['nombre' => 'RAFOXANIDE', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3678-DB'],
            'A11049' => ['nombre' => 'Q B COMPLEX', 'presentacion' => 'Frasco x 10 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => null],
            'A11050' => ['nombre' => 'Q TARTILO 100', 'presentacion' => 'Sobre x 50 GR', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 18, 'ica' => '7934-MV'],
            'A11051' => ['nombre' => 'COCCIDIOL', 'presentacion' => 'Sobre x 25 GR', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '2645-DB'],
            'A11052' => ['nombre' => 'COCCIDIOL', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '2645-DB'],
            'A11053' => ['nombre' => 'SULFACOCCIDIOL', 'presentacion' => 'Sobre x 25 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '3106-DB'],
            'A11054' => ['nombre' => 'AUROCHAMPU P', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SHAMPOO TÓPICO', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6343-MV'],
            'A11055' => ['nombre' => 'CIPERMETRINA 15 EC', 'presentacion' => 'Frasco x 20 ML', 'forma' => 'CONCENTRADO EMULSIONABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6671-MV'],
            'A11056' => ['nombre' => 'CIPERMETRINA 15 EC', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'CONCENTRADO EMULSIONABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6671-MV'],
            'A11057' => ['nombre' => 'CIPERMETRINA 15 EC', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'CONCENTRADO EMULSIONABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6671-MV'],
            'A11058' => ['nombre' => 'CIPERMETRINA 15 EC', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'CONCENTRADO EMULSIONABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6671-MV'],
            'A11059' => ['nombre' => 'PULPHOX', 'presentacion' => 'Tarro x 100 G', 'forma' => 'POLVO TÓPICO', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '6199-MV'],
            'A11060' => ['nombre' => 'AUROFARVIT INYECTABLE', 'presentacion' => 'Frasco x 10 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4946-DB'],
            'A11061' => ['nombre' => 'AUROFARVIT INYECTABLE', 'presentacion' => 'Frasco x 50 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4946-DB'],
            'A11062' => ['nombre' => 'AUROFARVIT INYECTABLE', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4946-DB'],
            'A11063' => ['nombre' => 'AUROFARVIT INYECTABLE', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4946-DB'],
            'A11064' => ['nombre' => 'AUROFARVIT ORAL', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4919-DB'],
            'A11065' => ['nombre' => 'AUROFARVIT ORAL', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4919-DB'],
            'A11066' => ['nombre' => 'AUROFARVIT ORAL', 'presentacion' => 'Garrafa x 4000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4919-DB'],
            'A11067' => ['nombre' => 'AUROVITEL', 'presentacion' => 'Sobre x 20 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4551-DB'],
            'A11068' => ['nombre' => 'AUROVITEL', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4551-DB'],
            'A11069' => ['nombre' => 'BRILLA PEL', 'presentacion' => 'Sobre x 150 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5701-SL'],
            'A11070' => ['nombre' => 'BRILLA PEL', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5701-SL'],
            'A11071' => ['nombre' => 'ERIPANTO', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2347-DB'],
            'A11072' => ['nombre' => 'Q B COMPLEX', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => null],
            'A11073' => ['nombre' => 'Q OXY 200 LA', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8107-MV'],
            'A11074' => ['nombre' => 'AUROFARVIT INYECTABLE', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4946-DB'],
            'A11075' => ['nombre' => 'AUROTEL', 'presentacion' => 'Frasco x 10 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4474-DB'],
            'A11076' => ['nombre' => 'Q OXY 200 LA', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8107-MV'],
            'A11077' => ['nombre' => 'SULFACOCCIDIOL', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '3106-DB'],
            'A11078' => ['nombre' => 'MELOXIDOL', 'presentacion' => 'Frasco x 10 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7872-MV'],
            'A11079' => ['nombre' => 'MELOXIDOL', 'presentacion' => 'Frasco x 50 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7872-MV'],
            'A11080' => ['nombre' => 'MELOXIDOL', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7872-MV'],
            'A11081' => ['nombre' => 'AUROCEF', 'presentacion' => 'Frasco x 1 G', 'forma' => 'POLVO ESTÉRIL INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7849-MV'],
            'A11082' => ['nombre' => 'AUROCEF', 'presentacion' => 'Frasco x 4 G', 'forma' => 'POLVO ESTÉRIL INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7849-MV'],
            'A11083' => ['nombre' => 'ANAPIRAN INYECTABLE', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5906-DB'],
            'A11084' => ['nombre' => 'AUROLITOS SOBRE', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => '16582-SL'],
            'A11085' => ['nombre' => 'PENIDEXINA', 'presentacion' => 'Frasco x 4 MILLONES', 'forma' => 'POLVO ESTÉRIL INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '6292-MV'],
            'A11086' => ['nombre' => 'PENIDEXINA', 'presentacion' => 'Frasco x 8 MILLONES', 'forma' => 'POLVO ESTÉRIL INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '6292-MV'],
            'A11087' => ['nombre' => 'AUROTEL', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4474-DB'],
            'A11088' => ['nombre' => 'AUROPUPPY', 'presentacion' => 'Jeringa x 5 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2538-DB'],
            'A11089' => ['nombre' => 'AUROPUPPY', 'presentacion' => 'Jeringa x 2.5 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2538-DB'],
            'A11090' => ['nombre' => 'ERIPANTO INYECTABLE', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '4797-DB'],
            'A11091' => ['nombre' => 'DILUYENTE AUROCEF', 'presentacion' => 'Frasco x 20 ML', 'forma' => 'SOLUCIÓN DILUYENTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7849-MV'],
            'A11092' => ['nombre' => 'DILUYENTE AUROCEF', 'presentacion' => 'Frasco x 80 ML', 'forma' => 'SOLUCIÓN DILUYENTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7849-MV'],
            'A11093' => ['nombre' => 'BRIO PERFORMANCE', 'presentacion' => 'Tarro x 2 KG', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11094' => ['nombre' => 'BRIO INCREASE', 'presentacion' => 'Tarro x 2 KG', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11095' => ['nombre' => 'BRIO JOINTS', 'presentacion' => 'Tarro x 2 KG', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11096' => ['nombre' => 'EQUINOLISINA', 'presentacion' => 'Tarro x 1 KG', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11097' => ['nombre' => 'BRILLA PEL', 'presentacion' => 'Tarro x 2 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5701-SL'],
            'A11098' => ['nombre' => 'BRIO PERFORMANCE', 'presentacion' => 'Sobre x 300 G', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11099' => ['nombre' => 'AUROPETS INCREASE', 'presentacion' => 'Tarro x 200 G', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '15874-SL'],
            'A11100' => ['nombre' => 'AUROPETS PERFORMANCE', 'presentacion' => 'Tarro x 200 G', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '25873-SL'],
            'A11101' => ['nombre' => 'AUROPETS SENIOR', 'presentacion' => 'Tarro x 200 G', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '15977-SL'],
            'A11102' => ['nombre' => 'BRILLAPEL EMULSION', 'presentacion' => 'Frasco x 130 ML', 'forma' => 'EMULSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '16010-SL'],
            'A11103' => ['nombre' => 'BRILLAPEL EMULSION', 'presentacion' => 'Frasco x 270 ML', 'forma' => 'EMULSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '16010-SL'],
            'A11104' => ['nombre' => 'BRILLAPEL EMULSION', 'presentacion' => 'Frasco x 550 ML', 'forma' => 'EMULSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '16010-SL'],
            'A11105' => ['nombre' => 'BRILLA PEL', 'presentacion' => 'Tarro x 250 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5701-SL'],
            'A11106' => ['nombre' => 'AUROTILMICOSIN', 'presentacion' => 'Frasco x 240 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '10424-MV'],
            'A11107' => ['nombre' => 'AUROTILMICOSIN', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '10424-MV'],
            'A11108' => ['nombre' => 'BRIO EQBALANCE', 'presentacion' => 'Sobre x 40 G', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11109' => ['nombre' => 'BRIO EQBALANCE', 'presentacion' => 'Tarro x 1 KG', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11110' => ['nombre' => 'BRIO EQBALANCE', 'presentacion' => 'Balde x 5 KG', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11111' => ['nombre' => 'BRIO ENERGY', 'presentacion' => 'Jeringa x 30 ML', 'forma' => 'GEL ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11112' => ['nombre' => 'BRIO ENERGY', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11113' => ['nombre' => 'EQUINOLISINA', 'presentacion' => 'Sobre x 50 G', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11114' => ['nombre' => 'FLORMIX', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10453-MV'],
            'A11115' => ['nombre' => 'FLORMIX', 'presentacion' => 'Garrafa x 4000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10453-MV'],
            'A11116' => ['nombre' => 'FLORMIX', 'presentacion' => 'Garrafa x 2000 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10453-MV'],
            'A11117' => ['nombre' => 'AURODIARREGAN NF', 'presentacion' => 'Caja 10 Sobres x 10 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3494-MV'],
            'A11118' => ['nombre' => 'AURODIARREGAN NF', 'presentacion' => 'Caja 50 Sobres x 10 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3494-MV'],
            'A11119' => ['nombre' => 'CABATEL NF', 'presentacion' => 'Jeringa x 20 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10488-MV'],
            'A11120' => ['nombre' => 'CABATEL NF', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10488-MV'],
            'A11121' => ['nombre' => 'CABATEL NF', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SUSPENSIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10488-MV'],
            'A11122' => ['nombre' => 'AUROFRESH CHAMPU', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SHAMPOO TÓPICO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10506-MV'],
            'A11123' => ['nombre' => 'AUROFRESH CHAMPU', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SHAMPOO TÓPICO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10506-MV'],
            'A11124' => ['nombre' => 'AUROFRESH CHAMPU', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SHAMPOO TÓPICO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10506-MV'],
            'A11125' => ['nombre' => 'AUROFRESH CHAMPU', 'presentacion' => 'Garrafa x 2000 ML', 'forma' => 'SHAMPOO TÓPICO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10506-MV'],
            'A11126' => ['nombre' => 'AUROPETS JABON', 'presentacion' => 'Barra x 100 GR', 'forma' => 'JABÓN SÓLIDO', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '10524-MV'],
            'A11127' => ['nombre' => 'AUROPETS JABON', 'presentacion' => 'Barra x 30 G', 'forma' => 'JABÓN SÓLIDO', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '10524-MV'],
            'A11128' => ['nombre' => 'CIPROFARM 20%', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10661-MV'],
            'A11130' => ['nombre' => 'ERIPANTO INYECTABLE', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '4797-DB'],
            'A11131' => ['nombre' => 'AUROTILMICOSIN', 'presentacion' => 'Gotero x 10 ML', 'forma' => 'SOLUCIÓN ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '10424-MV'],
            'A11132' => ['nombre' => 'AURODIARREGAN NF', 'presentacion' => 'Caja 10 Sobres x 20 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3494-MV'],
            'A11133' => ['nombre' => 'AURODIARREGAN NF', 'presentacion' => 'Caja 50 Sobres x 20 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '3494-MV'],
            'A11134' => ['nombre' => 'DOXYCOL', 'presentacion' => 'Sobre x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => '10756-MV'],
            'A11135' => ['nombre' => 'FERRYDECK', 'presentacion' => 'Frasco x 50 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '11013-MV'],
            'A11136' => ['nombre' => 'FERRYDECK', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '11013-MV'],
            'A11137' => ['nombre' => 'CREO TAY', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '2262-DB'],
            'A11138' => ['nombre' => 'CREO TAY', 'presentacion' => 'Frasco x 250 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '2262-DB'],
            'A11139' => ['nombre' => 'CREO TAY', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '2262-DB'],
            'A11140' => ['nombre' => 'CREO TAY', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '2262-DB'],
            'A11141' => ['nombre' => 'CREO TAY', 'presentacion' => 'Garrafa x 3800 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '2262-DB'],
            'A11142' => ['nombre' => 'CREO TAY', 'presentacion' => 'Garrafa x 20 L', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '2262-DB'],
            'A11143' => ['nombre' => 'VANOVET', 'presentacion' => 'Frasco x 120 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2497-DB'],
            'A11144' => ['nombre' => 'VANOVET', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2497-DB'],
            'A11145' => ['nombre' => 'VANOVET', 'presentacion' => 'Garrafa x 18.75 L', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2497-DB'],
            'A11146' => ['nombre' => 'VANOVET', 'presentacion' => 'Garrafa x 3750 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 36, 'ica' => '2497-DB'],
            'A11147' => ['nombre' => 'GLH 20', 'presentacion' => 'Garrafa x 20 L', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 12, 'ica' => '10423-MV'],
            'A11148' => ['nombre' => 'GLH 20', 'presentacion' => 'Garrafa x 3800 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 12, 'ica' => '10423-MV'],
            'A11149' => ['nombre' => 'SULFACOCCIDIOL', 'presentacion' => 'Bolsa x 3 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 48, 'ica' => '3106-DB'],
            'A11150' => ['nombre' => 'DOXYCOL', 'presentacion' => 'Bolsa x 5 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '10756-MV'],
            'A11151' => ['nombre' => 'Q FOS 25', 'presentacion' => 'Bolsa x 25 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '8135-MV'],
            'A11152' => ['nombre' => 'GLH 20', 'presentacion' => 'Frasco x 1000 ML', 'forma' => 'SOLUCIÓN DESINFECTANTE', 'unidad' => 'UND', 'vigencia' => 12, 'ica' => '10423-MV'],
            'A11153' => ['nombre' => 'BRIO JOINTS', 'presentacion' => 'Tarro x 600 GR', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11156' => ['nombre' => 'ANAPIRAN INYECTABLE', 'presentacion' => 'Frasco x 500 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '5906-DB'],
            'A11157' => ['nombre' => 'FORTICAT', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11158' => ['nombre' => 'FORTICAT RENAL', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SUPLEMENTO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11159' => ['nombre' => 'AUROVITEL', 'presentacion' => 'Sobre x 10 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '4551-DB'],
            'A11160' => ['nombre' => 'PHYTO FISH', 'presentacion' => 'Bolsa x 1 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11162' => ['nombre' => 'NEOMIXIN', 'presentacion' => 'Sobre x 20 G', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11163' => ['nombre' => 'NEOMIXIN', 'presentacion' => 'Bolsa x 1 KG', 'forma' => 'POLVO ORAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11165' => ['nombre' => 'VITA MIRABILIS', 'presentacion' => 'Bolsa x 1 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11166' => ['nombre' => 'VITA MIRABILIS', 'presentacion' => 'Bolsa x 5 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11167' => ['nombre' => 'VITA MIRABILIS', 'presentacion' => 'Saco x 10 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11168' => ['nombre' => 'VITA MIRABILIS', 'presentacion' => 'Saco x 25 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11169' => ['nombre' => 'HEPAXYN', 'presentacion' => 'Bolsa x 1 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11170' => ['nombre' => 'POWERQUIN BARRA', 'presentacion' => 'Barra x 100 GR', 'forma' => 'SUPLEMENTO EN BARRA', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11171' => ['nombre' => 'FLY NO MORE', 'presentacion' => 'Frasco x 1500 ML', 'forma' => 'CEBO LÍQUIDO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A11172' => ['nombre' => 'FERRYDECK', 'presentacion' => 'Frasco x 10 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '11013-MV'],
            'A11173' => ['nombre' => 'ACTIV GEL', 'presentacion' => 'Tubo x 35 G', 'forma' => 'GEL TÓPICO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '22806-SL'],
            'A11174' => ['nombre' => 'MELOXIDOL AFRICA', 'presentacion' => 'Frasco x 100 ML', 'forma' => 'SOLUCIÓN INYECTABLE', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '7872-MV'],
            'A31000' => ['nombre' => 'Q CLORMUTIN', 'presentacion' => 'Granel x KG', 'forma' => 'POLVO ORAL', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => '8048-MV'],
            'A31003' => ['nombre' => 'Q SULFATYL', 'presentacion' => 'Granel x KG', 'forma' => 'POLVO ORAL', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => '8188-MV'],
            'A31004' => ['nombre' => 'Q TILMICOX', 'presentacion' => 'Granel x KG', 'forma' => 'POLVO ORAL', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => '8131-MV'],
            'A31009' => ['nombre' => 'HEPAXYN', 'presentacion' => 'Bolsa x 5 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A31010' => ['nombre' => 'HEPAXYN', 'presentacion' => 'Bolsa x 10 KG', 'forma' => 'PREMEZCLA NUTRICIONAL', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => null],
            'A31012' => ['nombre' => 'Q FLORFEN', 'presentacion' => 'Bolsa x 1 KG', 'forma' => 'PREMEZCLA', 'unidad' => 'KG', 'vigencia' => 33, 'ica' => '8132-MV'],
            'A31013' => ['nombre' => 'Q FLORFEN', 'presentacion' => 'Bolsa x 5 KG', 'forma' => 'PREMEZCLA', 'unidad' => 'KG', 'vigencia' => 33, 'ica' => '8132-MV'],
            'A31019' => ['nombre' => 'AUROLISTINA 10%', 'presentacion' => 'Granel x KG', 'forma' => 'POLVO ORAL', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => null],
            'A31021' => ['nombre' => 'Q MICOSPECTIN L', 'presentacion' => 'Granel x KG', 'forma' => 'PREMEZCLA', 'unidad' => 'KG', 'vigencia' => 36, 'ica' => '8134-MV'],
            'A31025' => ['nombre' => 'ACTIV GEL', 'presentacion' => 'Pote x KG', 'forma' => 'GEL TÓPICO', 'unidad' => 'UND', 'vigencia' => 24, 'ica' => '22806-SL'],
            'A31026' => ['nombre' => 'GRANEL AUROESENCIAL', 'presentacion' => 'Granel x KG', 'forma' => 'PREMEZCLA', 'unidad' => 'KG', 'vigencia' => 24, 'ica' => '21403AL'],
        ];
    }

    /**
     * API Fetch Autocompletado de ítems por código (Autocompleta producto, forma farmacéutica, presentación y vigencia)
     */
    public function apiGetItem($codigo)
    {
        $code = strtoupper(trim((string)$codigo));
        if (empty($code)) {
            return response()->json(['found' => false, 'message' => 'Código no proporcionado']);
        }

        // 0. PRIORIDAD ABSOLUTA: Catálogo Oficial Literal de 1.954 Ítems (Descripciones exactas suministradas por el usuario)
        $exactDesc = \App\Data\MasterItemsCatalog::find($code);
        if ($exactDesc !== null) {
            $master = self::getMasterCatalog();
            $prodNombre = $exactDesc;
            $forma = 'POLVO ORAL';
            $vigencia = 24;
            $unidad = 'UND';
            $ica = null;

            if (isset($master[$code])) {
                $m = $master[$code];
                $prodNombre = $m['nombre'];
                $forma = $m['forma'];
                $vigencia = $m['vigencia'];
                $unidad = $m['unidad'];
                $ica = $m['ica'] ?? null;
            } else {
                if (preg_match('/^(.*?)\s+(FRASCO|CAJA|SOBRE|BOLSA|JERINGA|TUBO|GARRAFA|TARRO|SACO|BOTELLA|POTE|BALDE|ENVASE|AMPOLLETA)\b/i', $exactDesc, $mMatch)) {
                    $prodNombre = trim($mMatch[1]);
                }
                $upper = strtoupper($exactDesc);
                if (str_contains($upper, 'INYECT') || str_contains($upper, 'AMPOLLETA')) {
                    $forma = 'SOLUCIÓN INYECTABLE';
                } elseif (str_contains($upper, 'SUSPENSI') || str_contains($upper, 'ORAL LIQ') || str_contains($upper, 'GOTERO')) {
                    $forma = 'SUSPENSIÓN ORAL';
                } elseif (str_contains($upper, 'GEL') || str_contains($upper, 'UNG') || str_contains($upper, 'POMADA') || str_contains($upper, 'JABON') || str_contains($upper, 'CHAMPU')) {
                    $forma = 'USO TÓPICO / DERMATOLÓGICO';
                }
                if (str_contains($upper, ' X KG') || str_ends_with($upper, ' KG') || str_contains($upper, ' BULTO') || str_contains($upper, ' SACO')) {
                    $unidad = 'KG';
                } elseif (str_contains($upper, ' X L') || str_ends_with($upper, ' L') || str_contains($upper, ' LITRO') || str_contains($upper, ' LITROS')) {
                    $unidad = 'L';
                }
            }

            return response()->json([
                'found' => true,
                'codigo' => $code,
                'descripcion' => $exactDesc,
                'presentacion' => $exactDesc,
                'presentacion_corta' => $exactDesc,
                'referencia_completa' => $exactDesc,
                'unidad' => $unidad,
                'producto_id' => null,
                'producto_nombre' => $prodNombre,
                'forma_farmaceutica' => $forma,
                'vigencia_meses' => $vigencia,
                'registro_ica' => $ica,
            ]);
        }

        // 1. PRIORIDAD 1: Catálogo Maestro Nativo Aurofarma (170 productos corporativos con código AXXXXX)
        $master = self::getMasterCatalog();
        if (isset($master[$code])) {
            $m = $master[$code];
            $nombre = trim($m['nombre']);
            $presCorta = trim($m['presentacion']);
            $referencia = (stripos($presCorta, $nombre) !== false) ? $presCorta : trim($nombre . ' ' . $presCorta);

            return response()->json([
                'found' => true,
                'codigo' => $code,
                'descripcion' => $nombre,
                'presentacion' => strtoupper($referencia),
                'presentacion_corta' => $presCorta,
                'referencia_completa' => strtoupper($referencia),
                'unidad' => $m['unidad'],
                'producto_id' => null,
                'producto_nombre' => $nombre,
                'forma_farmaceutica' => $m['forma'],
                'vigencia_meses' => $m['vigencia'],
                'registro_ica' => $m['ica'] ?? null,
            ]);
        }

        // Búsqueda flexible en Catálogo Maestro (por código numérico o prefijo)
        $cleanCode = ltrim(str_replace('A', '', $code), '0');
        if (!empty($cleanCode)) {
            foreach ($master as $mCode => $m) {
                $cleanM = ltrim(str_replace('A', '', $mCode), '0');
                if ($cleanM === $cleanCode) {
                    $nombre = trim($m['nombre']);
                    $presCorta = trim($m['presentacion']);
                    $referencia = (stripos($presCorta, $nombre) !== false) ? $presCorta : trim($nombre . ' ' . $presCorta);

                    return response()->json([
                        'found' => true,
                        'codigo' => $mCode,
                        'descripcion' => $nombre,
                        'presentacion' => strtoupper($referencia),
                        'presentacion_corta' => $presCorta,
                        'referencia_completa' => strtoupper($referencia),
                        'unidad' => $m['unidad'],
                        'producto_id' => null,
                        'producto_nombre' => $nombre,
                        'forma_farmaceutica' => $m['forma'],
                        'vigencia_meses' => $m['vigencia'],
                        'registro_ica' => $m['ica'] ?? null,
                    ]);
                }
            }
        }

        // 2. PRIORIDAD 2: Catálogo Especializado de Maquilas en Base de Datos
        if (Schema::hasTable('maquila_catalog_items')) {
            $catItem = DB::table('maquila_catalog_items')
                ->whereRaw('UPPER(TRIM(codigo_item)) = ?', [$code])
                ->first();

            if ($catItem) {
                $nombre = trim($catItem->producto_nombre);
                $presCorta = trim($catItem->presentacion);
                $referencia = (stripos($presCorta, $nombre) !== false) ? $presCorta : trim($nombre . ' ' . $presCorta);

                return response()->json([
                    'found' => true,
                    'codigo' => $catItem->codigo_item,
                    'descripcion' => $nombre,
                    'presentacion' => strtoupper($referencia),
                    'presentacion_corta' => $presCorta,
                    'referencia_completa' => strtoupper($referencia),
                    'unidad' => $catItem->unidad_medida,
                    'producto_id' => null,
                    'producto_nombre' => $nombre,
                    'forma_farmaceutica' => $catItem->forma_farmaceutica,
                    'vigencia_meses' => $catItem->vigencia_meses ?? 24,
                    'registro_ica' => $catItem->registro_ica ?? null,
                ]);
            }
        }

        // 3. PRIORIDAD 3: Base de Datos Macro de Productos (product_presentations + products)
        if (Schema::hasTable('product_presentations')) {
            $pres = DB::table('product_presentations')
                ->join('products', 'products.id', '=', 'product_presentations.product_id')
                ->whereRaw('UPPER(product_presentations.presentation_code) = ?', [$code])
                ->select(
                    'product_presentations.presentation_code',
                    'product_presentations.name as presentation_name',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.pharmaceutical_form',
                    'products.base_unit',
                    'products.vigencia_meses',
                    'products.ica_license'
                )
                ->first();

            if ($pres) {
                $nombre = trim($pres->product_name);
                $presCorta = trim($pres->presentation_name);
                $referencia = (stripos($presCorta, $nombre) !== false) ? $presCorta : trim($nombre . ' ' . $presCorta);

                return response()->json([
                    'found' => true,
                    'codigo' => $pres->presentation_code,
                    'descripcion' => $nombre,
                    'presentacion' => strtoupper($referencia),
                    'presentacion_corta' => $presCorta,
                    'referencia_completa' => strtoupper($referencia),
                    'unidad' => $pres->base_unit ?? 'UND',
                    'producto_id' => $pres->product_id,
                    'producto_nombre' => $nombre,
                    'forma_farmaceutica' => $pres->pharmaceutical_form ?? 'POLVO ORAL',
                    'vigencia_meses' => $pres->vigencia_meses ?? 24,
                    'registro_ica' => $pres->ica_license ?? null,
                ]);
            }
        }

        // 4. PRIORIDAD 4: Buscar en tabla items general (DMS / ERP) por item_code o referencia
        if (Schema::hasTable('items')) {
            $hasRef = Schema::hasColumn('items', 'reference');
            $hasExt = Schema::hasColumn('items', 'ext_1_detail');
            $hasUom = Schema::hasColumn('items', 'inventory_uom');

            $q1 = DB::table('items')->whereRaw('UPPER(TRIM(CAST(item_code AS TEXT))) = ?', [$code]);
            if ($hasRef) {
                $q1->orWhereRaw('UPPER(TRIM(CAST(reference AS TEXT))) = ?', [$code]);
            }
            $item = $q1->first();

            if (!$item) {
                $codePadded = str_pad($code, 7, '0', STR_PAD_LEFT);
                $q2 = DB::table('items')->whereRaw('UPPER(TRIM(CAST(item_code AS TEXT))) = ?', [$codePadded]);
                if ($hasRef) {
                    $q2->orWhereRaw('UPPER(TRIM(CAST(reference AS TEXT))) = ?', [$codePadded]);
                }
                $item = $q2->first();
            }

            if ($item) {
                $desc = trim($item->description);
                $ref = $hasRef ? trim($item->reference ?? '') : '';
                $ext = $hasExt ? trim($item->ext_1_detail ?? '') : '';
                $uom = ($hasUom && in_array(strtoupper($item->inventory_uom ?? ''), ['UND', 'UNIDAD', 'FRASCO', 'CAJA', 'BOLSA', 'JERINGA', 'L', 'KG'])) ? $item->inventory_uom : 'UND';

                $prodNombre = $desc;
                $presentacion = $ext ?: ($ref ?: 'UNIDAD');
                $forma = 'POLVO ORAL';

                if (preg_match('/^(.*?)\s+(FRASCO|CAJA|SOBRE|BOLSA|JERINGA|TUBO|GARRAFA|TARRO|SACO|BOTELLA|POTE|BALDE|ENVASE|AMPOLLETA)\s*(.*)$/i', $desc, $matches)) {
                    $prodNombre = trim($matches[1]);
                    $presentacion = trim($matches[2] . ' ' . $matches[3]);
                }

                $upperDesc = strtoupper($desc . ' ' . $presentacion);
                if (str_contains($upperDesc, 'INYECT') || str_contains($upperDesc, 'FRASCO') || str_contains($upperDesc, 'AMPOLLETA')) {
                    $forma = 'SOLUCIÓN INYECTABLE';
                } elseif (str_contains($upperDesc, 'SUSPENSI') || str_contains($upperDesc, 'ORAL LIQ')) {
                    $forma = 'SUSPENSIÓN ORAL';
                } elseif (str_contains($upperDesc, 'GEL') || str_contains($upperDesc, 'UNG') || str_contains($upperDesc, 'POMADA')) {
                    $forma = 'GEL / TÓPICO';
                } elseif (str_contains($upperDesc, 'CHAMPU') || str_contains($upperDesc, 'JABON')) {
                    $forma = 'USO TÓPICO / DERMATOLÓGICO';
                } elseif (str_contains($upperDesc, 'DESINFECT') || str_contains($upperDesc, 'GARRAFA')) {
                    $forma = 'SOLUCIÓN DESINFECTANTE';
                } elseif (str_contains($upperDesc, 'POLVO') || str_contains($upperDesc, 'SOBRE') || str_contains($upperDesc, 'PREMEZCLA')) {
                    $forma = 'POLVO ORAL';
                }

                $referencia = !empty($desc) ? $desc : trim($prodNombre . ' ' . $presentacion);

                return response()->json([
                    'found' => true,
                    'codigo' => $item->item_code,
                    'descripcion' => $desc,
                    'presentacion' => strtoupper($referencia),
                    'presentacion_corta' => $presentacion,
                    'referencia_completa' => strtoupper($referencia),
                    'unidad' => $uom,
                    'producto_id' => null,
                    'producto_nombre' => $prodNombre,
                    'forma_farmaceutica' => $forma,
                    'vigencia_meses' => 24,
                ]);
            }
        }

        // 5. PRIORIDAD 5: Buscar en tabla products por code exacto
        if (Schema::hasTable('products')) {
            $product = DB::table('products')->whereRaw('UPPER(TRIM(code)) = ?', [$code])->first();
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
                    'vigencia_meses' => $product->vigencia_meses ?? 24,
                ]);
            }
        }

        return response()->json([
            'found' => false,
            'message' => 'Código de ítem no encontrado en el catálogo maestro.'
        ]);
    }

    /**
     * Formulario de Edición de Expediente / API JSON
     */
    public function edit(Request $request, $id)
    {
        $this->checkQaNotAllowed();
        $this->ensureSchema();
        $order = MaquilaProductionOrder::with(['maquilador', 'items'])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            $maquiladores = Maquilador::select('id', 'nombre')->orderBy('nombre')->get();
            $archiveLocation = Schema::hasTable('batch_record_archive_locations') 
                ? DB::table('batch_record_archive_locations')->where('lote', $order->lote)->first() 
                : null;

            return response()->json([
                'success' => true,
                'order' => $order,
                'maquiladores' => $maquiladores,
                'archive_location' => $archiveLocation
            ]);
        }

        $maquiladores = Maquilador::whereRaw('"activo" IS NOT FALSE')->orderBy('nombre')->get();
        $productos = Product::where('status', 'ACTIVO')->orderBy('name')->get();

        return view('maquila.edit', compact('order', 'maquiladores', 'productos'));
    }

    /**
     * Actualizar datos de la Orden de Maquila y Expediente (21 CFR Part 11 Audit Trail)
     */
    public function update(Request $request, $id)
    {
        $this->checkQaNotAllowed();
        $this->ensureSchema();
        $order = MaquilaProductionOrder::with('items')->findOrFail($id);

        $validated = $request->validate([
            'op' => 'required|string|max:100',
            'lote' => 'required|string|max:100',
            'pre_orden' => 'nullable|string|max:100',
            'numero_odm' => 'nullable|string|max:100',
            'producto_nombre' => 'required|string|max:255',
            'producto_id' => 'nullable',
            'forma_farmaceutica' => 'nullable|string|max:100',
            'maquilador_id' => 'required|exists:maquiladores,id',
            'tamano_lote' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:20',
            'fecha_creacion' => 'nullable|date',
            'fecha_fabricacion' => 'nullable|string',
            'fecha_vencimiento' => 'nullable|string',
            'fecha_envio_maquila' => 'nullable|date',
            'fecha_llegada_br' => 'nullable|date',
            'total_producto_terminado_fabricado' => 'nullable|numeric|min:0',
            'rendimiento_real' => 'nullable|numeric|min:0',
            'estado' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'posicion_archivo_fisico' => 'nullable|string|max:255',
            'archivador_numero' => 'nullable|integer|min:1|max:210',
        ]);

        DB::beginTransaction();
        try {
            $oldValues = $order->toArray();

            // 1. Limpiar Pre-Orden si viene
            if (!empty($validated['pre_orden'])) {
                $cleanPre = strtoupper(trim($validated['pre_orden']));
                if (!preg_match('/^PL-.*-G$/i', $cleanPre)) {
                    $cleanPre = preg_replace('/[^A-Z0-9]/', '', $cleanPre);
                    $cleanPre = 'PL-' . $cleanPre . '-G';
                }
                $validated['pre_orden'] = $cleanPre;
            }

            // 2. Limpiar ODM si viene
            if (!empty($validated['numero_odm'])) {
                $odmRaw = strtoupper(trim($validated['numero_odm']));
                if (!str_starts_with($odmRaw, 'ODM-')) {
                    $odmRaw = 'ODM-' . $odmRaw;
                }
                $validated['numero_odm'] = $odmRaw;
            }

            // 3. Formatear posición física abreviada si viene
            if (!empty($validated['posicion_archivo_fisico'])) {
                $posStr = preg_replace(
                    ['/RACK\s*/i', '/NIVEL\s*0?/i', '/ARCHIVADOR\s*#?/i', '/SLOT\s*/i', '/\s*·\s*/'],
                    ['R ', 'N ', 'A ', 'S ', ' '],
                    strtoupper(trim($validated['posicion_archivo_fisico']))
                );
                $validated['posicion_archivo_fisico'] = trim(preg_replace('/\s+/', ' ', $posStr));
            }

            // 4. Actualizar orden principal
            $order->update([
                'op' => strtoupper(trim($validated['op'])),
                'lote' => strtoupper(trim($validated['lote'])),
                'pre_orden' => $validated['pre_orden'] ?? $order->pre_orden,
                'numero_odm' => $validated['numero_odm'] ?? $order->numero_odm,
                'producto_nombre' => strtoupper(trim($validated['producto_nombre'])),
                'producto_id' => $validated['producto_id'] ?? $order->producto_id,
                'forma_farmaceutica' => !empty($validated['forma_farmaceutica']) ? strtoupper(trim($validated['forma_farmaceutica'])) : $order->forma_farmaceutica,
                'maquilador_id' => $validated['maquilador_id'],
                'tamano_lote' => isset($validated['tamano_lote']) ? (float)$validated['tamano_lote'] : $order->tamano_lote,
                'unidad_medida' => $validated['unidad_medida'] ?? $order->unidad_medida,
                'fecha_creacion' => $validated['fecha_creacion'] ?? $order->fecha_creacion,
                'fecha_fabricacion' => $validated['fecha_fabricacion'] ?? $order->fecha_fabricacion,
                'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? $order->fecha_vencimiento,
                'fecha_envio_maquila' => $validated['fecha_envio_maquila'] ?? $order->fecha_envio_maquila,
                'fecha_llegada_br' => $validated['fecha_llegada_br'] ?? $order->fecha_llegada_br,
                'total_producto_terminado_fabricado' => isset($validated['total_producto_terminado_fabricado']) ? (float)$validated['total_producto_terminado_fabricado'] : $order->total_producto_terminado_fabricado,
                'rendimiento_real' => isset($validated['rendimiento_real']) ? (float)$validated['rendimiento_real'] : $order->rendimiento_real,
                'posicion_archivo_fisico' => $validated['posicion_archivo_fisico'] ?? $order->posicion_archivo_fisico,
                'estado' => $validated['estado'] ?? $order->estado,
                'observaciones' => $validated['observaciones'] ?? $order->observaciones,
            ]);

            // 5. Actualizar Ítems / Presentaciones asociadas
            if ($request->has('items') && is_array($request->input('items'))) {
                MaquilaItem::where('maquila_production_order_id', $order->id)->delete();
                foreach ($request->input('items') as $it) {
                    $code = trim($it['codigo_item'] ?? '');
                    $pres = trim($it['presentacion'] ?? '');
                    if (!empty($code) || !empty($pres)) {
                        MaquilaItem::create([
                            'maquila_production_order_id' => $order->id,
                            'codigo_item' => strtoupper($code ?: 'GEN-ITEM'),
                            'presentacion' => strtoupper($pres ?: $order->producto_nombre),
                            'cantidad_programada' => isset($it['cantidad_programada']) && is_numeric($it['cantidad_programada']) ? (float)$it['cantidad_programada'] : 1.0,
                            'unidad_medida' => !empty($it['unidad_medida']) ? strtoupper(trim($it['unidad_medida'])) : 'UND',
                            'sdm' => !empty($it['sdm']) ? strtoupper(trim($it['sdm'])) : null,
                            'descripcion_producto' => $order->producto_nombre,
                            'lote_fisico' => $order->lote,
                        ]);
                    }
                }
            }

            // Actualizar Ubicación en Archivo Físico si aplica
            $posicionInput = $order->posicion_archivo_fisico;
            $numArch = $validated['archivador_numero'] ?? null;
            $slot = 1;

            if ($posicionInput) {
                if (preg_match('/(?:ARCHIVADOR|A)\s*#?\s*(\d+)/i', $posicionInput, $matches)) {
                    $numArch = (int)$matches[1];
                }
                if (preg_match('/(?:SLOT|S)\s*#?\s*([1-4])/i', $posicionInput, $matchesSlot)) {
                    $slot = (int)$matchesSlot[1];
                }
            }

            if (!empty($numArch) && $numArch >= 1 && $numArch <= 210 && Schema::hasTable('batch_record_archive_locations')) {
                $nivel = (int)ceil($numArch / 42);
                $cara = ($numArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                $posicionStr = "R 1 N {$nivel} A {$numArch} S {$slot}";

                DB::table('batch_record_archive_locations')
                    ->where('maquila_production_order_id', $order->id)
                    ->orWhere('lote', $order->lote)
                    ->delete();

                DB::table('batch_record_archive_locations')->insert([
                    'lote' => $order->lote,
                    'rack' => 'RACK 1',
                    'nivel' => $nivel,
                    'archivador_numero' => $numArch,
                    'slot' => $slot,
                    'cara' => $cara,
                    'op_number' => $order->op,
                    'producto_nombre' => $order->producto_nombre,
                    'tipo_origen' => 'MAQUILA',
                    'maquila_production_order_id' => $order->id,
                    'fecha_archivo' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $order->update([
                    'posicion_archivo_fisico' => $posicionStr
                ]);
            }

            // Audit Trail (CFR 21 Part 11)
            AuditLog::create([
                'user_id' => Auth::id() ?? 1,
                'action' => 'EDICION_COMPLETA_EXPEDIENTE_MAQUILA',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Edición completa de expediente de maquila OP {$order->op} / Lote {$order->lote} por " . (Auth::user()->name ?? 'Administrador'),
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($order->fresh()->toArray()),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Expediente OP {$order->op} (Lote {$order->lote}) actualizado exitosamente.",
                    'order' => $order
                ]);
            }

            return redirect()->route('maquila.index')->with('success', "Expediente OP {$order->op} (Lote {$order->lote}) actualizado exitosamente con Audit Trail registrado.");

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar expediente: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Error al actualizar expediente: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint directo para actualizar la ubicación 3D del expediente
     */
    public function updateLocation(Request $request, $id)
    {
        $this->checkQaNotAllowed();
        $this->ensureSchema();
        $validated = $request->validate([
            'posicion_archivo_fisico' => 'required|string|max:255',
        ]);

        $order = MaquilaProductionOrder::findOrFail($id);
        $posicionInput = strtoupper(trim($validated['posicion_archivo_fisico']));
        $numArch = null;
        $slot = 1;

        if (preg_match('/(?:ARCHIVADOR|A)\s*#?\s*(\d+)/i', $posicionInput, $matches)) {
            $numArch = (int)$matches[1];
        }
        if (preg_match('/(?:SLOT|S)\s*#?\s*([1-4])/i', $posicionInput, $matchesSlot)) {
            $slot = (int)$matchesSlot[1];
        }

        if (!$numArch || $numArch < 1 || $numArch > 210) {
            return response()->json(['success' => false, 'message' => 'Número de archivador no válido (debe ser 1 a 210).'], 422);
        }

        $nivel = (int)ceil($numArch / 42);
        $cara = ($numArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
        $posicionStr = "R 1 N {$nivel} A {$numArch} S {$slot}";

        // Verificar conflicto de slot con otra orden diferente
        $occupiedByOther = DB::table('batch_record_archive_locations')
            ->where('rack', 'RACK 1')
            ->where('archivador_numero', $numArch)
            ->where('slot', $slot)
            ->where('lote', '!=', $order->lote)
            ->where(function($q) use ($order) {
                $q->whereNull('maquila_production_order_id')
                  ->orWhere('maquila_production_order_id', '!=', $order->id);
            })
            ->first();

        if ($occupiedByOther) {
            return response()->json([
                'success' => false,
                'message' => "El Slot {$slot} del Archivador #{$numArch} ya está ocupado por el Lote {$occupiedByOther->lote} (OP: {$occupiedByOther->op_number}). Seleccione un slot libre."
            ], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('batch_record_archive_locations')
                ->where('maquila_production_order_id', $order->id)
                ->orWhere('lote', $order->lote)
                ->delete();

            DB::table('batch_record_archive_locations')->insert([
                'lote' => $order->lote,
                'rack' => 'RACK 1',
                'nivel' => $nivel,
                'archivador_numero' => $numArch,
                'slot' => $slot,
                'cara' => $cara,
                'op_number' => $order->op ?? 'OP-EXT',
                'producto_nombre' => $order->producto_nombre ?? 'PRODUCTO MAQUILA',
                'tipo_origen' => 'MAQUILA',
                'maquila_production_order_id' => $order->id,
                'fecha_archivo' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $order->update([
                'posicion_archivo_fisico' => $posicionStr
            ]);

            AuditLog::create([
                'user_id' => Auth::id() ?? 1,
                'action' => 'CAMBIO_UBICACION_EXPEDIENTE_3D',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Reubicación espacial 3D del expediente OP {$order->op} / Lote {$order->lote} a {$posicionStr}",
                'new_values' => json_encode(['posicion_archivo_fisico' => $posicionStr]),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Ubicación 3D actualizada exitosamente a {$posicionStr}.",
                'posicion_archivo_fisico' => $posicionStr
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al actualizar ubicación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una orden de producción de maquila (Solo Admin)
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: Solo el perfil Administrador tiene permisos para eliminar órdenes de producción.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $order = MaquilaProductionOrder::findOrFail($id);
            $opNumber = $order->op;
            $lote = $order->lote;

            // Liberar/eliminar ubicación asociada en el archivo físico
            if (Schema::hasTable('batch_record_archive_locations')) {
                DB::table('batch_record_archive_locations')
                    ->where('maquila_production_order_id', $order->id)
                    ->orWhere('lote', $lote)
                    ->delete();
            }

            // Eliminar entregas o ítems vinculados si aplican
            if (Schema::hasTable('maquila_order_items')) {
                DB::table('maquila_order_items')->where('maquila_production_order_id', $order->id)->delete();
            }

            // Registrar log de auditoría
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ELIMINAR_ORDEN_MAQUILA',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $id,
                'reason' => "Eliminación de la orden de maquila OP #{$opNumber} / Lote {$lote}",
                'old_values' => json_encode($order->toArray()),
                'ip_address' => request()->ip()
            ]);

            // Eliminar físicamente ítems y entregas vinculadas
            MaquilaItem::where('maquila_production_order_id', $order->id)->forceDelete();
            $order->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "La Orden de Producción OP #{$opNumber} (Lote: {$lote}) fue eliminada correctamente."
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la orden de producción: ' . $e->getMessage()
            ], 500);
        }
    }
}
